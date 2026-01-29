<?php

declare(strict_types=1);

namespace Foxws\AV1\Filesystem;

use Foxws\AV1\FFmpeg\VideoEncoder;
use Foxws\AV1\MediaOpener;

class MediaOpenerFactory
{
    protected string $defaultDisk;

    protected ?string $defaultPath;

    protected \Closure $encoderResolver;

    public function __construct(
        string $defaultDisk = 'local',
        ?string $defaultPath = null,
        ?\Closure $encoderResolver = null
    ) {
        $this->defaultDisk = $defaultDisk;
        $this->defaultPath = $defaultPath;
        $this->encoderResolver = $encoderResolver ?? fn () => app(VideoEncoder::class);
    }

    /**
     * Create a media opener from a disk
     */
    public function fromDisk(string $disk): MediaOpener
    {
        return app(MediaOpener::class)->fromDisk($disk);
    }

    /**
     * Open a media file from a path
     */
    public function open(string $path): MediaOpener
    {
        return app(MediaOpener::class)->path($path);
    }

    /**
     * Create a media opener from the default disk
     */
    public function disk(?string $disk = null): MediaOpener
    {
        return $this->fromDisk($disk ?? $this->defaultDisk);
    }

    /**
     * Create a video encoder instance
     */
    public function encoder(): VideoEncoder
    {
        return ($this->encoderResolver)();
    }
}
