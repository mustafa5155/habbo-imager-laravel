<?php

namespace App\Support\HabboImaging;

class HabboImagingSyncLauncher
{
    public function capabilities(): array
    {
        $phpBinary = PHP_BINARY ?: null;

        if (!$phpBinary || !file_exists($phpBinary)) {
            return [
                'supported' => false,
                'method' => null,
                'reason' => 'PHP binary could not be resolved for background launch.',
                'manual_command' => 'php artisan habbo-imaging:sync --batch=100',
                'cron_command' => '* * * * * php /path/to/artisan habbo-imaging:sync --batch=100',
            ];
        }

        $artisan = base_path('artisan');

        if (!file_exists($artisan)) {
            return [
                'supported' => false,
                'method' => null,
                'reason' => 'Artisan entrypoint is missing.',
                'manual_command' => 'php artisan habbo-imaging:sync --batch=100',
                'cron_command' => '* * * * * php /path/to/artisan habbo-imaging:sync --batch=100',
            ];
        }

        if (DIRECTORY_SEPARATOR === '\\') {
            if ($this->isFunctionDisabled('popen')) {
                return [
                    'supported' => false,
                    'method' => null,
                    'reason' => 'Background launch needs popen on Windows, and it is disabled here.',
                    'manual_command' => 'php artisan habbo-imaging:sync --batch=100',
                    'cron_command' => 'powershell -Command "php artisan habbo-imaging:sync --batch=100"',
                ];
            }

            return [
                'supported' => true,
                'method' => 'popen_windows_start',
                'reason' => null,
                'manual_command' => 'php artisan habbo-imaging:sync --batch=100',
                'cron_command' => 'powershell -Command "php artisan habbo-imaging:sync --batch=100"',
            ];
        }

        if ($this->isFunctionDisabled('exec')) {
            return [
                'supported' => false,
                'method' => null,
                'reason' => 'Background launch needs exec on this host, and it is disabled here.',
                'manual_command' => 'php artisan habbo-imaging:sync --batch=100',
                'cron_command' => '* * * * * php /path/to/artisan habbo-imaging:sync --batch=100',
            ];
        }

        return [
            'supported' => true,
            'method' => 'exec_unix_background',
            'reason' => null,
            'manual_command' => 'php artisan habbo-imaging:sync --batch=100',
            'cron_command' => '* * * * * php /path/to/artisan habbo-imaging:sync --batch=100',
        ];
    }

    public function launch(): bool
    {
        $capabilities = $this->capabilities();

        if (!$capabilities['supported']) {
            return false;
        }

        $phpBinary = PHP_BINARY ?: null;

        $artisan = base_path('artisan');

        if (DIRECTORY_SEPARATOR === '\\') {
            $command = 'start /B "" ' . escapeshellarg($phpBinary) . ' ' . escapeshellarg($artisan) . ' habbo-imaging:sync';

            try {
                $process = @popen($command, 'r');

                if (is_resource($process)) {
                    pclose($process);
                    return true;
                }
            } catch (\Throwable) {
                return false;
            }

            return false;
        }

        $command = 'cd ' . escapeshellarg(base_path()) . ' && ' . escapeshellarg($phpBinary) . ' artisan habbo-imaging:sync > /dev/null 2>&1 &';

        try {
            @exec($command);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function isFunctionDisabled(string $function): bool
    {
        $disabled = array_filter(array_map('trim', explode(',', (string) ini_get('disable_functions'))));
        return in_array($function, $disabled, true) || !function_exists($function);
    }
}
