<?php

declare(strict_types=1);

namespace Foxws\AV1\AbAV1;

use Foxws\AV1\Contracts\EncoderInterface;
use Foxws\AV1\Filesystem\TemporaryDirectories;
use Foxws\AV1\Support\CommandBuilder;
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

        // Set working directory to temp directory to prevent .av-av1 folders in project root
        // Create a temporary directory for this process
        $tempDir = app(TemporaryDirectories::class)->create();

        $process = $factory
            ->timeout($this->timeout)
            ->path($tempDir)
            ->run($command);

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

    /**
     * Set command to auto-encode with defaults from configuration
     */
    public function vmafEncode(CommandBuilder $builder): self
    {
        $builder->command('auto-encode');

        // Apply default configuration values
        $abAv1Config = Config::get('av1.ab-av1', []);

        if (isset($abAv1Config['preset'])) {
            $builder->preset((string) $abAv1Config['preset']);
        }

        if (isset($abAv1Config['min_vmaf'])) {
            $builder->minVmaf($abAv1Config['min_vmaf']);
        }

        if (isset($abAv1Config['max_encoded_percent'])) {
            $builder->maxEncodedPercent($abAv1Config['max_encoded_percent']);
        }

        return $this;
    }

    /**
     * Set command to crf-search with defaults from configuration
     */
    public function crfSearch(CommandBuilder $builder): self
    {
        $builder->command('crf-search');

        // Apply default configuration values
        $abAv1Config = Config::get('av1.ab-av1', []);

        if (isset($abAv1Config['preset'])) {
            $builder->preset((string) $abAv1Config['preset']);
        }

        if (isset($abAv1Config['min_vmaf'])) {
            $builder->minVmaf($abAv1Config['min_vmaf']);
        }

        if (isset($abAv1Config['max_encoded_percent'])) {
            $builder->maxEncodedPercent($abAv1Config['max_encoded_percent']);
        }

        return $this;
    }

    /**
     * Set command to sample-encode
     */
    public function sampleEncode(CommandBuilder $builder): self
    {
        $builder->command('sample-encode');

        return $this;
    }

    /**
     * Set command to encode
     */
    public function encode(CommandBuilder $builder): self
    {
        $builder->command('encode');

        return $this;
    }

    /**
     * Set command to vmaf
     */
    public function vmaf(CommandBuilder $builder): self
    {
        $builder->command('vmaf');

        return $this;
    }

    /**
     * Set command to xpsnr
     */
    public function xpsnr(CommandBuilder $builder): self
    {
        $builder->command('xpsnr');

        return $this;
    }
}
