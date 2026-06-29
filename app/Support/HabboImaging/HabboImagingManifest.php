<?php

namespace App\Support\HabboImaging;

use App\Models\HabboImagingVersion;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class HabboImagingManifest
{
    public function __construct(
        private readonly HabboImagingStorage $storage,
        private readonly HabboImagingLock $lock,
        private readonly HabboImagingSyncLauncher $launcher,
    ) {
    }

    public function status(): array
    {
        $this->storage->ensureStructure();

        if (!Storage::disk('local')->exists($this->storage->manifestPath())) {
            $this->write($this->defaultManifest());
        }

        $manifest = json_decode((string) Storage::disk('local')->get($this->storage->manifestPath()), true) ?: [];
        $manifest += $this->defaultManifest();
        $manifest['locked'] = $this->lock->isLocked();
        $manifest['directories'] = $this->storage->directories();
        $manifest['latest_version'] = $this->latestVersionSummary();
        $manifest['launcher'] = $this->launcher->capabilities();

        return $manifest;
    }

    public function requestRefreshIfStale(int $minutes = 360): array
    {
        $manifest = $this->status();
        $lastCheckedAt = $manifest['last_checked_at'] ? strtotime((string) $manifest['last_checked_at']) : false;
        $isStale = !$lastCheckedAt || $lastCheckedAt < now()->subMinutes($minutes)->getTimestamp();

        if ($isStale && !$this->lock->isLocked()) {
            $manifest['status'] = 'refresh_requested';
            $manifest['refresh_requested_at'] = now()->toIso8601String();
            $manifest['last_checked_at'] = now()->toIso8601String();
            $manifest['last_launch_attempt_at'] = now()->toIso8601String();
            $manifest['background_launch_attempted'] = $this->launcher->launch();
            $this->write($manifest);
        }

        return $this->status();
    }

    public function requestRefresh(): array
    {
        $manifest = $this->status();

        if (!$this->lock->isLocked()) {
            $manifest['status'] = 'refresh_requested';
            $manifest['refresh_requested_at'] = now()->toIso8601String();
            $manifest['last_checked_at'] = now()->toIso8601String();
            $manifest['last_launch_attempt_at'] = now()->toIso8601String();
            $manifest['background_launch_attempted'] = $this->launcher->launch();
            $this->write($manifest);
        }

        return $this->status();
    }

    public function markSyncStarted(): array
    {
        $manifest = $this->status();
        $manifest['status'] = 'syncing';
        $manifest['last_checked_at'] = now()->toIso8601String();
        $manifest['last_sync_started_at'] = now()->toIso8601String();
        $manifest['last_error'] = null;
        $this->write($manifest);

        return $this->status();
    }

    public function markSyncFinished(array $summary): array
    {
        $manifest = $this->status();
        $manifest['status'] = $summary['status'] ?? 'ready';
        $manifest['current_source_version'] = $summary['source_version'] ?? $manifest['current_source_version'];
        $manifest['current_parsed_version'] = $summary['source_version'] ?? $manifest['current_parsed_version'];
        $manifest['last_checked_at'] = now()->toIso8601String();
        $manifest['last_synced_at'] = now()->toIso8601String();
        $manifest['last_sync_summary'] = $summary;
        $manifest['last_error'] = null;
        $this->write($manifest);

        return $this->status();
    }

    public function markSyncFailed(Throwable $exception): array
    {
        $manifest = $this->status();
        $manifest['status'] = 'failed';
        $manifest['last_checked_at'] = now()->toIso8601String();
        $manifest['last_error'] = $exception->getMessage();
        $this->write($manifest);

        return $this->status();
    }

    private function write(array $manifest): void
    {
        Storage::disk('local')->put(
            $this->storage->manifestPath(),
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    private function defaultManifest(): array
    {
        return [
            'status' => 'idle',
            'current_source_version' => null,
            'current_parsed_version' => null,
            'last_checked_at' => null,
            'last_synced_at' => null,
            'refresh_requested_at' => null,
            'last_sync_started_at' => null,
            'last_sync_summary' => null,
            'background_launch_attempted' => false,
            'last_launch_attempt_at' => null,
            'last_error' => null,
        ];
    }

    private function latestVersionSummary(): ?array
    {
        if (!Schema::hasTable('habbo_imaging_versions')) {
            return null;
        }

        $version = HabboImagingVersion::query()
            ->orderByDesc('synced_at')
            ->orderByDesc('id')
            ->first();

        if (!$version) {
            return null;
        }

        return [
            'id' => $version->getKey(),
            'source_version' => $version->source_version,
            'status' => $version->status,
            'synced_at' => optional($version->synced_at)->toIso8601String(),
            'metadata' => $version->metadata,
        ];
    }
}
