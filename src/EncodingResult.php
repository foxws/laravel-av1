<?php

declare(strict_types=1);

namespace Foxws\AV1;

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
