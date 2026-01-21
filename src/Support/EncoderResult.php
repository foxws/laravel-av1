<?php

declare(strict_types=1);

namespace Foxws\AV1\Support;

use Illuminate\Process\ProcessResult;

class EncoderResult
{
    public function __construct(
        protected ProcessResult $processOutput,
        protected ?string $outputPath = null,
    ) {}

    public function isSuccessful(): bool
    {
        return $this->processOutput->successful();
    }

    public function getOutput(): string
    {
        return $this->processOutput->output();
    }

    public function getErrorOutput(): string
    {
        return $this->processOutput->errorOutput();
    }

    public function getOutputPath(): ?string
    {
        return $this->outputPath;
    }

    public function getExitCode(): int
    {
        return $this->processOutput->exitCode();
    }
}
