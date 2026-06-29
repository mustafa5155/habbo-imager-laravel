<?php

use Illuminate\Support\Facades\Route;
use App\Support\HabboImaging\HabboImagingDresser;
use App\Support\HabboImaging\HabboImagingAssetRepository;
use App\Support\HabboImaging\HabboImagingFigureInspector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

// Imager Views
Route::get('/imager', function () {
    return view('imager', ['defaultMode' => 'normal']);
})->name('imager');

Route::get('/imager/advanced', function () {
    return view('imager', ['defaultMode' => 'advanced']);
})->name('imager.advanced');

// Image Render Endpoints
Route::get('/imager/composite-thumb', function (Request $request) {
    $category = strtolower(trim((string) $request->query('category')));
    $gender = strtolower(trim((string) $request->query('gender', 'm')));
    $setId = (int) $request->query('set');

    if ($category === '' || $setId <= 0 || !in_array($gender, ['m', 'f', 'u'], true)) {
        abort(404);
    }

    $path = app(HabboImagingDresser::class)
        ->ensureCompositeThumbnailPath($category, strtoupper($gender), $setId);

    if (!$path || !str_starts_with($path, 'db:')) {
        abort(404);
    }

    $renderHash = substr($path, 3);
    $blob = app(HabboImagingAssetRepository::class)->findFigureRender($renderHash);

    if ($blob === null) {
        abort(404);
    }

    return response($blob, 200, [
        'Content-Type' => 'image/png',
        'Cache-Control' => 'public, max-age=604800',
    ]);
})->name('imager.composite');

Route::get('/imager/render-thumb', function (Request $request) {
    $category = strtolower(trim((string) $request->query('category')));
    $gender = strtolower(trim((string) $request->query('gender', 'm')));
    $setId = (int) $request->query('set');

    if ($category === '' || $setId <= 0 || !in_array($gender, ['m', 'f', 'u'], true)) {
        abort(404);
    }

    $path = app(HabboImagingDresser::class)
        ->ensurePreviewRenderPath($category, strtoupper($gender), $setId, [
            'direction' => (int) $request->query('direction', 2),
            'head_direction' => (int) $request->query('head_direction', 2),
            'gesture' => (string) $request->query('gesture', 'nrm'),
            'action' => (string) $request->query('action', 'std'),
            'head_only' => (bool) $request->query('head_only', false),
        ]);

    if (!$path || !str_starts_with($path, 'db:')) {
        abort(404);
    }

    $renderHash = substr($path, 3);
    $blob = app(HabboImagingAssetRepository::class)->findDresserRender($renderHash);

    if ($blob === null) {
        abort(404);
    }

    return response($blob, 200, [
        'Content-Type' => 'image/png',
        'Cache-Control' => 'public, max-age=604800',
    ]);
})->name('imager.render');

Route::get('/imager/render-figure', function (Request $request) {
    session_write_close();

    $gender = strtoupper(trim((string) $request->query('gender', 'M')));
    $figure = trim((string) $request->query('figure'));

    if ($figure === '' || !in_array($gender, ['M', 'F', 'U'], true)) {
        abort(404);
    }

    $path = app(HabboImagingDresser::class)->ensureFigureRenderPath($gender, $figure, [
        'direction' => (int) $request->query('direction', 2),
        'head_direction' => (int) $request->query('head_direction', 2),
        'gesture' => (string) $request->query('gesture', 'nrm'),
        'action' => (string) $request->query('action', 'std'),
        'frame' => (int) $request->query('frame', 0),
        'head_only' => (bool) $request->query('head_only', false),
        'size' => strtolower(trim((string) $request->query('size', 'm'))),
    ]);

    if (!$path || !str_starts_with($path, 'db:')) {
        abort(404);
    }

    $renderHash = substr($path, 3);
    $blob = app(HabboImagingAssetRepository::class)->findFigureRender($renderHash);

    if ($blob === null) {
        abort(404);
    }

    return response($blob, 200, [
        'Content-Type' => 'image/png',
        'Cache-Control' => 'public, max-age=604800',
    ]);
})->name('imager.render-figure');

