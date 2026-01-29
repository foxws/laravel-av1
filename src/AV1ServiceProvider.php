<?php

declare(strict_types=1);

namespace Foxws\AV1;

use Foxws\AV1\FFmpeg\VideoEncoder;
use Foxws\AV1\Filesystem\MediaOpenerFactory;
use Foxws\AV1\Filesystem\TemporaryDirectories;
use Illuminate\Support\Facades\Config;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AV1ServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-av1')
            ->hasConfigFile('av1')
            ->hasCommands([
                Commands\VerifyInstallationCommand::class,
                Commands\PackageInfoCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton('laravel-av1-logger', function () {
            $logChannel = Config::get('av1.log_channel');

            if ($logChannel === false) {
                return null;
            }

            return app('log')->channel($logChannel ?: Config::get('logging.default'));
        });

        $this->app->singleton('laravel-av1-configuration', function () {
            return Config::get('av1');
        });

        $this->app->singleton(TemporaryDirectories::class, function () {
            return new TemporaryDirectories(
                Config::string('av1.temporary_files_root', sys_get_temp_dir())
            );
        });

        // Register the Video Encoder
        $this->app->singleton(VideoEncoder::class, function ($app) {
            $logger = $app->make('laravel-av1-logger');
            $config = $app->make('laravel-av1-configuration');

            return new VideoEncoder($logger, $config);
        });

        // Register the main class to use with the facade
        $this->app->singleton('laravel-av1', function () {
            return new MediaOpenerFactory(
                Config::string('filesystems.default'),
                null,
                fn () => app(VideoEncoder::class)
            );
        });
    }
}
