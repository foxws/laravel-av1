<?php

declare(strict_types=1);

namespace Foxws\AV1\Contracts;

use Illuminate\Process\ProcessResult;

interface EncoderInterface
{
    /**
     * Get the binary path
     */
    public function getBinaryPath(): string;

    /**
     * Set the binary path
     */
    public function setBinaryPath(string $path): self;

    /**
     * Run the encoder with given arguments
     */
    public function run(array $arguments): ProcessResult;

    /**
     * Get the encoder version
     */
    public function version(): string;
}
