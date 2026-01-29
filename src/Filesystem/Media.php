<?php

declare(strict_types=1);

namespace Foxws\AV1\Filesystem;

use Illuminate\Filesystem\FilesystemAdapter;

class Media
{
    protected ?Disk $disk = null;

    protected ?string $path = null;

    protected ?string $temporaryDirectory = null;

    protected ?string $genericAlias = null;

    public function __construct(Disk $disk, string $path, bool $createTemp = true)
    {
        $this->disk = $disk;
        $this->path = $path;

        if ($createTemp) {
            $this->makeDirectory();
        }
    }

    public static function make($disk, string $path, bool $createTemp = true): self
    {
        return new self(Disk::make($disk), $path, $createTemp);
    }

    public function getDisk(): Disk
    {
        return $this->disk;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getDirectory(): ?string
    {
        $directory = rtrim(pathinfo($this->getPath())['dirname'], DIRECTORY_SEPARATOR);

        if ($directory === '.') {
            $directory = '';
        }

        if ($directory) {
            $directory .= DIRECTORY_SEPARATOR;
        }

        return $directory;
    }

    private function makeDirectory(): void
    {
        $disk = $this->getDisk();

        if (! $disk->isLocalDisk()) {
            $disk = $this->temporaryDirectoryDisk();
        }

        $directory = $this->getDirectory();

        if ($disk->has($directory)) {
            return;
        }

        $disk->makeDirectory($directory);
    }

    public function getFilenameWithoutExtension(): string
    {
        return pathinfo($this->getPath())['filename'];
    }

    public function getFilename(): string
    {
        return pathinfo($this->getPath())['basename'];
    }

    private function temporaryDirectoryDisk(): Disk
    {
        return Disk::make($this->temporaryDirectoryAdapter());
    }

    private function temporaryDirectoryAdapter(): FilesystemAdapter
    {
        if (! $this->temporaryDirectory) {
            $this->temporaryDirectory = $this->getDisk()->getTemporaryDirectory();
        }

        /** @var FilesystemAdapter $adapter */
        $adapter = app('filesystem')->createLocalDriver(
            ['root' => $this->temporaryDirectory]
        );

        return $adapter;
    }

    /**
     * Returns the local path to the file, either directly or via a temporary directory.
     */
    public function getLocalPath(): string
    {
        if ($this->getDisk()->isLocalDisk()) {
            return $this->getDisk()->path($this->getPath());
        }

        $temporaryDisk = $this->temporaryDirectoryDisk();

        if ($temporaryDisk->has($this->getPath())) {
            return $temporaryDisk->path($this->getPath());
        }

        $temporaryDisk->put(
            $this->getPath(),
            $this->getDisk()->get($this->getPath())
        );

        return $temporaryDisk->path($this->getPath());
    }

    public function exists(): bool
    {
        return $this->disk->exists($this->path);
    }
}
