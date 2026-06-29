<?php

namespace App\Support\HabboImaging;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

class HabboImagingSwfExtractor
{
    private ?HabboImagingAssetRepository $repository = null;

    public function __construct(?HabboImagingAssetRepository $repository = null)
    {
        $this->repository = $repository;
    }

    public function extract(string $sourcePath, string $targetDirectory, string $versionKey = 'unknown'): array
    {
        $disk = Storage::disk('local');

        if (!$disk->exists($sourcePath)) {
            throw new RuntimeException('SWF source file does not exist.');
        }

        $data = (string) $disk->get($sourcePath);
        $swf = $this->normalizeSwf($data);
        [$bitmaps, $symbolClasses, $binaryDataTags] = $this->parseSwf($swf);
        $manifestAssets = $this->parseManifestAssets($binaryDataTags, $symbolClasses);
        $binaryXmlDocuments = $this->parseBinaryXmlDocuments($binaryDataTags, $symbolClasses);

        $files = [];
        $metadata = [
            'type' => 'swf',
            'symbol_classes' => $symbolClasses,
            'symbol_to_character' => $this->buildSymbolToCharacterMap($symbolClasses),
            'character_aliases' => $this->normalizeCharacterAliases($symbolClasses),
            'manifest_assets' => $manifestAssets,
            'xml_documents' => [],
            'bitmaps' => [],
        ];

        $newBlobsStored = 0;
        $existingBlobsSkipped = 0;
        $totalBitmaps = count($bitmaps);

        if (!empty($binaryXmlDocuments) && $this->repository !== null) {
            foreach ($binaryXmlDocuments as $document) {
                $xmlContent = (string) ($document['xml'] ?? '');
                $this->repository->storeXmlDocument(
                    $versionKey,
                    $document['name'],
                    $document['kind'],
                    $xmlContent,
                    [
                        'root' => $document['root'],
                        'character_id' => $document['character_id'],
                        'symbol_names' => $document['symbol_names'],
                    ]
                );
                $metadata['xml_documents'][] = [
                    'name' => $document['name'],
                    'kind' => $document['kind'],
                    'root' => $document['root'],
                    'character_id' => $document['character_id'],
                    'symbol_names' => $document['symbol_names'],
                    'stored_in_db' => true,
                ];
            }
        }

        foreach ($bitmaps as $bitmap) {
            $symbolNames = $symbolClasses[$bitmap['character_id']] ?? [];

            if (!is_array($symbolNames)) {
                $symbolNames = $symbolNames !== null ? [(string) $symbolNames] : [];
            }

            if (empty($symbolNames)) {
                $symbolNames = ['character_' . $bitmap['character_id']];
            }

            foreach (array_values(array_unique($symbolNames)) as $symbolName) {
                $manifestAsset = $this->manifestAssetForSymbol($manifestAssets, $symbolName);

                $blobAlreadyExists = $this->repository !== null
                    && $this->repository->findBySymbol($symbolName) !== null;

                if ($blobAlreadyExists) {
                    $this->repository->updateAssetMetadata($symbolName, [
                        'character_id' => $bitmap['character_id'],
                        'width' => $bitmap['width'],
                        'height' => $bitmap['height'],
                        'format' => $bitmap['format'],
                        'offset_x' => $manifestAsset['offset_x'] ?? null,
                        'offset_y' => $manifestAsset['offset_y'] ?? null,
                    ]);

                    $existingBlobsSkipped++;
                    $files[] = "blob:{$symbolName}";
                    $metadata['bitmaps'][] = [
                        'character_id' => $bitmap['character_id'],
                        'symbol_name' => $symbolName,
                        'width' => $bitmap['width'],
                        'height' => $bitmap['height'],
                        'format' => $bitmap['format'],
                        'stored_in_db' => true,
                        'offset_x' => $manifestAsset['offset_x'] ?? null,
                        'offset_y' => $manifestAsset['offset_y'] ?? null,
                        'already_existed' => true,
                    ];
                    continue;
                }

                $pngBlob = $this->writeBitmapPngToBlob($bitmap);

                if ($this->repository !== null && $pngBlob !== null) {
                    $this->repository->storeAsset($versionKey, $symbolName, $pngBlob, [
                        'character_id' => $bitmap['character_id'],
                        'width' => $bitmap['width'],
                        'height' => $bitmap['height'],
                        'format' => $bitmap['format'],
                        'offset_x' => $manifestAsset['offset_x'] ?? null,
                        'offset_y' => $manifestAsset['offset_y'] ?? null,
                    ]);
                    $newBlobsStored++;
                }

                $files[] = "blob:{$symbolName}";
                $metadata['bitmaps'][] = [
                    'character_id' => $bitmap['character_id'],
                    'symbol_name' => $symbolName,
                    'width' => $bitmap['width'],
                    'height' => $bitmap['height'],
                    'format' => $bitmap['format'],
                    'stored_in_db' => true,
                    'offset_x' => $manifestAsset['offset_x'] ?? null,
                    'offset_y' => $manifestAsset['offset_y'] ?? null,
                    'already_existed' => false,
                ];
            }
        }

        if ($this->repository !== null && !empty($metadata['bitmaps'])) {
            $libraryName    = basename($targetDirectory);
            $metadataJson   = json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            if ($metadataJson !== false && $metadataJson !== '') {
                $this->repository->storeXmlDocument(
                    $versionKey,
                    $libraryName,
                    'library_metadata',
                    $metadataJson,
                    [
                        'bitmap_count'  => count($metadata['bitmaps']),
                        'symbol_count'  => count($metadata['symbol_classes'] ?? []),
                        'total_processed' => $totalBitmaps,
                        'new_blobs_stored' => $newBlobsStored,
                        'existing_blobs_skipped' => $existingBlobsSkipped,
                        'extracted_at'  => now()->toDateTimeString(),
                    ]
                );
            }
        }

        if ($disk->exists($sourcePath)) {
            $disk->delete($sourcePath);
        }

        return [
            'files'    => $files,
            'metadata' => $metadata,
            'stats'    => [
                'total_bitmaps' => $totalBitmaps,
                'new_blobs_stored' => $newBlobsStored,
                'existing_blobs_skipped' => $existingBlobsSkipped,
                'xml_documents' => count($metadata['xml_documents'] ?? []),
            ],
        ];
    }

