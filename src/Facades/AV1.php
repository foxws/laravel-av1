<?php

declare(strict_types=1);

namespace Foxws\AV1\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Foxws\AV1\MediaOpener new()
 * @method static \Foxws\AV1\MediaOpener fromDisk(\Foxws\AV1\Filesystem\Disk|\Illuminate\Contracts\Filesystem\Filesystem|string $disk)
 * @method static \Foxws\AV1\MediaOpener open(mixed $paths)
 * @method static \Foxws\AV1\MediaOpener openFromDisk(\Illuminate\Contracts\Filesystem\Filesystem|string $disk, mixed $paths)
 * @method static \Foxws\AV1\Filesystem\MediaCollection get()
 * @method static \Foxws\AV1\FFmpeg\VideoEncoder getEncoder()
 * @method static \Foxws\AV1\MediaExporter export()
 * @method static \Foxws\AV1\MediaOpener cleanupTemporaryFiles()
 *
 * @see \Foxws\AV1\Filesystem\MediaOpenerFactory
 */
class AV1 extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-av1';
    }
}
