<?php

namespace App\Support\HabboImaging;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class HabboImagingDresser
{
    private const CATEGORY_ORDER = ['hd', 'hr', 'ha', 'he', 'ea', 'fa', 'ca', 'cc', 'cp', 'ch', 'lg', 'sh', 'wa', 'pt', 'mc'];
    private const STATIC_RENDER_REV = '12345615';
    private const DIRECTION_2_LAYER_ORDER = [
        'li',   'lh', 'ls', 'lc', 'mcl', 'ptl',
        'bd', 'lg', 'sh', 'cp', 'ch', 
        'cc', 'wa',
        'ca', 'mc',
        'hd', 'fc', 'ey', 'hr', 'hrb', 'fa', 'ea', 'ha', 'he', 'cri', 
        'ri', 'rh', 'rs', 'rc', 'mcr', 'pt', 'ptr',  
    ];

    private const DIRECTION_0_LAYER_ORDER = [
        'li',   'lh', 'ls', 'lc', 'mcl','ptl',
        'bd', 'lg', 'sh', 'ch', 'cp', 
        'cc', 'wa',
        'ca', 'mc',          
        'hd', 'fc', 'ey', 'hr', 'hrb', 'fa', 'ea', 'ha', 'he', 'cri', 
        'ri', 'rh', 'rs', 'rc', 'mcr',  'pt',  'ptr',  
    ];

    private const DIRECTION_4_LAYER_ORDER = [
        'rh', 'rs', 'rc',  'ri', 'mcr', 'ptr',
        'bd', 'lg', 'sh', 'cp', 'ch', 
        'cc', 'wa',
        'ca',
        'mc',
        'hd', 'fc', 'ey', 'hr', 'hrb', 'fa', 'ea', 'ha', 'he', 'cri',  
        'li', 'lh', 'ls', 'lc', 'mcl', 'ptl', 'pt',
    ];
    
    private const DIRECTION_6_LAYER_ORDER = [
        'rh', 'rs', 'rc',  'ri', 'mcr', 'ptr',
        'bd', 'lg', 'sh', 'ch', 'cp',
        'cc', 'wa',
        'ca', 'mc',        
        'hd', 'fc', 'ey', 'hr', 'hrb', 'fa', 'ea', 'ha', 'he', 'cri', 
        'li', 'lh', 'ls', 'lc', 'mcl',  'ptl','pt', 
    ];

    private const DIRECTION_3_LAYER_ORDER = [
        'bd', 'lg', 'sh', 'cp', 'ch',
        'cc', 'wa',
        'ca', 'mc',
        'li', 'lh', 'ls', 'lc',
        'ri', 'rh', 'rs', 'rc',      
        'mcr', 'mcl',
        'hd', 'fc', 'ey', 'hr', 'hrb', 'fa', 'ea', 'ha', 'he', 'cri', 'ptr','pt', 
    ];

    private const DIRECTION_4_LAY_ORDER = [
        'li', 'lh', 'ls', 'lc', 'mcl', 'ptl', 'bd', 
        'lg', 'sh', 'cp', 'ch', 'cc', 'wa', 'ca', 'mc',
        'ri', 'rh', 'rs', 'rc',
        'hd', 'fc', 'ey', 'hr', 'hrb', 'fa', 'ea', 'ha', 'he', 'cri',
        'mcr', 'pt', 'ptr',
    ];

    private const DIRECTION_4_HANDITEM_FRONT_ORDER = [
        'rh', 'rs', 'rc',   'mcr', 'ptr',
        'bd', 'lg', 'sh', 'cp', 'ch', 
        'cc', 'wa',
        'ca', 'mc',
        'hd', 'fc', 'ey', 'hr', 'hrb', 'fa', 'ea', 'ha', 'he', 'cri', 'ri',
        'li', 'lh', 'ls', 'lc', 'mcl', 'pt', 'ptl',
    ];
    
    private const DIRECTION_2_HANDITEM_FRONT_ORDER = [
        'li', 'lh', 'ls', 'lc', 'mcl', 'ptl',
        'bd', 'lg', 'sh', 'cp', 'ch',
        'cc', 'wa',
        'ca', 'mc',          
        'hd', 'fc', 'ey', 'hr', 'hrb', 'fa', 'ea', 'ha', 'he', 'cri',       
        'ri', 'rh', 'rs', 'rc', 'mcr', 'pt', 'ptr',
    ];

    private const DIRECTION_3_HANDITEM_FRONT_ORDER = [
        'bd', 'lg', 'sh', 'cp', 'ch',
        'cc', 'wa',
        'ca', 'mc',   
        'li', 'lh', 'ls', 'lc',  'ptl',
        'mcl',     
        'hd', 'fc', 'ey', 'hr', 'hrb', 'fa', 'ea', 'ha', 'he', 'cri',   
        'ri',  'rh', 'rs', 'rc', 'pt',  'mcr', 'ptr', 
    ];

    private const DIRECTION_5_WAV_SIG_LAYER_ORDER = [    
        'rh', 'rs', 'rc', 'ri', 'mcr',
        'bd', 'lg', 'sh', 'ch', 'cp', 'cc', 
        'ca', 'mc',
        'hd', 'fc', 'ey', 'hr', 'hrb', 'fa', 'ea', 'ha', 'he', 'wa',  
        'cri', 'li', 'lh', 'ls', 'lc', 'mcl', 'pt', 'ptr',
    ];

    private const DIRECTION_7_LAYER_ORDER = [   
        'ri', 'rh', 'rs', 'rc', 'mcr',   'ptr'   ,
        'li', 'lh', 'ls', 'lc', 'mcl', 'ptl' ,
        'bd', 'lg', 'sh', 'ch', 'cp',  'cc',  'wa', 'ca',  'mc', 
        'hd', 'fc', 'ey', 'hr', 'hrb', 'fa', 'ea', 'ha', 'he', 'cri', 'pt', 
    ];
    private const REQUIRED_CATEGORIES = ['hd', 'ch', 'lg', 'sh'];

    private const HEAD_PREVIEW_CATEGORIES = ['hd', 'hr', 'ha', 'he', 'ea', 'fa'];
    private const ACTION_SEQUENCE_PROFILES = [
        'wav' => [
            'frame_duration_ms' => 180,
            'loop' => true,
            'loop_mode' => 'restart',
            'fallback_frames' => [0, 1, 2],
        ],
        'wlk' => [
            'frame_duration_ms' => 140,
            'loop' => true,
            'loop_mode' => 'restart',
            'fallback_frames' => [0, 1, 2],
        ],
        'spk' => [
            'frame_duration_ms' => 120,
            'loop' => true,
            'loop_mode' => 'restart',
            'fallback_frames' => [0, 1, 2],
        ],
    ];

    private array $storedThumbnailUrlCache = [];
    private array $storedThumbnailDirectoryIndex = [];

    private const CATEGORY_LABELS = [
        'hd' => 'Face',
        'hr' => 'Hair',
        'ha' => 'Hats',
        'he' => 'Headwear',
        'ea' => 'Eyewear',
        'fa' => 'Face Accs',
        'ca' => 'Chest Accs',
        'cc' => 'Jackets',
        'cp' => 'Prints',
        'ch' => 'Tops',
        'lg' => 'Bottoms',
        'sh' => 'Shoes',
        'wa' => 'Waist',
        'pt' => 'Pets',
        'mc' => 'Others',
    ];

    public function __construct(
    private readonly HabboImagingFigureInspector $inspector,
    private readonly HabboImagingAssetRepository $repository,
    ) {
    }

    public function parseFigureString(string $figure): array
    {
        $selections = [];
        $segments = array_values(array_filter(explode('.', trim($figure))));

        foreach ($segments as $segment) {
            $pieces = array_values(array_filter(explode('-', trim($segment)), fn ($value) => $value !== '' && strtolower((string) $value) !== 'undefined'));

            if (count($pieces) < 2) {
                continue;
            }

            $setType = strtolower((string) $pieces[0]);
            $setId = (int) $pieces[1];
            $colors = array_values(array_filter(array_slice($pieces, 2), fn ($value) => is_numeric($value)));

            $selections[$setType] = [
                'set_id' => $setId,
                'colors' => array_map('strval', $colors),
            ];
        }

        return $selections;
    }
    private function headPairCorrection(
        int $bodyDirection,
        int $headRequestedDirection,
        int $headSourceDirection,
        string $action,
        bool $mirrored
    ): array {
        $action = strtolower(trim($action));

        $key = $bodyDirection
            . ':' . $headRequestedDirection
            . ':' . $headSourceDirection
            . ':' . $action
            . ':' . ($mirrored ? 'm' : 'n');

        return match ($key) {
            '2:4:2:std:m', '2:4:2:wav:m', '2:4:2:sit:m', '2:4:2:wlk:m' => ['x' => 66, 'y' => 0],
            '4:2:2:std:n', '4:2:2:wav:n', '4:2:2:sit:n', '4:2:2:wlk:n' => ['x' => -66, 'y' => 0],
            '0:6:0:std:m', '0:6:0:wav:m', '0:6:0:sit:m', '0:6:0:wlk:m' => ['x' => 66, 'y' => 0],
            '6:0:0:std:n', '6:0:0:wav:n', '6:0:0:sit:n', '6:0:0:wlk:n' => ['x' => -66, 'y' => 0],

            '3:5:1:std:m', '3:5:1:wav:m', '3:5:1:wlk:m' => ['x' => 65, 'y' => 0],
            '7:5:1:std:m', '7:5:1:wav:m', '7:5:1:wlk:m' => ['x' => 65, 'y' => 0],
            
            '3:4:2:std:m', '3:4:2:wav:m', '3:4:2:wlk:m' => ['x' => 65, 'y' => 0],
            '7:6:0:std:m', '7:6:0:wav:m', '7:6:0:wlk:m' => ['x' => 65, 'y' => 0],

            '5:3:3:std:n', '5:3:3:wav:n', '5:3:3:sit:n', '5:3:3:wlk:n' => ['x' => -65, 'y' => 0],
            '4:3:3:std:n', '4:3:3:wav:n', '4:3:3:sit:n', '4:3:3:wlk:n' => ['x' => -65, 'y' => 0],      
        
            '5:7:7:std:n', '5:7:7:wav:n', '5:7:7:sit:n', '5:7:7:wlk:n' => ['x' => -65, 'y' => 0],
            '6:7:7:std:n', '6:7:7:wav:n', '6:7:7:sit:n', '6:7:7:wlk:n' => ['x' => -65, 'y' => 0],
            default => ['x' => 0, 'y' => 0],
        };
    }
    private function actionTokens(string $action): array
    {
        return array_values(array_filter(array_map(
            fn ($value) => strtolower(trim((string) $value)),
            preg_split('/[,\s]+/', $action) ?: []
        )));
    }
    private function applyLayNrmFaceOffsets(
        string $figure,
        array $renderOptions,
        array $matchedParts
    ): array {
        $action = strtolower(trim((string) ($renderOptions['action'] ?? 'std')));

        if (!in_array($action, ['lay', 'lsp'], true)) {
            return $matchedParts;
        }

        $gesture = strtolower(trim((string) ($renderOptions['gesture'] ?? 'nrm')));

        if ($gesture === 'nrm') {
            return $matchedParts;
        }

        $baselineOptions = $renderOptions;
        $baselineOptions['gesture'] = 'nrm';

        $baselineReport = $this->inspector->inspect($figure, $baselineOptions);
        $baselineMatches = array_values($baselineReport['matched_parts'] ?? []);

        $baselineByPartType = [];

        foreach ($baselineMatches as $baselineMatch) {
            $partType = strtolower((string) ($baselineMatch['part_type'] ?? ''));

            if (!in_array($partType, ['ey', 'fc'], true)) {
                continue;
            }

            if (empty($baselineMatch['matched']) || empty($baselineMatch['best_asset'])) {
                continue;
            }

            $baselineByPartType[$partType] = $baselineMatch;
        }

        foreach ($matchedParts as &$match) {
            $partType = strtolower((string) ($match['part_type'] ?? ''));

            if (!in_array($partType, ['ey', 'fc'], true)) {
                continue;
            }

            if (empty($match['matched']) || empty($match['best_asset'])) {
                continue;
            }

            $baseline = $baselineByPartType[$partType] ?? null;

            if (!$baseline || empty($baseline['best_asset'])) {
                continue;
            }

            $match['best_asset']['offset_x'] = data_get($baseline, 'best_asset.offset_x');
            $match['best_asset']['offset_y'] = data_get($baseline, 'best_asset.offset_y');
        }
        unset($match);

        return $matchedParts;
    }
    private function dominantPoseAction(string $action): ?string
    {
        $tokens = $this->actionTokens($action);

        foreach (['lay', 'lsp', 'sit'] as $pose) {
            if (in_array($pose, $tokens, true)) {
                return $pose;
            }
        }

        return null;
    }
    public function build(string $gender, array $selections, string $activeCategory, array $options = []): array
    {
        $context = $this->inspector->latestContext(true);

        if (!$context) {
            return [
                'available' => false,
                'message' => 'Advanced dresser metadata is not available yet.',
            ];
        }

        $gender = $this->normalizeGender($gender);
        $setTypes = $context['set_types'];
        $availableCategories = $this->availableCategories($setTypes);
        $normalizedSelections = $this->normalizeSelections($setTypes, $context['palettes'], $gender, $selections, $availableCategories);
        $requestedItemLimit = (int) ($options['item_limit'] ?? 0);
        $itemLimit = $requestedItemLimit > 0 ? $requestedItemLimit : PHP_INT_MAX;

        if (!in_array($activeCategory, $availableCategories, true)) {
            $activeCategory = $availableCategories[0] ?? 'hd';
        }

        $currentSelection = $normalizedSelections[$activeCategory] ?? null;
        $currentSet = $currentSelection ? ($setTypes[$activeCategory]['sets'][(string) $currentSelection['set_id']] ?? null) : null;
        $activeCategorySetCount = count($this->filteredSets($setTypes[$activeCategory]['sets'] ?? [], $gender));
        $items = $this->buildItems(
            $context,
            $activeCategory,
            $gender,
            $normalizedSelections,
            $normalizedSelections[$activeCategory] ?? null,
            $options,
            $itemLimit
        );
        $colorSlots = $currentSelection['color_slots'] ?? 0;

        return [
            'available' => true,
            'version' => $context['version']->source_version,
            'gender' => $gender,
            'categories' => array_map(function (string $category) use ($setTypes, $activeCategory, $gender, $normalizedSelections, $context, $options) {
                $categorySelection = $normalizedSelections[$category] ?? null;
                $categorySet = null;

                if ($categorySelection && !empty($categorySelection['set_id'])) {
                    $categorySet = $setTypes[$category]['sets'][(string) $categorySelection['set_id']] ?? null;
                }

                if (!$categorySet) {
                    $categorySet = $this->filteredSets($setTypes[$category]['sets'] ?? [], $gender)[0] ?? null;
                }

                $candidateSelections = $normalizedSelections;

                if ($categorySet) {
                    $candidateSelections[$category] = [
                        'set_id' => (int) ($categorySet['id'] ?? 0),
                        'colors' => $this->candidateColors(
                            $setTypes,
                            $context['palettes'],
                            $category,
                            $this->colorSlotCount($categorySet),
                            array_values($categorySelection['colors'] ?? [])
                        ),
                        'color_slots' => $this->colorSlotCount($categorySet),
                    ];
                }

                return [
                    'key' => $category,
                    'label' => self::CATEGORY_LABELS[$category] ?? strtoupper($category),
                    'active' => $category === $activeCategory,
                    'set_count' => count($this->filteredSets($setTypes[$category]['sets'] ?? [], $gender)),
                    'tab_preview_url' => null,
                ];
            }, $availableCategories),
            'active_category' => $activeCategory,
            'active_category_label' => self::CATEGORY_LABELS[$activeCategory] ?? strtoupper($activeCategory),
            'selections' => $normalizedSelections,
            'current_selection' => $currentSelection,
            'current_set' => $currentSet,
            'current_palette' => $colorSlots >= 1 ? $this->paletteForCategory($setTypes, $context['palettes'], $activeCategory) : null,
            'secondary_palette' => $colorSlots >= 2 ? $this->paletteForCategory($setTypes, $context['palettes'], $activeCategory) : null,
            'items' => $items,
            'item_total' => $activeCategorySetCount,
            'has_more_items' => false,
            'figure_string' => $this->buildFigureString($normalizedSelections),
        ];
    }

