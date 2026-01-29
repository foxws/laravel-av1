<?php

declare(strict_types=1);

namespace Foxws\AV1\AbAV1;

use Foxws\AV1\Contracts\EncoderInterface;
use Foxws\AV1\Filesystem\Media;
use Foxws\AV1\Filesystem\TemporaryDirectories;
use Foxws\AV1\Support\EncodingResult;
use Foxws\AV1\Support\MediaExporter;
use Illuminate\Process\Factory as ProcessFactory;
use Illuminate\Process\ProcessResult;
use Psr\Log\LoggerInterface;

class AbAV1Encoder implements EncoderInterface
{
    protected string $binaryPath;

    protected ?LoggerInterface $logger;

    protected ?int $timeout;

    protected array $config;

    protected ?Media $sourceMedia = null;

    protected ?EncodingResult $result = null;

    protected int $preset = 6;

    protected float|int $minVmaf = 95;

    protected int $maxEncodedPercent = 300;

    public function __construct(
        ?LoggerInterface $logger = null,
        ?string $binaryPath = null,
        ?int $timeout = null,
        ?array $config = null
    ) {
        $this->binaryPath = $binaryPath ?? 'ab-av1';
        $this->logger = $logger;
        $this->timeout = $timeout ?? 3600;
        $this->config = $config ?? [];
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
     * Set source media for encoding
     */
    public function setSourceMedia(?Media $media): self
    {
        $this->sourceMedia = $media;

        return $this;
    }

    /**
     * Set encoder preset
     */
    public function preset(int $preset): self
    {
        $this->preset = $preset;

        return $this;
    }

    /**
     * Set minimum VMAF score
     */
    public function minVmaf(float|int $vmaf): self
    {
        $this->minVmaf = $vmaf;

        return $this;
    }

    /**
     * Set maximum encoded percent
     */
    public function maxEncodedPercent(int $percent): self
    {
        $this->maxEncodedPercent = $percent;

        return $this;
    }

    /**
     * Encode using ab-av1 auto-encode
     */
    public function encode(?string $inputPath = null, ?string $outputPath = null): EncodingResult
    {
        // Use source media if no input provided
        if ($inputPath === null) {
            $inputPath = $this->sourceMedia?->getLocalPath()
                ?? throw new \InvalidArgumentException('No input path or source media set. Use fromDisk()->open() first.');
        }

        // Generate temp output if not specified
        if ($outputPath === null) {
            $outputPath = storage_path('app/temp/'.uniqid('av1_').'.mp4');
            $tempDir = dirname($outputPath);
            if (! is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
        }

        $preset = $this->preset ?? $this->config['preset'] ?? 6;
        $minVmaf = $this->minVmaf ?? $this->config['min_vmaf'] ?? 95;
        $maxEncodedPercent = $this->maxEncodedPercent ?? $this->config['max_encoded_percent'] ?? 300;

        $builder = CommandBuilder::make('auto-encode')
            ->input($inputPath)
            ->output($outputPath)
            ->preset((string) $preset)
            ->minVmaf($minVmaf)
            ->maxEncodedPercent($maxEncodedPercent);

        $result = $this->run($builder->buildArray());

        $this->result = new EncodingResult($result, $outputPath);

        return $this->result;
    }

    /**
     * Export encoding result (executes encode if not already done)
     */
    public function export(): MediaExporter
    {
        if ($this->result === null) {
            $this->result = $this->encode();
        }

        return $this->result->export();
    }

    /**
     * Set command to auto-encode with defaults from configuration
     */
    public function vmafEncode(CommandBuilder $builder): self
    {
        $builder->command('auto-encode');

        // Apply default configuration values
        if (isset($this->config['preset'])) {
            $builder->preset((string) $this->config['preset']);
        }

        if (isset($this->config['min_vmaf'])) {
            $builder->minVmaf($this->config['min_vmaf']);
        }

        if (isset($this->config['max_encoded_percent'])) {
            $builder->maxEncodedPercent($this->config['max_encoded_percent']);
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
        if (isset($this->config['preset'])) {
            $builder->preset((string) $this->config['preset']);
        }

        if (isset($this->config['min_vmaf'])) {
            $builder->minVmaf($this->config['min_vmaf']);
        }

        if (isset($this->config['max_encoded_percent'])) {
            $builder->maxEncodedPercent($this->config['max_encoded_percent']);
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
     * Set command to encode (low-level builder configuration)
     */
    public function withEncodeCommand(CommandBuilder $builder): self
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
