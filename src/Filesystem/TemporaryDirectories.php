<?php

declare(strict_types=1);

namespace Foxws\AV1\Filesystem;

/**
 * Manages temporary directories for encoding operations
 */
class TemporaryDirectories
{
    protected string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? sys_get_temp_dir();
    }

    /**
     * Create a new temporary directory
     */
    public function create(): string
    {
        $path = $this->basePath.'/av1_'.uniqid();

        if (! is_dir($path)) {
            mkdir($path, 0755, true);
        }

        return $path;
    }

    /**
     * Get the base path for temporary directories
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Clean up old temporary directories
     */
    public function cleanup(int $olderThanSeconds = 86400): void
    {
        $pattern = $this->basePath.'/av1_*';

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
