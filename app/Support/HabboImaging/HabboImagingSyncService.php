<?php

namespace App\Support\HabboImaging;

use App\Models\HabboImagingAsset;
use App\Models\HabboImagingVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;
use ZipArchive;

class HabboImagingSyncService
{
    public function __construct(
        private readonly HabboImagingStorage $storage,
        private readonly HabboImagingLock $lock,
        private readonly HabboImagingManifest $manifest,
        private readonly HabboImagingSourceResolver $resolver,
        private readonly HabboImagingSourceParser $parser,
        private readonly HabboImagingSwfExtractor $swfExtractor,
        private readonly HabboImagingDresser $dresser,
        private readonly HabboImagingAssetRepository $repository,
    ) {
    }

    public function sync(bool $force = false, int $batchSize = 25): array
    {
        $this->guardTablesExist();
        $this->storage->ensureStructure();

        if ($this->lock->isLocked() && !$force) {
            return [
                'status' => 'locked',
                'message' => 'Habbo imaging sync is already running.',
            ];
        }

        $this->lock->acquire();
        $version = null;

        try {
            $this->manifest->markSyncStarted();

            $source = $this->resolver->discover();
            $sourceVersion = $source['source_version'];
            $versionKey = 'current';
            $directories = $this->storage->ensureVersionDirectories($versionKey);
            $version = HabboImagingVersion::query()->firstOrNew(['source_version' => $versionKey]);

            $version->fill([
                'hotel' => $source['hotel'],
                'status' => 'syncing_metadata',
                'external_variables_url' => $source['external_variables_url'],
                'figuredata_url' => $source['figuredata_url'],
                'figuremap_url' => $source['figuremap_url'],
                'asset_base_url' => $source['asset_base_url'],
                'asset_name_template' => $source['asset_name_template'],
                'source_path' => $directories['source'],
                'parsed_path' => $directories['parsed'],
                'metadata' => array_merge(is_array($version->metadata) ? $version->metadata : [], [
                    'discovered_source_version' => $sourceVersion,
                ]),
                'last_error' => null,
            ]);
            $version->save();

            Storage::disk('local')->put("{$directories['source']}/external_variables.txt", $source['external_variables_payload']);
            Storage::disk('local')->put(
                "{$directories['parsed']}/external_variables.json",
                json_encode($source['variables'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );

            $figuredataPayload = $source['figuredata_url'] ? $this->resolver->fetchText($source['figuredata_url']) : null;
            $figuremapPayload = $source['figuremap_url'] ? $this->resolver->fetchText($source['figuremap_url']) : null;

            if (!$figuredataPayload || !$figuremapPayload) {
                throw new RuntimeException('Habbo figuredata or figuremap URL could not be resolved.');
            }

            Storage::disk('local')->put("{$directories['source']}/figuredata.xml", $figuredataPayload);
            Storage::disk('local')->put("{$directories['source']}/figuremap.xml", $figuremapPayload);
            Storage::disk('local')->put('habbo-imaging/source/figuredata.xml', $figuredataPayload);
            Storage::disk('local')->put('habbo-imaging/source/figuremap.xml', $figuremapPayload);
            $parsedFiguredata = $this->parser->parseFiguredata($figuredataPayload);
            $parsedFiguremap = $this->parser->parseFiguremap($figuremapPayload);

            Storage::disk('local')->put(
                "{$directories['parsed']}/figuredata.json",
                json_encode($parsedFiguredata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
            Storage::disk('local')->put(
                "{$directories['parsed']}/figuremap.json",
                json_encode($parsedFiguremap, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );

            $libraries = $parsedFiguremap['libraries'] ?? [];
            $this->seedAssetRows($version, $libraries);
            $reuseResult = $this->carryForwardReusableAssets($version, $libraries);
            $reusedAssets = $reuseResult['reused'] ?? [];

            $processedAssets = [];

            if (!empty($source['asset_base_url'])) {
                $processedAssets = $this->syncPendingAssets($version, $source['asset_base_url'], $source['asset_name_template'], $batchSize, $force);
            }

            $processedAssets = array_merge($reusedAssets, $processedAssets);

            $assetCounts = $this->assetCounts($version);
            $assetDetails = $this->assetDetails($version, $assetCounts, $processedAssets);
            $isReady = ($assetCounts['pending'] + $assetCounts['syncing']) === 0;

            $version->update([
                'status' => $isReady ? 'ready' : 'syncing_assets',
                'metadata' => [
                    'figuredata' => $parsedFiguredata['summary'] ?? [],
                    'figuremap' => $parsedFiguremap['summary'] ?? [],
                    'asset_counts' => $assetCounts,
                    'asset_details' => $assetDetails,
                    'last_processed_assets' => $processedAssets,
                    'discovered_source_version' => $sourceVersion,
                ],
                'synced_at' => now(),
                'last_error' => null,
            ]);

            $summary = [
                'status' => $isReady ? 'ready' : 'syncing_assets',
                'version_id' => $version->getKey(),
                'source_version' => $versionKey,
                'discovered_source_version' => $sourceVersion,
                'asset_counts' => $assetCounts,
                'asset_details' => $assetDetails,
                'figuredata_summary' => $parsedFiguredata['summary'] ?? [],
                'figuremap_summary' => $parsedFiguremap['summary'] ?? [],
                'processed_assets' => $processedAssets,
                'storage' => $directories,
            ];

            if ($isReady) {
                try {
                    $summary['final_render'] = $this->dresser->autoRenderAllDresserFinal();
                } catch (Throwable $exception) {
                    $summary['final_render'] = [
                        'ok' => false,
                        'message' => $exception->getMessage(),
                    ];
                }
            }

            $this->manifest->markSyncFinished($summary);
            Cache::forget('habbo-imager:asset-map:v4:current');

            return $summary;
        } catch (Throwable $exception) {
            if ($version instanceof HabboImagingVersion) {
                $version->update([
                    'status' => 'failed',
                    'last_error' => $exception->getMessage(),
                ]);
            }

            $this->manifest->markSyncFailed($exception);

            throw $exception;
        } finally {
            $this->lock->release();
        }
    }

    private function guardTablesExist(): void
    {
        if (!Schema::hasTable('habbo_imaging_versions') || !Schema::hasTable('habbo_imaging_assets')) {
            throw new RuntimeException('Habbo imaging tables are missing. Run the latest migrations first.');
        }
    }

    private function seedAssetRows(HabboImagingVersion $version, array $libraries): void
    {
        foreach ($libraries as $libraryName => $libraryData) {
            $asset = HabboImagingAsset::query()->firstOrNew([
                'version_id' => $version->getKey(),
                'library_name' => $libraryName,
            ]);

            $metadata = is_array($asset->metadata) ? $asset->metadata : [];
            $metadata['library_revision'] = (string) ($libraryData['revision'] ?? '');
            $asset->metadata = $metadata;

            if (!$asset->exists) {
                $asset->status = 'pending';
            }

            $asset->save();
        }
    }

    private function carryForwardReusableAssets(HabboImagingVersion $version, array $libraries): array
    {
        if (empty($libraries)) {
            return [];
        }

        $currentAssets = HabboImagingAsset::query()
            ->where('version_id', $version->getKey())
            ->whereIn('library_name', array_keys($libraries))
            ->get()
            ->keyBy('library_name');

        $candidateAssets = HabboImagingAsset::query()
            ->where('version_id', '!=', $version->getKey())
            ->whereIn('library_name', array_keys($libraries))
            ->where('status', 'extracted')
            ->orderByDesc('id')
            ->get();

        $candidateMap = [];
        foreach ($candidateAssets as $candidate) {
            $revision = (string) data_get($candidate->metadata, 'library_revision', '');
            $key = $candidate->library_name . '|' . $revision;

            if (!isset($candidateMap[$key])) {
                $candidateMap[$key] = $candidate;
            }
        }

        $reused = [];
        $alreadyHaveCount = 0;

        foreach ($libraries as $libraryName => $libraryData) {
            $asset = $currentAssets->get($libraryName);

            if ($asset && $asset->status === 'extracted') {
                $alreadyHaveCount++;
                continue;
            }

            $revision = (string) ($libraryData['revision'] ?? '');
            $candidate = $candidateMap[$libraryName . '|' . $revision] ?? null;

            if (!$candidate) {
                continue;
            }

            if ($asset) {
                $asset->update([
                    'status' => 'extracted',
                    'extension' => $candidate->extension,
                    'source_url' => $candidate->source_url,
                    'source_path' => $candidate->source_path,
                    'extracted_path' => $candidate->extracted_path,
                    'checksum' => $candidate->checksum,
                    'extracted_file_count' => $candidate->extracted_file_count,
                    'metadata' => array_merge(
                        is_array($asset->metadata) ? $asset->metadata : [],
                        is_array($candidate->metadata) ? $candidate->metadata : [],
                        [
                            'library_revision' => $revision,
                            'reused_from_asset_id' => $candidate->getKey(),
                            'reused_from_version_id' => $candidate->version_id,
                            'reused_from_source_version' => $candidate->version?->source_version,
                            'blobs_already_in_repository' => true,
                        ]
                    ),
                    'synced_at' => now(),
                ]);
            } else {
                HabboImagingAsset::create([
                    'version_id' => $version->getKey(),
                    'library_name' => $libraryName,
                    'status' => 'extracted',
                    'extension' => $candidate->extension,
                    'source_url' => $candidate->source_url,
                    'source_path' => $candidate->source_path,
                    'extracted_path' => $candidate->extracted_path,
                    'checksum' => $candidate->checksum,
                    'extracted_file_count' => $candidate->extracted_file_count,
                    'metadata' => array_merge(
                        is_array($candidate->metadata) ? $candidate->metadata : [],
                        [
                            'library_revision' => $revision,
                            'reused_from_asset_id' => $candidate->getKey(),
                            'reused_from_version_id' => $candidate->version_id,
                            'reused_from_source_version' => $candidate->version?->source_version,
                            'blobs_already_in_repository' => true,
                        ]
                    ),
                    'synced_at' => now(),
                ]);
            }

            $reused[] = [
                'library' => $libraryName,
                'status' => 'reused',
                'revision' => $revision,
                'note' => 'Reused from version ' . ($candidate->version?->source_version ?? 'unknown'),
            ];
        }

        return [
            'reused' => $reused,
            'already_extracted' => $alreadyHaveCount,
            'total_libraries' => count($libraries),
            'needs_download' => count($libraries) - count($reused) - $alreadyHaveCount,
        ];
    }
    private function syncPendingAssets(HabboImagingVersion $version, string $assetBaseUrl, string $template, int $batchSize, bool $force = false): array
    {
        $limit = max(1, $batchSize);
        $this->recoverStaleSyncingAssets($version);
        $primaryStatuses = $force ? ['binary_only', 'downloaded', 'pending', 'failed'] : ['pending', 'failed'];
        $assets = HabboImagingAsset::query()
            ->where('version_id', $version->getKey())
            ->whereIn('status', $primaryStatuses)
            ->when($force, function ($query) {
                $query->orderByRaw("CASE WHEN status IN ('binary_only','downloaded') THEN 0 ELSE 1 END");
            })
            ->orderBy('library_name')
            ->limit($limit)
            ->get();

        if ($assets->count() < $limit) {
            $upgradeCandidates = HabboImagingAsset::query()
                ->where('version_id', $version->getKey())
                ->whereIn('status', ['binary_only', 'downloaded'])
                ->where('extension', 'swf')
                ->where(function ($query) {
                    $query->whereNull('extracted_path')
                        ->orWhere('extracted_file_count', 0);
                })
                ->orderBy('library_name')
                ->limit($limit - $assets->count())
                ->get();

            $assets = $assets->concat($upgradeCandidates);
        }

        $processed = [];

        foreach ($assets as $asset) {
            $sourceUrl = $asset->source_url ?: $this->buildAssetUrl($assetBaseUrl, $template, $asset->library_name);
            $asset->update([
                'status' => 'syncing',
                'source_url' => $sourceUrl,
            ]);

            try {
                $extension = $asset->extension ?: $this->resolveExtension($sourceUrl, $template);
                $sourcePath = $asset->source_path ?: $this->storage->assetSourcePath($version->source_version, $asset->library_name, $extension);

                if (!Storage::disk('local')->exists($sourcePath)) {
                    $binary = $this->resolver->fetchBinary($sourceUrl);
                    Storage::disk('local')->put($sourcePath, $binary);
                    $checksum = md5($binary);
                    $sizeBytes = strlen($binary);
                } else {
                    $binary = Storage::disk('local')->get($sourcePath);
                    $checksum = md5((string) $binary);
                    $sizeBytes = strlen((string) $binary);
                }

                [$status, $extractedPath, $fileCount, $assetMetadata] = $this->extractAssetPackage($version->source_version, $asset->library_name, $sourcePath, $extension);

                $asset->update([
                    'extension' => $extension,
                    'source_path' => $sourcePath,
                    'extracted_path' => $extractedPath,
                    'status' => $status,
                    'checksum' => $checksum,
                    'extracted_file_count' => $fileCount,
                    'metadata' => array_merge($asset->metadata ?? [], $assetMetadata, [
                        'size_bytes' => $sizeBytes,
                    ]),
                    'synced_at' => now(),
                ]);

                $processed[] = [
                    'library' => $asset->library_name,
                    'status' => $status,
                    'extracted_file_count' => $fileCount,
                ];
            } catch (Throwable $exception) {
                $asset->update([
                    'status' => 'failed',
                    'metadata' => [
                        'error' => $exception->getMessage(),
                    ],
                ]);

                $processed[] = [
                    'library' => $asset->library_name,
                    'status' => 'failed',
                ];
            }
        }

        return $processed;
    }

    private function recoverStaleSyncingAssets(HabboImagingVersion $version): void
    {
        $staleThreshold = now()->subMinutes(10);

        HabboImagingAsset::query()
            ->where('version_id', $version->getKey())
            ->where('status', 'syncing')
            ->where(function ($query) use ($staleThreshold) {
                $query->whereNull('source_path')
                    ->orWhereNull('updated_at')
                    ->orWhere('updated_at', '<', $staleThreshold);
            })
            ->chunkById(100, function ($assets) {
                foreach ($assets as $asset) {
                    $metadata = is_array($asset->metadata) ? $asset->metadata : [];
                    $metadata['recovered_from_stale_syncing'] = true;
                    $metadata['recovered_at'] = now()->toIso8601String();

                    $asset->update([
                        'status' => 'pending',
                        'metadata' => $metadata,
                    ]);
                }
            });
    }

    private function extractAssetPackage(string $versionKey, string $libraryName, string $sourcePath, string $extension): array
    {
        $disk = Storage::disk('local');
        $extractedDirectory = $this->storage->assetExtractedDirectory($versionKey, $libraryName);

        $status = 'binary_only';
        $fileCount = 0;
        $metadata = [];

        if ($extension === 'swf') {
            $swfExtraction = $this->swfExtractor->extract($sourcePath, $extractedDirectory, $versionKey);
            $fileCount = count($swfExtraction['files']);
            $status = $fileCount > 0 ? 'extracted' : 'binary_only';
            $metadata = [
                'extractor' => 'swf',
                'symbol_count' => count($swfExtraction['metadata']['symbol_classes'] ?? []),
                'bitmap_count' => count($swfExtraction['metadata']['bitmaps'] ?? []),
            ];

            return [
                $status,
                $extractedDirectory,
                $fileCount,
                $metadata,
            ];
        }

        $absoluteSourcePath = $disk->path($sourcePath);

        $zip = new ZipArchive();

        if ($zip->open($absoluteSourcePath) === true) {
            $zip->extractTo($disk->path($extractedDirectory));
            $zip->close();
            $fileCount = count($disk->allFiles($extractedDirectory));
            $status = $fileCount > 0 ? 'extracted' : 'downloaded';
            $metadata = [
                'extractor' => 'zip',
            ];
        } elseif (in_array($extension, ['png', 'gif', 'jpg', 'jpeg', 'xml', 'json', 'txt'], true)) {
            $targetPath = "{$extractedDirectory}/{$libraryName}.{$extension}";
            $disk->copy($sourcePath, $targetPath);
            $fileCount = 1;
            $status = 'extracted';
            $metadata = [
                'extractor' => 'direct_copy',
            ];
        }

        return [
            $status,
            $extractedDirectory,
            $fileCount,
            $metadata,
        ];
    }

    private function buildAssetUrl(string $assetBaseUrl, string $template, string $libraryName): string
    {
        $filename = str_replace(
            ['%libname%', '{libname}', '%library%', '{library}'],
            [$libraryName, $libraryName, $libraryName, $libraryName],
            $template
        );

        return rtrim($assetBaseUrl, '/') . '/' . ltrim($filename, '/');
    }

    private function resolveExtension(string $sourceUrl, string $template): string
    {
        $path = parse_url($sourceUrl, PHP_URL_PATH);
        $extension = strtolower((string) pathinfo((string) $path, PATHINFO_EXTENSION));

        if ($extension !== '') {
            return $extension;
        }

        $templateExtension = strtolower((string) pathinfo($template, PATHINFO_EXTENSION));
        return $templateExtension !== '' ? $templateExtension : 'bin';
    }

    private function assetCounts(HabboImagingVersion $version): array
    {
        $counts = [
            'pending' => 0,
            'syncing' => 0,
            'failed' => 0,
            'downloaded' => 0,
            'binary_only' => 0,
            'extracted' => 0,
            'total' => 0,
        ];

        $rows = HabboImagingAsset::query()
            ->selectRaw('status, COUNT(*) as aggregate_count')
            ->where('version_id', $version->getKey())
            ->groupBy('status')
            ->pluck('aggregate_count', 'status')
            ->all();

        foreach ($rows as $status => $count) {
            $counts[$status] = (int) $count;
            $counts['total'] += (int) $count;
        }

        return $counts;
    }

    private function assetDetails(HabboImagingVersion $version, array $assetCounts, array $processedAssets): array
    {
        $assets = HabboImagingAsset::query()
            ->where('version_id', $version->getKey())
            ->get(['library_name', 'status', 'extracted_file_count', 'metadata']);

        $totalExtractedFiles = 0;
        $totalBitmapCount = 0;
        $totalSymbolCount = 0;
        $extractedLibraries = 0;
        $binaryOnlyLibraries = 0;
        $failedLibraries = 0;
        $reusedLibraries = 0;

        foreach ($assets as $asset) {
            $metadata = is_array($asset->metadata) ? $asset->metadata : [];
            $totalExtractedFiles += (int) $asset->extracted_file_count;
            $totalBitmapCount += (int) ($metadata['bitmap_count'] ?? 0);
            $totalSymbolCount += (int) ($metadata['symbol_count'] ?? 0);

            if ($asset->status === 'extracted') {
                $extractedLibraries++;
                if (!empty($metadata['reused_from_asset_id'])) {
                    $reusedLibraries++;
                }
            } elseif ($asset->status === 'binary_only') {
                $binaryOnlyLibraries++;
            } elseif ($asset->status === 'failed') {
                $failedLibraries++;
            }
        }

        $totalLibraries = max(1, (int) ($assetCounts['total'] ?? 0));
        $completedLibraries = $totalLibraries - (int) ($assetCounts['pending'] ?? 0) - (int) ($assetCounts['syncing'] ?? 0);
        $completionPercent = round(($completedLibraries / $totalLibraries) * 100, 2);

        return [
            'completed_libraries' => $completedLibraries,
            'completion_percent' => $completionPercent,
            'extracted_libraries' => $extractedLibraries,
            'reused_libraries' => $reusedLibraries,
            'binary_only_libraries' => $binaryOnlyLibraries,
            'failed_libraries' => $failedLibraries,
            'total_extracted_files' => $totalExtractedFiles,
            'total_bitmap_count' => $totalBitmapCount,
            'total_symbol_count' => $totalSymbolCount,
            'processed_this_run' => count($processedAssets),
            'processed_sample' => array_slice($processedAssets, 0, 8),
        ];
    }
}
