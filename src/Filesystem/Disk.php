<?php

declare(strict_types=1);

namespace Foxws\AV1\Filesystem;

use Illuminate\Support\Facades\Storage;

/**
 * Simplified disk wrapper for media files
 */
class Disk
{
    protected string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Get file path for local disk or download to temp for remote disks
     */
    public function getPath(string $path): string
    {
        $storage = Storage::disk($this->name);

        // Try to get local path, fallback to download for remote disks
        try {
            $localPath = $storage->path($path);

            // Verify it's actually a local file
            if (file_exists($localPath)) {
                return $localPath;
            }
        } catch (\RuntimeException $e) {
            // Remote disk - will download to temp
        }

        // For remote disks (S3, etc), download to temp
        return $this->downloadToTemp($path);
    }

    /**
     * Download file to temporary location
     */
    protected function downloadToTemp(string $path): string
    {
        $tempPath = storage_path('app/temp/'.uniqid('av1_').'_'.basename($path));
        $tempDir = dirname($tempPath);

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        file_put_contents($tempPath, Storage::disk($this->name)->get($path));

        return $tempPath;
    }

    /**
     * Put file to disk
     */
    public function put(string $path, string $contents, string $visibility = 'private'): bool
    {
        return Storage::disk($this->name)->put($path, $contents, $visibility);
    }

    /**
     * Check if file exists
     */
    public function exists(string $path): bool
    {
        return Storage::disk($this->name)->exists($path);
    }

    /**
     * Get disk name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get underlying storage instance
     */
    public function storage(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk($this->name);
    }
}
