<?php

declare(strict_types=1);

namespace Foxws\AV1\Support;

use Foxws\AV1\Contracts\EncoderInterface;
use Illuminate\Process\Factory as ProcessFactory;
use Illuminate\Process\ProcessResult;
use Illuminate\Support\Facades\Config;
use Psr\Log\LoggerInterface;

class AbAV1Encoder implements EncoderInterface
{
    protected string $binaryPath;

    protected ?LoggerInterface $logger;

    protected ?int $timeout;

    public function __construct(
        ?string $binaryPath = null,
        ?LoggerInterface $logger = null,
        ?int $timeout = null
    ) {
        $this->binaryPath = Config::string('av1.binaries.ab-av1', 'ab-av1');
        $this->logger = $logger;
        $this->timeout = $timeout ?? Config::integer('av1.ab-av1.timeout', 3600);
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
    public function run(array $arguments): ProcessResult
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

        if ($this->logger) {
            if ($process->successful()) {
                $this->logger->info('ab-av1 command completed successfully', [
                    'exitCode' => $process->exitCode(),
                ]);
            } else {
                $this->logger->error('ab-av1 command failed', [
                    'exitCode' => $process->exitCode(),
                    'error' => $process->errorOutput(),
                ]);
            }
        }

        return $process;
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

    /**
     * Get ab-av1 version
     */
    public function version(): string
    {
        /** @var ProcessFactory $factory */
        $factory = app(ProcessFactory::class);
        $process = $factory->timeout(5)->run([$this->binaryPath, '--version']);

        if ($process->exitCode() !== 0) {
            throw new \RuntimeException('Failed to get ab-av1 version: '.$process->errorOutput());
        }

        // Extract version from output (e.g., "ab-av1 0.7.0")
        $output = trim($process->output());

        if (preg_match('/ab-av1 ([\d.]+)/', $output, $matches)) {
            return $matches[1];
        }

        return $output;
    }

    /**
     * Alias for version() for backward compatibility
     */
    public function getVersion(): string
    {
        return $this->version();
    }
}
