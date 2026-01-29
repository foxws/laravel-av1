<?php

declare(strict_types=1);

namespace Foxws\AV1\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Foxws\AV1\MediaOpener fromDisk(string $disk)
 * @method static \Foxws\AV1\MediaOpener open(string $path)
 * @method static \Foxws\AV1\MediaOpener disk(?string $disk = null)
 * @method static \Foxws\AV1\FFmpeg\VideoEncoder encoder()
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