    public function buildFigureString(array $selections): string
    {
        $segments = [];

        foreach (self::CATEGORY_ORDER as $category) {
            $selection = $selections[$category] ?? null;

            if (!$selection || empty($selection['set_id'])) {
                continue;
            }

            $parts = [$category, (string) $selection['set_id']];

            foreach (($selection['colors'] ?? []) as $color) {
                $color = trim((string) $color);

                if ($color !== '') {
                    $parts[] = $color;
                }
            }

            $segments[] = implode('-', $parts);
        }

        return implode('.', $segments);
    }

  public function ensureCompositeThumbnailPath( string $category, string $gender, int $setId): ?string
{
    $context = $this->inspector->latestContext(true);

    if (
        !$context
        || empty($context['set_types'][$category]['sets'][(string) $setId])
    ) {
        return null;
    }

    $set = $context['set_types'][$category]['sets'][(string) $setId];
    
    $renderHash = hash('sha256', json_encode([
        //'source_version' => $sourceVersion,
        'gender' => strtoupper($gender),
        'category' => $category,
        'set_id' => $setId,
        'type' => 'composite_thumbnail',
    ], JSON_UNESCAPED_SLASHES));

    if ($this->repository !== null) {
        $existingBlob = $this->repository->findDresserRender($renderHash);
        if ($existingBlob !== null) {
            return 'db:' . $renderHash;
        }
    }

    $options = $this->normalizeThumbnailOptions($category, $setId, [], in_array($category, self::HEAD_PREVIEW_CATEGORIES, true));
    $matches = $this->inspector->previewSetMatches($context, $category, $set, $options);
    $layers = $this->compositeLayersForMatches($matches);

    if (empty($layers)) {
        return null;
    }

    [$canvasWidth, $canvasHeight, $placedLayers] = array_slice($this->layoutCompositeLayers($layers, $category), 0, 3);

    if ($canvasWidth <= 0 || $canvasHeight <= 0) {
        return null;
    }

    $canvas = imagecreatetruecolor($canvasWidth, $canvasHeight);

    if (!$canvas) {
        return null;
    }

    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);
    $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
    imagefill($canvas, 0, 0, $transparent);
    imagealphablending($canvas, true);

    foreach ($placedLayers as $layer) {
        imagecopy(
            $canvas,
            $layer['image'],
            $layer['x'],
            $layer['y'],
            0,
            0,
            $layer['width'],
            $layer['height']
        );
        imagedestroy($layer['image']);
    }

    ob_start();
    imagepng($canvas);
    $blob = ob_get_clean();
    imagedestroy($canvas);

    if ($blob === false || $blob === '') {
        return null;
    }

    if ($this->repository !== null) {
        $this->repository->storeDresserRender($renderHash, $blob, [
            'type' => 'composite_thumbnail',
            //'source_version' => $sourceVersion,
            'gender' => strtoupper($gender),
            'category' => $category,
            'set_id' => $setId,
        ]);
    }

    return 'db:' . $renderHash;
}

    public function ensurePreviewRenderPath( string $category, string $gender, int $setId, array $options = []): ?string
{
    $context = $this->inspector->latestContext(true);

    if (
        !$context
        || empty($context['set_types'][$category]['sets'][(string) $setId])
    ) {
        return null;
    }
    

    

    $set = $context['set_types'][$category]['sets'][(string) $setId];
    $renderOptions = $this->normalizeThumbnailOptions(
        $category,
        $setId,
        $options,
        in_array($category, self::HEAD_PREVIEW_CATEGORIES, true)
    );
    
    $renderHash = $this->previewRenderHash($category, $gender, $setId, $renderOptions);

    if ($this->repository !== null) {
        $existingBlob = $this->repository->findDresserRender($renderHash);
        if ($existingBlob !== null) {
            return 'db:' . $renderHash;
        }
    }

    $matchedParts = $this->previewMatchedPartsForCategory($context, $category, $gender, $set, $renderOptions);
    
    $layers = $this->compositeLayersForReportMatches($matchedParts);

    if (empty($layers)) {
        return null;
    }

    [$canvasWidth, $canvasHeight, $placedLayers, $layoutDebug] = $this->layoutCompositeLayers($layers, $category);

    if ($canvasWidth <= 0 || $canvasHeight <= 0) {
        return null;
    }

    $canvas = imagecreatetruecolor($canvasWidth, $canvasHeight);

    if (!$canvas) {
        return null;
    }

    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);
    $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
    imagefill($canvas, 0, 0, $transparent);
    imagealphablending($canvas, true);

    foreach ($placedLayers as $layer) {
        imagecopy(
            $canvas,
            $layer['image'],
            (int) ($layer['x'] ?? 0),
            (int) ($layer['y'] ?? 0),
            0,
            0,
            (int) ($layer['width'] ?? 0),
            (int) ($layer['height'] ?? 0)
        );
        imagedestroy($layer['image']);
    }

    ob_start();
    imagepng($canvas);
    $pngBlob = ob_get_clean();
    imagedestroy($canvas);

    if ($pngBlob === false || $pngBlob === '') {
        return null;
    }

    // Store in database
    if ($this->repository !== null) {
        $this->repository->storeDresserRender($renderHash, $pngBlob, [

            'gender' => strtoupper($gender),
            'category' => $category,
            'set_id' => $setId,
            'options' => $renderOptions,
            'layout_debug' => $layoutDebug,
        ]);
    }

    return 'db:' . $renderHash;
}

