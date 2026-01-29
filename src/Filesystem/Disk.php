<?php

declare(strict_types=1);

namespace Foxws\AV1\Filesystem;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Traits\ForwardsCalls;

/**
 * Simplified disk wrapper for media files
 *
 * @method bool has(string $path)
 * @method bool exists(string $path)
 * @method string|null get(string $path)
 * @method bool put(string $path, string|resource $contents, mixed $options = [])
 * @method resource|null readStream(string $path)
 * @method bool writeStream(string $path, resource $resource, array $options = [])
 * @method bool delete(string|array $paths)
 * @method bool copy(string $from, string $to)
 * @method bool move(string $from, string $to)
 * @method int size(string $path)
 * @method int lastModified(string $path)
 * @method string path(string $path)
 * @method array files(string|null $directory = null, bool $recursive = false)
 * @method array allFiles(string|null $directory = null)
 * @method array directories(string|null $directory = null, bool $recursive = false)
 * @method array allDirectories(string|null $directory = null)
 * @method bool makeDirectory(string $path)
 * @method bool deleteDirectory(string $directory)
 */
class Disk
{
    use ForwardsCalls;

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
        // Try to get local path, fallback to download for remote disks
        try {
            $localPath = $this->path($path);

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
     * Download file to temporary location using streaming for memory efficiency
     */
    protected function downloadToTemp(string $path): string
    {
        $tempPath = storage_path('app/temp/'.uniqid('av1_').'_'.basename($path));
        $tempDir = dirname($tempPath);

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Use streaming for memory efficiency with large video files
        $stream = $this->readStream($path);

        if (! $stream) {
            throw new \RuntimeException("Failed to read stream for: {$path}");
        }

        $localStream = fopen($tempPath, 'wb');

        if (! $localStream) {
            if (is_resource($stream)) {
                fclose($stream);
            }
            throw new \RuntimeException("Failed to create local file: {$tempPath}");
        }

        // Stream copy in chunks for memory efficiency
        stream_copy_to_stream($stream, $localStream);

        fclose($localStream);

        if (is_resource($stream)) {
            fclose($stream);
        }

        return $tempPath;
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

    /**
     * Forward calls to the underlying Storage instance
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->forwardCallTo($this->storage(), $method, $parameters);
    }
}