    public function inspectManifest(string $sourcePath): array
    {
        $disk = Storage::disk('local');

        if (!$disk->exists($sourcePath)) {
            return [];
        }

        $swf = $this->normalizeSwf((string) $disk->get($sourcePath));
        [, $symbolClasses, $binaryDataTags] = $this->parseSwf($swf);

        return $this->parseManifestAssets($binaryDataTags, $symbolClasses);
    }

    public function retrimExtractedHorizontalBlobs(string $targetDirectory, ?string $symbolPrefix = null): array
    {
        $disk = Storage::disk('local');
        $metadataPath = "{$targetDirectory}/metadata.json";

        if (!$disk->exists($metadataPath)) {
            throw new RuntimeException('metadata.json was not found for extracted asset directory.');
        }

        $metadata = json_decode((string) $disk->get($metadataPath), true);

        if (!is_array($metadata)) {
            throw new RuntimeException('metadata.json is invalid.');
        }

        $bitmaps = $metadata['bitmaps'] ?? [];
        $updated = [];
        $skipped = 0;

        foreach ($bitmaps as $bitmap) {
            $symbolName = (string) ($bitmap['symbol_name'] ?? '');
            $relativePath = (string) ($bitmap['path'] ?? '');

            if ($symbolPrefix !== null && !str_starts_with($symbolName, $symbolPrefix)) {
                $skipped++;
                continue;
            }

            if ($relativePath === '' || !$disk->exists($relativePath)) {
                $skipped++;
                continue;
            }

            $absolutePath = $disk->path($relativePath);
            $image = @imagecreatefrompng($absolutePath);

            if (!$image) {
                $skipped++;
                continue;
            }

            try {
                imagealphablending($image, false);
                imagesavealpha($image, true);

                $trim = $this->trimHorizontalTransparentBounds($image);

                if ($trim === null) {
                    $skipped++;
                    continue;
                }

                [$left, $right] = $trim;

                $oldWidth = imagesx($image);
                $oldHeight = imagesy($image);
                $newWidth = $right - $left + 1;

                if ($newWidth <= 0 || $newWidth === $oldWidth) {
                    $skipped++;
                    continue;
                }

                $cropped = imagecreatetruecolor($newWidth, $oldHeight);

                if ($cropped === false) {
                    $skipped++;
                    continue;
                }

                imagealphablending($cropped, false);
                imagesavealpha($cropped, true);
                $transparent = imagecolorallocatealpha($cropped, 0, 0, 0, 127);
                imagefill($cropped, 0, 0, $transparent);

                imagecopy(
                    $cropped,
                    $image,
                    0,
                    0,
                    $left,
                    0,
                    $newWidth,
                    $oldHeight
                );

                imagepng($cropped, $absolutePath);
                imagedestroy($cropped);

                $updated[] = [
                    'symbol_name' => $symbolName,
                    'path' => $relativePath,
                    'old_width' => $oldWidth,
                    'new_width' => $newWidth,
                    'trim_left' => $left,
                    'trim_right' => ($oldWidth - 1 - $right),
                ];
            } finally {
                imagedestroy($image);
            }
        }

        return [
            'target_directory' => $targetDirectory,
            'symbol_prefix' => $symbolPrefix,
            'updated_count' => count($updated),
            'skipped_count' => $skipped,
            'updated' => $updated,
        ];
    }