private function appendHandItem(array $context, array $matchedParts, array $options): array
{
    $action = strtolower((string) ($options['action'] ?? ''));
    $itemId = $options['hand_item_id'] ?? null;

    if ($itemId === null || $itemId === '' || !is_numeric($itemId)) {
        return $matchedParts;
    }

    $itemId = (int) $itemId;    

    if ($itemId < 0) {
        return $matchedParts;
    }

    if (!in_array($action, ['sig', 'crr', 'drk'], true)) {
        return $matchedParts;
    }

    $asset = $context['asset_map']['hh_human_item'] ?? null;

    if (!$asset || $asset->status !== 'extracted') {
        return $matchedParts;
    }

    $requestedDirection = (int) ($options['direction'] ?? 2);
    $frame = (int) (($options['frame_preferences'][0] ?? 0));

    // keep semantic final item side only
    $renderPartType = ($action === 'sig') ? 'li' : 'ri';

    $prefix = match ($action) {
        'sig' => 'hh_human_item_h_sig',
        'crr' => 'hh_human_item_h_crr',
        'drk' => 'hh_human_item_h_drk',
        default => null,
    };

    if (!$prefix) {
        return $matchedParts;
    }

    // exact lookup only for item sprite
    $candidates = $this->inspector->libraryCandidatesForPart($asset, $renderPartType, $itemId);

    foreach ($candidates as $candidate) {
        if (!str_contains($candidate['symbol_name'], $prefix)) {
            continue;
        }

        if ((int) $candidate['direction'] !== $requestedDirection) {
            continue;
        }

        if ((int) $candidate['frame'] !== $frame) {
            continue;
        }

        $matchedParts[] = [
            'set_type' => 'item',
            'part_type' => $renderPartType,
            'part_id' => $itemId,
            'matched' => true,
            'best_asset' => [
                'symbol_name' => $candidate['symbol_name'],
                'relative_path' => $candidate['relative_path'],
                'asset_url' => $this->inspector->assetUrlForPath($candidate['relative_path']),
                'offset_x' => $candidate['offset_x'],
                'offset_y' => $candidate['offset_y'],
                'direction' => $requestedDirection,
                'source_direction' => $requestedDirection,
                'mirrored' => false,
                'frame' => $frame,
                'action' => $action,
            ],
            'requested_direction' => $requestedDirection,
            'used_mirroring' => false,
            'direction_resolution' => 'exact',
            'source_part_type' => $renderPartType,
            'render_part_type' => $renderPartType,
        ];

        break;
    }

    return $matchedParts;
}
public function ensureFigureRenderPath(string $gender, string $figure, array $options = []): ?string
{
    $context = $this->inspector->latestContext(true);

    if (
        !$context
        || trim($figure) === ''
    ) {
        return null;
    }
    

    
    $selections = $this->parseFigureString($figure);

    $bodyDirection = max(0, min(7, (int) ($options['direction'] ?? 2)));

    $rawAction = strtolower(trim((string) ($options['action'] ?? 'std')));

    $handItemId = null;

    if (str_contains($rawAction, '=')) {
        [$base, $param] = explode('=', $rawAction, 2);
        $rawAction = $base;
        $handItemId = is_numeric($param) ? (int) $param : null;
    }

    $action = $rawAction;
    $gesture = strtolower(trim((string) ($options['gesture'] ?? 'nrm')));
    $poseAction = $this->dominantPoseAction($action);

    if ($poseAction !== null) {
        $bodyDirection = $this->normalizePoseDirection($poseAction, $bodyDirection);
    }

    $rawHeadDirection = max(0, min(7, (int) ($options['head_direction'] ?? $bodyDirection)));

    if (!empty($options['head_only'])) {
        $headDirection = $rawHeadDirection;
        $bodyDirection = $rawHeadDirection;
    } elseif (in_array($poseAction, ['lay', 'lsp'], true)) {
        $headDirection = $this->normalizePoseDirection($poseAction, $rawHeadDirection);
    } else {
        $headDirection = $this->clampHeadDirectionForBody($bodyDirection, $rawHeadDirection);
    }

    $correctionAction = $action !== '' ? $action : 'std';
    $correctionTokens = $this->actionTokens($correctionAction);
    $correctionAction = 'std';

    foreach (['wav', 'sit', 'wlk'] as $poseActionForCorrection) {
        if (in_array($poseActionForCorrection, $correctionTokens, true)) {
            $correctionAction = $poseActionForCorrection;
            break;
        }
    }

    $renderOptions = [
        'direction' => $bodyDirection,
        'head_direction' => $headDirection,
        'gesture' => $gesture !== '' ? $gesture : 'nrm',
        'action' => $action !== '' ? $action : 'std',
        'hand_item_id' => $handItemId,
        'correction_action' => $correctionAction,
        'frame_preferences' => $this->normalizeFramePreferences($options),
        'head_only' => !empty($options['head_only']),
        'strict_direction' => !array_key_exists('strict_direction', $options) || (bool) $options['strict_direction'],
        'strict_action' => !array_key_exists('strict_action', $options) || (bool) $options['strict_action'],
        'allow_flip_fallback' => !array_key_exists('allow_flip_fallback', $options) || (bool) $options['allow_flip_fallback'],
        'size' => in_array(strtolower((string) ($options['size'] ?? 'm')), ['m', 'l'], true)
            ? strtolower((string) ($options['size'] ?? 'm'))
            : 'm',
    ];

    if ($renderOptions['gesture'] === '') {
        $renderOptions['gesture'] = 'nrm';
    }

    if ($renderOptions['action'] === '') {
        $renderOptions['action'] = 'std';
    }

    $renderHash = $this->figureRenderHash($gender, $figure, $renderOptions);

    if ($this->repository !== null) {
        $existingBlob = $this->repository->findFigureRender($renderHash);
        if ($existingBlob !== null) {
            return 'db:' . $renderHash;
        }
    }

    $report = $this->inspector->inspect($figure, $renderOptions);
    
    $matchedParts = array_values($report['matched_parts'] ?? []);
    $matchedParts = $this->applyLayNrmFaceOffsets($figure, $renderOptions, $matchedParts);
    
    $selections = $this->parseFigureString($figure);
    $matchedParts = $this->attachColorDataToMatches($context, $matchedParts, $selections);
    
    if (($renderOptions['head_only'] ?? false) !== true) {
        $matchedParts = $this->appendHandItem($context, $matchedParts, $renderOptions);
    }
    
    $hiddenPartTypes = array_values(
        $report['hidden_layers']
        ?? $this->hiddenPartTypesForSelections($context, $this->parseFigureString($figure))
    );

    $matchedParts = $this->filterMatchedPartsByHiddenLayers($matchedParts, $hiddenPartTypes);

    $layers = $this->compositeLayersForReportMatches($matchedParts);
    
    if (empty($layers)) {
        return null;
    }

    [$canvasWidth, $canvasHeight, $placedLayers, $layoutDebug] = $this->layoutCompositeLayers(
        $layers,
        !empty($renderOptions['head_only']) ? 'hd' : 'figure'
    );

    if ($canvasWidth <= 0 || $canvasHeight <= 0) {
        return null;
    }
    
    $direction = (int) ($renderOptions['direction'] ?? 2);
    $actionRaw = strtolower((string) ($renderOptions['action'] ?? ''));

    $isCarryDrink = str_starts_with($actionRaw, 'crr') || str_starts_with($actionRaw, 'drk');

    if (in_array($direction, [0, 1, 2, 3, 4, 5, 6, 7], true) && $isCarryDrink) {
        $frontOrder = match ($direction) {
            0 => self::DIRECTION_0_LAYER_ORDER,
            3 => self::DIRECTION_3_HANDITEM_FRONT_ORDER,
            4 => self::DIRECTION_4_HANDITEM_FRONT_ORDER,
            5 => self::DIRECTION_5_WAV_SIG_LAYER_ORDER,
            6 => self::DIRECTION_6_LAYER_ORDER,
            7 => self::DIRECTION_7_LAYER_ORDER,
            default => self::DIRECTION_2_HANDITEM_FRONT_ORDER,
        };

        usort($placedLayers, function (array $a, array $b) use ($frontOrder) {
            $aType = strtolower((string) ($a['part_type'] ?? ''));
            $bType = strtolower((string) ($b['part_type'] ?? ''));

            $aPos = array_search($aType, $frontOrder, true);
            $bPos = array_search($bType, $frontOrder, true);

            $aPos = $aPos === false ? 999 : $aPos;
            $bPos = $bPos === false ? 999 : $bPos;

            if ($aPos !== $bPos) {
                return $aPos <=> $bPos;
            }

            return ((int) ($a['source_part_index'] ?? $a['part_index'] ?? 0))
                <=> ((int) ($b['source_part_index'] ?? $b['part_index'] ?? 0));
        });
    }

    $canvas = imagecreatetruecolor($canvasWidth, $canvasHeight);

    if (!$canvas) {
        return null;
    }

    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);
    $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
    imagefill($canvas, 0, 0, $transparent);
    imagealphablending($canvas, true);

    foreach ($placedLayers as $layer) {
        imagecopy(
            $canvas,
            $layer['image'],
            (int) ($layer['x'] ?? 0),
            (int) ($layer['y'] ?? 0),
            0,
            0,
            (int) ($layer['width'] ?? 0),
            (int) ($layer['height'] ?? 0)
        );
        imagedestroy($layer['image']);
    }

    $sizeMode = strtolower((string) ($renderOptions['size'] ?? 'm'));

    if ($sizeMode === 'l') {
        $scaledWidth = $canvasWidth * 2;
        $scaledHeight = $canvasHeight * 2;

        $scaledCanvas = imagecreatetruecolor($scaledWidth, $scaledHeight);

        if ($scaledCanvas) {
            imagealphablending($scaledCanvas, false);
            imagesavealpha($scaledCanvas, true);
            $scaledTransparent = imagecolorallocatealpha($scaledCanvas, 0, 0, 0, 127);
            imagefill($scaledCanvas, 0, 0, $scaledTransparent);

            imagecopyresized(
                $scaledCanvas,
                $canvas,
                0,
                0,
                0,
                0,
                $scaledWidth,
                $scaledHeight,
                $canvasWidth,
                $canvasHeight
            );

            imagedestroy($canvas);
            $canvas = $scaledCanvas;
            $canvasWidth = $scaledWidth;
            $canvasHeight = $scaledHeight;
        }
    }

    ob_start();
    imagepng($canvas);
    $pngBlob = ob_get_clean();
    imagedestroy($canvas);

    if ($pngBlob === false || $pngBlob === '') {
        return null;
    }

    $boundsData = is_array($layoutDebug) ? $layoutDebug : [];
    if (empty($boundsData)) {
        $boundsData = [
            'canvas_width' => $canvasWidth,
            'canvas_height' => $canvasHeight,
            'min_x' => 0,
            'min_y' => 0,
            'max_x' => $canvasWidth,
            'max_y' => $canvasHeight,
        ];
    }

    $debugLayers = [];
    foreach ($placedLayers as $layerIndex => $layer) {
        $debugLayers[] = [
            'symbol_name' => $layer['symbol_name'] ?? '',
            'relative_path' => $layer['relative_path'] ?? '',
            'part_type' => $layer['part_type'] ?? '',
            'part_id' => $layer['part_id'] ?? 0,
            'x' => $layer['x'] ?? 0,
            'y' => $layer['y'] ?? 0,
            'width' => $layer['width'] ?? 0,
            'height' => $layer['height'] ?? 0,
            'mirrored' => $layer['mirrored'] ?? false,
        ];
    }

    if ($this->repository !== null) {
        $this->repository->storeFigureRender($renderHash, $pngBlob, [
            'gender' => $gender,
            'figure' => $figure,
            'options' => $renderOptions,
            'layout_debug' => [
                'bounds' => $boundsData,
                'hidden_layers' => $hiddenPartTypes,
                'layers' => $debugLayers,
            ],
        ]);
    }

    return 'db:' . $renderHash;
}
public function getRenderDebugData(string $renderHash): array
{
    if ($this->repository === null) {
        return [];
    }
    
    $metadata = $this->repository->getRenderMetadata($renderHash);
    
    if (empty($metadata) || empty($metadata['layout_debug'])) {
        return [];
    }
    
    return $metadata['layout_debug'];
}
    private function applyHiddenLayerRules(array $parts): array
    {
        $hasJacket = false;

        foreach ($parts as $p) {
            if (($p['part_type'] ?? '') === 'ch') {
                $hasJacket = true;
                break;
            }
        }

        if ($hasJacket) {
            $parts = array_values(array_filter($parts, function ($p) {
                return ($p['part_type'] ?? '') !== 'ls';
            }));
        }

        return $parts;
    }

    public function ensureFigureActionSequence( string $gender, string $figure, array $options = []): ?array
    {
         $context = $this->inspector->latestContext(true);

    if (
        !$context
        || trim($figure) === ''
    ) {
        return null;
    }

        $action = strtolower(trim((string) ($options['action'] ?? 'wav')));
        $profile = self::ACTION_SEQUENCE_PROFILES[$action] ?? null;

        if (!$profile) {
            return null;
        }

        $bodyDirection = $this->normalizePoseDirection(
            $action,
            max(0, min(7, (int) ($options['direction'] ?? 2)))
        );

        $rawHeadDirection = max(0, min(7, (int) ($options['head_direction'] ?? $bodyDirection)));

        if (in_array($action, ['lay', 'lsp'], true)) {
            $headDirection = $this->normalizePoseDirection($action, $rawHeadDirection);
        } else {
            $headDirection = $this->clampHeadDirectionForBody($bodyDirection, $rawHeadDirection);
        }

        $sequenceOptions = [
            'direction' => $bodyDirection,
            'head_direction' => $headDirection,
            'gesture' => strtolower(trim((string) ($options['gesture'] ?? 'nrm'))),
            'action' => $action,
            'head_only' => !empty($options['head_only']),
            'size' => in_array(strtolower((string) ($options['size'] ?? 'm')), ['m', 'l'], true)
                ? strtolower((string) ($options['size'] ?? 'm'))
                : 'm',
        ];
        $report = $this->inspector->inspect($figure, $sequenceOptions);
        $frameIndices = $this->resolveActionFrameIndices($report, $action, $sequenceOptions);

        if (empty($frameIndices)) {
            $frameIndices = $profile['fallback_frames'] ?? [0, 1, 2];
        }

        $frameIndices = array_values(array_unique(array_map('intval', $frameIndices)));
        sort($frameIndices);

        if (empty($frameIndices)) {
            $frameIndices = [0];
        }

        $frames = [];

        foreach ($frameIndices as $frameIndex) {
            $frameOptions = array_merge($sequenceOptions, [
                'frame' => $frameIndex,
                'frame_preferences' => [$frameIndex, 0, 1],
            ]);
            $renderPath = $this->ensureFigureRenderPath($gender, $figure, $frameOptions);

            if (!$renderPath) {
                continue;
            }

            $frames[] = [
                'index' => $frameIndex,
                'duration_ms' => (int) $profile['frame_duration_ms'],
                'render_path' => $renderPath,
                'render_url' => route('public.imager.render-figure', [

                    'gender' => strtoupper($gender),
                    'figure' => $figure,
                    'direction' => $sequenceOptions['direction'],
                    'head_direction' => $sequenceOptions['head_direction'],
                    'gesture' => $sequenceOptions['gesture'],
                    'action' => $action,
                    'head_only' => $sequenceOptions['head_only'] ? 1 : 0,
                    'frame' => $frameIndex,
                ]),
                'debug_path' => $this->debugPathForRender($renderPath),
            ];
        }

        $payload = [
            'kind' => 'figure_sequence',
            'source_version' => (string) ($context['version']->source_version ?? 'unknown'),
            'gender' => strtolower($gender),
            'figure' => $figure,
            'action' => $action,
            'direction' => $sequenceOptions['direction'],
            'head_direction' => $sequenceOptions['head_direction'],
            'loop' => (bool) $profile['loop'],
            'loop_mode' => (string) $profile['loop_mode'],
            'frame_duration_ms' => (int) $profile['frame_duration_ms'],
            'frame_count' => count($frames),
            'frames' => $frames,
        ];

        $cachePath = $this->figureSequenceCachePath($context, $gender, $figure, $sequenceOptions);
        Storage::disk('local')->makeDirectory(dirname($cachePath));
        Storage::disk('local')->put($cachePath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $payload + ['cache_path' => $cachePath];
    }

    private function previewMatchedPartsForCategory(array $context, string $category, string $gender, array $set, array $options = []): array
    {
        return in_array($category, self::HEAD_PREVIEW_CATEGORIES, true)
            ? $this->previewMatchedPartsForHeadCategory($context, $category, $gender, $set, $options)
            : $this->previewMatchedPartsForBodyCategory($context, $category, $set, $options);
    }

private function previewMatchedPartsForHeadCategory(array $context, string $category, string $gender, array $set, array $options = []): array
{
    $matchedParts = [];
    $renderOptions = $this->normalizeThumbnailOptions($category, (int) ($set['id'] ?? 0), $options, true);
    $baseHeadSelection = $this->baseHeadSelection($context, $gender);

    $baselineHeadMatches = [];

    if ($baseHeadSelection) {
        $baselineHeadMatches = array_values($this->inspectSelectionsForPreview([
            'hd' => $baseHeadSelection,
        ], $renderOptions)['matched_parts'] ?? []);
    }

    if ($category === 'hd') {
        $colorSlots = $this->colorSlotCount($set);
        $matchedParts = array_values($this->inspectSelectionsForPreview([
            'hd' => [
                'set_id' => (int) ($set['id'] ?? 0),
                'colors' => $this->candidateColors(
                    $context['set_types'],
                    $context['palettes'],
                    $category,
                    $colorSlots,
                    []
                ),
                'color_slots' => $colorSlots,
            ],
        ], $renderOptions)['matched_parts'] ?? []);
    } else {
        $matchedParts = $baselineHeadMatches;
        $colorSlots = $this->colorSlotCount($set);
        $matchedParts = array_merge(
            $matchedParts,
            array_values($this->inspectSelectionsForPreview([
                $category => [
                    'set_id' => (int) ($set['id'] ?? 0),
                    'colors' => $this->candidateColors(
                        $context['set_types'],
                        $context['palettes'],
                        $category,
                        $colorSlots,
                        []
                    ),
                    'color_slots' => $colorSlots,
                ],
            ], $renderOptions)['matched_parts'] ?? [])
        );
    }

    if ($category === 'hd' && !empty($baselineHeadMatches)) {
        $presentPartTypes = [];
        foreach ($matchedParts as $match) {
            $partType = strtolower((string) ($match['part_type'] ?? ''));
            if ($partType !== '') {
                $presentPartTypes[$partType] = true;
            }
        }
        foreach (['ey', 'fc'] as $requiredFacePart) {
            if (isset($presentPartTypes[$requiredFacePart])) continue;
            foreach ($baselineHeadMatches as $match) {
                if (strtolower((string) ($match['part_type'] ?? '')) === $requiredFacePart) {
                    $matchedParts[] = $match;
                }
            }
        }
    }
    
    $matchedParts = $this->filterMatchedPartsByHiddenLayers(
        $matchedParts,
        array_values(array_filter(array_map(
            fn ($partType) => strtolower(trim((string) $partType)),
            $set['hiddenlayers'] ?? []
        )))
    );
    
    return array_values($this->dedupeMatchedParts($matchedParts));
}

    private function previewMatchedPartsForBodyCategory(array $context, string $category, array $set, array $options = []): array
    {
        $colorSlots = $this->colorSlotCount($set);
        $renderOptions = $this->normalizeThumbnailOptions($category, (int) ($set['id'] ?? 0), $options, false);

        return array_values($this->inspectSelectionsForPreview([
            $category => [
                'set_id' => (int) ($set['id'] ?? 0),
                'colors' => $this->candidateColors(
                    $context['set_types'],
                    $context['palettes'],
                    $category,
                    $colorSlots,
                    []
                ),
                'color_slots' => $colorSlots,
            ],
        ], $renderOptions)['matched_parts'] ?? []);
    }

    private function previewDirectionForSet(string $category, int $setId): int
    {
        if ($category === 'cp' && $setId >= 3119 && $setId <= 3128) {
            return 6;
        }

        return 2;
    }

    private function normalizeThumbnailOptions(string $category, int $setId, array $options, bool $headOnly): array
    {
        $direction = max(0, min(7, (int) ($options['direction'] ?? 2)));
        $headDirection = max(0, min(7, (int) ($options['head_direction'] ?? 2)));

        if ($headOnly) {
            $direction = $headDirection;
        }

        if ($category === 'cp' && $setId >= 3119 && $setId <= 3128) {
            $direction = 6;
            $headDirection = 6;
        }

        if (
            $category === 'wa'
            && in_array($setId, [5674, 5676, 5719, 5775, 5778, 5780, 5782, 5784, 5786, 6392, 6394], true)
            && $direction === 2
        ) {
            $direction = 6;
            $headDirection = 6;
        }

        // thumbnails must always stay static
        $action = 'std';
        $gesture = 'nrm';
        $staticOnly = true;

        return [
            'direction' => $direction,
            'head_direction' => $headDirection,
            'gesture' => $gesture,
            'action' => $action,
            'head_only' => $headOnly,
            'static_only' => $staticOnly,
            'strict_direction' => true,
            'strict_action' => true,
            'preferred_variant' => 'h',
            'allow_overlay_fallbacks' => $category === 'cp',
            'allow_flip_fallback' => true,
        ];
    }

    private function inspectSelectionsForPreview(array $selections, array $options): array
    {
        $figureString = $this->buildFigureString($selections);

        if ($figureString === '') {
            return ['matched_parts' => []];
        }

        return $this->inspector->inspect($figureString, $options);
    }

    private function baseHeadSelection(array $context, string $gender): ?array
    {
        $baseline = $this->baselineSelections($context['set_types'], $context['palettes'], $gender);

        return !empty($baseline['hd']) ? $baseline['hd'] : null;
    }

    private function dedupeMatchedParts(array $matches): array
    {
        $deduped = [];

        foreach ($matches as $match) {
            $key = implode(':', [
                (string) ($match['set_type'] ?? ''),
                (string) ($match['part_type'] ?? ''),
                (string) ($match['part_id'] ?? ''),
                (string) data_get($match, 'best_asset.symbol_name', ''),
            ]);

            if ($key === ':::') {
                continue;
            }

            $deduped[$key] = $match;
        }

        return $deduped;
    }

    private function filterMatchedPartsByHiddenLayers(array $matches, array $hiddenPartTypes): array
    {
        if (empty($hiddenPartTypes)) {
            return $matches;
        }

        $hiddenLookup = array_fill_keys($hiddenPartTypes, true);

        return array_values(array_filter($matches, function (array $match) use ($hiddenLookup) {
            $partType = strtolower(trim((string) ($match['part_type'] ?? '')));

            return $partType === '' || !isset($hiddenLookup[$partType]);
        }));
    }

    private function buildItems(array $context, string $category, string $gender, array $allSelections, ?array $currentSelection, array $options, int $itemLimit): array
    {
        $items = [];

        if ($this->categoryAllowsDeselect($category)) {
            $items[] = [
                'set_id' => 0,
                'gender' => 'U',
                'club' => 0,
                'selected' => empty($currentSelection['set_id']),
                'color_slots' => 0,
                'thumbnail_url' => null,
                'thumbnail_mode' => 'clear',
                'clear_option' => true,
            ];
        }

        if ($category !== 'hd') {
            $catalog = $this->includeClubItemsInLimitedList($this->cachedCategoryCatalog($context, $category, $gender), $itemLimit);

            foreach ($catalog as $item) {
                $item['selected'] = (int) ($currentSelection['set_id'] ?? 0) === (int) ($item['set_id'] ?? 0);
                $thumbnail = $this->thumbnailPayloadForCategory(
                    $context,
                    $category,
                    $gender,
                    $allSelections,
                    [
                        'id' => (int) ($item['set_id'] ?? 0),
                        'gender' => (string) ($item['gender'] ?? 'U'),
                        'club' => (int) ($item['club'] ?? 0),
                    ],
                    $options
                );
                $item['thumbnail_url'] = $thumbnail['url'];
                $item['thumbnail_mode'] = $thumbnail['mode'];
                $items[] = $item;
            }

            return $items;
        }

        $sets = $this->includeClubItemsInLimitedList($this->filteredSets($context['set_types'][$category]['sets'] ?? [], $gender), $itemLimit);

        foreach ($sets as $set) {
            $candidateSelections = $allSelections;
            $colorSlots = $this->colorSlotCount($set);
            $candidateColors = $this->candidateColors(
                $context['set_types'],
                $context['palettes'],
                $category,
                $colorSlots,
                array_values($currentSelection['colors'] ?? [])
            );

            $candidateSelections[$category] = [
                'set_id' => (int) ($set['id'] ?? 0),
                'colors' => $candidateColors,
                'color_slots' => $colorSlots,
            ];

            $figureString = $this->buildFigureString($candidateSelections);
            $thumbnail = $this->thumbnailPayloadForCategory(
                $context,
                $category,
                $gender,
                $candidateSelections,
                $set,
                $options
            );

            $items[] = [
                'set_id' => (int) ($set['id'] ?? 0),
                'gender' => (string) ($set['gender'] ?? 'U'),
                'club' => (int) ($set['club'] ?? 0),
                'selected' => (int) ($currentSelection['set_id'] ?? 0) === (int) ($set['id'] ?? 0),
                'color_slots' => $colorSlots,
                'thumbnail_url' => $thumbnail['url'],
                'thumbnail_mode' => $thumbnail['mode'],
            ];
        }

        return $items;
    }

    private function availableCategories(array $setTypes): array
    {
        $available = [];

        foreach (self::CATEGORY_ORDER as $category) {
            if (!empty($setTypes[$category]['sets'])) {
                $available[] = $category;
            }
        }

        return $available;
    }

    private function includeClubItemsInLimitedList(array $items, int $itemLimit): array
    {
        $limited = array_slice($items, 0, $itemLimit);
        $seen = [];

        foreach ($limited as $item) {
            $seen[(string) ($item['set_id'] ?? $item['id'] ?? '')] = true;
        }

        foreach ($items as $item) {
            if ((int) ($item['club'] ?? 0) <= 0) {
                continue;
            }

            $id = (string) ($item['set_id'] ?? $item['id'] ?? '');

            if ($id === '' || isset($seen[$id])) {
                continue;
            }

            $limited[] = $item;
            $seen[$id] = true;
        }

        return array_values($limited);
    }

    private function normalizeSelections(array $setTypes, array $palettes, string $gender, array $selections, array $availableCategories): array
    {
        $normalized = [];

        foreach ($availableCategories as $category) {
            $availableSets = $this->filteredSets($setTypes[$category]['sets'] ?? [], $gender);
            $selected = $selections[$category] ?? null;
            $selectedSet = null;

            if ($selected && !empty($selected['set_id']) && isset($setTypes[$category]['sets'][(string) $selected['set_id']])) {
                $candidate = $setTypes[$category]['sets'][(string) $selected['set_id']];

                if ($this->matchesGender($candidate, $gender)) {
                    $selectedSet = $candidate;
                }
            }

            if (!$selectedSet && in_array($category, self::REQUIRED_CATEGORIES, true)) {
                $selectedSet = $availableSets[0] ?? null;
            } elseif (!$selectedSet && $selected && !empty($selected['set_id'])) {
                $selectedSet = $availableSets[0] ?? null;

                if (!$selectedSet && isset($setTypes[$category]['sets'][(string) $selected['set_id']])) {
                    $selectedSet = $setTypes[$category]['sets'][(string) $selected['set_id']];
                }
            }

            if (!$selectedSet) {
                continue;
            }

            $colorSlots = $this->colorSlotCount($selectedSet);
            $colors = array_map('strval', array_values($selected['colors'] ?? []));
            $palette = $this->paletteForCategory($setTypes, $palettes, $category);
            $defaultColorId = $this->defaultPaletteColorId($palette);

            while (count($colors) < $colorSlots) {
                $colors[] = $defaultColorId;
            }

            $colors = array_slice($colors, 0, $colorSlots);

            $normalized[$category] = [
                'set_id' => (int) ($selectedSet['id'] ?? 0),
                'colors' => $colors,
                'color_slots' => $colorSlots,
            ];
        }

        return $normalized;
    }

    private function candidateColors(
        array $setTypes,
        array $palettes,
        string $category,
        int $slotCount,
        array $requestedColors = []
    ): array {
        $slotCount = max(0, min(2, $slotCount));

        if ($slotCount === 0) {
            return [];
        }

        $palette = $this->paletteForCategory($setTypes, $palettes, $category);
        $availableColors = $palette['colors'] ?? [];

        if ($availableColors === []) {
            return [];
        }

        $allowedIds = array_map(
            static fn (array $color): string => (string) $color['id'],
            $availableColors
        );

        $defaultId = $allowedIds[0] ?? null;
        if ($defaultId === null) {
            return [];
        }

        $resolved = [];

        for ($slot = 0; $slot < $slotCount; $slot++) {
            $requested = (string) ($requestedColors[$slot] ?? '');

            $resolved[] = in_array($requested, $allowedIds, true)
                ? $requested
                : $defaultId;
        }

        return $resolved;
    }

    private function filteredSets(array $sets, string $gender): array
    {
        $filtered = array_filter($sets, fn (array $set) => $this->matchesGender($set, $gender) && (!empty($set['selectable']) || !empty($set['preselectable'])));

        usort($filtered, fn (array $left, array $right) => (int) ($left['id'] ?? 0) <=> (int) ($right['id'] ?? 0));

        return array_values($filtered);
    }

    private function matchesGender(array $set, string $gender): bool
    {
        $setGender = strtoupper((string) ($set['gender'] ?? 'U'));

        return $setGender === 'U' || $setGender === $gender;
    }

    private function colorSlotCount(array $set): int
    {
        $maxIndex = 0;

        foreach ($set['parts'] ?? [] as $part) {
            $maxIndex = max($maxIndex, (int) ($part['colorindex'] ?? 0));
        }

        return $maxIndex;
    }

    private function paletteForCategory(array $setTypes, array $palettes, string $category): ?array
    {
        $setType = $setTypes[$category] ?? null;

        if (!is_array($setType)) {
            return null;
        }

        $paletteId = (string) ($setType['palette_id'] ?? '');

        if ($paletteId === '' || !isset($palettes[$paletteId]) || !is_array($palettes[$paletteId])) {
            return null;
        }

        $colors = array_values(array_filter(
            $palettes[$paletteId]['colors'] ?? [],
            static fn (array $color): bool => !empty($color['selectable'])
        ));

        if ($colors === []) {
            return null;
        }

        return [
            'id' => (int) $paletteId,
            'colors' => $colors,
        ];
    }

    private function defaultPaletteColorId(?array $palette): string
    {
        foreach (($palette['colors'] ?? []) as $color) {
            if (!empty($color['selectable'])) {
                return (string) ($color['id'] ?? '1');
            }
        }

        return '1';
    }

private function thumbnailPayloadForCategory(array $context, string $category, string $gender, array $candidateSelections, array $set, array $options = []): array
{
    $renderGender = $this->finalRenderGenderForSet($set, $gender);
    $setId        = (int) ($set['id'] ?? 0);
    $headOnly     = in_array($category, self::HEAD_PREVIEW_CATEGORIES, true);
    $renderOptions = $this->normalizeThumbnailOptions($category, $setId, [
        'direction' => 2,
        'head_direction' => 2,
        'gesture' => 'nrm',
        'action' => 'std',
        'head_only' => $headOnly,
    ], $headOnly);

    $storedUrl = $this->storedDresserThumbnailUrl($context, $category, $renderGender, $setId);

    return [
    'url'  => $storedUrl ?? route('public.imager.dresser-render', [
            'hash'           => $this->previewRenderHash($category, $renderGender, $setId, $renderOptions),
            'category'       => $category,
            'gender'         => strtolower($renderGender),
            'set'            => $setId,
            'direction'      => 2,
            'head_direction' => 2,
            'gesture'        => 'nrm',
            'action'         => 'std',
            'head_only'      => $headOnly ? 1 : 0,
        ], false),
        'mode' => $headOnly ? 'avatar-head' : 'avatar-body',
    ];
}

    private function storedDresserThumbnailUrl(array $context, string $category, string $gender, int $setId): ?string
    {
        $cacheKey = strtolower(implode(':', [
            (string) ($context['version']->source_version ?? 'current'),
            $gender,
            $category,
            $setId,
        ]));

        if (array_key_exists($cacheKey, $this->storedThumbnailUrlCache)) {
            return $this->storedThumbnailUrlCache[$cacheKey];
        }

        $sourceVersion = (string) ($context['version']->source_version ?? 'current');
        $relativePath = sprintf(
            'Final/dresser/%s/%s/%s/%d.png',
            $sourceVersion,
            strtolower($gender),
            strtolower($category),
            $setId
        );

        if (is_file(public_path('storage/' . str_replace('\\', '/', $relativePath)))) {
            return $this->storedThumbnailUrlCache[$cacheKey] = '/storage/' . str_replace('\\', '/', $relativePath);
        }

        $fallbackUrl = $this->storedThumbnailUrlFromIndex($gender, $category, $setId);

        if ($fallbackUrl !== null) {
            return $this->storedThumbnailUrlCache[$cacheKey] = $fallbackUrl;
        }

        return $this->storedThumbnailUrlCache[$cacheKey] = null;
    }

    private function storedThumbnailUrlFromIndex(string $gender, string $category, int $setId): ?string
    {
        $gender = strtolower($gender);
        $category = strtolower($category);
        $indexKey = $gender . ':' . $category;

        if (!array_key_exists($indexKey, $this->storedThumbnailDirectoryIndex)) {
            $this->storedThumbnailDirectoryIndex[$indexKey] = [];
            $finalRoot = storage_path('app/public/Final/dresser');
            $root = is_dir($finalRoot) ? realpath($finalRoot) : false;

            if ($root) {
                $pattern = $root . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . $gender . DIRECTORY_SEPARATOR . $category . DIRECTORY_SEPARATOR . '*.png';

                foreach (glob($pattern) ?: [] as $path) {
                    $resolved = realpath($path);

                    if (!$resolved || !str_starts_with($resolved, $root) || !is_file($resolved)) {
                        continue;
                    }

                    $id = (int) pathinfo($resolved, PATHINFO_FILENAME);
                    if ($id <= 0) {
                        continue;
                    }

                    $relative = str_replace('\\', '/', substr($resolved, strlen($root) + 1));
                    $this->storedThumbnailDirectoryIndex[$indexKey][$id] = '/storage/Final/dresser/' . $relative;
                }
            }
        }

        return $this->storedThumbnailDirectoryIndex[$indexKey][$setId] ?? null;
    }

    public function previewRenderHash(string $category, string $gender, int $setId, array $renderOptions): string
    {
        return hash('sha256', json_encode([
            'render_rev' => self::STATIC_RENDER_REV,
            'gender' => strtoupper($gender),
            'category' => strtolower($category),
            'set_id' => $setId,
            'options' => $renderOptions,
        ], JSON_UNESCAPED_SLASHES));
    }

    public function figureRenderHash(string $gender, string $figure, array $renderOptions): string
    {
        return hash('sha256', json_encode([
            'render_rev' => self::STATIC_RENDER_REV,
            'gender' => strtoupper($gender),
            'figure' => $figure,
            'options' => $renderOptions,
        ], JSON_UNESCAPED_SLASHES));
    }
    private function categoryAllowsDeselect(string $category): bool
    {
        return !in_array($category, self::REQUIRED_CATEGORIES, true);
    }

    private function previewStrategyForCategory(string $category, array $set): string
    {
        $partCount = count($set['parts'] ?? []);

        if ($partCount > 1) {
            return 'composite';
        }

        return 'isolated';
    }

    private function isolatedThumbnailUrl(array $context, string $category, array $set): ?string
    {
        $setId = (int) ($set['id'] ?? 0);

        if ($setId <= 0) {
            return null;
        }

        $cachePath = sprintf(
            'habbo-imaging/cache/dresser-previews/%s/%s/%d.json',
            $context['version']->source_version,
            $category,
            $setId
        );

        if (Storage::disk('local')->exists($cachePath)) {
            $cached = json_decode((string) Storage::disk('local')->get($cachePath), true) ?: [];
            return $cached['asset_url'] ?? null;
        }

        $preview = $this->inspector->previewSet($context, $category, $set, [
            'direction' => 2,
            'head_direction' => 2,
            'gesture' => 'nrm',
            'action' => '',
            'head_only' => false,
        ]);

        Storage::disk('local')->makeDirectory(dirname($cachePath));
        Storage::disk('local')->put($cachePath, json_encode([
            'asset_url' => data_get($preview, 'best_asset.asset_url'),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return data_get($preview, 'best_asset.asset_url');
    }

    private function cachedCategoryCatalog(array $context, string $category, string $gender): array
    {
        $cacheKey = sprintf(
            'habbo-imager:dresser:catalog:%s:%s:%s:%s',
            self::STATIC_RENDER_REV,
            (string) ($context['version']->source_version ?? 'unknown'),
            $gender,
            $category
        );

        return Cache::rememberForever($cacheKey, function () use ($context, $category, $gender) {
            $items = [];
            $sets = $this->filteredSets($context['set_types'][$category]['sets'] ?? [], $gender);

            foreach ($sets as $set) {
                $items[] = [
                    'set_id' => (int) ($set['id'] ?? 0),
                    'gender' => (string) ($set['gender'] ?? 'U'),
                    'club' => (int) ($set['club'] ?? 0),
                    'selected' => false,
                    'color_slots' => $this->colorSlotCount($set),
                ];
            }

            return $items;
        });
    }

    private function baselineSelections(array $setTypes, array $palettes, string $gender): array
    {
        $baselineCategories = ['hd', 'hr', 'ch', 'lg', 'sh'];
        $selections = [];

        foreach ($baselineCategories as $category) {
            if (empty($setTypes[$category]['sets'])) {
                continue;
            }

            $availableSets = $this->filteredSets($setTypes[$category]['sets'] ?? [], $gender);
            $set = $category === 'hd'
                ? $this->preferredBaselineHeadSet($availableSets)
                : ($availableSets[0] ?? null);

            if (!$set) {
                continue;
            }

            $colorSlots = $this->colorSlotCount($set);
            $selections[$category] = [
                'set_id' => (int) ($set['id'] ?? 0),
                'colors' => $this->candidateColors(
                    $setTypes,
                    $palettes,
                    $category,
                    $colorSlots,
                    []
                ),
                'color_slots' => $colorSlots,
            ];
        }

        return $selections;
    }

    private function preferredBaselineHeadSet(array $sets): ?array
    {
        foreach ($sets as $set) {
            foreach (($set['parts'] ?? []) as $part) {
                if (($part['type'] ?? '') === 'hd' && (int) ($part['id'] ?? 0) === 1) {
                    return $set;
                }
            }
        }

        return $sets[0] ?? null;
    }

    private function normalizeGender(string $gender): string
    {
        $gender = strtoupper(trim($gender));

        return in_array($gender, ['M', 'F', 'U'], true) ? $gender : 'M';
    }

    private function previewFigureSelectionsForCategory(array $context, string $category, string $gender, array $candidateSelections): array
    {
        $baseline = $this->baselineSelections($context['set_types'], $context['palettes'], $gender);
        $selections = [];

        if (!empty($candidateSelections['hd'])) {
            $selections['hd'] = $candidateSelections['hd'];
        } elseif (!empty($baseline['hd'])) {
            $selections['hd'] = $baseline['hd'];
        }

        if (in_array($category, self::HEAD_PREVIEW_CATEGORIES, true)) {
            if (!empty($candidateSelections[$category])) {
                $selections[$category] = $candidateSelections[$category];
            }

            return $selections;
        }

        foreach (['ch', 'lg', 'sh'] as $requiredCategory) {
            if ($requiredCategory === $category && !empty($candidateSelections[$requiredCategory])) {
                $selections[$requiredCategory] = $candidateSelections[$requiredCategory];
                continue;
            }

            if (!empty($baseline[$requiredCategory])) {
                $selections[$requiredCategory] = $baseline[$requiredCategory];
            }
        }

        foreach (['ca', 'cc', 'cp', 'wa', 'pt', 'mc'] as $optionalCategory) {
            if ($optionalCategory === $category && !empty($candidateSelections[$optionalCategory])) {
                $selections[$optionalCategory] = $candidateSelections[$optionalCategory];
            }
        }

        if (!isset($selections[$category]) && !empty($candidateSelections[$category])) {
            $selections[$category] = $candidateSelections[$category];
        }

        return $selections;
    }

private function compositeThumbnailUrl(array $context, string $category, string $gender, array $set): ?string
{
    $setId = (int) ($set['id'] ?? 0);

    if ($setId <= 0 || !function_exists('imagecreatetruecolor') || !function_exists('imagepng')) {
        return null;
    }

    $renderHash = hash('sha256', json_encode([
        'category' => $category,
        'gender' => $gender,
        'set_id' => $setId,
        'action' => 'std',
    ], JSON_UNESCAPED_SLASHES));
    
    if ($this->repository !== null) {
        $cachedBlob = $this->repository->findDresserRender($renderHash);
        if ($cachedBlob !== null) {
            return route('public.imager.dresser-render', ['hash' => $renderHash]);
        }
    }

    $matches = $this->inspector->previewSetMatches($context, $category, $set, [
        'direction' => in_array($category, ['cp', 'wa'], true) ? 6 : 2,
        'head_direction' => in_array($category, ['cp', 'wa'], true) ? 6 : 2,
        'gesture' => '',
        'action' => 'std',
        'head_only' => false,
        'strict_direction' => true,
        'strict_action' => true,
        'preferred_variant' => 'h',
        'allow_overlay_fallbacks' => true,
    ]);

    $layers = $this->compositeLayersForMatches($matches);

    if (empty($layers)) {
        return null;
    }

    [$canvasWidth, $canvasHeight, $placedLayers] = array_slice($this->layoutCompositeLayers($layers, $category), 0, 3);

    if ($canvasWidth <= 0 || $canvasHeight <= 0) {
        return null;
    }

    $canvas = imagecreatetruecolor($canvasWidth, $canvasHeight);

    if (!$canvas) {
        return null;
    }

    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);
    $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
    imagefill($canvas, 0, 0, $transparent);
    imagealphablending($canvas, true);

    foreach ($placedLayers as $layer) {
        imagecopy(
            $canvas,
            $layer['image'],
            $layer['x'],
            $layer['y'],
            0,
            0,
            $layer['width'],
            $layer['height']
        );
        imagedestroy($layer['image']);
    }

    ob_start();
    imagepng($canvas);
    $blob = ob_get_clean();
    imagedestroy($canvas);

    if ($blob === false || $blob === '') {
        return null;
    }

    if ($this->repository !== null) {
        $this->repository->storeDresserRender($renderHash, $blob, [
            'type' => 'composite',
            'category' => $category,
            'gender' => $gender,
            'set_id' => $setId,
        ]);
    }

    return route('public.imager.dresser-render', ['hash' => $renderHash]);
}
    private function mirroredHandOffsetCorrection(
    string $renderPartType,
    string $sourcePartType,
    int $requestedDirection,
    int $sourceDirection,
    bool $usedMirroring
): array {
    if (!$usedMirroring) {
        return ['x' => 0, 'y' => 0];
    }
    
    $renderPartType = strtolower(trim($renderPartType));
    $sourcePartType = strtolower(trim($sourcePartType));
    
    if (in_array($renderPartType, ['lh', 'ls', 'lc', 'li']) && in_array($sourcePartType, ['rh', 'rs', 'rc', 'ri'])) {
        return ['x' => 0, 'y' => 0];
    }
    
    if (in_array($renderPartType, ['rh', 'rs', 'rc', 'ri']) && in_array($sourcePartType, ['lh', 'ls', 'lc', 'li'])) {
        return ['x' => 0, 'y' => 0];
    }
    
    return ['x' => 0, 'y' => 0];
}
private function compositeLayersForMatches(array $matches): array
{
    $layers = [];

    foreach ($matches as $match) {
        $symbolName = data_get($match, 'best_asset.symbol_name', '');
        
        if ($symbolName === '') {
            continue;
        }
        
        $existsInBlob = $this->repository !== null && $this->repository->findBySymbol($symbolName) !== null;
        
        if (!$existsInBlob) {
            continue;
        }
        
        $image = $this->openLayerImageBySymbol($symbolName, (bool) data_get($match, 'best_asset.mirrored', false));
        
        if (!$image) {
            continue;
        }

        imagealphablending($image, true);
        imagesavealpha($image, true);

        $width = imagesx($image);
        $height = imagesy($image);
        
        if ($width <= 0 || $height <= 0) {
            imagedestroy($image);
            continue;
        }

        $renderPartType = (string) ($match['render_part_type'] ?? $match['part_type'] ?? '');
        $sourcePartType = (string) ($match['source_part_type'] ?? $match['part_type'] ?? '');

        $offsetX = (int) data_get($match, 'best_asset.offset_x', 0);
        $offsetY = (int) data_get($match, 'best_asset.offset_y', 0);
        $requestedDirection = (int) ($match['requested_direction'] ?? $match['target_direction'] ?? data_get($match, 'best_asset.direction', 2));
        $sourceDirection = (int) data_get($match, 'best_asset.source_direction', data_get($match, 'best_asset.direction', 2));
        $usedMirroring = !empty($match['used_mirroring']) || (bool) data_get($match, 'best_asset.mirrored', false);

        $semanticCorrection = $this->mirroredHandOffsetCorrection(
            $renderPartType,
            $sourcePartType,
            $requestedDirection,
            $sourceDirection,
            $usedMirroring
        );

        $offsetX += $semanticCorrection['x'];
        $offsetY += $semanticCorrection['y'];

        $anchorPartType = (string) ($match['part_type'] ?? '');

        $layers[] = [
            'image' => $image,
            'width' => $width,
            'height' => $height,
            'anchor' => $this->compositeAnchorForPart($anchorPartType),
            'part_type' => $anchorPartType,
            'source_part_type' => $sourcePartType,
            'set_type' => (string) ($match['set_type'] ?? ''),
            'part_index' => $match['part_index'] ?? 0,
            'source_part_index' => (int) ($match['source_part_index'] ?? $match['part_index'] ?? 0),
            'part_id' => (int) ($match['part_id'] ?? 0),
            'direction' => $requestedDirection,
            'offset_x' => $offsetX,
            'offset_y' => $offsetY,
            'frame' => (int) data_get($match, 'best_asset.frame', 0),
            'action' => (string) data_get($match, 'best_asset.action', 'std'),
            'mirrored' => $usedMirroring,
            'source_direction' => $sourceDirection,
            'requested_direction' => $requestedDirection,
            'render_part_type' => $renderPartType,
            'direction_resolution' => (string) ($match['direction_resolution'] ?? 'exact'),
            'used_direction_fallback' => !empty($match['used_direction_fallback']),
            'used_action_fallback' => !empty($match['used_action_fallback']),
            'symbol_name' => $symbolName,
            'relative_path' => data_get($match, 'best_asset.relative_path', ''),
        ];
    }

    return $layers;
}

    private function attachColorDataToMatches(array $context, array $matches, array $selections): array
    {
        $setTypes = $context['set_types'] ?? [];
        $palettes = $context['palettes'] ?? [];

        foreach ($matches as &$match) {
            $setType = strtolower((string) ($match['set_type'] ?? ''));
            $partType = strtolower((string) ($match['part_type'] ?? ''));
            $partId = (int) ($match['part_id'] ?? 0);

            $selection = $selections[$setType] ?? null;
            $setId = (int) ($selection['set_id'] ?? 0);
            $set = $setTypes[$setType]['sets'][(string) $setId] ?? null;

            $match['color_ids'] = array_values(array_map('strval', $selection['colors'] ?? []));
            $match['matched_set_part'] = null;
            $match['colorable'] = false;
            $match['colorindex'] = 0;

            if (is_array($set)) {
                foreach (($set['parts'] ?? []) as $setPart) {
                    if (
                        strtolower((string) ($setPart['type'] ?? '')) === $partType
                        && (int) ($setPart['id'] ?? 0) === $partId
                    ) {
                        $match['matched_set_part'] = $setPart;
                        $match['colorable'] = ((int) ($setPart['colorable'] ?? 0)) === 1;
                        $match['colorindex'] = (int) ($setPart['colorindex'] ?? 0);
                        break;
                    }
                }
            }

            $palette = $this->paletteForCategory($setTypes, $palettes, $setType);
            $match['palette_id'] = (string) ($palette['id'] ?? '');
            $match['palette_colors'] = $palette['colors'] ?? [];
        }
        unset($match);

        return $matches;
    }

private function selectedHexColorForMatch(array $match): ?string
{
    if (empty($match['colorable'])) {
        return null;
    }

    $colorIndex = (int) ($match['colorindex'] ?? 0);

    if ($colorIndex <= 0) {
        return null;
    }

    $slot = $colorIndex - 1;
    $colorIds = array_values($match['color_ids'] ?? []);
    $selectedColorId = $colorIds[$slot] ?? null;

    if ($selectedColorId === null || $selectedColorId === '') {
        return null;
    }

    foreach (($match['palette_colors'] ?? []) as $color) {
        if ((string) ($color['id'] ?? '') === (string) $selectedColorId) {
            return strtoupper((string) ($color['hex'] ?? ''));
        }
    }

    return null;
}
    private function applyMatchColorsToImage($image, array $match)
    {
        $hex = $this->selectedHexColorForMatch($match);

        if (!$hex || strlen($hex) !== 6) {
            return $image;
        }

        [$targetR, $targetG, $targetB] = [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];

        $width = imagesx($image);
        $height = imagesy($image);

        imagealphablending($image, false);
        imagesavealpha($image, true);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgba = imagecolorat($image, $x, $y);

                $alpha = ($rgba >> 24) & 0x7F;
                $red = ($rgba >> 16) & 0xFF;
                $green = ($rgba >> 8) & 0xFF;
                $blue = $rgba & 0xFF;

                if ($alpha >= 127) {
                    continue;
                }

                $newR = (int) round(($red / 255) * $targetR);
                $newG = (int) round(($green / 255) * $targetG);
                $newB = (int) round(($blue / 255) * $targetB);

                $color = imagecolorallocatealpha($image, $newR, $newG, $newB, $alpha);
                imagesetpixel($image, $x, $y, $color);
            }
        }

        imagealphablending($image, true);

        return $image;
    }

private function compositeLayersForReportMatches(array $matches): array
{
    $layers = [];

    foreach ($matches as $index => $match) {
        // Get the symbol name directly from best_asset
        $symbolName = data_get($match, 'best_asset.symbol_name', '');
        
        if ($symbolName === '') {
            continue;
        }
        
        $existsInBlob = $this->repository !== null && $this->repository->findBySymbol($symbolName) !== null;
        
        if (!$existsInBlob) {
            continue;
        }
        
        $image = $this->openLayerImageBySymbol($symbolName, (bool) data_get($match, 'best_asset.mirrored', false));
        $image = $this->applyMatchColorsToImage($image, $match);
        
        if (!$image) {
            continue;
        }

        imagealphablending($image, true);
        imagesavealpha($image, true);

        $width = imagesx($image);
        $height = imagesy($image);
        
        if ($width <= 0 || $height <= 0) {
            imagedestroy($image);
            continue;
        }
        
        $renderPartType = (string) ($match['render_part_type'] ?? $match['part_type'] ?? '');
        $sourcePartType = (string) ($match['source_part_type'] ?? $match['part_type'] ?? '');
        
        $layers[] = [
            'image' => $image,
            'width' => $width,
            'height' => $height,
            'anchor' => $this->compositeAnchorForPart($renderPartType),
            'part_type' => $renderPartType,
            'source_part_type' => $sourcePartType,
            'set_type' => (string) ($match['set_type'] ?? ''),
            'part_index' => $index,
            'source_part_index' => (int) ($match['source_part_index'] ?? $match['part_index'] ?? $index),
            'part_id' => (int) ($match['part_id'] ?? 0),
            'direction' => (int) ($match['requested_direction'] ?? $match['target_direction'] ?? data_get($match, 'best_asset.direction', 2)),
            'offset_x' => data_get($match, 'best_asset.offset_x'),
            'offset_y' => data_get($match, 'best_asset.offset_y'),
            'frame' => (int) data_get($match, 'best_asset.frame', 0),
            'action' => (string) data_get($match, 'best_asset.action', 'std'),
            'mirrored' => !empty($match['used_mirroring']) || (bool) data_get($match, 'best_asset.mirrored', false),
            'source_direction' => (int) data_get($match, 'best_asset.source_direction', data_get($match, 'best_asset.direction', 2)),
            'requested_direction' => (int) ($match['requested_direction'] ?? $match['target_direction'] ?? data_get($match, 'best_asset.direction', 2)),
            'body_direction' => (int) ($match['body_direction'] ?? $match['target_direction'] ?? 2),
            'requested_action' => (string) ($match['requested_action'] ?? 'std'),
            'direction_resolution' => (string) ($match['direction_resolution'] ?? 'exact'),
            'used_direction_fallback' => !empty($match['used_direction_fallback']),
            'used_action_fallback' => !empty($match['used_action_fallback']),
            'used_mirroring' => !empty($match['used_mirroring']),
            'lay_nrm_offset_x' => (int) data_get($match, 'best_asset.lay_nrm_offset_x', 0),
            'lay_nrm_offset_y' => (int) data_get($match, 'best_asset.lay_nrm_offset_y', 0),
            'head_only' => !empty($match['head_only']),
            'symbol_name' => $symbolName,
            'relative_path' => data_get($match, 'best_asset.relative_path', ''),
        ];
    }

    return $layers;
}
private function openLayerImageBySymbol(string $symbolName, bool $mirrored)
{
    $image = null;

    if ($this->repository !== null) {
        $blob = $this->repository->findBySymbol($symbolName);
        if ($blob !== null) {
            $image = @imagecreatefromstring($blob);
        }
    }

    if (!$image) {
        return null;
    }

    if ($mirrored) {
        $this->flipImageHorizontally($image);
    }

    return $image;
}
    private function transformDirectionOffset(int $x, int $sourceDir, int $targetDir): int
{
    // only care about 4/5/6 cases
    if (!in_array($targetDir, [4,5,6], true)) {
        return $x;
    }

    return match ($targetDir . ':' . $sourceDir) {
        '4:2' => -$x,
        '5:1' => -$x,
        '6:0' => -$x,
        default => $x,
    };
}
    private function layoutCompositeLayers(array $layers, string $category): array
    {

        $hasRealOffsets = $this->allLayersHaveOffsets($layers);

        if ($hasRealOffsets) {
            $normalizedLayers = array_map(
                fn (array $layer) => $layer + $this->renderOffsetsForLayer($layer),
                $layers
            );
            $debugHandLayers = array_values(array_filter($normalizedLayers, function (array $layer) {
                $partType = strtolower((string) ($layer['part_type'] ?? ''));
                $renderPartType = strtolower((string) ($layer['render_part_type'] ?? ''));
                $sourcePartType = strtolower((string) ($layer['source_part_type'] ?? ''));

                return in_array($partType, ['lh','ls','lc','rh','rs','rc','li','ri'], true)
                    || in_array($renderPartType, ['lh','ls','lc','rh','rs','rc','li','ri'], true)
                    || in_array($sourcePartType, ['lh','ls','lc','rh','rs','rc','li','ri'], true);
            }));

            Storage::disk('local')->put(
                'habbo-imaging/debug-normalized-hand-layers.json',
                json_encode($debugHandLayers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
            $minX = min(array_map(fn (array $layer) => (int) $layer['render_offset_x'], $normalizedLayers));
            $minY = min(array_map(fn (array $layer) => (int) $layer['render_offset_y'], $normalizedLayers));
            $maxX = max(array_map(fn (array $layer) => (int) $layer['render_offset_x'] + (int) $layer['width'], $normalizedLayers));
            $maxY = max(array_map(fn (array $layer) => (int) $layer['render_offset_y'] + (int) $layer['height'], $normalizedLayers));
            $padding = 2;
            $canvasWidth = max(1, ($maxX - $minX) + ($padding * 2));
            $canvasHeight = max(1, ($maxY - $minY) + ($padding * 2));

            $positioned = array_map(function (array $layer) use ($minX, $minY, $padding) {
                return $layer + [
                    'x' => ((int) $layer['render_offset_x'] - $minX) + $padding,
                    'y' => ((int) $layer['render_offset_y'] - $minY) + $padding,
                ];
            }, $normalizedLayers);

            usort($positioned, fn (array $left, array $right) => $this->comparePreviewLayers($left, $right, $category));
            
            return [
                $canvasWidth,
                $canvasHeight,
                $positioned,
                [
                    'used_real_offsets' => true,
                    'min_x' => $minX,
                    'min_y' => $minY,
                    'max_x' => $maxX,
                    'max_y' => $maxY,
                    'padding' => $padding,
                ],
            ];
        }

        $headCategory = in_array($category, self::HEAD_PREVIEW_CATEGORIES, true);
        $paddingX = $headCategory ? 8 : 10;
        $paddingY = $headCategory ? 8 : 10;
        $overlap = $headCategory ? 4 : 6;

        $groups = [
            'left' => array_values(array_filter($layers, fn (array $layer) => $layer['anchor'] === 'left')),
            'center' => array_values(array_filter($layers, fn (array $layer) => $layer['anchor'] === 'center')),
            'right' => array_values(array_filter($layers, fn (array $layer) => $layer['anchor'] === 'right')),
        ];

        $leftWidth = $this->maxLayerDimension($groups['left'], 'width');
        $centerWidth = max(1, $this->maxLayerDimension($groups['center'], 'width'));
        $rightWidth = $this->maxLayerDimension($groups['right'], 'width');
        $maxHeight = max(1, $this->maxLayerDimension($layers, 'height'));

        $bodyLeft = $paddingX + max(0, $leftWidth - $overlap);
        $bodyRight = $bodyLeft + $centerWidth;
        $canvasWidth = max(1, $bodyRight + max(0, $rightWidth - $overlap) + $paddingX);
        $canvasHeight = max(1, $maxHeight + ($paddingY * 2));
        $baselineY = $canvasHeight - $paddingY;

        $positioned = array_map(function (array $layer) use ($bodyLeft, $bodyRight, $centerWidth, $baselineY) {
            $x = match ($layer['anchor']) {
                'left' => $bodyLeft - $layer['width'],
                'right' => $bodyRight,
                default => $bodyLeft + (int) floor(($centerWidth - $layer['width']) / 2),
            };

            $y = $baselineY - $layer['height'];

            return $layer + ['x' => $x, 'y' => $y];
        }, $layers);

        usort($positioned, fn (array $left, array $right) => $this->comparePreviewLayers($left, $right, $category));

        return [
            $canvasWidth,
            $canvasHeight,
            $positioned,
            [
                'used_real_offsets' => false,
                'padding_x' => $paddingX,
                'padding_y' => $paddingY,
                'overlap' => $overlap,
                'body_left' => $bodyLeft,
                'body_right' => $bodyRight,
                'baseline_y' => $baselineY,
            ],
        ];
    }

    private function compositeCachePath(array $context, string $category, string $gender, int $setId): string
    {
        return sprintf(
            'habbo-imaging/cache/dresser-composites/v5/%s/%s/%s/%d.png',
            (string) ($context['version']->source_version ?? 'unknown'),
            strtolower($gender),
            $category,
            $setId
        );
    }

    private function previewRenderCachePath(array $context, string $category, string $gender, int $setId, array $options = []): string
    {
        $poseKey = md5(json_encode([
            'direction' => (int) ($options['direction'] ?? 2),
            'head_direction' => (int) ($options['head_direction'] ?? 2),
            'gesture' => (string) ($options['gesture'] ?? 'nrm'),
            'action' => (string) ($options['action'] ?? 'std'),
            'head_only' => !empty($options['head_only']),
        ]));

        return sprintf(
            'habbo-imaging/renders/dresser/%s/%s/%s/%s/%d-%s.png',
            self::STATIC_RENDER_REV,
            (string) ($context['version']->source_version ?? 'unknown'),
            strtolower($gender),
            $category,
            $setId,
            $poseKey
        );
    }

    private function figureRenderCachePath(array $context, string $gender, string $figure, array $options = []): string
    {
        $poseKey = md5(json_encode([
            'figure' => $figure,
            'direction' => (int) ($options['direction'] ?? 2),
            'head_direction' => (int) ($options['head_direction'] ?? 2),
            'gesture' => (string) ($options['gesture'] ?? 'nrm'),
            'action' => (string) ($options['action'] ?? 'std'),
            'frame_preferences' => array_values($options['frame_preferences'] ?? []),
            'head_only' => !empty($options['head_only']),
        ]));

        return sprintf(
            'habbo-imaging/renders/figure/%s/%s/%s/%s.png',
            self::STATIC_RENDER_REV,
            (string) ($context['version']->source_version ?? 'unknown'),
            strtolower($gender),
            $poseKey
        );
    }

    private function figureSequenceCachePath(array $context, string $gender, string $figure, array $options = []): string
    {
        $sequenceKey = md5(json_encode([
            'figure' => $figure,
            'direction' => (int) ($options['direction'] ?? 2),
            'head_direction' => (int) ($options['head_direction'] ?? 2),
            'gesture' => (string) ($options['gesture'] ?? 'nrm'),
            'action' => (string) ($options['action'] ?? 'wav'),
            'head_only' => !empty($options['head_only']),
        ]));

        return sprintf(
            'habbo-imaging/renders/figure-sequences/v1/%s/%s/%s.json',
            (string) ($context['version']->source_version ?? 'unknown'),
            strtolower($gender),
            $sequenceKey
        );
    }

    private function normalizeFramePreferences(array $options): array
    {
        $framePreferences = $options['frame_preferences'] ?? [];

        if (!is_array($framePreferences)) {
            $framePreferences = [$framePreferences];
        }

        if (array_key_exists('frame', $options)) {
            array_unshift($framePreferences, (int) $options['frame']);
        }

        $framePreferences = array_values(array_unique(array_map(
            fn ($frame) => max(0, (int) $frame),
            array_filter($framePreferences, fn ($frame) => $frame !== null && $frame !== '')
        )));

        return !empty($framePreferences) ? $framePreferences : [0];
    }

    private function resolveActionFrameIndices(array $report, string $action, array $options): array
    {
        $frameIndices = [];
        $targetDirections = array_values(array_unique([
            (int) ($options['direction'] ?? 2),
            (int) ($options['head_direction'] ?? 2),
        ]));

        foreach (($report['matched_parts'] ?? []) as $match) {
            foreach (($match['debug_candidates'] ?? []) as $candidate) {
                if (strtolower((string) ($candidate['action'] ?? '')) !== $action) {
                    continue;
                }

                if (!in_array((int) ($candidate['direction'] ?? -1), $targetDirections, true)) {
                    continue;
                }

                if (!empty($candidate['mirrored'])) {
                    continue;
                }

                $frameIndices[(int) ($candidate['frame'] ?? 0)] = true;
            }
        }

        $frames = array_map('intval', array_keys($frameIndices));
        sort($frames);

        return $frames;
    }

    private function renderOffsetsForLayer(array $layer): array
    {
        $offsetX = (int) ($layer['offset_x'] ?? 0);
        $offsetY = (int) ($layer['offset_y'] ?? 0);
        $width = (int) ($layer['width'] ?? 0);
        $height = (int) ($layer['height'] ?? 0);
        $requestedDirection = (int) ($layer['requested_direction'] ?? $layer['direction'] ?? 2);
        $sourceDirection = (int) ($layer['source_direction'] ?? $requestedDirection);

        if (!empty($layer['mirrored'])) {
            $renderOffsetX = -1 * ($width - $offsetX);
        } else {
            $renderOffsetX = -1 * $offsetX;
        }

        $renderOffsetY = -1 * $offsetY;

        if (
            (string) ($layer['requested_action'] ?? 'std') === 'wav'
            && in_array((string) ($layer['part_type'] ?? ''), ['lh', 'ls'], true)
            && (int) ($layer['requested_direction'] ?? -1) === 7
            && empty($layer['mirrored'])
        ) {
            $renderOffsetY += 4;
        }

        $headMountedParts = ['hd', 'ey', 'fc', 'hr', 'hrb', 'ha', 'he', 'ea', 'fa', 'cri'];

        if (
            empty($layer['head_only'])
            && in_array((string) ($layer['part_type'] ?? ''), $headMountedParts, true)
        ) {
            $correction = $this->headPairCorrection(
                (int) ($layer['body_direction'] ?? 2),
                (int) ($layer['requested_direction'] ?? 2),
                (int) ($layer['source_direction'] ?? $layer['requested_direction'] ?? 2),
                (string) ($layer['correction_action'] ?? 'std'),
                !empty($layer['mirrored'])
            );

            $renderOffsetX += (int) ($correction['x'] ?? 0);
            $renderOffsetY += (int) ($correction['y'] ?? 0);
        }
        

        $setType  = (string) ($layer['set_type'] ?? '');
        $partType = trim((string) ($layer['part_type'] ?? ''));
        $partId   = (int) ($layer['part_id'] ?? 0);
        $dir      = (int) ($layer['requested_direction'] ?? 2);
        $action   = strtolower((string) ($layer['correction_action'] ?? $layer['action'] ?? ''));

        $offsets =  $defaultOffsets = [
            4 => -26,
            5 => -36,
            6 => -46,
        ];

        if (
            $setType === 'cc'
            && $partType === 'lc'
            && in_array($partId, [3000, 3001, 3002, 3003], true)
            && isset($offsets[$dir])
            && $action !== 'wav'
        ) {
            $renderOffsetX = $offsets[$dir];
        }

        if (
            $setType === 'ch'
            && in_array($partType, ['ls', 'rs'], true)
            && in_array($partId, [3177, 3179], true)
            && $dir === 4
        ) {
            $renderOffsetX = -46;
        }
        
        if (
            $setType === 'mc'
            && $partType === 'mc'
            && in_array($partId, [2609, 3285, 3286, 3715], true)
            && in_array($dir, [4, 5, 6], true)
            && $action !== 'wav'
        ) {

            if (in_array($partId, [2609, 3285, 3286], true)) {
                $renderOffsetX = $offsets[$dir];
            }
        }

        if ($setType === 'mc' && in_array($dir, [4, 5, 6], true)) {
            $partType = trim((string) ($layer['part_type'] ?? ''));
            $partId   = (int) ($layer['part_id'] ?? 0);
            $action   = strtolower((string) ($layer['action'] ?? $layer['correction_action'] ?? ''));

            $newX = match (true) {
                $partType === 'mc' && in_array($partId, [3715, 7220, 4025, 4024], true)
                    => $defaultOffsets[$dir],

                $partType === 'mcr' && $partId === 3716 && in_array($action, ['crr', 'drk'], true)
                    => 30,

                $partType === 'mcr' && $partId === 3716
                    => $defaultOffsets[$dir],

                default
                    => $renderOffsetX,
            };

            $renderOffsetX = $newX;
        }

        $renderPartType = strtolower((string) ($layer['render_part_type'] ?? $layer['part_type'] ?? ''));
        $sourcePartType = strtolower((string) ($layer['source_part_type'] ?? $layer['part_type'] ?? ''));
        

        $requestedDirection = (int) ($layer['requested_direction'] ?? $layer['direction'] ?? 2);
        $action = strtolower((string) ($layer['action'] ?? 'std'));
        $partType = strtolower((string) ($layer['part_type'] ?? ''));
        $mirrored = !empty($layer['mirrored']);

        $handCorrection = $this->exactHandActionCorrection(
            $requestedDirection,
            $action,
            $partType,
            $mirrored
        );

        $renderOffsetX += (int) ($handCorrection['x'] ?? 0);
        $renderOffsetY += (int) ($handCorrection['y'] ?? 0);  
        

        
        return [
            'render_offset_x' => $renderOffsetX,
            'render_offset_y' => $renderOffsetY,
        ];
    }

    private function exactHandActionCorrection(
        int $requestedDirection,
        string $action,
        string $partType,
        bool $mirrored
    ): array {
        $action = strtolower(trim($action));
        $partType = strtolower(trim($partType));

        if ($mirrored) {
            return ['x' => 0, 'y' => 0];
        }

        return match ($requestedDirection . ':' . $action . ':' . $partType) {
            '4:sig:lh' , '4:wav:ls' , '4:wav:lc' , '4:sig:li' , '4:wav:lh' , '4:wav:mcl'=> ['x' => -65, 'y' => 0],
            '6:sig:lh' , '6:wav:ls' , '6:wav:lc' , '6:sig:li' , '6:wav:lh' , '6:wav:mcl' => ['x' => -65, 'y' => 0],
            '5:sig:lh' , '5:wav:ls' , '5:wav:lc' , '5:sig:li' , '5:wav:lh' , '5:wav:mcl' => ['x' => -65, 'y' => 0],
            '4:crr:rh', '4:crr:rs', '4:crr:rc', '4:crr:ri', '4:drk:rh', '4:drk:rs', '4:drk:rc', '4:drk:ri' , '4:crr:mcr', '4:drk:mcr' => ['x' => -65, 'y' => 0],
            '6:crr:rh', '6:crr:rs', '6:crr:rc', '6:crr:ri', '6:drk:rh', '6:drk:rs', '6:drk:rc', '6:drk:ri' , '6:crr:mcr', '6:drk:mcr' => ['x' => -65, 'y' => 0],
            '5:crr:rh', '5:crr:rs', '5:crr:rc', '5:crr:ri', '5:drk:rh', '5:drk:rs', '5:drk:rc', '5:drk:ri' , '5:crr:mcr', '5:drk:mcr' => ['x' => -65, 'y' => 0],
            default => ['x' => 0, 'y' => 0],
        };
    }
private function openLayerImage(string $relativePath, bool $mirrored)
{
    $image = null;

    if ($this->repository !== null) {
        $symbolName = pathinfo($relativePath, PATHINFO_FILENAME);
        if ($symbolName !== '') {
            $blob = $this->repository->findBySymbol($symbolName);
            if ($blob !== null) {
                $image = @imagecreatefromstring($blob);
            }
        }
    }

    // Fall back to file system if not in repository (legacy)
    if ($image === null && $relativePath !== '' && Storage::disk('local')->exists($relativePath)) {
        $image = @imagecreatefrompng(Storage::disk('local')->path($relativePath));
    }

    if (!$image) {
        return null;
    }

    if ($mirrored) {
        $this->flipImageHorizontally($image);
    }

    return $image;
}

    private function flipImageHorizontally($image): void
    {
        if (function_exists('imageflip')) {
            imageflip($image, IMG_FLIP_HORIZONTAL);

            return;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $flipped = imagecreatetruecolor($width, $height);
        imagealphablending($flipped, false);
        imagesavealpha($flipped, true);
        $transparent = imagecolorallocatealpha($flipped, 0, 0, 0, 127);
        imagefill($flipped, 0, 0, $transparent);
        imagealphablending($flipped, true);

        imagecopyresampled($flipped, $image, 0, 0, $width - 1, 0, $width, $height, -$width, $height);
        imagecopy($image, $flipped, 0, 0, 0, 0, $width, $height);
        imagedestroy($flipped);
    }

    private function debugPathForRender(string $cachePath): string
    {
        return preg_replace('/\.png$/i', '.debug.json', $cachePath) ?: ($cachePath . '.debug.json');
    }

    private function writeRenderDebugPayload(string $cachePath, array $payload): void
    {
        Storage::disk('local')->put(
            $this->debugPathForRender($cachePath),
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    public function readRenderDebugPayload(string $cachePath): array
    {
        $debugPath = $this->debugPathForRender($cachePath);

        if (!Storage::disk('local')->exists($debugPath)) {
            return [];
        }

        return json_decode((string) Storage::disk('local')->get($debugPath), true) ?: [];
    }

    private function serializeDebugLayers(array $layers): array
    {
        return array_values(array_map(function (array $layer) {
            return [
                'symbol_name' => (string) ($layer['symbol_name'] ?? ''),
                'relative_path' => (string) ($layer['relative_path'] ?? ''),
                'part_type' => (string) ($layer['part_type'] ?? ''),
                'set_type' => (string) ($layer['set_type'] ?? ''),
                'part_id' => (int) ($layer['part_id'] ?? 0),
                'part_index' => (int) ($layer['part_index'] ?? 0),
                'source_part_index' => (int) ($layer['source_part_index'] ?? 0),
                'direction' => (int) ($layer['direction'] ?? 0),
                'source_direction' => (int) ($layer['source_direction'] ?? 0),
                'requested_direction' => (int) ($layer['requested_direction'] ?? $layer['direction'] ?? 0),
                'direction_resolution' => (string) ($layer['direction_resolution'] ?? 'exact'),
                'used_direction_fallback' => !empty($layer['used_direction_fallback']),
                'used_action_fallback' => !empty($layer['used_action_fallback']),
                'action' => (string) ($layer['action'] ?? ''),
                'frame' => (int) ($layer['frame'] ?? 0),
                'offset_x' => $layer['offset_x'],
                'offset_y' => $layer['offset_y'],
                'mirrored' => !empty($layer['mirrored']),
                'anchor' => (string) ($layer['anchor'] ?? ''),
                'x' => (int) ($layer['x'] ?? 0),
                'y' => (int) ($layer['y'] ?? 0),
                'width' => (int) ($layer['width'] ?? 0),
                'height' => (int) ($layer['height'] ?? 0),
                'render_offset_x' => $layer['render_offset_x'] ?? null,
                'render_offset_y' => $layer['render_offset_y'] ?? null,
            ];
        }, $layers));
    }

    private function serializeMissingMatches(array $matches): array
    {
        $missing = array_values(array_filter($matches, fn (array $match) => empty($match['matched'])));

        return array_values(array_map(function (array $match) {
            return [
                'segment' => (string) ($match['segment'] ?? ''),
                'set_type' => (string) ($match['set_type'] ?? ''),
                'part_type' => (string) ($match['part_type'] ?? ''),
                'part_id' => (int) ($match['part_id'] ?? 0),
                'requested_direction' => (int) ($match['requested_direction'] ?? $match['target_direction'] ?? 0),
                'missing_reason' => (string) ($match['missing_reason'] ?? 'missing'),
                'available_source_directions' => array_values($match['available_source_directions'] ?? []),
                'available_source_actions' => array_values($match['available_source_actions'] ?? []),
                'library_names' => array_values($match['library_names'] ?? []),
            ];
        }, $missing));
    }

    private function serializeFallbackMatches(array $matches): array
    {
        $fallbacks = array_values(array_filter($matches, function (array $match) {
            return !empty($match['matched']) && (
                !empty($match['used_mirroring'])
                || !empty($match['used_direction_fallback'])
                || !empty($match['used_action_fallback'])
            );
        }));

        return array_values(array_map(function (array $match) {
            return [
                'segment' => (string) ($match['segment'] ?? ''),
                'set_type' => (string) ($match['set_type'] ?? ''),
                'part_type' => (string) ($match['part_type'] ?? ''),
                'part_id' => (int) ($match['part_id'] ?? 0),
                'requested_direction' => (int) ($match['requested_direction'] ?? $match['target_direction'] ?? 0),
                'chosen_asset_direction' => (int) ($match['chosen_asset_direction'] ?? data_get($match, 'best_asset.direction', 0)),
                'chosen_source_direction' => (int) ($match['chosen_source_direction'] ?? data_get($match, 'best_asset.source_direction', 0)),
                'direction_resolution' => (string) ($match['direction_resolution'] ?? 'exact'),
                'used_mirroring' => !empty($match['used_mirroring']),
                'used_direction_fallback' => !empty($match['used_direction_fallback']),
                'used_action_fallback' => !empty($match['used_action_fallback']),
                'chosen_action' => (string) ($match['chosen_action'] ?? data_get($match, 'best_asset.action', '')),
                'symbol_name' => (string) data_get($match, 'best_asset.symbol_name', ''),
            ];
        }, $fallbacks));
    }

    private function compositeAnchorForPart(string $partType): string
    {
        $partType = strtolower(trim($partType));

        if (str_starts_with($partType, 'l')) {
            return 'left';
        }

        if (str_starts_with($partType, 'r')) {
            return 'right';
        }

        return 'center';
    }

    private function compositeLayerPriority(string $anchor): int
    {
        return match ($anchor) {
            'center' => 0,
            'left' => 1,
            'right' => 2,
            default => 3,
        };
    }
private function useFrontHandItemLayerOrder(int $direction, string $action): bool
{
    $action = strtolower(trim($action));

    return in_array($direction, [2, 3, 4], true)
        && in_array($action, ['crr', 'drk'], true);
}
private function comparePreviewLayers(array $left, array $right, string $category): int
{
    $direction = (int) (
        $left['body_direction']
        ?? $right['body_direction']
        ?? $left['requested_direction']
        ?? $right['requested_direction']
        ?? $left['direction']
        ?? $right['direction']
        ?? 2
    );

    $leftRequestedAction = strtolower((string) ($left['requested_action'] ?? $left['action'] ?? ''));
    $rightRequestedAction = strtolower((string) ($right['requested_action'] ?? $right['action'] ?? ''));

    $effectiveAction = in_array('sig', [$leftRequestedAction, $rightRequestedAction], true) ? 'sig'
        : (in_array('wav', [$leftRequestedAction, $rightRequestedAction], true) ? 'wav'
        : (in_array('spk', [$leftRequestedAction, $rightRequestedAction], true) ? 'spk'
        : $leftRequestedAction));

    $order = match (true) {
        $direction === 4 && in_array($effectiveAction, ['lay', 'lsp'], true)
            => self::DIRECTION_4_LAY_ORDER,

        $direction === 5 && in_array($effectiveAction, ['wav', 'sig', 'spk'], true)
            => self::DIRECTION_5_WAV_SIG_LAYER_ORDER,

        $this->useFrontHandItemLayerOrder($direction, $effectiveAction) && $direction === 2
            => self::DIRECTION_2_HANDITEM_FRONT_ORDER,

        $this->useFrontHandItemLayerOrder($direction, $effectiveAction) && in_array($direction, [3, 4], true)
            => self::DIRECTION_3_HANDITEM_FRONT_ORDER,

        in_array($direction, [1, 2], true)
            => self::DIRECTION_2_LAYER_ORDER,

        in_array($direction, [4, 5], true)
            => self::DIRECTION_4_LAYER_ORDER,

        $direction === 3
            => self::DIRECTION_3_LAYER_ORDER,

        $direction === 7
            => self::DIRECTION_7_LAYER_ORDER,
            
        $direction === 6
            => self::DIRECTION_6_LAYER_ORDER,

        $direction === 0
            => self::DIRECTION_0_LAYER_ORDER,

        default => null,
    };
    $leftType = (string) ($left['part_type'] ?? '');
    $rightType = (string) ($right['part_type'] ?? '');

    $leftPos = $order ? array_search($leftType, $order, true) : false;
    $rightPos = $order ? array_search($rightType, $order, true) : false;

    $leftPos = $leftPos === false ? 999 : $leftPos;
    $rightPos = $rightPos === false ? 999 : $rightPos;

    if ($leftPos !== $rightPos) {
        return $leftPos <=> $rightPos;
    }

    return ((int) ($left['source_part_index'] ?? $left['part_index'] ?? 0))
        <=> ((int) ($right['source_part_index'] ?? $right['part_index'] ?? 0));
}

    private function compareSortTuples(array $left, array $right): int
    {
        foreach (['body_rank', 'item_rank', 'source_part_index', 'part_id', 'part_index'] as $key) {
            $comparison = ((int) ($left[$key] ?? 0)) <=> ((int) ($right[$key] ?? 0));

            if ($comparison !== 0) {
                return $comparison;
            }
        }

        return 0;
    }

    private function compareLegacyLayers(array $left, array $right): int
    {
        $leftPriority = $this->semanticLayerPriority($left);
        $rightPriority = $this->semanticLayerPriority($right);

        if ($leftPriority !== $rightPriority) {
            return $leftPriority <=> $rightPriority;
        }

        $leftSource = -1 * (int) ($left['source_part_index'] ?? $left['part_index'] ?? 0);
        $rightSource = -1 * (int) ($right['source_part_index'] ?? $right['part_index'] ?? 0);

        if ($leftSource !== $rightSource) {
            return $leftSource <=> $rightSource;
        }

        $leftPartId = -1 * (int) ($left['part_id'] ?? 0);
        $rightPartId = -1 * (int) ($right['part_id'] ?? 0);

        if ($leftPartId !== $rightPartId) {
            return $leftPartId <=> $rightPartId;
        }

        return ((int) ($left['part_index'] ?? 0)) <=> ((int) ($right['part_index'] ?? 0));
    }

    private function semanticLayerPriority(array $layer): int
{
    $partType = strtolower((string) ($layer['part_type'] ?? ''));
    $setType = strtolower((string) ($layer['set_type'] ?? ''));
    $direction = (int) ($layer['direction'] ?? 2);
    $action = strtolower((string) (
        $layer['requested_action']
        ?? $layer['action']
        ?? ''
    ));

    $orderedPriority = $this->directionOrderedLayerPriority(
        $setType,
        $partType,
        $direction,
        $action
    );

    if ($orderedPriority !== null) {
        return $orderedPriority;
    }

    return match ($partType) {
        'bd' => 10,
        'lg' => 20,
        'sh' => 21,
        'ls' => 28,
        'rs' => 29,
        'ch' => 40,
        'cp' => 41,
        'ca' => 42,
        'cc' => 43,
        'wa' => 44,
        'lc' => 50,
        'rc' => 51,
        'lh' => 70,
        'rh' => 71,
        'mc' => 72,
        'mcl' => 73,
        'mcr' => 74,
        'hrb' => 89,
        'hd' => 80,
        'ey' => 81,
        'fc' => 82,
        'hr' => 90,
        'ha' => 91,
        'he' => 92,
        'ea' => 93,
        'fa' => 94,
        'pt' => 95,
        'ptr' => 96,
        default => 60 + $this->compositeLayerPriority((string) ($layer['anchor'] ?? 'center')),
    };
}

private function directionOrderedLayerPriority(
    string $setType,
    string $partType,
    int $direction,
    string $action = ''
): ?int {
    $effectivePartType = strtolower(trim($partType));
    $action = strtolower(trim($action));

    if ($setType === 'ea' && $effectivePartType === 'sh') {
        $effectivePartType = 'ea';
    }

    $order = match (true) {
        $direction === 5 && in_array($action, ['wav', 'sig', 'spk'], true)
            => self::DIRECTION_5_WAV_SIG_LAYER_ORDER,

        $this->useFrontHandItemLayerOrder($direction, $action) && $direction === 2
            => self::DIRECTION_2_HANDITEM_FRONT_ORDER,

        $this->useFrontHandItemLayerOrder($direction, $action) && in_array($direction, [3, 4], true)
            => self::DIRECTION_3_HANDITEM_FRONT_ORDER,

        in_array($direction, [1, 2], true)
            => self::DIRECTION_2_LAYER_ORDER,

        in_array($direction, [4, 5], true)
            => self::DIRECTION_4_LAYER_ORDER,

        $direction === 3
            => self::DIRECTION_3_LAYER_ORDER,

        $direction === 7
            => self::DIRECTION_7_LAYER_ORDER,
            
        $direction === 6
            => self::DIRECTION_6_LAYER_ORDER,

        $direction === 0
            => self::DIRECTION_0_LAYER_ORDER,
            

        default => null,
    };

    if ($order === null) {
        return null;
    }

    $index = array_search($effectivePartType, $order, true);

    return $index === false ? null : $index;
}
    private function maxLayerDimension(array $layers, string $key): int
    {
        $max = 0;

        foreach ($layers as $layer) {
            $max = max($max, (int) ($layer[$key] ?? 0));
        }

        return $max;
    }

    private function allLayersHaveOffsets(array $layers): bool
    {
        if (empty($layers)) {
            return false;
        }

        foreach ($layers as $layer) {
            if (!isset($layer['offset_x'], $layer['offset_y']) || $layer['offset_x'] === null || $layer['offset_y'] === null) {
                return false;
            }
        }

        return true;
    }

    private function hiddenPartTypesForSelections(array $context, array $selections): array
    {
        $hidden = [];

        foreach ($selections as $category => $selection) {
            $setId = (string) ((int) ($selection['set_id'] ?? 0));

            if ($setId === '0') {
                continue;
            }

            $set = $context['set_types'][$category]['sets'][$setId] ?? null;

            if (!$set) {
                continue;
            }

            foreach (($set['hiddenlayers'] ?? []) as $partType) {
                $partType = strtolower(trim((string) $partType));

                if ($partType !== '') {
                    $hidden[$partType] = true;
                }
            }
        }

        return array_keys($hidden);
    }

    private function assetUrlForSymbol(string $symbolName): ?string
    {
        if ($symbolName === '') {
            return null;
        }

        if ($this->repository !== null) {
            $blob = $this->repository->findBySymbol($symbolName);
            if ($blob !== null) {
                return '/imager/asset?symbol=' . rawurlencode($symbolName);
            }
        }

        return null;
    }
    private function allowedHeadDirectionsForBody(int $bodyDirection): array
    {
        $bodyDirection = max(0, min(7, $bodyDirection));

        return match ($bodyDirection) {
            0 => [6, 7, 0, 1, 2],
            1 => [7, 0, 1, 2, 3],
            2 => [0, 1, 2, 3, 4],
            3 => [1, 2, 3, 4, 5],
            4 => [2, 3, 4, 5, 6],
            5 => [3, 4, 5, 6, 7],
            6 => [4, 5, 6, 7, 0],
            7 => [5, 6, 7, 0, 1],
            default => [0, 1, 2, 3, 4, 5, 6, 7],
        };
    }

    private function clampHeadDirectionForBody(int $bodyDirection, int $headDirection): int
    {
        $bodyDirection = max(0, min(7, $bodyDirection));
        $headDirection = max(0, min(7, $headDirection));

        $allowed = $this->allowedHeadDirectionsForBody($bodyDirection);

        if (in_array($headDirection, $allowed, true)) {
            return $headDirection;
        }

        return $bodyDirection;
    }

    private function normalizePoseDirection(string $action, int $direction): int
    {
        $action = strtolower(trim($action));
        $direction = max(0, min(7, $direction));

        return match ($action) {
            'sit' => match ($direction) {
                1 => 0,
                3 => 2,
                5 => 4,
                7 => 6,
                default => $direction,
            },
            default => $direction,
        };
    }
private function finalRenderGenderForSet(array $set, string $requestedGender): string
{
    $setGender = strtoupper((string) ($set['gender'] ?? 'U'));
    $requestedGender = strtoupper(trim($requestedGender));

    if ($setGender === 'U') {
        return 'U';
    }

    return in_array($requestedGender, ['M', 'F', 'U'], true) ? $requestedGender : 'M';
}

public function finalDresserRenderPath( string $gender, string $category, int $setId): string
{
    return sprintf(
        'public/Final/dresser/%s/%s/%s/%d.png',

        strtolower($gender),
        strtolower($category),
        $setId
    );
}

public function finalDresserRenderUrl( string $gender, string $category, int $setId): string
{
    return asset(sprintf(
        'storage/Final/dresser/%s/%s/%s/%d.png',

        strtolower($gender),
        strtolower($category),
        $setId
    ));
}

/**
     * Capture and store final render to BLOB repository
     * 
     * @return string|null Render hash if successful, null otherwise
     */
    public function savePreviewRenderToFinal(

        string $renderGender,
        string $category,
        int $setId,
        array $options = []
    ): ?string {
        $previewPath = $this->ensurePreviewRenderPath(

            $category,
            strtoupper($renderGender),
            $setId,
            $options
        );

        if (!$previewPath || !Storage::disk('local')->exists($previewPath)) {
            return null;
        }

        $pngBlob = Storage::disk('local')->get($previewPath);
        Storage::disk('local')->delete($previewPath);

        if (!$pngBlob) {
            return null;
        }

        $renderHash = hash('sha256', json_encode([
           // 'source_version' => $sourceVersion,
            'gender' => strtoupper($renderGender),
            'category' => $category,
            'set_id' => $setId,
            'options' => $options,
        ], JSON_UNESCAPED_SLASHES));

        if ($this->repository !== null) {
            $this->repository->storeDresserRender($renderHash, $pngBlob, [

                'gender' => strtoupper($renderGender),
                'category' => $category,
                'set_id' => $setId,
                'options' => $options,
            ]);
        }

        return $renderHash;
    }

    public function autoRenderAllDresserFinal(): array
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        @ini_set('memory_limit', '1024M');

    $context = $this->inspector->latestContext(true);

    if (!$context) {
        return [
            'ok' => false,
            'message' => 'No latest Habbo imaging version available.',
        ];
    }

    $categories = self::CATEGORY_ORDER;
    $requestedGenders = ['M', 'F'];

    $rendered = 0;
    $skipped = 0;
    $failed = 0;

    foreach ($requestedGenders as $requestedGender) {
        foreach ($categories as $category) {
            $sets = $this->filteredSets($context['set_types'][$category]['sets'] ?? [], $requestedGender);

            foreach ($sets as $set) {
                $setId = (int) ($set['id'] ?? 0);

                if ($setId <= 0) {
                    continue;
                }

                $renderGender = $this->finalRenderGenderForSet($set, $requestedGender);

                $renderHash = hash('sha256', json_encode([
                    //'source_version' => $sourceVersion,
                    'gender' => strtoupper($renderGender),
                    'category' => $category,
                    'set_id' => $setId,
                    'options' => [
                        'direction' => 2,
                        'head_direction' => 2,
                        'gesture' => 'nrm',
                        'action' => 'std',
                        'head_only' => in_array($category, self::HEAD_PREVIEW_CATEGORIES, true),
                    ],
                ], JSON_UNESCAPED_SLASHES));

                if ($this->repository !== null && $this->repository->findDresserRender($renderHash) !== null) {
                    $skipped++;
                    continue;
                }

                $options = [
                    'direction' => 2,
                    'head_direction' => 2,
                    'gesture' => 'nrm',
                    'action' => 'std',
                    'head_only' => in_array($category, self::HEAD_PREVIEW_CATEGORIES, true),
                ];

                if (
                    $category === 'wa'
                    && in_array($setId, [5674, 5676, 5719, 5775, 5778, 5780, 5782, 5784, 5786, 6392, 6394], true)
                ) {
                    $options['direction'] = 6;
                    $options['head_direction'] = 6;
                }

                $saved = $this->savePreviewRenderToFinal(
                   
                    $renderGender,
                    $category,
                    $setId,
                    $options
                );

                if ($saved) {
                    $rendered++;
                } else {
                    $failed++;
                }

                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
            }
        }
    }

    return [
        'ok' => true,
 
        'rendered' => $rendered,
        'skipped' => $skipped,
        'failed' => $failed,
    ];
}
}
