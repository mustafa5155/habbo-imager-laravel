<?php

namespace App\Support\HabboImaging;

use App\Models\HabboImagingAsset;
use App\Models\HabboImagingVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class HabboImagingFigureInspector
{
    private array $libraryMetadataCache = [];
    private array $libraryPartIndexCache = [];
    private array $libraryManifestCache = [];

    private const HEAD_SET_TYPES = ['hd', 'hr', 'ha', 'he', 'ea', 'fa'];
    private const GESTURE_TO_ACTION = [
        'nrm' => 'std', 'sad' => 'sad', 'sml' => 'sml', 'srp' => 'srp',
        'eyb' => 'eyb', 'agr' => 'agr', 'spk' => 'spk',
    ];
    private const LAY_GESTURE_TO_ACTION_EY = [
        'nrm' => 'lay', 'sad' => 'lsa', 'sml' => 'lsm', 'srp' => 'lsr',
        'eyb' => 'ley', 'agr' => 'lag', 'spk' => 'lay',
    ];
    private const LAY_GESTURE_TO_ACTION_FC = [
        'nrm' => 'lay', 'sad' => 'lsa', 'sml' => 'lsm', 'srp' => 'lsr',
        'eyb' => 'lay', 'agr' => 'lag', 'spk' => 'lsp',
    ];
    private const PRIMARY_SET_PART_TYPES = [
        'hd' => 'hd', 'hr' => 'hr', 'ha' => 'ha', 'he' => 'he',
        'ea' => 'ea', 'fa' => 'fa', 'ca' => 'ca', 'cc' => 'cc',
        'cp' => 'cp', 'ch' => 'ch', 'lg' => 'lg', 'sh' => 'sh', 'wa' => 'wa',
    ];

    public function __construct(
        private readonly HabboImagingStorage $storage,
        private readonly HabboImagingSourceParser $parser,
        private readonly HabboImagingSwfExtractor $swfExtractor,
        private readonly HabboImagingAssetRepository $repository,
    ) {
    }

    public function latestContext(bool $includeAssets = true): ?array
    {
        if (!Schema::hasTable('habbo_imaging_versions')) {
            return null;
        }

        $version = HabboImagingVersion::query()
            ->where('source_version', 'current')
            ->first();

        if (!$version) {
            $version = HabboImagingVersion::query()
                ->orderByDesc('synced_at')
                ->first();
        }

        if (!$version) {
            return null;
        }

        $figuredataPath = 'habbo-imaging/source/figuredata.xml';
        $figuremapPath = 'habbo-imaging/source/figuremap.xml';

        if (!$figuremapPath || !$figuredataPath || !Storage::disk('local')->exists($figuremapPath) || !Storage::disk('local')->exists($figuredataPath)) {
            return null;
        }

        $disk = Storage::disk('local');
        $metadataCacheKey = 'habbo-imager:metadata:v4:current:' . md5(
            (string) $disk->lastModified($figuredataPath) . ':' . (string) $disk->lastModified($figuremapPath)
        );

        $metadata = Cache::rememberForever(
            $metadataCacheKey,
            function () use ($figuredataPath, $figuremapPath) {
                return [
                    'figuredata' => $this->loadFiguredata($figuredataPath),
                    'figuremap' => $this->loadFiguremap($figuremapPath),
                ];
            }
        );

        return [
            'version' => $version,
            'figuredata' => $metadata['figuredata'] ?? [],
            'figuremap' => $metadata['figuremap'] ?? [],
            'set_types' => $metadata['figuredata']['set_types'] ?? [],
            'palettes' => $metadata['figuredata']['palettes'] ?? [],
            'part_index' => $metadata['figuremap']['part_index'] ?? [],
            'asset_map' => $includeAssets ? $this->loadAssetMap($version) : [],
        ];
    }

    public function inspect(string $figure, array $options = []): array
    {
        $figure = trim($figure);
        if ($figure === '') {
            return ['available' => false, 'message' => 'Enter a figure string to inspect against the locally parsed metadata.'];
        }

        $context = $this->latestContext();
        if (!$context) {
            return ['available' => false, 'message' => 'Advanced local imaging metadata is not available yet.'];
        }

        $setTypes = $context['set_types'];
        $partIndex = $context['part_index'];
        $segments = array_values(array_filter(explode('.', $figure)));
        $options = $this->normalizeOptions($options);
        $resolvedSegments = [];
        $requiredLibraries = [];
        $matchedParts = [];

        foreach ($segments as $segment) {
            $pieces = explode('-', $segment);
            $setType = $pieces[0] ?? '';
            $setId = $pieces[1] ?? '';
            $colors = array_values(array_filter(array_slice($pieces, 2), fn($value) => $value !== '' && strtolower((string) $value) !== 'undefined'));

            if ($options['head_only'] && !in_array($setType, self::HEAD_SET_TYPES, true)) {
                continue;
            }

            $set = $setTypes[$setType]['sets'][$setId] ?? null;
            $libraries = [];

            if (is_array($set)) {
                foreach ($set['parts'] ?? [] as $part) {
                    if ($options['head_only'] && !$this->shouldIncludeHeadOnlyPart($setType, (string) ($part['type'] ?? ''))) {
                        continue;
                    }

                    $key = sprintf('%s:%s', $part['type'] ?? '', $part['id'] ?? '');
                    $libraryNames = array_values($partIndex[$key] ?? []);

                    foreach ($libraryNames as $libraryName) {
                        $libraries[$libraryName] = true;
                        $requiredLibraries[$libraryName] = true;
                    }

                    $matchedParts[] = $this->resolvePartMatch($context, $setType, $segment, $part, $libraryNames, $options);
                }
            }

            $resolvedSegments[] = [
                'segment' => $segment,
                'set_type' => $setType,
                'set_id' => $setId,
                'colors' => $colors,
                'resolved' => is_array($set),
                'part_count' => count($set['parts'] ?? []),
                'libraries' => array_keys($libraries),
            ];
        }

        $libraryStatuses = [];
        foreach (array_keys($requiredLibraries) as $libraryName) {
            $asset = $context['asset_map'][$libraryName] ?? null;
            $libraryStatuses[] = [
                'name' => $libraryName,
                'status' => $asset?->status ?? 'missing',
                'extracted_file_count' => (int) ($asset?->extracted_file_count ?? 0),
            ];
        }

        usort($libraryStatuses, fn($left, $right) => strcmp($left['name'], $right['name']));
        usort($matchedParts, function ($left, $right) {
            $leftSource = (int) ($left['source_part_index'] ?? 0);
            $rightSource = (int) ($right['source_part_index'] ?? 0);
            if ($leftSource !== $rightSource) return $leftSource <=> $rightSource;
            $leftPartId = (int) ($left['part_id'] ?? 0);
            $rightPartId = (int) ($right['part_id'] ?? 0);
            if ($leftPartId !== $rightPartId) return $leftPartId <=> $rightPartId;
            return strcmp((string) data_get($left, 'best_asset.symbol_name', ''), (string) data_get($right, 'best_asset.symbol_name', ''));
        });

        $resolvedCount = count(array_filter($resolvedSegments, fn($segment) => $segment['resolved']));
        $readyLibraryCount = count(array_filter($libraryStatuses, fn($asset) => in_array($asset['status'], ['extracted', 'binary_only', 'downloaded'], true)));
        $matchedCount = count(array_filter($matchedParts, fn($part) => !empty($part['matched'])));

        return [
            'available' => true,
            'version' => $context['version']->source_version,
            'figure' => $figure,
            'options' => $options,
            'summary' => [
                'segment_count' => count($resolvedSegments),
                'resolved_segments' => $resolvedCount,
                'required_library_count' => count($requiredLibraries),
                'ready_library_count' => $readyLibraryCount,
                'matched_part_count' => $matchedCount,
                'unmatched_part_count' => count($matchedParts) - $matchedCount,
            ],
            'segments' => $resolvedSegments,
            'libraries' => $libraryStatuses,
            'matched_parts' => $matchedParts,
        ];
    }

    public function previewSet(array $context, string $setType, array $set, array $options = []): ?array
    {
        $options = $this->normalizeOptions($options);
        $best = null;
        $preferredPartType = self::PRIMARY_SET_PART_TYPES[$setType] ?? null;

        foreach ($set['parts'] ?? [] as $part) {
            $key = sprintf('%s:%s', $part['type'] ?? '', $part['id'] ?? '');
            $libraryNames = array_values($context['part_index'][$key] ?? []);
            $match = $this->resolvePartMatch($context, $setType, $setType . '-' . ($set['id'] ?? ''), $part, $libraryNames, $options);

            if (empty($match['matched']) || empty($match['best_asset'])) {
                continue;
            }

            $previewScore = (int) ($match['best_asset']['score'] ?? 0);
            if ($preferredPartType !== null && ($match['part_type'] ?? null) === $preferredPartType) {
                $previewScore += 500;
            }
            $match['preview_score'] = $previewScore;

            if ($best === null || $previewScore > ($best['preview_score'] ?? 0)) {
                $best = $match;
            }
        }
        return $best;
    }

    public function previewSetMatches(array $context, string $setType, array $set, array $options = []): array
    {
        $options = $this->normalizeOptions($options);
        $matches = [];

        foreach (($set['parts'] ?? []) as $index => $part) {
            $key = sprintf('%s:%s', $part['type'] ?? '', $part['id'] ?? '');
            $libraryNames = array_values($context['part_index'][$key] ?? []);
            $match = $this->resolvePartMatch($context, $setType, $setType . '-' . ($set['id'] ?? ''), $part, $libraryNames, $options);

            if (empty($match['matched']) || empty($match['best_asset'])) {
                continue;
            }

            $match['part_index'] = (int) ($part['index'] ?? $index);
            $matches[] = $match;
        }
        return $matches;
    }

    private function loadFiguredata(string $figuredataPath): array
    {
        if (Storage::disk('local')->exists($figuredataPath)) {
            return $this->parser->parseFiguredata((string) Storage::disk('local')->get($figuredataPath));
        }
        return [];
    }

    private function loadFiguremap(string $figuremapPath): array
    {
        if (Storage::disk('local')->exists($figuremapPath)) {
            return $this->parser->parseFiguremap((string) Storage::disk('local')->get($figuremapPath));
        }
        return [];
    }

    private function loadAssetMap(HabboImagingVersion $version): array
    {
        if (!Schema::hasTable('habbo_imaging_assets')) {
            return [];
        }

        return Cache::rememberForever(
            'habbo-imager:asset-map:v4:current',
            fn() => HabboImagingAsset::query()
                ->whereIn('status', ['extracted', 'binary_only', 'downloaded'])
                ->orderBy('synced_at')
                ->orderBy('id')
                ->get()
                ->keyBy('library_name')
                ->all()
        );
    }

    private function normalizeOptions(array $options): array
    {
        $direction = max(0, min(7, (int) ($options['direction'] ?? 2)));
        $headDirection = max(0, min(7, (int) ($options['head_direction'] ?? 3)));
        $gesture = strtolower(trim((string) ($options['gesture'] ?? 'nrm')));
        $action = strtolower(trim((string) ($options['action'] ?? '')));

        return [
            'direction' => $direction,
            'head_direction' => $headDirection,
            'gesture' => $gesture,
            'action' => $action,
            'head_only' => (bool) ($options['head_only'] ?? false),
            'static_only' => (bool) ($options['static_only'] ?? false),
            'action_preferences' => $this->buildActionPreferences($gesture, $action),
            'correction_action' => strtolower(trim((string) ($options['correction_action'] ?? 'std'))),
            'strict_direction' => !array_key_exists('strict_direction', $options) || (bool) $options['strict_direction'],
            'strict_action' => !array_key_exists('strict_action', $options) || (bool) $options['strict_action'],
            'frame_preferences' => array_values(array_map('intval', $options['frame_preferences'] ?? [0])),
            'preferred_variant' => strtolower(trim((string) ($options['preferred_variant'] ?? ''))),
            'strict_variant' => (bool) ($options['strict_variant'] ?? false),
            'allow_flip_fallback' => (bool) ($options['allow_flip_fallback'] ?? false),
            'allow_overlay_fallbacks' => (bool) ($options['allow_overlay_fallbacks'] ?? false),
        ];
    }

    private function buildActionPreferences(string $gesture, string $action): array
    {
        $preferences = [];
        foreach (preg_split('/[,\s]+/', $action) ?: [] as $token) {
            $token = strtolower(trim((string) $token));
            if ($token !== '') $preferences[] = $token;
        }
        if (empty($preferences) && isset(self::GESTURE_TO_ACTION[$gesture]) && $gesture !== 'nrm') {
            $preferences[] = self::GESTURE_TO_ACTION[$gesture];
        }
        if (empty($preferences)) $preferences[] = 'std';
        $preferences = array_values(array_unique($preferences));
        if (array_intersect($preferences, ['wav', 'sit', 'wlk']) && !in_array('std', $preferences, true)) {
            $preferences[] = 'std';
        }
        return $preferences;
    }

    private function resolveRenderDirectionsForPart(string $setType, string $partType, int $requestedDirection, array $options): array
    {
        $partType = strtolower(trim($partType));
        $mirrorablePartTypes = [
            'bd', 'hd', 'ey', 'fc', 'hr', 'hrb', 'ha', 'he', 'ea', 'fa',
            'lh', 'rh', 'ls', 'rs', 'lc', 'rc', 'ch', 'lg', 'sh',
            'ca', 'cc', 'wa', 'pt', 'mc', 'ptr', 'mcr', 'mcl'
        ];

        if (!empty($options['allow_flip_fallback']) && in_array($requestedDirection, [4, 5, 6], true) && in_array($partType, $mirrorablePartTypes, true)) {
            $sourceDirection = match ($requestedDirection) { 4 => 2, 5 => 1, 6 => 0, default => $requestedDirection };
            return ['requested_direction' => $requestedDirection, 'source_direction' => $sourceDirection, 'flip' => true];
        }
        return ['requested_direction' => $requestedDirection, 'source_direction' => $requestedDirection, 'flip' => false];
    }

    private function buildCandidates(array $context, array $names, string $partType, int $partId): array
    {
        $rawCandidates = [];
        foreach ($names as $libraryName) {
            $asset = $context['asset_map'][$libraryName] ?? null;
            if (!$asset) {
                continue;
            }

            foreach ($this->libraryCandidatesForPart($asset, $partType, $partId) as $candidate) {
                $rawCandidates[] = [
                    'library' => $libraryName,
                    'status' => $asset->status,
                    'symbol_name' => $candidate['symbol_name'],
                    'render_variant' => $candidate['render_variant'],
                    'action' => strtolower((string) $candidate['action']),
                    'part_type' => strtolower((string) ($candidate['part_type'] ?? $partType)),
                    'direction' => (int) $candidate['direction'],
                    'source_direction' => (int) $candidate['direction'],
                    'mirrored' => false,
                    'frame' => (int) $candidate['frame'],
                    'relative_path' => $candidate['relative_path'],
                    'offset_x' => $candidate['offset_x'],
                    'offset_y' => $candidate['offset_y'],
                    'asset_url' => $this->assetUrlForSymbol($candidate['symbol_name']),
                ];
            }
        }
        return $rawCandidates;
    }

    private function assetUrlForSymbol(string $symbolName): string
    {
        return '/imager/asset?symbol=' . rawurlencode($symbolName);
    }

    private function rankCandidates(array $candidates, int $scoreDirection, array $actions, array $framePreferences = [0]): ?array
    {
        if (empty($candidates)) return null;
        foreach ($candidates as &$candidate) {
            $candidate['score'] = $this->scoreBitmapCandidate($candidate, $scoreDirection, $actions, $framePreferences);
        }
        unset($candidate);
        usort($candidates, fn($a, $b) => (($b['score'] ?? 0) <=> ($a['score'] ?? 0)) ?: strcmp((string) ($a['symbol_name'] ?? ''), (string) ($b['symbol_name'] ?? '')));
        return $candidates[0];
    }

    private function mirroredActionLookup(string $partType, int $requestedDirection): array
    {
        $partType = strtolower(trim($partType));
        return [
            'source_part_type' => $this->mirroredSourcePartType($partType),
            'source_direction' => match ($requestedDirection) { 4 => 2, 5 => 1, 6 => 0, default => $requestedDirection },
            'mirrored' => in_array($requestedDirection, [4, 5, 6], true),
        ];
    }

    private function mirroredSourcePartType(string $partType): string
    {
        $partType = strtolower(trim($partType));
        return match ($partType) {
            'lh' => 'rh', 'rh' => 'lh', 'ls' => 'rs', 'rs' => 'ls',
            'lc' => 'rc', 'rc' => 'lc', 'mcl' => 'mcr', 'mcr' => 'mcl',
            default => $partType,
        };
    }

    private function resolveCompositeActionOverride(string $actionName, string $setType, string $partType, int $partId, int $requestedDirection, array $actionRawCandidates, array $options): ?array
    {
        $actionName = strtolower(trim($actionName));
        if ($actionName === '' || $actionName === 'std') return null;

        $rankCandidates = function (array $candidates, int $scoreDirection, array $actions, array $framePreferences = [0]): ?array {
            if (empty($candidates)) return null;
            foreach ($candidates as &$candidate) {
                $candidate['score'] = $this->scoreBitmapCandidate($candidate, $scoreDirection, $actions, $framePreferences);
            }
            unset($candidate);
            usort($candidates, fn($a, $b) => (($b['score'] ?? 0) <=> ($a['score'] ?? 0)) ?: strcmp((string) ($a['symbol_name'] ?? ''), (string) ($b['symbol_name'] ?? '')));
            return $candidates[0] ?? null;
        };

        if ($actionName === 'wav') {
            if (!in_array($partType, ['lh', 'ls', 'lc', 'mcl'], true)) return null;
            $wavCandidates = $this->filterCompositeCandidates($actionRawCandidates, $setType, $partType, $requestedDirection, $options, ['wav']);
            $wavBest = $rankCandidates($wavCandidates, $requestedDirection, ['wav'], $options['frame_preferences'] ?? [0]);
            if ($wavBest !== null) return $wavBest;
            if (in_array($requestedDirection, [4, 5, 6], true)) {
                $mirror = $this->mirroredActionLookup($partType, $requestedDirection);
                $wavCandidates = $this->filterCompositeCandidates($actionRawCandidates, $setType, $mirror['source_part_type'], $mirror['source_direction'], $options, ['wav']);
                $wavBest = $rankCandidates($wavCandidates, $mirror['source_direction'], ['wav'], $options['frame_preferences'] ?? [0]);
                if ($wavBest !== null) {
                    $wavBest['direction'] = $requestedDirection;
                    $wavBest['source_direction'] = $mirror['source_direction'];
                    $wavBest['mirrored'] = true;
                    $wavBest['render_part_type'] = $partType;
                    $wavBest['source_part_type'] = $mirror['source_part_type'];
                    return $wavBest;
                }
            }
            return null;
        }

        if ($actionName === 'sig') {
            if ($partType === 'lh') {
                $sigCandidates = $this->filterCompositeCandidates($actionRawCandidates, $setType, $partType, $requestedDirection, $options, ['sig']);
                $sigBest = $rankCandidates($sigCandidates, $requestedDirection, ['sig'], $options['frame_preferences'] ?? [0]);
                if ($sigBest !== null) return $sigBest;
                if (in_array($requestedDirection, [4, 5, 6], true)) {
                    $mirror = $this->mirroredActionLookup($partType, $requestedDirection);
                    $sigCandidates = $this->filterCompositeCandidates($actionRawCandidates, $setType, $mirror['source_part_type'], $mirror['source_direction'], $options, ['sig']);
                    $sigBest = $rankCandidates($sigCandidates, $mirror['source_direction'], ['sig'], $options['frame_preferences'] ?? [0]);
                    if ($sigBest !== null) {
                        $sigBest['direction'] = $requestedDirection;
                        $sigBest['source_direction'] = $mirror['source_direction'];
                        $sigBest['mirrored'] = true;
                        $sigBest['render_part_type'] = $partType;
                        $sigBest['source_part_type'] = $mirror['source_part_type'];
                        return $sigBest;
                    }
                }
                return null;
            }
            if (in_array($partType, ['ls', 'lc'], true)) {
                $wavCandidates = $this->filterCompositeCandidates($actionRawCandidates, $setType, $partType, $requestedDirection, $options, ['wav']);
                $wavBest = $rankCandidates($wavCandidates, $requestedDirection, ['wav'], $options['frame_preferences'] ?? [0]);
                if ($wavBest !== null) return $wavBest;
                if (in_array($requestedDirection, [4, 5, 6], true)) {
                    $mirror = $this->mirroredActionLookup($partType, $requestedDirection);
                    $wavCandidates = $this->filterCompositeCandidates($actionRawCandidates, $setType, $mirror['source_part_type'], $mirror['source_direction'], $options, ['wav']);
                    $wavBest = $rankCandidates($wavCandidates, $mirror['source_direction'], ['wav'], $options['frame_preferences'] ?? [0]);
                    if ($wavBest !== null) {
                        $wavBest['direction'] = $requestedDirection;
                        $wavBest['source_direction'] = $mirror['source_direction'];
                        $wavBest['mirrored'] = true;
                        $wavBest['render_part_type'] = $partType;
                        $wavBest['source_part_type'] = $mirror['source_part_type'];
                        return $wavBest;
                    }
                }
                return null;
            }
            return null;
        }

        if (in_array($actionName, ['crr', 'drk'], true)) {
            if (!in_array($partType, ['rh', 'rs', 'rc', 'mcr'], true)) return null;
            $itemCandidates = $this->filterCompositeCandidates($actionRawCandidates, $setType, $partType, $requestedDirection, $options, [$actionName]);
            $itemBest = $rankCandidates($itemCandidates, $requestedDirection, [$actionName], $options['frame_preferences'] ?? [0]);
            if ($itemBest !== null) return $itemBest;
            if (in_array($requestedDirection, [4, 5, 6], true)) {
                $mirror = $this->mirroredActionLookup($partType, $requestedDirection);
                $itemCandidates = $this->filterCompositeCandidates($actionRawCandidates, $setType, $mirror['source_part_type'], $mirror['source_direction'], $options, [$actionName]);
                $itemBest = $rankCandidates($itemCandidates, $mirror['source_direction'], [$actionName], $options['frame_preferences'] ?? [0]);
                if ($itemBest !== null) {
                    $itemBest['direction'] = $requestedDirection;
                    $itemBest['source_direction'] = $mirror['source_direction'];
                    $itemBest['mirrored'] = true;
                    $itemBest['render_part_type'] = $partType;
                    $itemBest['source_part_type'] = $mirror['source_part_type'];
                    return $itemBest;
                }
            }
            return null;
        }

        if ($actionName === 'sit') {
            $directionSpec = match ($requestedDirection) {
                0, 1 => ['source_direction' => 0, 'mirrored' => false],
                2, 3 => ['source_direction' => 2, 'mirrored' => false],
                4, 5 => ['source_direction' => 2, 'mirrored' => true],
                6, 7 => ['source_direction' => 0, 'mirrored' => true],
                default => ['source_direction' => 2, 'mirrored' => false],
            };
            $sourcePartType = $partType;
            if (!empty($directionSpec['mirrored'])) {
                $sourcePartType = match ($partType) {
                    'lh' => 'rh', 'rh' => 'lh', 'ls' => 'rs', 'rs' => 'ls', default => $partType
                };
            }
            $sitCandidates = $this->filterCompositeCandidates($actionRawCandidates, $setType, $sourcePartType, (int) $directionSpec['source_direction'], $options, ['sit']);
            if (empty($sitCandidates)) return null;
            foreach ($sitCandidates as &$candidate) {
                $candidate['score'] = $this->scoreBitmapCandidate($candidate, (int) $directionSpec['source_direction'], ['sit'], $options['frame_preferences'] ?? [0]);
                $candidate['direction'] = $requestedDirection;
                $candidate['source_direction'] = (int) $directionSpec['source_direction'];
                $candidate['mirrored'] = (bool) $directionSpec['mirrored'];
                $candidate['render_part_type'] = $partType;
                $candidate['source_part_type'] = $sourcePartType;
            }
            unset($candidate);
            usort($sitCandidates, fn($a, $b) => (($b['score'] ?? 0) <=> ($a['score'] ?? 0)) ?: strcmp((string) ($a['symbol_name'] ?? ''), (string) ($b['symbol_name'] ?? '')));
            return $sitCandidates[0] ?? null;
        }

        if (in_array($actionName, ['lay', 'lsp'], true)) {
            $poseCandidates = $this->filterCompositeCandidates($actionRawCandidates, $setType, $partType, 2, $options, [$actionName]);
            if (empty($poseCandidates)) return null;
            foreach ($poseCandidates as &$candidate) {
                $candidate['score'] = $this->scoreBitmapCandidate($candidate, 2, [$actionName], $options['frame_preferences'] ?? [0]);
                if ($requestedDirection >= 4) {
                    $candidate['mirrored'] = true;
                    $candidate['direction'] = $requestedDirection;
                    $candidate['source_direction'] = 2;
                }
            }
            unset($candidate);
            usort($poseCandidates, fn($a, $b) => (($b['score'] ?? 0) <=> ($a['score'] ?? 0)) ?: strcmp((string) ($a['symbol_name'] ?? ''), (string) ($b['symbol_name'] ?? '')));
            return $poseCandidates[0] ?? null;
        }

        if ($actionName === 'wlk') {
            if (!in_array($partType, ['lh', 'rh', 'ls', 'rs', 'lc', 'rc', 'lg', 'sh'], true)) return null;
            $wlkCandidates = $this->filterCompositeCandidates($actionRawCandidates, $setType, $partType, $requestedDirection, $options, ['wlk']);
            return $rankCandidates($wlkCandidates, $requestedDirection, ['wlk'], $options['frame_preferences'] ?? [0]);
        }

        if (in_array($partType, ['ey', 'fc'], true)) {
            $expressionCandidates = $this->filterCompositeCandidates($actionRawCandidates, $setType, $partType, $requestedDirection, $options, [$actionName]);
            $expressionBest = $rankCandidates($expressionCandidates, $requestedDirection, [$actionName], $options['frame_preferences'] ?? [0]);
            if ($expressionBest !== null) return $expressionBest;
        }
        return null;
    }

    private function directionForPart(string $setType, string $partType, array $options): int
    {
        $partType = strtolower(trim($partType));
        $bodyDirection = (int) ($options['direction'] ?? 2);
        $headDirection = (int) ($options['head_direction'] ?? $bodyDirection);

        if ($setType === 'hd') {
            $bodyParts = ['bd', 'lh', 'rh'];
            return in_array($partType, $bodyParts, true) ? $bodyDirection : $headDirection;
        }
        return in_array($setType, self::HEAD_SET_TYPES, true) ? $headDirection : $bodyDirection;
    }

    private function resolvePartMatch(array $context, string $setType, string $segment, array $part, array $libraryNames, array $options): array
    {
        $partType = strtolower((string) ($part['type'] ?? ''));
        $partId = (int) ($part['id'] ?? 0);
        $requestedDirection = $this->directionForPart($setType, $partType, $options);

        $rawRequestedAction = strtolower(trim((string) ($options['action'] ?? 'std')));
        $isLayFamily = in_array($rawRequestedAction, ['lay', 'lsp'], true);
        $actionPreferences = array_values(array_filter(array_map(fn($value) => strtolower(trim((string) $value)), $options['action_preferences'] ?? ['std'])));
        if (empty($actionPreferences)) $actionPreferences = ['std'];

        if (in_array($partType, ['ey', 'fc'], true)) {
            $bodyAction = $actionPreferences[0] ?? 'std';
            $gesture = strtolower(trim((string) ($options['gesture'] ?? 'nrm')));
            if (in_array($bodyAction, ['lay', 'lsp'], true)) {
                $faceAction = ($partType === 'ey') ? (self::LAY_GESTURE_TO_ACTION_EY[$gesture] ?? 'lay') : (self::LAY_GESTURE_TO_ACTION_FC[$gesture] ?? 'lay');
                $actionPreferences = [$faceAction, 'lay', 'std'];
            } else {
                $faceAction = self::GESTURE_TO_ACTION[$gesture] ?? 'std';
                $actionPreferences = [$faceAction, 'std'];
            }
            $actionPreferences = array_values(array_unique($actionPreferences));
        }

        $requestedAction = $actionPreferences[0] ?? 'std';
        $correctionAction = strtolower(trim((string) ($options['correction_action'] ?? 'std')));

        $exactLibraryNames = array_values(array_unique(array_filter($libraryNames)));
        $siblingLibraryNames = $this->fallbackLibraryNamesForSegment($context, $setType, $segment);
        $stdLibraryNames = array_values(array_unique(array_merge($exactLibraryNames, $siblingLibraryNames)));

        $stdRawCandidates = $this->buildCandidates($context, $stdLibraryNames, $partType, $partId);
        $mirroredSourcePartType = $this->mirroredSourcePartType($partType);

        if (!empty($options['allow_flip_fallback']) && in_array($requestedDirection, [4, 5, 6], true) && $mirroredSourcePartType !== $partType) {
            $stdRawCandidates = array_merge($stdRawCandidates, $this->buildCandidates($context, $stdLibraryNames, $mirroredSourcePartType, $partId));
        }

        $stdBest = null;
        $final = null;

        if (!$isLayFamily) {
            $stdCandidates = $this->filterCompositeCandidates($stdRawCandidates, $setType, $partType, $requestedDirection, $options, ['std']);
            $stdBest = $this->rankCandidates($stdCandidates, $requestedDirection, ['std'], $options['frame_preferences'] ?? [0]);
            $final = $stdBest;
        }

        $layNrmBest = null;
        if (in_array($partType, ['ey', 'fc'], true)) {
            $bodyAction = $actionPreferences[0] ?? 'std';
            if (in_array($bodyAction, ['lay', 'lsp'], true)) {
                $layRawCandidates = $this->buildCandidates($context, $stdLibraryNames, $partType, $partId);
                $layCandidates = $this->filterCompositeCandidates($layRawCandidates, $setType, $partType, $requestedDirection, $options, ['lay']);
                $layNrmBest = $this->rankCandidates($layCandidates, $requestedDirection, ['lay'], $options['frame_preferences'] ?? [0]);
            }
        }

        $actionRawCandidates = $this->buildCandidates($context, $stdLibraryNames, $partType, $partId);
        if (!empty($options['allow_flip_fallback']) && in_array($requestedDirection, [4, 5, 6], true) && $mirroredSourcePartType !== $partType) {
            $actionRawCandidates = array_merge($actionRawCandidates, $this->buildCandidates($context, $stdLibraryNames, $mirroredSourcePartType, $partId));
        }

        foreach ($actionPreferences as $actionName) {
            $actionName = strtolower(trim((string) $actionName));
            if ($actionName === '' || $actionName === 'std') continue;
            $override = $this->resolveCompositeActionOverride($actionName, $setType, $partType, $partId, $requestedDirection, $actionRawCandidates, $options);
            if ($override !== null) {
                $final = $override;
                break;
            }
        }

        if ($final !== null && $stdBest !== null) {
            $final['std_offset_x'] = $stdBest['offset_x'] ?? 0;
            $final['std_offset_y'] = $stdBest['offset_y'] ?? 0;
        }
        if ($final !== null && $layNrmBest !== null) {
            $final['lay_nrm_offset_x'] = $layNrmBest['offset_x'] ?? 0;
            $final['lay_nrm_offset_y'] = $layNrmBest['offset_y'] ?? 0;
        }

        $chosenAction = $final !== null ? (string) ($final['action'] ?? '') : null;
        $usedActionOverride = $final !== null && $stdBest !== $final;
        $usedActionFallback = $final !== null && $chosenAction === 'std' && !empty(array_diff($actionPreferences, ['std']));

        return [
            'segment' => $segment,
            'set_type' => $setType,
            'part_type' => $partType,
            'part_id' => $partId,
            'source_part_index' => (int) ($part['index'] ?? 0),
            'target_direction' => $requestedDirection,
            'requested_direction' => $requestedDirection,
            'matched' => $final !== null,
            'candidate_count' => $final !== null ? 1 : 0,
            'library_names' => $exactLibraryNames,
            'best_asset' => $final,
            'body_direction' => (int) ($options['direction'] ?? $requestedDirection),
            'chosen_asset_direction' => $final !== null ? (int) ($final['direction'] ?? $requestedDirection) : null,
            'chosen_source_direction' => $final !== null ? (int) ($final['source_direction'] ?? $requestedDirection) : null,
            'used_mirroring' => $final !== null ? (bool) ($final['mirrored'] ?? false) : false,
            'used_direction_fallback' => $final !== null ? (bool) ($final['mirrored'] ?? false) : false,
            'used_action_override' => $usedActionOverride,
            'used_action_fallback' => $usedActionFallback,
            'direction_resolution' => $final !== null ? ((bool) ($final['mirrored'] ?? false) ? 'mirrored' : 'exact') : 'missing',
            'action_resolution' => $final !== null ? ($usedActionOverride ? 'override' : ($usedActionFallback ? 'fallback' : 'exact')) : 'missing',
            'requested_action' => $requestedAction,
            'requested_actions' => $actionPreferences,
            'chosen_action' => $chosenAction,
            'head_only' => !empty($options['head_only']),
            'correction_action' => $correctionAction,
        ];
    }

    private function filterCompositeCandidates(array $candidates, string $setType, string $partType, int $targetDirection, array $options, array $allowedActions): array
    {
        if (empty($candidates) || empty($allowedActions)) return [];
        $candidates = array_values(array_filter($candidates, fn($c) => in_array((string) ($c['action'] ?? ''), $allowedActions, true)));
        if (empty($candidates)) return [];

        $directionResolution = $this->resolveRenderDirectionsForPart($setType, $partType, $targetDirection, $options);
        $sourceDirection = (int) ($directionResolution['source_direction'] ?? $targetDirection);
        $flip = !empty($directionResolution['flip']);
        $requestedPartType = strtolower(trim($partType));
        $sourcePartType = $flip ? $this->mirroredSourcePartType($requestedPartType) : $requestedPartType;

        $exact = array_values(array_filter($candidates, fn($c) => (int) ($c['direction'] ?? -1) === $targetDirection && strtolower((string) ($c['part_type'] ?? '')) === $requestedPartType));
        if (!empty($exact)) return $exact;
        if (!$flip) return [];

        $mirrored = [];
        foreach ($candidates as $candidate) {
            if ((int) ($candidate['direction'] ?? -1) !== $sourceDirection) continue;
            if (strtolower((string) ($candidate['part_type'] ?? '')) !== $sourcePartType) continue;
            $candidate['mirrored'] = true;
            $candidate['source_direction'] = $sourceDirection;
            $candidate['direction'] = $targetDirection;
            $mirrored[] = $candidate;
        }
        return $mirrored;
    }

    private function shouldIncludeHeadOnlyPart(string $setType, string $partType): bool
    {
        $partType = strtolower(trim($partType));
        return match ($setType) {
            'hd' => !in_array($partType, ['bd', 'lh', 'rh'], true),
            'hr' => in_array($partType, ['hr', 'hrb'], true),
            'ha' => in_array($partType, ['ha'], true),
            'he' => in_array($partType, ['he'], true),
            'ea' => in_array($partType, ['ea', 'he', 'sh'], true),
            'fa' => in_array($partType, ['fa', 'fc'], true),
            default => false,
        };
    }

    private function fallbackLibraryNamesForSegment(array $context, string $setType, string $segment): array
    {
        $pieces = explode('-', $segment);
        $setId = $pieces[1] ?? null;
        if ($setId === null || !isset($context['set_types'][$setType]['sets'][$setId])) return [];

        $libraries = [];
        $set = $context['set_types'][$setType]['sets'][$setId];
        foreach (($set['parts'] ?? []) as $siblingPart) {
            $key = sprintf('%s:%s', $siblingPart['type'] ?? '', $siblingPart['id'] ?? '');
            foreach (array_values($context['part_index'][$key] ?? []) as $libraryName) {
                $libraries[$libraryName] = true;
            }
        }
        $resolved = array_keys($libraries);
        sort($resolved);
        return $resolved;
    }

    public function libraryCandidatesForPart(HabboImagingAsset $asset, string $partType, int $partId): array
    {
        $cacheKey = (string) $asset->library_name;
        if (!isset($this->libraryPartIndexCache[$cacheKey])) {
            $this->libraryPartIndexCache[$cacheKey] = [];
            foreach ($this->libraryBitmaps($asset) as $bitmap) {
                $parsed = $this->parseSymbolName((string) ($bitmap['symbol_name'] ?? ''));
                if (!$parsed) continue;
                $key = $parsed['part_type'] . ':' . $parsed['part_id'];
                $this->libraryPartIndexCache[$cacheKey][$key][] = [
                    'symbol_name' => (string) ($bitmap['symbol_name'] ?? ''),
                    'render_variant' => $parsed['render_variant'],
                    'action' => $parsed['action'],
                    'part_type' => $parsed['part_type'],
                    'part_id' => $parsed['part_id'],
                    'direction' => $parsed['direction'],
                    'frame' => $parsed['frame'],
                    'relative_path' => (string) ($bitmap['path'] ?? ''),
                    'offset_x' => isset($bitmap['offset_x']) ? (int) $bitmap['offset_x'] : null,
                    'offset_y' => isset($bitmap['offset_y']) ? (int) $bitmap['offset_y'] : null,
                ];
            }
        }
        return $this->libraryPartIndexCache[$cacheKey][$partType . ':' . $partId] ?? [];
    }

    private function libraryBitmaps(HabboImagingAsset $asset): array
    {
        $cacheKey = (string) $asset->library_name;
        if (array_key_exists($cacheKey, $this->libraryMetadataCache)) {
            return $this->libraryMetadataCache[$cacheKey];
        }

        $rawJson = $this->libraryMetadataFromDb($asset);
        if ($rawJson === null || $rawJson === '') {
            return $this->libraryMetadataCache[$cacheKey] = [];
        }

        $metadata = json_decode($rawJson, true) ?: [];
        $bitmaps = array_values($metadata['bitmaps'] ?? []);
        $manifestAssets = $metadata['manifest_assets'] ?? [];

        if (!empty($manifestAssets)) {
            foreach ($bitmaps as &$bitmap) {
                $symbolName = (string) ($bitmap['symbol_name'] ?? '');
                if ($symbolName === '') continue;
                $manifestAsset = $manifestAssets[$symbolName] ?? null;
                if (!$manifestAsset) continue;
                if (!array_key_exists('offset_x', $bitmap) || $bitmap['offset_x'] === null) {
                    $bitmap['offset_x'] = isset($manifestAsset['offset_x']) ? (int) $manifestAsset['offset_x'] : null;
                }
                if (!array_key_exists('offset_y', $bitmap) || $bitmap['offset_y'] === null) {
                    $bitmap['offset_y'] = isset($manifestAsset['offset_y']) ? (int) $manifestAsset['offset_y'] : null;
                }
            }
            unset($bitmap);
        }

        return $this->libraryMetadataCache[$cacheKey] = $bitmaps;
    }

    private function libraryMetadataFromDb(HabboImagingAsset $asset): ?string
    {
        if (!Schema::hasTable('habbo_imaging_xml_documents')) {
            return null;
        }

        $currentRow = DB::table('habbo_imaging_xml_documents')
            ->where('version_key', 'current')
            ->where('name', $asset->library_name)
            ->where('kind', 'library_metadata')
            ->first(['xml_content', 'metadata']);

        if ($currentRow && $this->libraryMetadataHasUsefulOffsets((string) $currentRow->xml_content)) {
            return (string) $currentRow->xml_content;
        }

        $fallbackRow = DB::table('habbo_imaging_xml_documents')
            ->where('version_key', '!=', 'current')
            ->where('name', $asset->library_name)
            ->where('kind', 'library_metadata')
            ->orderByDesc('updated_at')
            ->first(['xml_content']);

        if ($fallbackRow && $this->libraryMetadataHasUsefulOffsets((string) $fallbackRow->xml_content)) {
            return (string) $fallbackRow->xml_content;
        }

        return $currentRow ? (string) $currentRow->xml_content : null;
    }

    private function libraryMetadataHasUsefulOffsets(string $rawJson): bool
    {
        if ($rawJson === '') {
            return false;
        }

        $metadata = json_decode($rawJson, true);
        if (!is_array($metadata)) {
            return false;
        }

        $bitmaps = array_slice(array_values($metadata['bitmaps'] ?? []), 0, 80);
        if (empty($bitmaps)) {
            return false;
        }

        foreach ($bitmaps as $bitmap) {
            if (!empty($bitmap['rebuilt_from_blob'])) {
                return false;
            }

            if (
                array_key_exists('offset_x', $bitmap)
                && array_key_exists('offset_y', $bitmap)
                && ((int) $bitmap['offset_x'] !== 0 || (int) $bitmap['offset_y'] !== 0)
            ) {
                return true;
            }
        }

        return false;
    }

    private function libraryManifestForAsset(HabboImagingAsset $asset): array
    {
        $cacheKey = (string) $asset->library_name;
        if (array_key_exists($cacheKey, $this->libraryManifestCache)) {
            return $this->libraryManifestCache[$cacheKey];
        }
        if (!$asset->source_path || !Storage::disk('local')->exists($asset->source_path)) {
            return $this->libraryManifestCache[$cacheKey] = [];
        }
        return $this->libraryManifestCache[$cacheKey] = $this->swfExtractor->inspectManifest((string) $asset->source_path);
    }

    private function parseSymbolName(string $symbolName): ?array
    {
        $tokens = array_values(array_filter(explode('_', $symbolName), fn($token) => $token !== ''));
        $count = count($tokens);

        if ($count >= 8) {
            $partType = $tokens[$count - 4] ?? null;
            $partId = $tokens[$count - 3] ?? null;
            $direction = $tokens[$count - 2] ?? null;
            $frame = $tokens[$count - 1] ?? null;
            $action = $tokens[$count - 5] ?? null;
            $variant = $tokens[$count - 6] ?? null;
            if ($partType === null || $partId === null || $direction === null || $frame === null || $action === null) return null;
            if (!is_numeric($partId) || !is_numeric($direction) || !is_numeric($frame)) return null;
            return [
                'render_variant' => strtolower((string) $variant),
                'action' => strtolower((string) $action),
                'part_type' => strtolower((string) $partType),
                'part_id' => (int) $partId,
                'direction' => (int) $direction,
                'frame' => (int) $frame,
            ];
        }

        if ($count < 6) return null;
        $partType = $tokens[$count - 4] ?? null;
        $partId = $tokens[$count - 3] ?? null;
        $direction = $tokens[$count - 2] ?? null;
        $frame = $tokens[$count - 1] ?? null;
        $action = $tokens[$count - 5] ?? null;
        if ($partType === null || $partId === null || $direction === null || $frame === null || $action === null) return null;
        if (!is_numeric($partId) || !is_numeric($direction) || !is_numeric($frame)) return null;
        return [
            'render_variant' => strtolower((string) ($tokens[$count - 6] ?? '')),
            'action' => strtolower((string) $action),
            'part_type' => strtolower((string) $partType),
            'part_id' => (int) $partId,
            'direction' => (int) $direction,
            'frame' => (int) $frame,
        ];
    }

    private function scoreBitmapCandidate(array $parsed, int $targetDirection, array $actionPreferences, array $framePreferences = [0]): int
    {
        $score = 0;
        $actionIndex = array_search($parsed['action'], $actionPreferences, true);
        if (($parsed['render_variant'] ?? '') === 'h') $score += 40;
        elseif (($parsed['render_variant'] ?? '') === 'sh') $score += 20;
        if ($actionIndex !== false) $score += max(0, 260 - ((int) $actionIndex * 20));
        $score += ($parsed['direction'] === $targetDirection) ? 100 : 0;
        $frameIndex = array_search((int) ($parsed['frame'] ?? 0), $framePreferences, true);
        if ($frameIndex !== false) $score += max(0, 24 - ((int) $frameIndex * 6));
        elseif ($parsed['frame'] === 0) $score += 10;
        elseif ($parsed['frame'] === 1) $score += 6;
        return $score;
    }

    public function assetUrlForPath(string $relativePath): ?string
    {
        if ($relativePath === '') return null;
        $symbolName = pathinfo($relativePath, PATHINFO_FILENAME);
        if ($symbolName !== '' && $this->repository->findBySymbol($symbolName) !== null) {
            return '/imager/asset?symbol=' . rawurlencode($symbolName);
        }
        if (Storage::disk('local')->exists($relativePath)) {
            return '/imager/asset?path=' . rawurlencode($relativePath);
        }
        return null;
    }
}
