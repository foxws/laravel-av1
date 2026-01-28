<?php

declare(strict_types=1);

namespace Foxws\AV1\Support;

use Foxws\AV1\AbAV1\AbAV1Encoder;
use Foxws\AV1\Contracts\EncoderInterface;
use Foxws\AV1\Filesystem\MediaCollection;
use Foxws\AV1\Filesystem\TemporaryDirectories;
use Illuminate\Support\Traits\ForwardsCalls;
use Psr\Log\LoggerInterface;

class Encoder
{
    use ForwardsCalls;

    protected EncoderInterface $encoder;

    protected ?MediaCollection $mediaCollection = null;

    protected ?LoggerInterface $logger;

    protected ?CommandBuilder $builder = null;

    protected ?string $temporaryDirectory = null;

    public function __construct(
        EncoderInterface $encoder,
        ?LoggerInterface $logger = null
    ) {
        $this->encoder = $encoder;
        $this->logger = $logger;
    }

    public static function create(
        ?LoggerInterface $logger = null,
        ?array $configuration = null
    ): self {
        $encoder = AbAV1Encoder::create($logger, $configuration);

        return new self($encoder, $logger);
    }

    public function fresh(): self
    {
        return new self($this->encoder, $this->logger);
    }

    public function getEncoder(): EncoderInterface
    {
        return $this->encoder;
    }

    public function setEncoder(EncoderInterface $encoder): self
    {
        $this->encoder = $encoder;

        return $this;
    }

    public function getMediaCollection(): MediaCollection
    {
        return $this->mediaCollection;
    }

    public function open(MediaCollection $mediaCollection): self
    {
        $this->mediaCollection = $mediaCollection;

        // Validate the media collection
        if ($mediaCollection->count() === 0) {
            throw new \InvalidArgumentException('MediaCollection cannot be empty');
        }

        // Initialize a fresh CommandBuilder
        $this->builder = CommandBuilder::make();

        if ($this->logger) {
            $this->logger->debug('Opened media collection', [
                'count' => $mediaCollection->count(),
                'paths' => $mediaCollection->getLocalPaths(),
            ]);
        }

        return $this;
    }

    public function getBuilder(): ?CommandBuilder
    {
        return $this->builder;
    }

    public function builder(): CommandBuilder
    {
        if (! $this->builder) {
            $this->builder = CommandBuilder::make();
        }

        return $this->builder;
    }

    /**
     * Get or create temporary directory
     */
    protected function getTemporaryDirectory(): string
    {
        if (! $this->temporaryDirectory) {
            $this->temporaryDirectory = app(TemporaryDirectories::class)->create();
        }

        return $this->temporaryDirectory;
    }

    /**
     * Resolve input path from MediaCollection
     */
    protected function resolveInputPath(string $input): string
    {
        if ($this->mediaCollection) {
            $media = $this->mediaCollection->findByPath($input);

            if ($media) {
                return $media->getSafeInputPath();
            }
        }

        return $input;
    }

    /**
     * Resolve output path to temporary directory
     */
    protected function resolveOutputPath(?string $output = null): string
    {
        if (! $output) {
            $output = 'output.mp4';
        }

        return $this->getTemporaryDirectory().'/'.basename($output);
    }

    /**
     * Execute the encoder with current builder settings
     */
    public function run(): EncoderResult
    {
        // Check if using FFmpeg encoder with auto CRF
        if ($this->encoder instanceof FFmpegEncoder && $this->shouldUseAutoCrf()) {
            return $this->runWithAutoCrf();
        }

        // Get the desired output path from builder
        $desiredOutputPath = $this->builder->getOutput();

        // Resolve to temporary directory for encoding
        $tempOutputPath = $this->resolveOutputPath($desiredOutputPath);

        // Ensure temporary directory and subdirectories exist
        $tempDirectory = dirname($tempOutputPath);

        if (! is_dir($tempDirectory)) {
            mkdir($tempDirectory, 0755, true);
        }

        // Update builder with temporary output path for encoding
        $this->builder->output($tempOutputPath);

        // For FFmpeg encoder, use its encode method
        if ($this->encoder instanceof FFmpegEncoder) {
            return $this->runFFmpegEncode($tempOutputPath);
        }

        // For ab-av1 encoder, build arguments
        $arguments = $this->builder->buildArray();

        $processOutput = $this->encoder->run($arguments);

        return new EncoderResult($processOutput, $tempOutputPath);
    }

    /**
     * Run FFmpeg encoding with builder options
     */
    protected function runFFmpegEncode(string $tempOutputPath): EncoderResult
    {
        $inputPath = $this->builder->getInput();
        if (! $inputPath) {
            throw new \InvalidArgumentException('Input file is required');
        }

        if (! $this->encoder instanceof FFmpegEncoder) {
            throw new \RuntimeException('FFmpeg encoder required for this operation');
        }

        $options = $this->builder->getOptions();

        // Add CRF if set
        if (isset($options['crf'])) {
            $options['crf'] = (int) $options['crf'];
        }

        // Add preset if set
        if (isset($options['preset'])) {
            $options['preset'] = (int) $options['preset'];
        }

        $processOutput = $this->encoder->encode($inputPath, $tempOutputPath, $options);

        return new EncoderResult($processOutput, $tempOutputPath);
    }

    /**
     * Run encoding with automatic CRF optimization
     */
    protected function runWithAutoCrf(): EncoderResult
    {
        $inputPath = $this->builder->getInput();
        if (! $inputPath) {
            throw new \InvalidArgumentException('Input file is required');
        }

        $options = $this->builder->getOptions();
        $targetVmaf = $options['target_vmaf'] ?? $options['min-vmaf'] ?? 95;
        $preset = $options['preset'] ?? 6;

        if ($this->logger) {
            $this->logger->info('Using auto CRF optimization', [
                'input' => $inputPath,
                'target_vmaf' => $targetVmaf,
                'preset' => $preset,
            ]);
        }

        // Find optimal CRF using ab-av1
        $optimizer = new CrfOptimizer($this->logger);
        $optimalCrf = $optimizer->findOptimalCrf(
            $inputPath,
            $targetVmaf,
            $preset
        );

        // Update builder with optimal CRF
        $this->builder->crf($optimalCrf);

        if ($this->logger) {
            $this->logger->info('Using optimal CRF for encoding', [
                'crf' => $optimalCrf,
            ]);
        }

        // Continue with regular FFmpeg encoding
        $desiredOutputPath = $this->builder->getOutput();
        $tempOutputPath = $this->resolveOutputPath($desiredOutputPath);

        $tempDirectory = dirname($tempOutputPath);
        if (! is_dir($tempDirectory)) {
            mkdir($tempDirectory, 0755, true);
        }

        $this->builder->output($tempOutputPath);

        return $this->runFFmpegEncode($tempOutputPath);
    }

    /**
     * Check if auto CRF should be used
     */
    protected function shouldUseAutoCrf(): bool
    {
        $options = $this->builder->getOptions();

        // Use auto CRF if explicitly enabled
        if (isset($options['auto_crf']) && $options['auto_crf']) {
            return true;
        }

        // Don't use auto CRF if CRF is already set
        if (isset($options['crf'])) {
            return false;
        }

        // Use auto CRF if target VMAF is set
        if (isset($options['target_vmaf']) || isset($options['min-vmaf'])) {
            return true;
        }

        return false;
    }

    /**
     * Get the command that would be executed
     */
    public function getCommand(): string
    {
        return $this->builder->build();
    }

    /**
     * Forward calls to the builder
     */
    public function __call($method, $arguments)
    {
        return $this->forwardCallTo($this->builder(), $method, $arguments);
    }
}
