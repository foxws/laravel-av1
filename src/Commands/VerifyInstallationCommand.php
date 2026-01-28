<?php

declare(strict_types=1);

namespace Foxws\AV1\Commands;

use Foxws\AV1\Exceptions\ExecutableNotFoundException;
use Foxws\AV1\AbAV1\AbAV1Encoder;
use Illuminate\Console\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;
use function Laravel\Prompts\warning;

class VerifyInstallationCommand extends Command
{
    protected $signature = 'av1:verify';

    protected $description = 'Verify ab-av1 installation and configuration';

    public function handle(): int
    {
        info('🔍 Verifying ab-av1 installation...');

        $config = app('laravel-av1-configuration');
        $binaryPath = $config['binaries']['ab-av1'] ?? 'ab-av1';

        note("Binary Path: {$binaryPath}");

        // Check if binary is in PATH or absolute path exists
        $binaryExists = $this->checkBinaryExists($binaryPath);

        if (! $binaryExists) {
            error("Binary not found: {$binaryPath}");
            warning('Please ensure ab-av1 is installed and in your PATH or specify the full path.');
            note('Install with: cargo install ab-av1');
            note('Or download from: https://github.com/alexheretic/ab-av1/releases');

            return self::FAILURE;
        }

        $this->components->info('Binary exists');

        // Try to get version with spinner
        try {
            $version = spin(
                fn () => $this->getVersion($binaryPath),
                'Checking ab-av1 version...'
            );

            $this->components->info("Version: {$version}");
        } catch (ExecutableNotFoundException $e) {
            error('Cannot execute binary');
            error($e->getMessage());

            return self::FAILURE;
        } catch (\Exception $e) {
            error('Error getting version');
            error($e->getMessage());

            return self::FAILURE;
        }

        // Configuration details
        $timeout = $config['ab-av1']['timeout'] ?? 'N/A';
        $preset = $config['ab-av1']['preset'] ?? 'N/A';
        $minVmaf = $config['ab-av1']['min_vmaf'] ?? 'N/A';
        $logChannel = $config['log_channel'] ?? null;
        $logStatus = $logChannel === false ? 'Disabled' : ($logChannel ?: 'Default');
        $tempDir = $config['temporary_files_root'] ?? 'N/A';

        table(
            ['Configuration', 'Value', 'Status'],
            [
                ['Binary Path', $binaryPath, '✓'],
                ['Timeout', "{$timeout} seconds", '✓'],
                ['Default Preset', $preset, '✓'],
                ['Min VMAF', $minVmaf, '✓'],
                ['Log Channel', $logStatus, '✓'],
                ['Temp Directory', $tempDir, $this->getTempDirStatus($tempDir)],
            ]
        );

        // Check temporary directory
        if (! is_dir($tempDir)) {
            warning('Temporary directory does not exist (will be created automatically)');
        } elseif (! is_writable($tempDir)) {
            error("Temporary directory is not writable: {$tempDir}");

            return self::FAILURE;
        }

        $this->components->info('✅ Installation verified successfully');

        return self::SUCCESS;
    }

    protected function checkBinaryExists(string $binaryPath): bool
    {
        // If it's an absolute path, check if file exists
        if (str_starts_with($binaryPath, '/')) {
            return file_exists($binaryPath) && is_executable($binaryPath);
        }

        // Check if command exists in PATH
        $process = proc_open(
            "command -v {$binaryPath}",
            [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes
        );

        if (is_resource($process)) {
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);

            return $exitCode === 0;
        }

        return false;
    }

    protected function getVersion(string $binaryPath): string
    {
        try {
            $encoder = AbAV1Encoder::create(null, config('av1'));

            return $encoder->getVersion();
        } catch (\Exception $e) {
            throw new ExecutableNotFoundException("Failed to get version: {$e->getMessage()}");
        }
    }

    protected function getTempDirStatus(string $tempDir): string
    {
        if (! is_dir($tempDir)) {
            return '⚠';
        }

        if (! is_writable($tempDir)) {
            return '✗';
        }

        return '✓';
    }
}
