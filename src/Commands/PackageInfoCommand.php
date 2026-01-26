<?php

declare(strict_types=1);

namespace Foxws\AV1\Commands;

use Foxws\AV1\Support\AbAV1Encoder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\table;

class PackageInfoCommand extends Command
{
    protected $signature = 'av1:info';

    protected $description = 'Display Laravel AV1 package information';

    public function handle(): int
    {
        info('Laravel AV1');

        // Package version
        $composerPath = base_path('vendor/foxws/laravel-av1/composer.json');

        $packageVersion = 'dev-main';

        if (file_exists($composerPath)) {
            $composer = json_decode(file_get_contents($composerPath), true);
            $packageVersion = $composer['version'] ?? 'dev-main';
        }

        // ab-av1 version
        $abav1Version = 'Not available';

        try {
            $encoder = AbAV1Encoder::create(null, config('av1'));
            $abav1Version = $encoder->getVersion();
        } catch (\Exception $e) {
            // Keep as "Not available"
        }

        note("Package Version: {$packageVersion}");
        note("ab-av1 Version: {$abav1Version}");

        // Configuration table
        $config = app('laravel-av1-configuration');
        $binaryPath = $config['binaries']['ab-av1'] ?? 'ab-av1';
        $logChannel = $config['log_channel'] ?? null;
        $logStatus = $logChannel === false ? 'Disabled' : ($logChannel ?: 'Default');

        table(
            ['Configuration', 'Value'],
            [
                ['Binary Path', $binaryPath],
                ['Timeout', ($config['ab-av1']['timeout'] ?? 'N/A').' seconds'],
                ['Default Preset', $config['ab-av1']['preset'] ?? 'N/A'],
                ['Min VMAF', $config['ab-av1']['min_vmaf'] ?? 'N/A'],
                ['Temp Directory', $config['temporary_files_root'] ?? 'N/A'],
                ['Logging', $logStatus],
            ]
        );

        return self::SUCCESS;
    }
}
