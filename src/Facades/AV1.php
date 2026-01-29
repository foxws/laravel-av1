<?php

declare(strict_types=1);

namespace Foxws\AV1\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Foxws\AV1\MediaOpener opener()
 * @method static int findCrf(string $inputPath, float|int $targetVmaf = 95, int $preset = 6, ?int $minCrf = null, ?int $maxCrf = null)
 * @method static \Foxws\AV1\FFmpeg\VideoEncoder encoder()
 * @method static \Foxws\AV1\AbAV1\CrfFinder crfFinder()
 *
 * @see \Foxws\AV1\AV1Manager
 */
class AV1 extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Foxws\AV1\AV1Manager::class;
    }
}
