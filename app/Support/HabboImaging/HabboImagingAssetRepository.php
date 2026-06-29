<?php

namespace App\Support\HabboImaging;

use Illuminate\Support\Facades\DB;

class HabboImagingAssetRepository
{
    public function findBySymbol(string $symbolName): ?string
    {
        $row = DB::table('habbo_imaging_asset_blobs')
            ->where('symbol_name', $symbolName)
            ->first(['image_data']);

        return $row ? (string) $row->image_data : null;
    }

    public function storeAsset(string $versionKey, string $symbolName, string $blob, array $metadata = []): void
    {
        $versionKey = 'current';
        $width = $metadata['width'] ?? 0;
        $height = $metadata['height'] ?? 0;
        $offsetX = $metadata['offset_x'] ?? 0;
        $offsetY = $metadata['offset_y'] ?? 0;

        DB::table('habbo_imaging_asset_blobs')->updateOrInsert(
            ['symbol_name' => $symbolName],
            [
                'version_key' => $versionKey,
                'image_data' => $blob,
                'width' => $width,
                'height' => $height,
                'offset_x' => $offsetX,
                'offset_y' => $offsetY,
                'metadata' => !empty($metadata) ? json_encode($metadata) : null,
                'updated_at' => now(),
            ]
        );
    }

    public function updateAssetMetadata(string $symbolName, array $metadata = []): void
    {
        if ($symbolName === '' || empty($metadata)) {
            return;
        }

        $payload = [
            'version_key' => 'current',
            'updated_at' => now(),
        ];

        foreach (['width', 'height', 'offset_x', 'offset_y'] as $key) {
            if (array_key_exists($key, $metadata) && $metadata[$key] !== null) {
                $payload[$key] = (int) $metadata[$key];
            }
        }

        $row = DB::table('habbo_imaging_asset_blobs')
            ->where('symbol_name', $symbolName)
            ->first(['metadata']);

        $existingMetadata = $row && $row->metadata
            ? json_decode((string) $row->metadata, true)
            : [];

        $payload['metadata'] = json_encode(array_merge(
            is_array($existingMetadata) ? $existingMetadata : [],
            $metadata,
            ['manifest_refreshed_at' => now()->toDateTimeString()]
        ));

        DB::table('habbo_imaging_asset_blobs')
            ->where('symbol_name', $symbolName)
            ->update($payload);
    }

    public function findDresserRender(string $renderHash): ?string
    {
        $row = DB::table('habbo_imaging_render_blobs')
            ->where('render_hash', $renderHash)
            ->first(['image_data']);

        return $row ? (string) $row->image_data : null;
    }

    public function storeDresserRender(string $renderHash, string $blob, array $metadata = []): void
    {
        DB::table('habbo_imaging_render_blobs')->updateOrInsert(
            ['render_hash' => $renderHash],
            [
                'image_data' => $blob,
                'metadata' => !empty($metadata) ? json_encode($metadata) : null,
                'updated_at' => now(),
            ]
        );
    }

    public function findFigureRender(string $renderHash): ?string
    {
        return $this->findDresserRender($renderHash);
    }

    public function storeFigureRender(string $renderHash, string $blob, array $metadata = []): void
    {
        $this->storeDresserRender($renderHash, $blob, $metadata);
    }

    public function symbolsMatchingPrefix(string $prefix): array
    {
        return DB::table('habbo_imaging_asset_blobs')
            ->where('symbol_name', 'like', $prefix . '%')
            ->orderBy('symbol_name')
            ->pluck('symbol_name')
            ->all();
    }

