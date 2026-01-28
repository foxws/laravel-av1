<?php

declare(strict_types=1);

namespace Foxws\AV1;

use Illuminate\Process\ProcessResult;
use Illuminate\Support\Facades\Storage;

class MediaExporter
{
    protected string $sourcePath;

    protected ProcessResult $result;

    protected string $disk = 'local';

    protected ?string $targetPath = null;

    protected string $visibility = 'private';

    public function __construct(ProcessResult $result, string $sourcePath)
    {
        $this->result = $result;
        $this->sourcePath = $sourcePath;
    }

    /**
     * Set target disk
     */
    public function toDisk(string $disk): self
    {
        $this->disk = $disk;

        return $this;
    }

    /**
     * Set target path (directory or full path)
     */
    public function toPath(string $path): self
    {
        $this->targetPath = $path;

        return $this;
    }

    /**
     * Set file visibility (public/private)
     */
    public function withVisibility(string $visibility): self
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Save the encoded file to the target disk/path
     */
    public function save(?string $filename = null): bool
    {
        if (! $this->result->successful()) {
            return false;
        }

        if (! file_exists($this->sourcePath)) {
            return false;
        }

        // Determine final path
        $finalPath = $this->resolveFinalPath($filename);

        // Copy to target disk
        $success = Storage::disk($this->disk)->put(
            $finalPath,
            file_get_contents($this->sourcePath),
            $this->visibility
        );

        // Optionally clean up source file
        // @unlink($this->sourcePath);

        return $success;
    }

    /**
     * Get the process result
     */
    public function result(): ProcessResult
    {
        return $this->result;
    }

    /**
     * Resolve the final path on target disk
     */
    protected function resolveFinalPath(?string $filename): string
    {
        if ($filename) {
            // If target path is set, use it as directory
            if ($this->targetPath) {
                return rtrim($this->targetPath, '/') . '/' . $filename;
            }

            return $filename;
        }

        // Use original filename
        $basename = basename($this->sourcePath);

        if ($this->targetPath) {
            // If targetPath ends with extension, treat as full path
            if (pathinfo($this->targetPath, PATHINFO_EXTENSION)) {
                return $this->targetPath;
            }

            // Otherwise treat as directory
            return rtrim($this->targetPath, '/') . '/' . $basename;
        }

        return $basename;
    }

    /**
     * Get the source file path
     */
    public function getSourcePath(): string
    {
        return $this->sourcePath;
    }

    /**
     * Get the target disk
     */
    public function getDisk(): string
    {
        return $this->disk;
    }

    /**
     * Get the target path
     */
    public function getTargetPath(): ?string
    {
        return $this->targetPath;
    }
}
