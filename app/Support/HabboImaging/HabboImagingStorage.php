<?php

namespace App\Support\HabboImaging;

use Illuminate\Support\Facades\Storage;

class HabboImagingStorage
{
    public function ensureStructure(): void
    {
        foreach ($this->directories() as $directory) {
            Storage::disk('local')->makeDirectory($directory);
        }
    }

    public function directories(): array
    {
        return [
            'habbo-imaging/source',
            'habbo-imaging/parsed',
            'habbo-imaging/renders',
            'habbo-imaging/tmp',
            'habbo-imaging/locks',
        ];
    }

    public function manifestPath(): string
    {
        return 'habbo-imaging/manifest.json';
    }

    public function ensureVersionDirectories(string $versionKey): array
    {
        $directories = [
            'source' => $this->versionSourceDirectory($versionKey),
            'parsed' => $this->versionParsedDirectory($versionKey),
            'renders' => $this->versionRenderDirectory($versionKey),
        ];

        foreach ($directories as $directory) {
            Storage::disk('local')->makeDirectory($directory);
        }

        Storage::disk('local')->makeDirectory("{$directories['source']}/libraries");
        Storage::disk('local')->makeDirectory("{$directories['parsed']}/libraries");

        return $directories;
    }

    public function versionSourceDirectory(string $versionKey): string
    {
        if ($versionKey === 'current') {
            return 'habbo-imaging/source';
        }

        return "habbo-imaging/source/{$versionKey}";
    }

    public function versionParsedDirectory(string $versionKey): string
    {
        if ($versionKey === 'current') {
            return 'habbo-imaging/parsed';
        }

        return "habbo-imaging/parsed/{$versionKey}";
    }

    public function versionRenderDirectory(string $versionKey): string
    {
        if ($versionKey === 'current') {
            return 'habbo-imaging/renders';
        }

        return "habbo-imaging/renders/{$versionKey}";
    }

    public function assetSourcePath(string $versionKey, string $libraryName, string $extension): string
    {
        return $this->versionSourceDirectory($versionKey) . '/libraries/' . $libraryName . '.' . $extension;
    }

    public function assetExtractedDirectory(string $versionKey, string $libraryName): string
    {
        return $this->versionParsedDirectory($versionKey) . '/libraries/' . $libraryName;
    }

    public function ensureTablesExist(): void
    {
        $repository = new HabboImagingAssetRepository();
        $repository->ensureTablesExist();
    }
}
