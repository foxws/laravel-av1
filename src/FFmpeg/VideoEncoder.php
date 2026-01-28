<?php

declare(strict_types=1);

namespace Foxws\AV1\FFmpeg;

use Foxws\AV1\FFmpeg\HardwareAcceleration\HardwareDetector;
use Illuminate\Process\Factory as ProcessFactory;
use Illuminate\Process\ProcessResult;
use Illuminate\Support\Facades\Config;
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

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->ffmpegPath = Config::string('av1.binaries.ffmpeg', 'ffmpeg');
        $this->logger = $logger;
        $this->timeout = Config::integer('av1.ffmpeg.timeout', 7200);
        $this->hardwareDetector = new HardwareDetector($this->ffmpegPath);
        $this->crf = Config::integer('av1.ffmpeg.default_crf', 30);
        $this->preset = Config::integer('av1.ffmpeg.default_preset', 6);
        $this->audioCodec = Config::string('av1.ffmpeg.audio_codec', 'libopus');
        $this->pixelFormat = Config::string('av1.ffmpeg.pixel_format', 'yuv420p');
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
    public function encode(string $inputPath, string $outputPath): ProcessResult
    {
        $args = $this->buildCommand($inputPath, $outputPath);

        if ($this->logger) {
            $this->logger->info('Encoding video with FFmpeg', [
                'input' => $inputPath,
                'output' => $outputPath,
                'encoder' => $this->getEncoder(),
                'crf' => $this->crf,
                'hw_accel' => $this->useHwAccel,
            ]);
        }

        /** @var ProcessFactory $factory */
        $factory = app(ProcessFactory::class);

        return $factory
            ->timeout($this->timeout)
            ->run($args);
    }

    /**
     * Build FFmpeg command
     */
    protected function buildCommand(string $input, string $output): array
    {
        $args = [$this->ffmpegPath];

        // Hardware acceleration for decoding
        if ($this->useHwAccel) {
            $hwaccelMethod = $this->hardwareDetector->getHardwareAccelMethod();
            if ($hwaccelMethod) {
                $args[] = '-hwaccel';
                $args[] = $hwaccelMethod;
            }
        }

        // Input
        $args[] = '-i';
        $args[] = $input;

        // Video codec
        $args[] = '-c:v';
        $args[] = $this->getEncoder();

        // Audio codec
        $args[] = '-c:a';
        $args[] = $this->audioCodec;

        // CRF
        $args[] = $this->getCrfOption();
        $args[] = (string) $this->crf;

        // Preset
        $args[] = $this->getPresetOption();
        $args[] = (string) $this->preset;

        // Pixel format
        if ($this->pixelFormat) {
            $args[] = '-pix_fmt';
            $args[] = $this->pixelFormat;
        }

        // Video filter
        if ($this->videoFilter) {
            $args[] = '-vf';
            $args[] = $this->videoFilter;
        }

        // Custom args
        if (! empty($this->customArgs)) {
            $args = array_merge($args, $this->customArgs);
        }

        // Overwrite
        $args[] = '-y';

        // Output
        $args[] = $output;

        return $args;
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
     * Get CRF option name for current encoder
     */
    protected function getCrfOption(): string
    {
        $encoder = $this->getEncoder();

        return match ($encoder) {
            'av1_qsv', 'av1_amf', 'av1_nvenc' => '-q:v',
            default => '-crf',
        };
    }

    /**
     * Get preset option name for current encoder
     */
    protected function getPresetOption(): string
    {
        $encoder = $this->getEncoder();

        return match ($encoder) {
            'av1_amf' => '-quality',
            default => '-preset',
        };
    }

    /**
     * Get hardware detector
     */
    public function hardwareDetector(): HardwareDetector
    {
        return $this->hardwareDetector;
    }
}
