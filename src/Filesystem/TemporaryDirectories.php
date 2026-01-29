<?php

declare(strict_types=1);

namespace Foxws\AV1\Filesystem;

use Illuminate\Filesystem\Filesystem;

/**
 * Manages temporary directories for encoding operations
 */
class TemporaryDirectories
{
    /**
     * Root of the temporary directories.
     */
    protected string $root;

    /**
     * Array of all directories
     */
    protected array $directories = [];

    /**
     * Sets the root and removes the trailing slash.
     */
    public function __construct(string $root)
    {
        $this->root = rtrim($root, '/');
    }

    /**
     * Returns the full path a of new temporary directory.
     */
    public function create(): string
    {
        $directory = $this->root.'/'.bin2hex(random_bytes(8));

        mkdir($directory, 0777, true);

        return $this->directories[] = $directory;
    }

    /**
     * Get the base path for temporary directories
     */
    public function getBasePath(): string
    {
        return $this->root;
    }

    /**
     * Loop through all directories and delete them.
     */
    public function deleteAll(): void
    {
        $filesystem = new Filesystem;

        foreach ($this->directories as $directory) {
            $filesystem->deleteDirectory($directory);
        }

        $this->directories = [];
    }

    /**
     * Clean up old temporary directories
     */
    public function cleanup(int $olderThanSeconds = 86400): void
    {
        $pattern = $this->root.'/av1_*';

        foreach (glob($pattern) as $dir) {
            if (! is_dir($dir)) {
                continue;
            }

            if (filemtime($dir) < time() - $olderThanSeconds) {
                $this->removeDirectory($dir);
            }
        }
    }

    /**
     * Recursively remove a directory
     */
    protected function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir.'/'.$file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }
}
