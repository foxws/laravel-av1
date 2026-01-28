<?php

declare(strict_types=1);

namespace Foxws\AV1;

use Foxws\AV1\FFmpeg\VideoEncoder;
use Foxws\AV1\Filesystem\Media;

/**
 * Wraps VideoEncoder with source media context
 */
class VideoEncoderWrapper
{
    protected VideoEncoder $encoder;

    protected ?Media $sourceMedia;

    public function __construct(VideoEncoder $encoder, ?Media $sourceMedia = null)
    {
        $this->encoder = $encoder;
        $this->sourceMedia = $sourceMedia;
    }

    /**
     * Encode the video
     */
    public function encode(?string $outputPath = null): EncodingResult
    {
        $inputPath = $this->sourceMedia
            ? $this->sourceMedia->getLocalPath()
            : throw new \InvalidArgumentException('No source media set. Use fromDisk()->path() first.');

        // Generate temp output if not specified
        if ($outputPath === null) {
            $outputPath = storage_path('app/temp/' . uniqid('av1_') . '.mp4');
            $tempDir = dirname($outputPath);
            if (! is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
        }

        return $this->encoder->encode($inputPath, $outputPath);
    }

    /**
     * Forward all other methods to encoder
     */
    public function __call(string $method, array $arguments)
    {
        $result = $this->encoder->$method(...$arguments);

        // Return wrapper for fluent chaining
        if ($result === $this->encoder) {
            return $this;
        }

        return $result;
    }
}