    public function rebuildLibraryMetadataFromBlobs(string $libraryName, string $versionKey = 'current'): bool
    {
        $rows = DB::table('habbo_imaging_asset_blobs')
            ->where('symbol_name', 'like', $libraryName . '%')
            ->orderBy('symbol_name')
            ->get(['symbol_name', 'width', 'height', 'offset_x', 'offset_y', 'metadata']);

        if ($rows->isEmpty()) {
            return false;
        }

        if (!$this->blobRowsHaveUsefulOffsets($rows)) {
            return false;
        }

        $bitmaps = [];
        $symbolClasses = [];
        $manifestAssets = [];

        foreach ($rows as $row) {
            $metadata = $row->metadata ? json_decode((string) $row->metadata, true) : [];
            $metadata = is_array($metadata) ? $metadata : [];
            $characterId = (int) ($metadata['character_id'] ?? 0);
            $symbolName = (string) $row->symbol_name;

            if ($characterId > 0) {
                $symbolClasses[(string) $characterId] ??= [];
                $symbolClasses[(string) $characterId][] = $symbolName;
            }

            $bitmap = [
                'character_id' => $characterId,
                'symbol_name' => $symbolName,
                'width' => (int) $row->width,
                'height' => (int) $row->height,
                'format' => (int) ($metadata['format'] ?? 5),
                'stored_in_db' => true,
                'offset_x' => (int) $row->offset_x,
                'offset_y' => (int) $row->offset_y,
                'rebuilt_from_blob' => true,
            ];

            $bitmaps[] = $bitmap;
            $manifestAssets[$symbolName] = [
                'name' => $symbolName,
                'library' => $libraryName,
                'offset_x' => (int) $row->offset_x,
                'offset_y' => (int) $row->offset_y,
            ];
        }

        foreach ($symbolClasses as &$symbols) {
            $symbols = array_values(array_unique($symbols));
            sort($symbols);
        }
        unset($symbols);
        ksort($symbolClasses);

        $symbolToCharacter = [];
        foreach ($symbolClasses as $characterId => $symbols) {
            foreach ($symbols as $symbolName) {
                $symbolToCharacter[$symbolName] = (int) $characterId;
            }
        }
        ksort($symbolToCharacter);

        $metadata = [
            'type' => 'swf',
            'symbol_classes' => $symbolClasses,
            'symbol_to_character' => $symbolToCharacter,
            'character_aliases' => $symbolClasses,
            'manifest_assets' => $manifestAssets,
            'xml_documents' => [],
            'bitmaps' => $bitmaps,
            'rebuilt_from_existing_blobs' => true,
        ];

        $this->storeXmlDocument($versionKey, $libraryName, 'library_metadata', json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}', [
            'bitmap_count' => count($bitmaps),
            'symbol_count' => count($symbolClasses),
            'rebuilt_from_existing_blobs' => true,
            'rebuilt_at' => now()->toDateTimeString(),
        ]);

        return true;
    }

    public function repairLibraryMetadataFromStoredSource(string $libraryName, string $versionKey = 'current'): bool
    {
        $fallback = DB::table('habbo_imaging_xml_documents')
            ->where('version_key', '!=', 'current')
            ->where('name', $this->normalizeXmlDocumentName($libraryName, 'library_metadata'))
            ->where('kind', 'library_metadata')
            ->orderByDesc('updated_at')
            ->get(['xml_content', 'metadata'])
            ->first(fn ($row) => $this->libraryMetadataHasUsefulOffsets((string) $row->xml_content));

        if ($fallback) {
            $this->storeXmlDocument(
                $versionKey,
                $libraryName,
                'library_metadata',
                (string) $fallback->xml_content,
                json_decode((string) ($fallback->metadata ?? ''), true) ?: [
                    'repaired_from_stored_source' => true,
                    'repaired_at' => now()->toDateTimeString(),
                ]
            );

            return true;
        }

        return $this->rebuildLibraryMetadataFromBlobs($libraryName, $versionKey);
    }

    public function currentLibraryMetadataIsUsable(string $libraryName): bool
    {
        $row = DB::table('habbo_imaging_xml_documents')
            ->where('version_key', 'current')
            ->where('name', $this->normalizeXmlDocumentName($libraryName, 'library_metadata'))
            ->where('kind', 'library_metadata')
            ->first(['xml_content']);

        return $row !== null && $this->libraryMetadataHasUsefulOffsets((string) $row->xml_content);
    }

    public function libraryMetadataHasUsefulOffsets(string $rawJson): bool
    {
        $metadata = json_decode($rawJson, true);

        if (!is_array($metadata)) {
            return false;
        }

        $hasBitmap = false;

        foreach (array_slice(array_values($metadata['bitmaps'] ?? []), 0, 120) as $bitmap) {
            $hasBitmap = true;

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

        return !$hasBitmap && !empty($metadata['manifest_assets']);
    }

    private function blobRowsHaveUsefulOffsets($rows): bool
    {
        foreach ($rows as $row) {
            if (
                ((int) $row->width > 0 || (int) $row->height > 0)
                && ((int) $row->offset_x !== 0 || (int) $row->offset_y !== 0)
            ) {
                return true;
            }
        }

        return false;
    }

    public function getRenderMetadata(string $renderHash): ?array
    {
        $row = DB::table('habbo_imaging_render_blobs')
            ->where('render_hash', $renderHash)
            ->first(['metadata']);

        if (!$row || !$row->metadata) {
            return null;
        }

        $metadata = json_decode((string) $row->metadata, true);
        return is_array($metadata) ? $metadata : null;
    }

    public function storeXmlDocument(string $versionKey, string $name, string $kind, string $xmlContent, array $metadata = []): void
    {
        $versionKey = 'current';
        $name = $this->normalizeXmlDocumentName($name, $kind);

        $payload = [
            'kind' => $kind,
            'xml_content' => $xmlContent,
            'metadata' => !empty($metadata) ? json_encode($metadata) : null,
            'updated_at' => now(),
        ];

        DB::table('habbo_imaging_xml_documents')->upsert(
            [$payload + [
                'version_key' => $versionKey,
                'name' => $name,
            ]],
            ['version_key', 'name'],
            ['kind', 'xml_content', 'metadata', 'updated_at']
        );
    }

    private function normalizeXmlDocumentName(string $name, string $kind): string
    {
        $name = trim($name);

        if ($kind === 'library_metadata') {
            return preg_replace('/_extracted$/', '', $name) ?: $name;
        }

        return $name;
    }

    public function ensureTablesExist(): void
    {
        if (!DB::connection()->getSchemaBuilder()->hasTable('habbo_imaging_asset_blobs')) {
            throw new \RuntimeException('habbo_imaging_asset_blobs table does not exist. Run migrations.');
        }
        if (!DB::connection()->getSchemaBuilder()->hasTable('habbo_imaging_render_blobs')) {
            throw new \RuntimeException('habbo_imaging_render_blobs table does not exist. Run migrations.');
        }
    }
}
