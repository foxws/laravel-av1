<?php

declare(strict_types=1);

namespace Foxws\AV1;

use Foxws\AV1\AbAV1\AbAV1Encoder;
use Foxws\AV1\FFmpeg\VideoEncoder;
use Foxws\AV1\Filesystem\TemporaryDirectories;
use Foxws\AV1\Support\Encoder;
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
                config('av1.temporary_files_root', sys_get_temp_dir()),
            );
        });

        $this->app->bind(AbAV1Encoder::class, function ($app) {
            $logger = $app->make('laravel-av1-logger');
            $config = $app->make('laravel-av1-configuration');

            return AbAV1Encoder::create(
                $logger,
                $config['ab-av1'] ?? []
            );
        });

        $this->app->bind(Encoder::class, function ($app) {
            $logger = $app->make('laravel-av1-logger');
            $config = $app->make('laravel-av1-configuration');

            return Encoder::create(
                $logger,
                $config
            );
        });

        $this->app->bind(VideoEncoder::class, function ($app) {
            $logger = $app->make('laravel-av1-logger');
            $config = $app->make('laravel-av1-configuration');

            return new VideoEncoder(
                logger: $logger,
                config: array_merge(
                    ['binaries' => $config['binaries'] ?? []],
                    $config['ffmpeg'] ?? []
                )
            );
        });

        $this->app->singleton('laravel-av1', function ($app) {
            return new AV1Manager($app->make('laravel-av1-logger'));
        });

        $this->app->bind(MediaOpener::class, function ($app) {
            return new MediaOpener;
        });
    }
}
