<?php

namespace App\Support\HabboImaging;

use Illuminate\Support\Facades\Storage;

class HabboImagingLock
{
    private const STALE_MINUTES = 30;

    public function __construct(
        private readonly HabboImagingStorage $storage,
    ) {
    }

    public function isLocked(): bool
    {
        $path = 'habbo-imaging/locks/sync.lock';

        if (!Storage::disk('local')->exists($path)) {
            return false;
        }

        $payload = trim((string) Storage::disk('local')->get($path));
        $lockedAt = $payload !== '' ? strtotime($payload) : false;

        if ($lockedAt !== false && $lockedAt < now()->subMinutes(self::STALE_MINUTES)->getTimestamp()) {
            Storage::disk('local')->delete($path);
            return false;
        }

        return true;
    }

    public function acquire(): void
    {
        $this->storage->ensureStructure();
        Storage::disk('local')->put('habbo-imaging/locks/sync.lock', now()->toIso8601String());
    }

    public function release(): void
    {
        Storage::disk('local')->delete('habbo-imaging/locks/sync.lock');
    }
}
