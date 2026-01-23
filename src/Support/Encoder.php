<?php

declare(strict_types=1);

namespace Foxws\AV1\Support;

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

        $arguments = $this->builder->buildArray();

        $processOutput = $this->encoder->run($arguments);

        return new EncoderResult($processOutput, $tempOutputPath);
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