Route::get('/imager/render-figure-apng', function (Request $request) {
    session_write_close();

    $gender = strtoupper(trim((string) $request->query('gender', 'M')));
    $figure = trim((string) $request->query('figure'));
    $action = strtolower(trim((string) $request->query('action', 'wav')));

    if ($figure === '' || !in_array($gender, ['M', 'F', 'U'], true)) {
        abort(404);
    }

    $payload = app(HabboImagingDresser::class)->ensureFigureActionSequence($gender, $figure, [
        'direction' => (int) $request->query('direction', 2),
        'head_direction' => (int) $request->query('head_direction', 2),
        'gesture' => (string) $request->query('gesture', 'nrm'),
        'action' => $action,
        'head_only' => (bool) $request->query('head_only', false),
        'size' => strtolower(trim((string) $request->query('size', 'm'))),
    ]);

    if (!$payload) {
        abort(404);
    }

    $apng = build_apng_from_sequence($payload);

    if ($apng === null) {
        return response()->json([
            'ok' => false,
            'message' => 'Unable to build APNG from frame sequence.',
        ], 500);
    }

    return response($apng, 200, [
        'Content-Type' => 'image/apng',
        'Cache-Control' => 'public, max-age=604800',
    ]);
})->name('imager.render-figure-apng');

Route::get('/imager/render-figure-sequence', function (Request $request) {
    session_write_close();

    $gender = strtoupper(trim((string) $request->query('gender', 'M')));
    $figure = trim((string) $request->query('figure'));
    $action = strtolower(trim((string) $request->query('action', 'wav')));

    if ($figure === '' || !in_array($gender, ['M', 'F', 'U'], true)) {
        abort(404);
    }

    $payload = app(HabboImagingDresser::class)->ensureFigureActionSequence($gender, $figure, [
        'direction' => (int) $request->query('direction', 2),
        'head_direction' => (int) $request->query('head_direction', 2),
        'gesture' => (string) $request->query('gesture', 'nrm'),
        'action' => $action,
        'head_only' => (bool) $request->query('head_only', false),
    ]);

    if (!$payload) {
        abort(404);
    }

    return response()->json($payload);
})->name('imager.render-figure-sequence');

// Asset Endpoints
Route::get('/imager/asset', function (Request $request) {
    $symbol = rawurldecode((string) $request->query('symbol'));

    if ($symbol === '') {
        abort(404);
    }

    $blob = app(HabboImagingAssetRepository::class)->findBySymbol($symbol);

    if ($blob === null) {
        abort(404);
    }

    return response($blob, 200, [
        'Content-Type' => 'image/png',
        'Cache-Control' => 'public, max-age=604800',
    ]);
})->name('imager.asset');

Route::get('/imager/dresser-render', function (Request $request) {
    $category = strtolower(trim((string) $request->query('category')));
    $gender = strtolower(trim((string) $request->query('gender', 'm')));
    $setId = (int) $request->query('set');

    if ($category === '' || $setId <= 0) {
        abort(404);
    }

    $context = app(HabboImagingFigureInspector::class)->latestContext(true);
    $sourceVersion = $context['version']->source_version ?? '';
    $finalPath = sprintf('public/Final/dresser/%s/%s/%s/%d.png', $sourceVersion, $gender, $category, $setId);
    $serveStoredPng = function (string $path) {
        return response(Storage::disk('local')->get($path), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=604800',
        ]);
    };
    $storeAndServePng = function (string $png) use ($finalPath, $serveStoredPng) {
        Storage::disk('local')->makeDirectory(dirname($finalPath));
        Storage::disk('local')->put($finalPath, $png);
        $publicRelativePath = preg_replace('/^public\//', '', str_replace('\\', '/', $finalPath));
        $publicPath = public_path('storage/' . $publicRelativePath);

        if (!is_dir(dirname($publicPath))) {
            mkdir(dirname($publicPath), 0775, true);
        }

        file_put_contents($publicPath, $png);

        return $serveStoredPng($finalPath);
    };
    
    if (Storage::disk('local')->exists($finalPath)) {
        return $storeAndServePng((string) Storage::disk('local')->get($finalPath));
    }

    $finalRoot = storage_path('app/public/Final/dresser');
    $legacyMatches = is_dir($finalRoot)
        ? glob($finalRoot . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . $gender . DIRECTORY_SEPARATOR . $category . DIRECTORY_SEPARATOR . $setId . '.png')
        : [];

    foreach ($legacyMatches ?: [] as $legacyPath) {
        $resolved = realpath($legacyPath);
        $root = realpath($finalRoot);

        if ($resolved && $root && str_starts_with($resolved, $root) && is_file($resolved)) {
            return $storeAndServePng((string) file_get_contents($resolved));
        }
    }

    $renderHash = strtolower(trim((string) $request->query('hash', '')));

    if ($renderHash !== '' && preg_match('/^[a-f0-9]{64}$/', $renderHash)) {
        $blob = app(HabboImagingAssetRepository::class)->findDresserRender($renderHash);

        if ($blob !== null) {
            return $storeAndServePng($blob);
        }
    }

    $path = app(HabboImagingDresser::class)
        ->ensurePreviewRenderPath($category, strtoupper($gender), $setId, [
            'direction' => 2,
            'head_direction' => 2,
            'gesture' => 'nrm',
            'action' => 'std',
            'head_only' => in_array($category, ['hd','hr','ha','he','ea','fa']),
        ]);

    if (!$path || !str_starts_with($path, 'db:')) {
        abort(404);
    }

    $renderHash = substr($path, 3);
    $blob = app(HabboImagingAssetRepository::class)->findDresserRender($renderHash);

    if ($blob === null) {
        abort(404);
    }

    return $storeAndServePng($blob);
})->name('imager.dresser-render');