    private function trimHorizontalTransparentBounds($image): ?array
    {
        $width = imagesx($image);
        $height = imagesy($image);

        $minX = null;
        $maxX = null;

        $alphaThreshold = 118;
        $minVisiblePixelsPerColumn = 3;

        for ($x = 0; $x < $width; $x++) {
            $visiblePixels = 0;

            for ($y = 0; $y < $height; $y++) {
                $rgba = imagecolorat($image, $x, $y);
                $alpha = ($rgba >> 24) & 0x7F;

                if ($alpha <= $alphaThreshold) {
                    $visiblePixels++;

                    if ($visiblePixels >= $minVisiblePixelsPerColumn) {
                        $minX = $minX === null ? $x : min($minX, $x);
                        $maxX = $maxX === null ? $x : max($maxX, $x);
                        break;
                    }
                }
            }
        }

        if ($minX === null || $maxX === null) {
            return null;
        }

        return [$minX, $maxX];
    }

    private function normalizeSwf(string $data): string
    {
        $signature = substr($data, 0, 3);

        if ($signature === 'FWS') {
            return $data;
        }

        if ($signature === 'CWS') {
            $decompressed = gzuncompress(substr($data, 8));

            if ($decompressed === false) {
                throw new RuntimeException('Unable to decompress CWS SWF payload.');
            }

            return 'FWS' . substr($data, 3, 5) . $decompressed;
        }

        throw new RuntimeException('Unsupported SWF signature: ' . $signature);
    }

    private function parseSwf(string $data): array
    {
        $length = strlen($data);

        if ($length < 16) {
            throw new RuntimeException('SWF payload is too small.');
        }

        $position = 8;
        $firstRectByte = ord($data[$position]);
        $nbits = $firstRectByte >> 3;
        $rectBits = 5 + ($nbits * 4);
        $rectBytes = (int) ceil($rectBits / 8);
        $position += $rectBytes + 4;

        $bitmaps = [];
        $symbolClasses = [];
        $binaryDataTags = [];

        while ($position + 2 <= $length) {
            $recordHeader = unpack('v', substr($data, $position, 2))[1];
            $position += 2;
            $tagCode = $recordHeader >> 6;
            $smallLength = $recordHeader & 0x3f;
            $tagLength = $smallLength === 0x3f ? unpack('V', substr($data, $position, 4))[1] : $smallLength;

            if ($smallLength === 0x3f) {
                $position += 4;
            }

            $tagBody = substr($data, $position, $tagLength);

            if (in_array($tagCode, [20, 36], true)) {
                $bitmaps[] = $this->parseLosslessBitmap($tagBody, $tagCode);
            } elseif ($tagCode === 76) {
                $symbolClasses = $this->mergeSymbolClasses($symbolClasses, $this->parseSymbolClass($tagBody));
            } elseif ($tagCode === 87) {
                $binaryDataTags[] = $this->parseBinaryData($tagBody);
            }

            $position += $tagLength;

            if ($tagCode === 0) {
                break;
            }
        }

        return [$bitmaps, $symbolClasses, $binaryDataTags];
    }

    private function parseSymbolClass(string $body): array
    {
        $count = unpack('v', substr($body, 0, 2))[1] ?? 0;
        $cursor = 2;
        $map = [];

        for ($index = 0; $index < $count; $index++) {
            $characterId = unpack('v', substr($body, $cursor, 2))[1] ?? 0;
            $cursor += 2;
            $end = strpos($body, "\0", $cursor);

            if ($end === false) {
                break;
            }

            $name = substr($body, $cursor, $end - $cursor);
            $cursor = $end + 1;
            $map[$characterId] ??= [];
            $map[$characterId][] = $name;
        }

        return $map;
    }

    private function mergeSymbolClasses(array $existing, array $incoming): array
    {
        foreach ($incoming as $characterId => $names) {
            $existing[$characterId] ??= [];

            foreach ((array) $names as $name) {
                if (!in_array($name, $existing[$characterId], true)) {
                    $existing[$characterId][] = $name;
                }
            }
        }

        return $existing;
    }

