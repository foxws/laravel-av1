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
     * Find optimal CRF value using ab-av1
     */
    public function findCrf(
        string $inputPath,
        float|int $targetVmaf = 95,
        int $preset = 6,
        ?int $minCrf = null,
        ?int $maxCrf = null
    ): int {
        $finder = new CrfFinder($this->logger);

        return $finder->find($inputPath, $targetVmaf, $preset, $minCrf, $maxCrf);
    }

    /**
     * Create a video encoder instance (removed immediate encoding - use encoder()->encode() instead)
     */
    public function encoder(): VideoEncoder
    {
        return new VideoEncoder($this->logger);
    }

    /**
     * Create a CRF finder instance
     */
    public function crfFinder(): CrfFinder
    {
        return new CrfFinder($this->logger);
    }

}