Route::get('/imager/clear-renders', function () {
    DB::table('habbo_imaging_render_blobs')->truncate();
    return 'All render blobs deleted. They will regenerate on next request.';
});

Route::get('/imager/generate-thumb', function (Request $request) {
    $category = strtolower(trim((string) $request->query('category')));
    $gender = strtoupper(trim((string) $request->query('gender', 'M')));
    $setId = (int) $request->query('set');

    if ($category === '' || $setId <= 0) {
        abort(404);
    }

    $dresser = app(HabboImagingDresser::class);
    
    $headCategories = ['hd', 'hr', 'ha', 'he', 'ea', 'fa'];
    $headOnly = in_array($category, $headCategories);

    $path = $dresser->ensurePreviewRenderPath(
        $category,
        $gender,
        $setId,
        [
            'direction' => 2,
            'head_direction' => 2,
            'gesture' => 'nrm',
            'action' => 'std',
            'head_only' => $headOnly,
        ]
    );

    if (!$path || !str_starts_with($path, 'db:')) {
        abort(404);
    }

    $renderHash = substr($path, 3);
    $blob = app(HabboImagingAssetRepository::class)->findDresserRender($renderHash);

    if ($blob === null) {
        abort(404);
    }

    return response($blob, 200, [
        'Content-Type' => 'image/png',
        'Cache-Control' => 'public, max-age=604800',
    ]);
})->name('imager.generate-thumb');

// Admin/Utility Endpoints
Route::get('/imager/render-final-dresser-auto', function () {
    $result = app(HabboImagingDresser::class)->autoRenderAllDresserFinal();
    return response()->json($result, !empty($result['ok']) ? 200 : 404);
})->name('imager.render-final-dresser-auto');

