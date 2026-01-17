<?php

declare(strict_types=1);

namespace Foxws\AV1\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Foxws\AV1\MediaOpener open($paths)
 * @method static \Foxws\AV1\MediaOpener fromDisk(\Foxws\AV1\Filesystem\Disk|\Illuminate\Contracts\Filesystem\Filesystem|string $disk)
 * @method static \Foxws\AV1\MediaOpener autoEncode()
 * @method static \Foxws\AV1\MediaOpener crfSearch()
 * @method static \Foxws\AV1\MediaOpener sampleEncode()
 * @method static \Foxws\AV1\MediaOpener encode()
 * @method static \Foxws\AV1\MediaOpener vmaf()
 * @method static \Foxws\AV1\MediaOpener xpsnr()
 * @method static \Foxws\AV1\MediaOpener input(string $path)
 * @method static \Foxws\AV1\MediaOpener output(string $path)
 * @method static \Foxws\AV1\MediaOpener preset(string $preset)
 * @method static \Foxws\AV1\MediaOpener minVmaf(float|int $vmaf)
 * @method static \Foxws\AV1\MediaOpener crf(int $crf)
 * @method static \Foxws\AV1\MediaOpener reference(string $path)
 * @method static \Foxws\AV1\MediaOpener distorted(string $path)
 * @method static \Foxws\AV1\Exporters\MediaExporter export()
 *
 * @see \Foxws\AV1\MediaOpener
 */
class AV1 extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Foxws\AV1\MediaOpener::class;
    }
}
