<?php

declare(strict_types=1);

namespace Foxws\AV1\Filesystem;

use Foxws\AV1\Exceptions\MediaNotFoundException;

class Media
{
    protected Disk $disk;

    protected string $path;

    protected ?string $localPath = null;

    public function __construct(Disk $disk, string $path)
    {
        $this->disk = $disk;
        $this->path = $path;
    }

    public static function make(Disk $disk, string $path): self
    {
        return new self($disk, $path);
    }

    public function getDisk(): Disk
    {
        return $this->disk;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function exists(): bool
    {
        return $this->disk->exists($this->path);
    }

    public function getLocalPath(): string
    {
        if ($this->localPath) {
            return $this->localPath;
        }

        // For local disks, try to get the real path
        $adapter = $this->disk->filesystem()->getAdapter();

        if (method_exists($adapter, 'getPathPrefix')) {
            $this->localPath = $adapter->getPathPrefix().$this->path;

            return $this->localPath;
        }

        // For remote disks, we'll need to download to temp
        $temporaryDirectory = app(TemporaryDirectories::class)->create();
        $localPath = $temporaryDirectory.'/'.basename($this->path);

        // Stream download for better memory efficiency
        $stream = $this->disk->readStream($this->path);

        if (! $stream) {
            throw new MediaNotFoundException("Failed to read stream for: {$this->path}");
        }

        $localStream = fopen($localPath, 'wb');

        if (! $localStream) {
            if (is_resource($stream)) {
                fclose($stream);
            }
            throw new \RuntimeException("Failed to create local file: {$localPath}");
        }

        // Stream copy in chunks for memory efficiency
        stream_copy_to_stream($stream, $localStream);

        fclose($localStream);
        if (is_resource($stream)) {
            fclose($stream);
        }

        $this->localPath = $localPath;

        return $this->localPath;
    }

    public function getSafeInputPath(): string
    {
        if (! $this->exists()) {
            throw new MediaNotFoundException("Media not found: {$this->path}");
        }

        return $this->getLocalPath();
    }

    public function get(): string
    {
        return $this->disk->get($this->path);
    }

    public function put(string $contents): bool
    {
        return $this->disk->put($this->path, $contents);
    }

    public function delete(): bool
    {
        return $this->disk->delete($this->path);
    }
}
