<?php

declare(strict_types=1);

namespace Foxws\AV1\Filesystem;

/**
 * Represents a media file on a disk
 */
class Media
{
    protected Disk $disk;

    protected string $path;

    public function __construct(Disk $disk, string $path)
    {
        $this->disk = $disk;
        $this->path = $path;
    }

    public static function make(Disk $disk, string $path): self
    {
        return new self($disk, $path);
    }

    /**
     * Get the local path (downloads if remote)
     */
    public function getLocalPath(): string
    {
        return $this->disk->getPath($this->path);
    }

    /**
     * Get the path on disk
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get the disk
     */
    public function getDisk(): Disk
    {
        return $this->disk;
    }

    /**
     * Check if file exists
     */
    public function exists(): bool
    {
        return $this->disk->exists($this->path);
    }
}