// Debug Routes
Route::get('/imager/debug/force-render-with-debug', function (Request $request) {
    $version = trim((string) $request->query('version'));
    $gender = strtoupper(trim((string) $request->query('gender', 'M')));
    $figure = trim((string) $request->query('figure'));
    
    $inspector = app(HabboImagingFigureInspector::class);
    $dresser = app(HabboImagingDresser::class);
    $repository = app(HabboImagingAssetRepository::class);
    
    $uniqueParam = time();
    
    $renderPath = $dresser->ensureFigureRenderPath($gender, $figure, [
        'direction' => (int) $request->query('direction', 2),
        'head_direction' => (int) $request->query('head_direction', 2),
        'gesture' => (string) $request->query('gesture', 'nrm'),
        'action' => (string) $request->query('action', 'std'),
        'frame' => (int) $request->query('frame', 0),
        'head_only' => (bool) $request->query('head_only', false),
        'force_timestamp' => $uniqueParam,
    ]);
    
    if (!$renderPath || !str_starts_with($renderPath, 'db:')) {
        return response()->json([
            'error' => 'Render failed',
            'render_path' => $renderPath
        ], 500);
    }
    
    $renderHash = substr($renderPath, 3);
    $metadata = $repository->getRenderMetadata($renderHash);
    
    return response()->json([
        'render_hash' => $renderHash,
        'metadata_keys' => $metadata ? array_keys($metadata) : [],
        'has_layout_debug' => isset($metadata['layout_debug']),
        'layout_debug_keys' => isset($metadata['layout_debug']) ? array_keys($metadata['layout_debug']) : [],
        'layers_count' => isset($metadata['layout_debug']['layers']) ? count($metadata['layout_debug']['layers']) : 0,
        'full_metadata' => $metadata,
    ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
});

Route::get('/imager/debug/composite-layers-debug', function (Request $request) {
    $version = trim((string) $request->query('version'));
    $gender = strtoupper(trim((string) $request->query('gender', 'M')));
    $figure = trim((string) $request->query('figure'));
    
    $inspector = app(HabboImagingFigureInspector::class);
    $dresser = app(HabboImagingDresser::class);
    $context = $inspector->latestContext(true);
    
    if (!$context) {
        return response()->json(['error' => 'No context'], 500);
    }
    
    $options = [
        'direction' => 2,
        'head_direction' => 2,
        'gesture' => 'nrm',
        'action' => 'std',
        'head_only' => false,
        'allow_flip_fallback' => true,
    ];
    
    $report = $inspector->inspect($figure, $options);
    $matchedParts = array_values($report['matched_parts'] ?? []);
    
    $reflection = new \ReflectionClass($dresser);
    $method = $reflection->getMethod('compositeLayersForReportMatches');
    $method->setAccessible(true);
    $layers = $method->invoke($dresser, $matchedParts);
    
    $layerStatus = [];
    foreach ($layers as $idx => $layer) {
        $symbolName = $layer['symbol_name'] ?? '';
        $repository = app(HabboImagingAssetRepository::class);
        $blob = $repository->findBySymbol($symbolName);
        $layerStatus[] = [
            'index' => $idx,
            'symbol_name' => $symbolName,
            'blob_exists' => $blob !== null,
            'blob_size' => $blob ? strlen($blob) : 0,
            'width' => $layer['width'] ?? 0,
            'height' => $layer['height'] ?? 0,
            'offset_x' => $layer['offset_x'] ?? null,
            'offset_y' => $layer['offset_y'] ?? null,
        ];
    }
    
    return response()->json([
        'matched_parts_count' => count($matchedParts),
        'layers_count' => count($layers),
        'layer_status' => $layerStatus,
        'sample_layer' => count($layers) > 0 ? $layers[0] : null,
    ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
});

Route::get('/imager/debug/check-layout', function (Request $request) {
    $version = trim((string) $request->query('version'));
    $gender = strtoupper(trim((string) $request->query('gender', 'M')));
    $figure = trim((string) $request->query('figure'));
    
    $inspector = app(HabboImagingFigureInspector::class);
    $dresser = app(HabboImagingDresser::class);
    $context = $inspector->latestContext(true);
    
    if (!$context) {
        return response()->json(['error' => 'No context'], 500);
    }
    
    $options = [
        'direction' => 2,
        'head_direction' => 2,
        'gesture' => 'nrm',
        'action' => 'std',
        'head_only' => false,
        'allow_flip_fallback' => true,
    ];
    
    $report = $inspector->inspect($figure, $options);
    $matchedParts = array_values($report['matched_parts'] ?? []);
    
    $reflection = new \ReflectionClass($dresser);
    $compositeMethod = $reflection->getMethod('compositeLayersForReportMatches');
    $compositeMethod->setAccessible(true);
    $layers = $compositeMethod->invoke($dresser, $matchedParts);
    
    $layerOffsets = [];
    foreach ($layers as $index => $layer) {
        $layerOffsets[] = [
            'symbol_name' => $layer['symbol_name'] ?? '',
            'part_type' => $layer['part_type'] ?? '',
            'has_offset_x' => isset($layer['offset_x']),
            'offset_x' => $layer['offset_x'] ?? null,
            'has_offset_y' => isset($layer['offset_y']),
            'offset_y' => $layer['offset_y'] ?? null,
            'width' => $layer['width'] ?? 0,
            'height' => $layer['height'] ?? 0,
        ];
    }
    
    $allLayersHaveOffsets = $reflection->getMethod('allLayersHaveOffsets');
    $allLayersHaveOffsets->setAccessible(true);
    $hasRealOffsets = $allLayersHaveOffsets->invoke($dresser, $layers);
    
    $layoutMethod = $reflection->getMethod('layoutCompositeLayers');
    $layoutMethod->setAccessible(true);
    [$canvasWidth, $canvasHeight, $positioned, $layoutDebug] = $layoutMethod->invoke($dresser, $layers, 'figure');
    
    return response()->json([
        'layers_count' => count($layers),
        'layer_offsets_sample' => array_slice($layerOffsets, 0, 5),
        'all_layers_have_offsets' => $hasRealOffsets,
        'layoutDebug' => $layoutDebug,
        'canvas_width' => $canvasWidth,
        'canvas_height' => $canvasHeight,
        'positioned_layers_count' => count($positioned),
    ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
});

Route::get('/imager/debug/dresser-build', function (Request $request) {
    $gender = strtoupper(trim($request->query('gender', 'M')));
    $figure = trim($request->query('figure', 'hd-180-1.ch-210-66.lg-270-66.sh-290-66'));
    
    $dresser = app(HabboImagingDresser::class);
    
    $selections = $dresser->parseFigureString($figure);
    
    $result = $dresser->build($gender, $selections, 'hd', [
        'item_limit' => 96,
        'strict_direction' => true,
        'strict_action' => true,
        'allow_flip_fallback' => true,
    ]);
    
    return response()->json([
        'dresser_available' => $result['available'] ?? false,
        'dresser_version' => $result['version'] ?? null,
        'categories_count' => count($result['categories'] ?? []),
        'categories' => array_map(fn($c) => $c['key'], $result['categories'] ?? []),
        'items_count' => count($result['items'] ?? []),
        'has_items' => !empty($result['items']),
        'message' => $result['message'] ?? null,
        'full_result' => $result,
    ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
});

Route::get('/imager/debug/layers-data', function (Request $request) {
    $gender = strtoupper(trim((string) $request->query('gender', 'M')));
    $figure = trim((string) $request->query('figure'));

    if ($figure === '' || !in_array($gender, ['M', 'F', 'U'], true)) {
        return response()->json([
            'ok' => false,
            'message' => 'Missing or invalid gender / figure.',
        ], 422);
    }

    $dresser = app(HabboImagingDresser::class);

    $renderHash = $dresser->ensureFigureRenderPath($gender, $figure, [
        'direction' => (int) $request->query('direction', 2),
        'head_direction' => (int) $request->query('head_direction', 2),
        'gesture' => (string) $request->query('gesture', 'nrm'),
        'action' => (string) $request->query('action', 'std'),
        'frame' => (int) $request->query('frame', 0),
        'head_only' => (bool) $request->query('head_only', false),
    ]);

    if (!$renderHash) {
        return response()->json(['ok' => false, 'message' => 'Render failed'], 404);
    }

    $debug = $dresser->getRenderDebugData(substr($renderHash, 3));

    if (empty($debug) || empty($debug['layers'])) {
        return response()->json(['ok' => false, 'message' => 'No debug data'], 404);
    }

    $layers = array_values(array_map(function (array $layer, int $index) {
        $symbolName = (string) ($layer['symbol_name'] ?? '');
        $relativePath = (string) ($layer['relative_path'] ?? '');
        
        $assetUrl = null;
        if ($symbolName !== '') {
            $assetUrl = route('imager.asset', ['symbol' => $symbolName]);
        } elseif ($relativePath !== '') {
            $assetUrl = route('imager.asset', ['path' => $relativePath]);
        }
        
        return $layer + [
            'debug_index' => $index,
            'asset_url' => $assetUrl,
            'ui_x' => (int) ($layer['x'] ?? 0),
            'ui_y' => (int) ($layer['y'] ?? 0),
        ];
    }, $debug['layers'] ?? [], array_keys($debug['layers'] ?? [])));

    return response()->json([
        'ok' => true,
        'render_hash' => $renderHash,
        'layers' => $layers,
        'bounds' => $debug['bounds'] ?? [],
        'hidden_layers' => $debug['hidden_layers'] ?? [],
        'meta' => [
            'gender' => $gender,
            'figure' => $figure,
            'direction' => (int) $request->query('direction', 2),
            'head_direction' => (int) $request->query('head_direction', 2),
            'gesture' => (string) $request->query('gesture', 'nrm'),
            'action' => (string) $request->query('action', 'std'),
            'frame' => (int) $request->query('frame', 0),
            'head_only' => (bool) $request->query('head_only', false),
        ],
    ]);
})->name('imager.debug.layers-data');

Route::get('/imager/debug/layers', function (Request $request) {
    return view('imager-layer-debugger', [
        'version' => trim((string) $request->query('version')),
        'gender' => strtoupper(trim((string) $request->query('gender', 'M'))),
        'figure' => trim((string) $request->query('figure')),
        'direction' => (int) $request->query('direction', 2),
        'headDirection' => (int) $request->query('head_direction', 2),
        'gesture' => (string) $request->query('gesture', 'nrm'),
        'action' => (string) $request->query('action', 'std'),
        'frame' => (int) $request->query('frame', 0),
        'headOnly' => (bool) $request->query('head_only', false),
    ]);
})->name('imager.debug.layers');

Route::get('/imager/debug/force-sync-metadata', function () {
    $service = app(\App\Support\HabboImaging\HabboImagingSyncService::class);
    
    try {
        $result = $service->sync(true, 100);
        return response()->json([
            'success' => true,
            'status' => $result['status'] ?? 'unknown',
            'asset_counts' => $result['asset_counts'] ?? [],
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
});

Route::get('/imager/debug/clear-caches', function () {
    \Illuminate\Support\Facades\Cache::flush();
    
    \Illuminate\Support\Facades\Cache::forget('habbo-imager:metadata:v3:*');
    \Illuminate\Support\Facades\Cache::forget('habbo-imager:asset-map:all');
    
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
    
    return response()->json([
        'success' => true,
        'message' => 'All caches cleared',
    ]);
});

Route::get('/imager/debug/current-version', function () {
    $inspector = app(HabboImagingFigureInspector::class);
    $context = $inspector->latestContext(true);
    
    return response()->json([
        'current_source_version' => $context['version']->source_version ?? null,
        'latest_versions' => DB::table('habbo_imaging_versions')
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id', 'source_version', 'status', 'synced_at']),
    ]);
});

Route::get('/imager/debug/check-part-index', function () {
    $context = app(HabboImagingFigureInspector::class)->latestContext(true);
    
    $partIndex = $context['part_index']['mc:7388'] ?? null;
    
    return response()->json([
        'mc_7388_in_part_index' => $partIndex !== null,
        'mapping' => $partIndex,
        'all_mc_parts' => array_keys($context['part_index'] ?? [])
    ]);
});

Route::get('/imager/debug/figuremap-mappings', function () {
    $context = app(HabboImagingFigureInspector::class)->latestContext(true);
    
    $mcMappings = [];
    foreach ($context['part_index'] as $key => $libraries) {
        if (str_starts_with($key, 'mc:')) {
            $mcMappings[$key] = $libraries;
        }
    }
    
    $mcLibraries = DB::table('habbo_imaging_xml_documents')
        ->where('name', 'LIKE', '%keytar%')
        ->orWhere('name', 'LIKE', '%nft%')
        ->get(['name', 'kind']);
    
    return response()->json([
        'mc_mappings_in_part_index' => $mcMappings,
        'keytar_libraries_in_db' => $mcLibraries,
    ]);
});

Route::get('/imager/debug/check-library-loaded', function () {
    $inspector = app(HabboImagingFigureInspector::class);
    $context = $inspector->latestContext(true);
    
    $assetMap = $context['asset_map'] ?? [];
    $keytarAsset = null;
    
    foreach ($assetMap as $library => $asset) {
        if ($library === 'misc_U_nftkeytar') {
            $keytarAsset = $asset;
            break;
        }
    }
    
    return response()->json([
        'library_in_asset_map' => $keytarAsset !== null,
        'asset_status' => $keytarAsset ? $keytarAsset->status : null,
        'has_metadata' => DB::table('habbo_imaging_xml_documents')
            ->where('name', 'misc_U_nftkeytar')
            ->exists(),
    ]);
});

Route::get('/imager/debug/figuredata-set-7388', function () {
    $context = app(HabboImagingFigureInspector::class)->latestContext(true);
    
    $set = $context['set_types']['mc']['sets']['7388'] ?? null;
    
    return response()->json([
        'set_exists' => $set !== null,
        'set_data' => $set,
        'parts' => $set['parts'] ?? [],
    ]);
});

Route::get('/imager/debug/loading-sources', function () {
    $inspector = app(HabboImagingFigureInspector::class);
    $context = $inspector->latestContext(true);
    
    $version = $context['version'];
    $sourceVersion = $version->source_version;
    
    $possiblePaths = [
        'root_source_figuredata' => storage_path('app/habbo-imaging/source/figuredata.xml'),
        'root_source_figuremap' => storage_path('app/habbo-imaging/source/figuremap.xml'),
        'versioned_source_figuredata' => storage_path("app/{$version->source_path}/figuredata.xml"),
        'versioned_source_figuremap' => storage_path("app/{$version->source_path}/figuremap.xml"),
        'versioned_parsed_figuredata' => storage_path("app/{$version->parsed_path}/figuredata.json"),
        'versioned_parsed_figuremap' => storage_path("app/{$version->parsed_path}/figuremap.json"),
    ];
    
    $exists = [];
    foreach ($possiblePaths as $name => $path) {
        $exists[$name] = [
            'exists' => file_exists($path),
            'path' => $path,
            'size' => file_exists($path) ? filesize($path) : 0,
            'modified' => file_exists($path) ? date('Y-m-d H:i:s', filemtime($path)) : null,
        ];
    }
    
    return response()->json([
        'active_version' => $sourceVersion,
        'version_source_path' => $version->source_path,
        'version_parsed_path' => $version->parsed_path,
        'files' => $exists,
    ]);
});

Route::get('/imager/debug/composite-layers', function (Request $request) {
    $version = trim((string) $request->query('version'));
    $gender = strtoupper(trim((string) $request->query('gender', 'M')));
    $figure = trim((string) $request->query('figure'));
    
    $inspector = app(HabboImagingFigureInspector::class);
    $dresser = app(HabboImagingDresser::class);
    $context = $inspector->latestContext(true);
    
    if (!$context || (string) ($context['version']->source_version ?? '') !== $version) {
        return response()->json(['error' => 'Context not available'], 500);
    }
    
    $options = [
        'direction' => 2,
        'head_direction' => 2,
        'gesture' => 'nrm',
        'action' => 'std',
        'head_only' => false,
        'allow_flip_fallback' => true,
    ];
    
    $report = $inspector->inspect($figure, $options);
    $matchedParts = array_values($report['matched_parts'] ?? []);
    
    $matchedPartsData = [];
    foreach ($matchedParts as $part) {
        $matchedPartsData[] = [
            'part_type' => $part['part_type'],
            'part_id' => $part['part_id'],
            'best_asset_symbol' => $part['best_asset']['symbol_name'] ?? null,
            'relative_path' => $part['best_asset']['relative_path'] ?? null,
            'offset_x' => $part['best_asset']['offset_x'] ?? null,
            'offset_y' => $part['best_asset']['offset_y'] ?? null,
        ];
    }
    
    $repository = app(HabboImagingAssetRepository::class);
    $testSymbol = 'hh_human_body_h_std_bd_1_2_0';
    $testBlob = $repository->findBySymbol($testSymbol);
    
    return response()->json([
        'matched_parts_count' => count($matchedParts),
        'matched_parts_sample' => $matchedPartsData,
        'test_blob_exists' => $testBlob !== null,
        'test_blob_size' => $testBlob ? strlen($testBlob) : 0,
    ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
});

Route::get('/imager/debug/mc-sets-in-figuredata', function () {
    $context = app(HabboImagingFigureInspector::class)->latestContext(true);
    
    $mcSets = array_keys($context['set_types']['mc']['sets'] ?? []);
    sort($mcSets, SORT_NUMERIC);
    
    return response()->json([
        'total_mc_sets' => count($mcSets),
        'mc_set_ids' => $mcSets,
        'has_7388' => in_array('7388', $mcSets),
    ]);
});

Route::get('/imager/debug/verify-mc-7388', function () {
    $inspector = app(HabboImagingFigureInspector::class);
    $context = $inspector->latestContext(true);
    
    $set = $context['set_types']['mc']['sets']['7388'] ?? null;
    
    return response()->json([
        'version_loaded' => $context['version']->source_version ?? null,
        'has_set_7388' => $set !== null,
        'set_details' => $set,
    ]);
});

// APNG Builder Functions
if (!function_exists('build_apng_from_sequence')) {
    function build_apng_from_sequence(array $sequence): ?string
    {
        $preparedFrames = [];
        $frameDurations = [];
        $globalMinX = null;
        $globalMinY = null;
        $globalMaxX = null;
        $globalMaxY = null;

        foreach (($sequence['frames'] ?? []) as $frame) {
            $renderPath = (string) ($frame['render_path'] ?? '');
            $png = null;

            if (str_starts_with($renderPath, 'db:')) {
                $hash = substr($renderPath, 3);
                $png = app(HabboImagingAssetRepository::class)->findFigureRender($hash);
            }

            if ($png === null || $png === '' || !str_starts_with($png, "\x89PNG\r\n\x1a\n")) {
                continue;
            }

            $image = @imagecreatefromstring($png);
            if (!$image) {
                continue;
            }

            $width = imagesx($image);
            $height = imagesy($image);

            $frameMinX = 0;
            $frameMinY = 0;
            $frameMaxX = $width - 1;
            $frameMaxY = $height - 1;

            $globalMinX = $globalMinX === null ? $frameMinX : min($globalMinX, $frameMinX);
            $globalMinY = $globalMinY === null ? $frameMinY : min($globalMinY, $frameMinY);
            $globalMaxX = $globalMaxX === null ? $frameMaxX : max($globalMaxX, $frameMaxX);
            $globalMaxY = $globalMaxY === null ? $frameMaxY : max($globalMaxY, $frameMaxY);

            $preparedFrames[] = [
                'image' => $image,
                'width' => $width,
                'height' => $height,
                'min_x' => $frameMinX,
                'min_y' => $frameMinY,
                'max_x' => $frameMaxX,
                'max_y' => $frameMaxY,
            ];

            $frameDurations[] = max(1, (int) ($frame['duration_ms'] ?? ($sequence['frame_duration_ms'] ?? 180)));
        }

        if (empty($preparedFrames) || $globalMinX === null) {
            return null;
        }

        $outerPadding = 5;
        $canvasWidth = (($globalMaxX - $globalMinX) + 1) + ($outerPadding * 2);
        $canvasHeight = (($globalMaxY - $globalMinY) + 1) + ($outerPadding * 2);

        if ($canvasWidth <= 0 || $canvasHeight <= 0) {
            foreach ($preparedFrames as $pf) { if (isset($pf['image'])) imagedestroy($pf['image']); }
            return null;
        }

        $frames = [];
        foreach ($preparedFrames as $pf) {
            $canvas = imagecreatetruecolor($canvasWidth, $canvasHeight);
            if (!$canvas) {
                foreach ($preparedFrames as $pf2) { if (isset($pf2['image'])) imagedestroy($pf2['image']); }
                return null;
            }

            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefill($canvas, 0, 0, $transparent);

            $destX = (int) ($pf['min_x'] - $globalMinX) + $outerPadding;
            $destY = (int) ($pf['min_y'] - $globalMinY) + $outerPadding;

            imagecopy($canvas, $pf['image'], $destX, $destY, 0, 0, $pf['width'], $pf['height']);

            ob_start();
            imagepng($canvas);
            $normalizedPng = (string) ob_get_clean();
            imagedestroy($canvas);
            imagedestroy($pf['image']);

            $parsed = parse_png_chunks($normalizedPng);
            if (!$parsed) return null;
            $frames[] = $parsed;
        }

        if (empty($frames)) return null;
        if (count($frames) === 1) return $frames[0]['raw'];

        $first = $frames[0];
        $sequenceNumber = 0;
        $apng = "\x89PNG\r\n\x1a\n";

        $apng .= png_pack_chunk('IHDR', $first['ihdr']);
        $apng .= png_pack_chunk('acTL', pack('NN', count($frames), !empty($sequence['loop']) ? 0 : 1));

        foreach ($first['pre_idat'] as $chunk) {
            $apng .= png_pack_chunk($chunk['type'], $chunk['data']);
        }

        $apng .= png_pack_chunk('fcTL', png_frame_control_data(
            $sequenceNumber++, $first['width'], $first['height'], 0, 0, $frameDurations[0]
        ));

        foreach ($first['idat'] as $idatData) {
            $apng .= png_pack_chunk('IDAT', $idatData);
        }

        for ($i = 1, $count = count($frames); $i < $count; $i++) {
            $frame = $frames[$i];
            $apng .= png_pack_chunk('fcTL', png_frame_control_data(
                $sequenceNumber++, $frame['width'], $frame['height'], 0, 0, $frameDurations[$i]
            ));
            foreach ($frame['idat'] as $idatData) {
                $apng .= png_pack_chunk('fdAT', pack('N', $sequenceNumber++) . $idatData);
            }
        }

        foreach ($first['post_idat'] as $chunk) {
            $apng .= png_pack_chunk($chunk['type'], $chunk['data']);
        }

        $apng .= png_pack_chunk('IEND', '');
        return $apng;
    }
}

if (!function_exists('parse_png_chunks')) {
    function parse_png_chunks(string $png): ?array
    {
        if (!str_starts_with($png, "\x89PNG\r\n\x1a\n")) return null;

        $offset = 8;
        $length = strlen($png);
        $ihdr = null;
        $width = 0;
        $height = 0;
        $preIdat = [];
        $idat = [];
        $postIdat = [];
        $seenIdat = false;

        while ($offset + 8 <= $length) {
            $chunkLength = unpack('N', substr($png, $offset, 4))[1] ?? null;
            $offset += 4;
            if ($chunkLength === null || $offset + 4 + $chunkLength + 4 > $length) return null;

            $type = substr($png, $offset, 4);
            $offset += 4;
            $data = substr($png, $offset, $chunkLength);
            $offset += $chunkLength + 4;

            if ($type === 'IHDR') {
                $ihdr = $data;
                $width = unpack('N', substr($data, 0, 4))[1] ?? 0;
                $height = unpack('N', substr($data, 4, 4))[1] ?? 0;
            } elseif ($type === 'IDAT') {
                $seenIdat = true;
                $idat[] = $data;
            } elseif ($type === 'IEND') {
                break;
            } elseif ($type !== 'acTL' && $type !== 'fcTL' && $type !== 'fdAT') {
                if (!$seenIdat) $preIdat[] = ['type' => $type, 'data' => $data];
                else $postIdat[] = ['type' => $type, 'data' => $data];
            }
        }

        if ($ihdr === null || $width <= 0 || $height <= 0 || empty($idat)) return null;

        return [
            'raw' => $png, 'ihdr' => $ihdr, 'width' => $width, 'height' => $height,
            'pre_idat' => $preIdat, 'idat' => $idat, 'post_idat' => $postIdat,
        ];
    }
}

if (!function_exists('png_frame_control_data')) {
    function png_frame_control_data(int $seq, int $w, int $h, int $x, int $y, int $dur): string
    {
        $delayNum = max(1, min(65535, $dur));
        return pack('NNNNNnnCC', $seq, $w, $h, $x, $y, $delayNum, 1200, 0, 0);
    }
}

if (!function_exists('png_pack_chunk')) {
    function png_pack_chunk(string $type, string $data): string
    {
        $chunk = $type . $data;
        $crc = crc32($chunk);
        if ($crc < 0) $crc += 4294967296;
        return pack('N', strlen($data)) . $chunk . pack('N', $crc);
    }
}