    private function parseBinaryData(string $body): array
    {
        return [
            'character_id' => unpack('v', substr($body, 0, 2))[1] ?? 0,
            'reserved' => unpack('V', substr($body, 2, 4))[1] ?? 0,
            'data' => substr($body, 6),
        ];
    }

    private function parseManifestAssets(array $binaryDataTags, array $symbolClasses): array
    {
        $assets = [];

        foreach ($binaryDataTags as $tag) {
            $data = (string) ($tag['data'] ?? '');

            if (!str_contains($data, '<manifest')) {
                continue;
            }

            $xmlPayload = $this->extractXmlPayload($data);

            if ($xmlPayload === null) {
                continue;
            }

            $xml = @simplexml_load_string($xmlPayload);

            if (!$xml || !isset($xml->library->assets->asset)) {
                continue;
            }

            $libraryName = trim((string) ($xml->library['name'] ?? $this->primarySymbolName($symbolClasses[$tag['character_id']] ?? [])));

            foreach ($xml->library->assets->asset as $asset) {
                $name = trim((string) ($asset['name'] ?? ''));

                if ($name === '') {
                    continue;
                }

                $entry = [
                    'name' => $name,
                    'library' => $libraryName,
                ];

                foreach ($asset->param as $param) {
                    $key = trim((string) ($param['key'] ?? ''));
                    $value = trim((string) ($param['value'] ?? ''));

                    if ($key === '') {
                        continue;
                    }

                    $entry[$key] = $value;

                    if ($key === 'offset') {
                        [$offsetX, $offsetY] = array_pad(array_map('trim', explode(',', $value, 2)), 2, '0');
                        $entry['offset_x'] = (int) $offsetX;
                        $entry['offset_y'] = (int) $offsetY;
                    }
                }

                $assets[$name] = $entry;

                if ($libraryName !== '') {
                    $assets[$libraryName . '_' . $name] = $entry;
                }
            }
        }

        return $assets;
    }

    private function parseBinaryXmlDocuments(array $binaryDataTags, array $symbolClasses): array
    {
        $documents = [];

        foreach ($binaryDataTags as $tag) {
            $data = (string) ($tag['data'] ?? '');
            $xmlPayload = $this->extractXmlPayload($data);

            if ($xmlPayload === null) {
                continue;
            }

            $xml = @simplexml_load_string($xmlPayload);

            if (!$xml) {
                continue;
            }

            $symbolNames = array_values(array_unique(array_map('strval', $symbolClasses[$tag['character_id']] ?? [])));
            $primarySymbolName = $this->primarySymbolName($symbolNames);

            $documents[] = [
                'name' => $primarySymbolName !== '' ? $primarySymbolName : ('binary_' . (int) ($tag['character_id'] ?? 0)),
                'kind' => $this->inferXmlDocumentKind($primarySymbolName, $xml),
                'root' => $xml->getName(),
                'character_id' => (int) ($tag['character_id'] ?? 0),
                'symbol_names' => $symbolNames,
                'xml' => $xmlPayload,
            ];
        }

        return $documents;
    }

    private function inferXmlDocumentKind(string $symbolName, \SimpleXMLElement $xml): string
    {
        if (preg_match('/_(manifest|assets|index|visualization|logic)$/i', $symbolName, $matches)) {
            return strtolower((string) $matches[1]);
        }

        return strtolower((string) $xml->getName());
    }

    private function buildSymbolToCharacterMap(array $symbolClasses): array
    {
        $symbolToCharacter = [];

        foreach ($symbolClasses as $characterId => $symbolNames) {
            foreach ((array) $symbolNames as $symbolName) {
                $symbolName = (string) $symbolName;

                if ($symbolName === '') {
                    continue;
                }

                $symbolToCharacter[$symbolName] = (int) $characterId;
            }
        }

        ksort($symbolToCharacter);

        return $symbolToCharacter;
    }

    private function normalizeCharacterAliases(array $symbolClasses): array
    {
        $aliases = [];

        foreach ($symbolClasses as $characterId => $symbolNames) {
            $normalizedNames = array_values(array_unique(array_map('strval', (array) $symbolNames)));
            sort($normalizedNames);
            $aliases[(string) (int) $characterId] = $normalizedNames;
        }

        ksort($aliases);

        return $aliases;
    }

    private function extractXmlPayload(string $data): ?string
    {
        $start = strpos($data, '<?xml');
        $end = strrpos($data, '>');

        if ($start === false || $end === false || $end < $start) {
            return null;
        }

        return substr($data, $start, ($end - $start) + 1);
    }

