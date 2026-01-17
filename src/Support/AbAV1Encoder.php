<?php

declare(strict_types=1);

namespace Foxws\AV1\Support;

use Illuminate\Process\Factory as ProcessFactory;
use Illuminate\Support\Facades\Config;
use Psr\Log\LoggerInterface;

class AbAV1Encoder
{
    protected string $binaryPath;

    protected ?LoggerInterface $logger;

    protected ?int $timeout;

    public function __construct(
        ?string $binaryPath = null,
        ?LoggerInterface $logger = null,
        ?int $timeout = null
    ) {
        $this->binaryPath = $binaryPath ?? Config::get('av1.binary_path', 'ab-av1');
        $this->logger = $logger;
        $this->timeout = $timeout ?? Config::get('av1.timeout', 3600);
    }

    public static function create(
        ?LoggerInterface $logger = null,
        ?array $configuration = null
    ): self {
        return new self(
            $configuration['binary_path'] ?? null,
            $logger,
            $configuration['timeout'] ?? null
        );
    }

    public function setBinaryPath(string $path): self
    {
        $this->binaryPath = $path;

        return $this;
    }

    public function getBinaryPath(): string
    {
        return $this->binaryPath;
    }

    public function setTimeout(?int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * Execute ab-av1 command
     */
    public function run(array $arguments): ProcessOutput
    {
        // Remove 'ab-av1' from arguments if present
        if (isset($arguments[0]) && $arguments[0] === 'ab-av1') {
            array_shift($arguments);
        }

        $command = array_merge([$this->binaryPath], $arguments);

        if ($this->logger) {
            $this->logger->debug('Running ab-av1 command', [
                'command' => implode(' ', $command),
            ]);
        }

        /** @var ProcessFactory $factory */
        $factory = app(ProcessFactory::class);
        $process = $factory->timeout($this->timeout)->run($command);

        $output = new ProcessOutput(
            $process->exitCode(),
            $process->output(),
            $process->errorOutput()
        );

        if ($this->logger) {
            if ($output->isSuccessful()) {
                $this->logger->info('ab-av1 command completed successfully', [
                    'exitCode' => $output->exitCode,
                ]);
            } else {
                $this->logger->error('ab-av1 command failed', [
                    'exitCode' => $output->exitCode,
                    'error' => $output->errorOutput,
                ]);
            }
        }

        return $output;
    }

    /**
     * Check if ab-av1 is available
     */
    public function isAvailable(): bool
    {
        try {
            /** @var ProcessFactory $factory */
            $factory = app(ProcessFactory::class);
            $process = $factory->timeout(5)->run([$this->binaryPath, '--version']);

            return $process->exitCode() === 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}
