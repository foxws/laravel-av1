<?php

declare(strict_types=1);

namespace Foxws\AV1;

use Foxws\AV1\AbAV1\CrfFinder;
use Foxws\AV1\FFmpeg\VideoEncoder;
use Psr\Log\LoggerInterface;

/**
 * Main AV1 encoding manager
 */
class AV1Manager
{
    protected ?LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Create a media opener instance
     */
    public function opener(): MediaOpener
    {
        return app(MediaOpener::class);
    }

    /**
     * Create a video encoder instance
     */
    public function encoder(): VideoEncoder
    {
        return app(VideoEncoder::class);
    }

    /**
     * Create a CRF finder instance
     */
    public function crfFinder(): CrfFinder
    {
        return app(CrfFinder::class);
    }

    /**
     * Find optimal CRF value using ab-av1
     */
    public function findCrf(
        string $inputPath,
        float|int $targetVmaf = 95,
        int $preset = 6,
        ?int $minCrf = null,
        ?int $maxCrf = null
    ): int {
        return $this->crfFinder()->find($inputPath, $targetVmaf, $preset, $minCrf, $maxCrf);
    }
}