    private function manifestAssetForSymbol(array $manifestAssets, ?string $symbolName): ?array
    {
        if (!$symbolName) {
            return null;
        }

        return $manifestAssets[$symbolName] ?? null;
    }

    private function primarySymbolName(array|string|null $symbols): string
    {
        if (is_string($symbols)) {
            return $symbols;
        }

        if (!is_array($symbols) || empty($symbols)) {
            return '';
        }

        return (string) reset($symbols);
    }

    private function sanitizeFileName(string $value): string
    {
        $value = preg_replace('/[^A-Za-z0-9._-]+/', '_', $value) ?: 'document';

        return trim($value, '._-') !== '' ? trim($value, '._-') : 'document';
    }

    private function parseLosslessBitmap(string $body, int $tagCode): array
    {
        $characterId = unpack('v', substr($body, 0, 2))[1];
        $format = ord($body[2]);
        $width = unpack('v', substr($body, 3, 2))[1];
        $height = unpack('v', substr($body, 5, 2))[1];
        $cursor = 7;
        $colorTableSize = null;

        if ($format === 3) {
            $colorTableSize = ord($body[$cursor]) + 1;
            $cursor++;
        }

        $inflated = gzuncompress(substr($body, $cursor));

        if ($inflated === false) {
            throw new RuntimeException('Unable to inflate lossless bitmap data.');
        }

        return [
            'tag_code' => $tagCode,
            'character_id' => $characterId,
            'format' => $format,
            'width' => $width,
            'height' => $height,
            'color_table_size' => $colorTableSize,
            'data' => $inflated,
        ];
    }

    private function writeBitmapPngToBlob(array $bitmap): ?string
    {
        if (!function_exists('imagecreatetruecolor')) {
            throw new RuntimeException('GD extension is required for SWF bitmap extraction.');
        }

        $width = (int) $bitmap['width'];
        $height = (int) $bitmap['height'];
        $format = (int) $bitmap['format'];
        $image = imagecreatetruecolor($width, $height);

        if ($image === false) {
            throw new RuntimeException('Unable to create GD image canvas.');
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);

        try {
            if ($format === 5) {
                $this->renderArgbBitmap($image, $bitmap['data'], $width, $height);
            } elseif ($format === 3) {
                $this->renderColorMappedBitmap($image, $bitmap['data'], $width, $height, (int) ($bitmap['color_table_size'] ?? 0));
            } else {
                throw new RuntimeException('Unsupported SWF bitmap format: ' . $format);
            }

            ob_start();
            imagepng($image);
            $blob = ob_get_clean();

            return $blob !== false ? $blob : null;
        } finally {
            imagedestroy($image);
        }
    }

    private function writeBitmapPng(array $bitmap, string $absolutePath): void
    {
        $blob = $this->writeBitmapPngToBlob($bitmap);

        if ($blob !== null) {
            file_put_contents($absolutePath, $blob);
        }
    }

    private function renderArgbBitmap($image, string $data, int $width, int $height): void
    {
        $cursor = 0;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $alpha = ord($data[$cursor++]);
                $red = ord($data[$cursor++]);
                $green = ord($data[$cursor++]);
                $blue = ord($data[$cursor++]);

                $gdAlpha = 127 - (int) round(($alpha / 255) * 127);
                $color = imagecolorallocatealpha($image, $red, $green, $blue, $gdAlpha);
                imagesetpixel($image, $x, $y, $color);
            }
        }
    }

    private function renderColorMappedBitmap($image, string $data, int $width, int $height, int $colorTableSize): void
    {
        $palette = [];
        $cursor = 0;

        for ($index = 0; $index < $colorTableSize; $index++) {
            $red = ord($data[$cursor++]);
            $green = ord($data[$cursor++]);
            $blue = ord($data[$cursor++]);
            $alpha = ord($data[$cursor++]);
            $palette[$index] = [$red, $green, $blue, $alpha];
        }

        $rowLength = (int) ceil($width / 4) * 4;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $paletteIndex = ord($data[$cursor++]);
                [$red, $green, $blue, $alpha] = $palette[$paletteIndex] ?? [0, 0, 0, 0];
                $gdAlpha = 127 - (int) round(($alpha / 255) * 127);
                $color = imagecolorallocatealpha($image, $red, $green, $blue, $gdAlpha);
                imagesetpixel($image, $x, $y, $color);
            }

            $padding = $rowLength - $width;
            $cursor += $padding;
        }
    }
}
