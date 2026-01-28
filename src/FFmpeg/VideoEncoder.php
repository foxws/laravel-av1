<?php

declare(strict_types=1);

namespace Foxws\AV1\FFmpeg;

use Foxws\AV1\EncodingResult;
use Illuminate\Process\Factory as ProcessFactory;
use Psr\Log\LoggerInterface;

/**
 * Encodes videos using FFmpeg with AV1
 */
class VideoEncoder
{
    protected string $ffmpegPath;

    protected ?LoggerInterface $logger;

    protected ?int $timeout;

    protected HardwareDetector $hardwareDetector;

    protected bool $useHwAccel = false;

    protected ?string $encoder = null;

    protected int $crf;

    protected int $preset;

    protected ?string $pixelFormat = null;

    protected ?string $audioCodec = null;

    protected ?string $videoFilter = null;

    protected array $customArgs = [];

    protected int $threads;

    protected array $config;

    public function __construct(
        ?LoggerInterface $logger = null,
        ?array $config = null
    ) {
        $this->config = $config ?? config('av1.ffmpeg', []);
        $this->logger = $logger;
        $this->ffmpegPath = $this->config['binaries']['ffmpeg'] ?? config('av1.binaries.ffmpeg', 'ffmpeg');
        $this->timeout = $this->config['timeout'] ?? 7200;
        $this->hardwareDetector = new HardwareDetector($this->ffmpegPath);
    }

    /**
     * Enable hardware acceleration
     */
    public function useHwAccel(bool $enabled = true): self
    {
        $this->useHwAccel = $enabled;

        return $this;
    }

    /**
     * Set CRF value
     */
    public function crf(int $crf): self
    {
        $this->crf = $crf;

        return $this;
    }

    /**
     * Set preset
     */
    public function preset(int $preset): self
    {
        $this->preset = $preset;

        return $this;
    }

    /**
     * Set number of threads (0 = auto)
     */
    public function threads(int $threads): self
    {
        $this->threads = $threads;

        return $this;
    }

    /**
     * Set encoder explicitly
     */
    public function encoder(string $encoder): self
    {
        $this->encoder = $encoder;

        return $this;
    }

    /**
     * Set pixel format
     */
    public function pixelFormat(string $format): self
    {
        $this->pixelFormat = $format;

        return $this;
    }

    /**
     * Set audio codec
     */
    public function audioCodec(string $codec): self
    {
        $this->audioCodec = $codec;

        return $this;
    }

    /**
     * Set video filter
     */
    public function videoFilter(string $filter): self
    {
        $this->videoFilter = $filter;

        return $this;
    }

    /**
     * Add custom FFmpeg arguments
     */
    public function withArgs(array $args): self
    {
        $this->customArgs = $args;

        return $this;
    }

    /**
     * Encode video
     */
    public function encode(string $inputPath, string $outputPath): EncodingResult
    {
        $args = $this->buildCommand($inputPath, $outputPath);

        if ($this->logger) {
            $this->logger->info('Encoding video with FFmpeg', [
                'input' => $inputPath,
                'output' => $outputPath,
                'encoder' => $this->getEncoder(),
                'crf' => $this->crf ?? $this->config['default_crf'] ?? 30,
                'hw_accel' => $this->useHwAccel,
            ]);
        }

        /** @var ProcessFactory $factory */
        $factory = app(ProcessFactory::class);

        $result = $factory
            ->timeout($this->timeout)
            ->run($args);

        return new EncodingResult($result, $outputPath);
    }

    /**
     * Build FFmpeg command using the command builder
     */
    protected function buildCommand(string $input, string $output): array
    {
        $builder = FFmpegCommandBuilder::make($this->ffmpegPath)
            ->withEncoder($this->getEncoder())
            ->withAudioCodec($this->audioCodec ?? $this->config['audio_codec'] ?? 'libopus')
            ->withCrf($this->crf ?? $this->config['default_crf'] ?? 30)
            ->withPreset($this->preset ?? $this->config['default_preset'] ?? 6)
            ->withThreads($this->threads ?? $this->config['threads'] ?? 0)
            ->withCustomArgs($this->customArgs);

        // Hardware acceleration for decoding
        if ($this->useHwAccel) {
            $hwaccelMethod = $this->hardwareDetector->getHardwareAccelMethod();

            if ($hwaccelMethod) {
                $builder->withHwaccel($hwaccelMethod);
            }
        }

        // Pixel format
        if ($this->pixelFormat || isset($this->config['pixel_format'])) {
            $builder->withPixelFormat($this->pixelFormat ?? $this->config['pixel_format']);
        }

        // Video filter
        if ($this->videoFilter) {
            $builder->withVideoFilter($this->videoFilter);
        }

        return $builder->build($input, $output);
    }

    /**
     * Get encoder to use
     */
    protected function getEncoder(): string
    {
        if ($this->encoder) {
            return $this->encoder;
        }

        if ($this->useHwAccel) {
            $hwEncoder = $this->hardwareDetector->getBestHardwareEncoder();

            if ($hwEncoder) {
                return $hwEncoder;
            }
        }

        return $this->hardwareDetector->getBestEncoder() ?? 'libsvtav1';
    }

    /**
     * Get hardware detector
     */
    public function hardwareDetector(): HardwareDetector
    {
        return $this->hardwareDetector;
    }
}
