<?php

declare(strict_types=1);

namespace Foxws\AV1\Support;

class EncoderResult
{
    public function __construct(
        protected ProcessOutput $processOutput,
        protected ?string $outputPath = null,
    ) {}

    public function isSuccessful(): bool
    {
        return $this->processOutput->isSuccessful();
    }

    public function getOutput(): string
    {
        return $this->processOutput->getOutput();
    }

    public function getErrorOutput(): string
    {
        return $this->processOutput->getErrorOutput();
    }

    public function getOutputPath(): ?string
    {
        return $this->outputPath;
    }

    public function getExitCode(): int
    {
        return $this->processOutput->exitCode;
    }
}
