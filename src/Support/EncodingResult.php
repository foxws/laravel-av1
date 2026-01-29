<?php

declare(strict_types=1);

namespace Foxws\AV1\Support;

use Foxws\AV1\Filesystem\Disk;
use Illuminate\Process\ProcessResult;

/**
 * Encoding result with export capabilities
 */
class EncodingResult
{
    protected ProcessResult $result;

    protected string $outputPath;

    public function __construct(ProcessResult $result, string $outputPath)
    {
        $this->result = $result;
        $this->outputPath = $outputPath;
    }

    /**
     * Copy the encoded file to a target disk
     */
    public function toDisk(Disk $disk, ?string $visibility = null, bool $cleanup = true, ?string $targetPath = null): void
    {
        if (! file_exists($this->outputPath)) {
            throw new \RuntimeException('Output file does not exist: '.$this->outputPath);
        }

        $filename = basename($this->outputPath);
        $finalPath = $targetPath ? rtrim($targetPath, '/').'/'.$filename : $filename;

        // Read the file and put it on the target disk
        $stream = fopen($this->outputPath, 'rb');

        if (! $stream) {
            throw new \RuntimeException('Failed to open output file: '.$this->outputPath);
        }

        try {
            if ($visibility) {
                $disk->put($finalPath, $stream, $visibility);
            } else {
                $disk->put($finalPath, $stream);
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        // Cleanup temporary file if requested
        if ($cleanup && file_exists($this->outputPath)) {
            @unlink($this->outputPath);
        }
    }

    /**
     * Start exporting the encoded file
     */
    public function export(): MediaExporter
    {
        return new MediaExporter($this->result, $this->outputPath);
    }

    /**
     * Get the output file path
     */
    public function path(): string
    {
        return $this->outputPath;
    }

    /**
     * Check if encoding was successful
     */
    public function successful(): bool
    {
        return $this->result->successful();
    }

    /**
     * Check if encoding failed
     */
    public function failed(): bool
    {
        return $this->result->failed();
    }

    /**
     * Get the exit code
     */
    public function exitCode(): int
    {
        return $this->result->exitCode();
    }

    /**
     * Get the process output
     */
    public function output(): string
    {
        return $this->result->output();
    }

    /**
     * Get the error output
     */
    public function errorOutput(): string
    {
        return $this->result->errorOutput();
    }

    /**
     * Get the underlying ProcessResult
     */
    public function result(): ProcessResult
    {
        return $this->result;
    }

    /**
     * Throw exception if encoding failed
     */
    public function throw(): self
    {
        $this->result->throw();

        return $this;
    }
}
