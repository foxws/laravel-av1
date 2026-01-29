<?php

declare(strict_types=1);

namespace Foxws\AV1;

use Foxws\AV1\FFmpeg\VideoEncoder;
use Foxws\AV1\Filesystem\Disk;
use Foxws\AV1\Filesystem\Media;

/**
 * Simplified media opener for v2.0 - clean disk abstraction
 */
class MediaOpener
{
    protected ?Media $sourceMedia = null;

    /**
     * Open media from a disk
     */
    public function fromDisk(string $disk): self
    {
        $this->sourceMedia = null;
        $this->disk = new Disk($disk);

        return $this;
    }

    protected ?Disk $disk = null;

    protected ?string $path = null;

    /**
     * Set the path on the disk
     */
    public function path(string $path): self
    {
        $this->path = $path;

        if ($this->disk) {
            $this->sourceMedia = new Media($this->disk, $path);
        }

        return $this;
    }

    /**
     * Alias for path() to match documentation
     */
    public function open(string $path): self
    {
        return $this->path($path);
    }

    /**
     * Get the source media
     */
    public function getSourceMedia(): ?Media
    {
        return $this->sourceMedia;
    }

    /**
     * Create encoder and configure with source media
     */
    public function encoder(): VideoEncoder
    {
        $encoder = app(VideoEncoder::class);
        $encoder->setSourceMedia($this->sourceMedia);

        return $encoder;
    }

    /**
     * Start ab-av1 encoding workflow
     */
    public function abav1(): VideoEncoder
    {
        return $this->encoder();
    }

    /**
     * Start FFmpeg encoding workflow
     */
    public function ffmpegEncode(): VideoEncoder
    {
        return $this->encoder();
    }

    /**
     * Start FFmpeg auto CRF encoding workflow
     */
    public function ffmpegAutoEncode(): VideoEncoder
    {
        return $this->encoder();
    }

    /**
     * Start VMAF encoding workflow (ab-av1)
     */
    public function vmafEncode(): VideoEncoder
    {
        return $this->encoder();
    }
}
