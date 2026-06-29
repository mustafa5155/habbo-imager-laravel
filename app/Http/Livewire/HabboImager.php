<?php

namespace App\Http\Livewire;

use App\Models\HabboImagingVersion;
use App\Support\HabboImaging\HabboImagingDresser;
use App\Support\HabboImaging\HabboImagingFigureInspector;
use App\Support\HabboImaging\HabboImagingManifest;
use Livewire\Component;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class HabboImager extends Component
{
    public string $mode = 'normal';
    public string $username = '';
    public string $figure = '';
    public string $direction = '2';
    public string $headDirection = '2';
    public string $gesture = 'nrm';
    public string $action = '';
    public string $headOnly = '0';
    public string $size = 'm';
    public string $previewUrl = '';
    public string $advancedGender = 'M';
    public string $advancedCategory = 'hd';
    public array $advancedSelections = [];
    public array $advancedDresser = [];
    public int $advancedItemLimit = 0;
    public array $manifest = [];
    public array $advancedReport = [];
    public array $advancedRenderDebug = [];
    public array $advancedSequence = [];
    public array $sizes = ['s', 'm', 'l'];
    public array $gestures = ['nrm', 'sml', 'agr', 'srp', 'spk', 'eyb'];
    public string $handItemMode = 'crr';
    public array $handItemCatalog = [];
    public array $signCatalog = [];
    private bool $syncingAdvancedFigure = false;
    public string $previewDisplayUrl = '';
    public string $previewMode = 'static';

    public function mount(string $defaultMode = 'normal'): void
    {
        $this->mode = in_array($defaultMode, ['normal', 'advanced'], true) ? $defaultMode : 'normal';
        $this->manifest = $this->getSyncStatus();

        $this->handItemCatalog = $this->buildHandItemCatalog();
        $this->signCatalog = $this->buildSignCatalog();

        if ($this->mode === 'advanced') {
            $this->bootstrapAdvancedDresser();
        } else {
            $this->rebuildPreview();
            $this->refreshAdvancedReport();
        }
    }

    private function getSyncStatus(): array
    {
        try {
            $syncStatus = DB::table('habbo_imaging_sync_status')->first();
            
            if ($syncStatus) {
                $pendingCount = DB::table('habbo_imaging_assets')->where('status', 'pending')->count();
                $extractedCount = DB::table('habbo_imaging_assets')->where('status', 'extracted')->count();
                $failedCount = DB::table('habbo_imaging_assets')->where('status', 'failed')->count();
                $total = $pendingCount + $extractedCount + $failedCount;
                $completionPercent = $total > 0 ? round(($extractedCount / $total) * 100) : 0;
                
                $processedSample = DB::table('habbo_imaging_assets')
                    ->whereIn('status', ['extracted', 'failed'])
                    ->orderByDesc('synced_at')
                    ->limit(5)
                    ->get()
                    ->map(function ($asset) {
                        return [
                            'library' => $asset->library_name,
                            'status' => $asset->status,
                            'extracted_file_count' => $asset->extracted_file_count ?? 0,
                        ];
                    })
                    ->toArray();
                
                return [
                    'status' => $syncStatus->status ?? 'idle',
                    'locked' => (bool) ($syncStatus->is_locked ?? false),
                    'current_source_version' => $syncStatus->current_version ?? 'Not set',
                    'current_parsed_version' => $syncStatus->current_version ?? 'Not set',
                    'last_synced_at' => $syncStatus->completed_at ?? $syncStatus->updated_at ?? 'Never',
                    'latest_version' => [
                        'metadata' => [
                            'asset_counts' => [
                                'pending' => $pendingCount,
                                'extracted' => $extractedCount,
                                'failed' => $failedCount,
                            ],
                            'asset_details' => [
                                'completion_percent' => $completionPercent,
                                'processed_sample' => $processedSample,
                            ],
                        ],
                    ],
                ];
            }
        } catch (\Exception $e) {
        }
        
        return [
            'status' => 'idle',
            'locked' => false,
            'current_source_version' => 'Not set',
            'current_parsed_version' => 'Not set',
            'last_synced_at' => 'Never',
            'latest_version' => [
                'metadata' => [
                    'asset_counts' => [
                        'pending' => 0,
                        'extracted' => 0,
                        'failed' => 0,
                    ],
                    'asset_details' => [
                        'completion_percent' => 0,
                        'processed_sample' => [],
                    ],
                ],
            ],
        ];
    }

    public function updated($name, $value): void
    {
        if ($name === 'figure' && $this->mode === 'advanced' && !$this->syncingAdvancedFigure) {
            $this->advancedSelections = app(HabboImagingDresser::class)->parseFigureString($this->figure);
            $this->syncAdvancedState(true, true, false);
            return;
        }

        if (in_array($name, ['direction', 'headDirection', 'gesture', 'action', 'headOnly'], true)) {
            $this->rebuildPreview();
            return;
        }

        if (in_array($name, ['username'], true)) {
            $this->rebuildPreview();
            return;
        }
    }
    
    public function updatedAction($value): void
    {
        $tokens = $this->actionTokens($value);

        if (array_intersect($tokens, ['wav', 'wlk'])) {
            $this->previewMode = 'animated';
        } else {
            $this->previewMode = 'static';
        }

        $this->rebuildPreview();
    }

    private function actionTokens(string $action): array
    {
        return array_values(array_filter(array_map(
            fn ($value) => strtolower(trim((string) $value)),
            preg_split('/[,\s]+/', $action) ?: []
        )));
    }

    public function setMode(string $mode): void
    {
        $this->mode = in_array($mode, ['normal', 'advanced'], true) ? $mode : 'normal';

        if ($this->mode === 'advanced') {
            $this->bootstrapAdvancedDresser();
        } else {
            $this->rebuildPreview();
            $this->refreshAdvancedReport();
        }
    }

    public function requestAdvancedRefresh(): void
    {
        $this->manifest = $this->getSyncStatus();
        $this->refreshAdvancedReport();
    }

    public function setAdvancedGender(string $gender): void
    {
        $this->advancedGender = strtoupper(trim($gender));
        $this->resetAdvancedItemLimit();
        $this->syncAdvancedState(true, true, false);
    }

    public function setAdvancedCategory(string $category): void
    {
        $category = strtolower(trim($category));

        if ($this->advancedCategory !== $category) {
            $this->resetAdvancedItemLimit();
        }

        $this->advancedCategory = $category;
        $this->syncAdvancedState(false, false, false);
    }

    public function selectAdvancedSet(string $category, int $setId): void
    {
        $category = strtolower(trim($category));
        $this->advancedSelections[$category] = array_merge($this->advancedSelections[$category] ?? [], [
            'set_id' => $setId,
        ]);
        $this->advancedCategory = $category;
        $this->syncAdvancedState(true, true, false);
    }

    private function humanItemLibraryPath(): ?string
    {
        $version = HabboImagingVersion::query()
            ->orderByDesc('synced_at')
            ->orderByDesc('id')
            ->first();

        return ($version && !empty($version->source_version)) ? 'db' : null;
    }

    private function buildHandItemCatalog(): array
    {
        return $this->buildHumanActionCatalog('crr');
    }

    private function buildSignCatalog(): array
    {
        return $this->buildHumanActionCatalog('sig');
    }

    public function setAdvancedHandItemMode(string $mode): void
    {
        $mode = strtolower(trim($mode));

        if (!in_array($mode, ['crr', 'drk'], true)) {
            return;
        }

        $this->handItemMode = $mode;
        $this->handItemCatalog = $this->buildHandItemCatalog();

        if (preg_match('/^(crr|drk)=(\d+)$/i', $this->action, $matches)) {
            $this->action = $mode . '=' . $matches[2];
            $this->rebuildPreview();
            $this->refreshAdvancedReport();
        }
    }

    private function renderFigureUrl(array $params): string
    {
        $action = isset($params['action']) ? (string) $params['action'] : null;

        if ($action !== null) {
            unset($params['action']);
        }

        $url = route('imager.render-figure', $params);

        if ($action === null || $action === '') {
            return $url;
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . 'action=' . $action;
    }

    private function prettyRenderFigureUrl(array $params): string
    {
        $action = array_key_exists('action', $params) ? (string) $params['action'] : null;

        if ($action !== null) {
            unset($params['action']);
        }

        $url = route('imager.render-figure', $params);

        if ($action === null || $action === '') {
            return $url;
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . 'action=' . $action;
    }

    private function buildHumanActionCatalog(string $actionPrefix): array
    {
        $libraryPath = $this->humanItemLibraryPath();
        if (!$libraryPath) {
            return [];
        }

        $repository = app(\App\Support\HabboImaging\HabboImagingAssetRepository::class);
        $symbols = $repository->symbolsMatchingPrefix('hh_human_item_h_' . $actionPrefix . '_');

        $items = [];

        foreach ($symbols as $symbolName) {
            $pattern = '/^hh_human_item_h_' . preg_quote($actionPrefix, '/') . '_([a-z]{2})_(\d+)_([0-7])_(\d+)$/i';

            if (!preg_match($pattern, $symbolName, $matches)) {
                continue;
            }

            $hand      = strtolower((string) $matches[1]);
            $id        = (string) $matches[2];
            $direction = (string) $matches[3];
            $frame     = (string) $matches[4];

            if ($actionPrefix === 'sig') {
                if ($hand !== 'li') continue;
                if ($direction !== '0') continue;
            } else {
                if ($hand !== 'ri') continue;
                if ($direction !== '2') continue;
            }

            if ($frame !== '0') continue;

            $items[$id] = [
                'value' => $id,
                'label' => match ($actionPrefix) {
                    'crr', 'drk' => 'Item ' . $id,
                    'sig'         => 'Sign ' . $id,
                    default       => strtoupper($actionPrefix) . ' ' . $id,
                },
                'thumb' => $this->staticAssetUrlForSymbol($symbolName),
            ];
        }

        ksort($items, SORT_NATURAL);
        return array_values($items);
    }

    public function staticFigureRenderUrl(array $params): string
    {
        return $this->renderFigureUrl($params);
    }

    private function staticAssetUrlForSymbol(string $symbolName): string
    {
        $safeName = preg_replace('/[^A-Za-z0-9_.-]/', '_', $symbolName) ?: sha1($symbolName);
        $relative = 'habbo-imaging/assets/' . $safeName . '.png';
        $publicPath = public_path('storage/' . $relative);

        if (is_file($publicPath)) {
            return '/storage/' . $relative;
        }

        $blob = app(\App\Support\HabboImaging\HabboImagingAssetRepository::class)->findBySymbol($symbolName);

        if ($blob === null || $blob === '') {
            return route('imager.asset', ['symbol' => $symbolName]);
        }

        if (!is_dir(dirname($publicPath))) {
            mkdir(dirname($publicPath), 0775, true);
        }

        file_put_contents($publicPath, $blob);

        return '/storage/' . $relative;
    }

    public function clearAdvancedCategorySelection(string $category): void
    {
        $category = strtolower(trim($category));

        if (in_array($category, ['hd', 'ch', 'lg', 'sh'], true)) {
            return;
        }

        unset($this->advancedSelections[$category]);
        $this->advancedCategory = $category;
        $this->syncAdvancedState(true, true, false);
    }

    public function selectAdvancedColor(string $category, int $slot, string $colorId): void
    {
        $category = strtolower(trim($category));
        $slotIndex = max(0, $slot - 1);
        $colors = $this->advancedSelections[$category]['colors'] ?? [];
        $colors[$slotIndex] = $colorId;
        ksort($colors);

        $this->advancedSelections[$category] = array_merge($this->advancedSelections[$category] ?? [], [
            'colors' => array_values($colors),
        ]);

        $this->syncAdvancedState(true, true, false);
    }

    public function loadMoreAdvancedItems(): void
    {
        $this->advancedItemLimit = 0;
        $this->syncAdvancedState(false, false, false);
    }

    public function setAdvancedBodyDirection(int $direction): void
    {
        $this->direction = (string) max(0, min(7, $direction));
        $this->rebuildPreview();
        $this->refreshAdvancedReport();
    }

    public function setAdvancedHeadDirection(int $direction): void
    {
        $this->headDirection = (string) max(0, min(7, $direction));
        $this->rebuildPreview();
        $this->refreshAdvancedReport();
    }

    public function setAdvancedActionPreset(string $action): void
    {
        $this->action = trim($action);

        if (preg_match('/^(crr|drk)=\d+$/i', $this->action, $matches)) {
            $this->handItemMode = strtolower($matches[1]);
        }

        $this->rebuildPreview();
        $this->refreshAdvancedReport();
    }

    public function setAdvancedHandItem(string $itemId): void
    {
        if (empty($this->handItemCatalog)) {
            $this->handItemCatalog = $this->buildHandItemCatalog();
        }

        $itemId = trim($itemId);
        $value = $this->handItemMode . '=' . $itemId;

        $this->action = $this->action === $value ? '' : $value;

        $this->rebuildPreview();
        $this->refreshAdvancedReport();
    }

    public function setAdvancedSign(string $signId): void
    {
        if (empty($this->signCatalog)) {
            $this->signCatalog = $this->buildSignCatalog();
        }

        $signId = trim($signId);
        $value = 'sig=' . $signId;

        $this->action = $this->action === $value ? '' : $value;

        $this->rebuildPreview();
        $this->refreshAdvancedReport();
    }

    public function setAdvancedGesturePreset(string $gesture): void
    {
        $this->gesture = trim($gesture);
        $this->rebuildPreview();
        $this->refreshAdvancedReport();
    }

    public function render()
    {
        return view('livewire.habbo-imager');
    }

    private function supportsAnimatedPreview(string $action): bool
    {
        $tokens = $this->actionTokens($action);

        foreach (['wav', 'wlk', 'spk'] as $animatedAction) {
            if (in_array($animatedAction, $tokens, true)) {
                return true;
            }
        }

        return false;
    }

    public function setPreviewMode(string $mode): void
    {
        $mode = strtolower(trim($mode));

        if (!in_array($mode, ['static', 'animated'], true)) {
            return;
        }

        $this->previewMode = $mode;
        $this->rebuildPreview();
    }    

    public function dresserPreviewUrl(array $overrides = [], ?string $figureOverride = null): string
    {
        $figure = trim($figureOverride ?? $this->figure);

        if ($figure === '' || empty($this->advancedDresser['version'])) {
            return '';
        }

        $params = array_merge([
            
            'figure' => $figure,
            'gender' => $this->advancedGender,
            'direction' => $this->direction,
            'head_direction' => $this->headDirection,
            'gesture' => $this->gesture,
        ], $overrides);

        if (($params['action'] ?? '') === '') {
            unset($params['action']);
        }

        if (array_key_exists('headonly', $params)) {
            $params['head_only'] = (int) !empty($params['headonly']);
            unset($params['headonly']);
        }

        if (($params['head_only'] ?? null) === null) {
            unset($params['head_only']);
        }

        return $this->staticFigureRenderUrl($params);
    }

    private function bootstrapAdvancedDresser(): void
    {
        $this->resetAdvancedItemLimit();

        if (!empty($this->figure)) {
            $this->advancedSelections = app(HabboImagingDresser::class)->parseFigureString($this->figure);
        }

        $this->syncAdvancedState();
    }

    private function syncAdvancedState(bool $syncFigure = true, bool $refreshPreview = true, bool $refreshReport = true): void
    {
        $this->advancedDresser = app(HabboImagingDresser::class)->build(
            $this->advancedGender,
            $this->advancedSelections,
            $this->advancedCategory,
            $this->advancedOptions()
        );

        if (!empty($this->advancedDresser['available'])) {
            $this->advancedGender = (string) ($this->advancedDresser['gender'] ?? $this->advancedGender);
            $this->advancedCategory = (string) ($this->advancedDresser['active_category'] ?? $this->advancedCategory);
            $this->advancedSelections = $this->advancedDresser['selections'] ?? $this->advancedSelections;

            if ($syncFigure) {
                $this->syncingAdvancedFigure = true;
                $this->figure = (string) ($this->advancedDresser['figure_string'] ?? $this->figure);
                $this->syncingAdvancedFigure = false;
            }
        }

        if ($refreshPreview) {
            $this->rebuildPreview();
        }

        if ($refreshReport) {
            $this->refreshAdvancedReport();
        }
    }

    private function advancedOptions(): array
    {
        $direction = (int) $this->direction;
        $headDirection = (int) $this->headDirection;
        $action = trim($this->action);
        $tokens = $this->actionTokens($action);

        if (in_array('lay', $tokens, true)) {
            $headDirection = $direction;
        }

        return [
            'direction' => $direction,
            'head_direction' => $headDirection,
            'gesture' => trim($this->gesture),
            'action' => $action,
            'head_only' => $this->headOnly === '1',
            'strict_direction' => true,
            'strict_action' => true,
            'allow_flip_fallback' => true,
            'item_limit' => $this->advancedItemLimit,
        ];
    }

    private function rebuildPreview(): void
    {
        if (trim($this->username) === '' && trim($this->figure) === '') {
            $this->previewUrl = '';
            $this->previewDisplayUrl = '';
            return;
        }

        $effectiveHeadDirection = $this->headDirection;
        $actionTokens = $this->actionTokens($this->action);

        if (in_array('lay', $actionTokens, true)) {
            $effectiveHeadDirection = $this->direction;
        }

        $params = [
            'direction' => $this->direction,
            'head_direction' => $effectiveHeadDirection,
            'gesture' => $this->gesture,
            'size' => $this->size,
        ];

        if ($this->action !== '') {
            $params['action'] = $this->action;
        }

        if ($this->headOnly) {
            $params['head_only'] = 1;
        }

        if (trim($this->figure) !== '') {
            if ($this->mode === 'advanced' && !empty($this->advancedDresser['version'])) {
              
                $params['figure'] = trim($this->figure);
                $params['gender'] = $this->advancedGender;
                $params['head_only'] = $this->headOnly ? 1 : 0;

                $useAnimatedPreview =
                    $this->previewMode === 'animated'
                    && $this->supportsAnimatedPreview($this->action);

                if ($useAnimatedPreview) {
                    $this->previewUrl = route('imager.render-figure-apng', $params);
                    $this->previewDisplayUrl = $this->previewUrl;
                } else {
                    $this->previewUrl = $this->staticFigureRenderUrl($params);
                    $this->previewDisplayUrl = $this->prettyRenderFigureUrl($params);
                }

                return;
            }

            $params['figure'] = trim($this->figure);
            $params['gender'] = $this->advancedGender;
        } else {
            $params['user'] = trim($this->username);
        }

        $this->previewUrl = '';
        $this->previewDisplayUrl = '';
    }

    public function getDebuggerUrlProperty(): string
    {
        if (trim($this->figure) === '') {
            return '';
        }

        return route('imager.debug.layers', [
            'figure' => trim((string) $this->figure),
            'gender' => strtoupper((string) $this->advancedGender),
            'direction' => (int) $this->direction,
            'head_direction' => (int) $this->headDirection,
            'gesture' => (string) $this->gesture,
            'action' => $this->action !== '' ? (string) $this->action : 'std',
            'frame' => 0,
            'head_only' => (int) $this->headOnly,
        ]);
    }

    private function refreshAdvancedReport(): void
    {
        $this->manifest = $this->getSyncStatus();
        $this->advancedReport = [];
        $this->advancedRenderDebug = [];
        $this->advancedSequence = [];
    }

    private function resetAdvancedItemLimit(): void
    {
        $this->advancedItemLimit = 0;
    }
}
