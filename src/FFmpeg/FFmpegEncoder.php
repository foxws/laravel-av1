<?php

declare(strict_types=1);

namespace Foxws\AV1\FFmpeg;

use Foxws\AV1\Contracts\EncoderInterface;
use Foxws\AV1\Filesystem\TemporaryDirectories;
use Illuminate\Process\Factory as ProcessFactory;
use Illuminate\Process\ProcessResult;
use Illuminate\Support\Facades\Config;
use Psr\Log\LoggerInterface;

/**
 * FFmpeg-based AV1 encoder with hardware acceleration support
 */
class FFmpegEncoder implements EncoderInterface
{
    protected string $binaryPath;

    protected ?LoggerInterface $logger;

    protected ?int $timeout;

    protected HardwareDetector $hardwareDetector;

    protected ?string $encoder = null;

    protected bool $useHardwareAcceleration = false;

    protected ?string $hwaccelMethod = null;

    public function __construct(
        ?LoggerInterface $logger = null,
        ?int $timeout = null
    ) {
        $this->binaryPath = Config::string('av1.binaries.ffmpeg', 'ffmpeg');
        $this->logger = $logger;
        $this->timeout = $timeout ?? Config::integer('av1.ffmpeg.timeout', 7200);
        $this->hardwareDetector = new HardwareDetector($this->binaryPath);
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
     * Set the AV1 encoder to use
     */
    public function setEncoder(string $encoder): self
    {
        if (! $this->hardwareDetector->hasEncoder($encoder)) {
            throw new \InvalidArgumentException("Encoder '{$encoder}' is not available");
        }

        $this->encoder = $encoder;

        return $this;
    }

    /**
     * Get the current encoder
     */
    public function getEncoder(): string
    {
        if ($this->encoder) {
            return $this->encoder;
        }

        // Auto-detect best encoder
        if ($this->useHardwareAcceleration) {
            $encoder = $this->hardwareDetector->getBestHardwareEncoder();
            if ($encoder) {
                return $encoder;
            }
        }

        return $this->hardwareDetector->getBestEncoder() ?? 'libsvtav1';
    }

    /**
     * Enable hardware acceleration
     */
    public function useHardwareAcceleration(bool $enabled = true): self
    {
        $this->useHardwareAcceleration = $enabled;

        if ($enabled) {
            $this->hwaccelMethod = $this->hardwareDetector->getHardwareAccelMethod();
        }

        return $this;
    }

    /**
     * Check if hardware acceleration is enabled
     */
    public function isUsingHardwareAcceleration(): bool
    {
        return $this->useHardwareAcceleration;
    }

    /**
     * Get hardware detector
     */
    public function getHardwareDetector(): HardwareDetector
    {
        return $this->hardwareDetector;
    }

    /**
     * Execute FFmpeg command
     */
    public function run(array $arguments): ProcessResult
    {
        // Build complete FFmpeg command
        $command = array_merge([$this->binaryPath], $arguments);

        if ($this->logger) {
            $this->logger->debug('Running FFmpeg command', [
                'command' => implode(' ', $command),
                'encoder' => $this->getEncoder(),
                'hardware_accel' => $this->useHardwareAcceleration,
            ]);
        }

        /** @var ProcessFactory $factory */
        $factory = app(ProcessFactory::class);

        // Create a temporary directory for this process
        $tempDir = app(TemporaryDirectories::class)->create();

        $process = $factory
            ->timeout($this->timeout)
            ->path($tempDir)
            ->run($command);

        if ($this->logger) {
            if ($process->successful()) {
                $this->logger->info('FFmpeg command completed successfully', [
                    'exitCode' => $process->exitCode(),
                ]);
            } else {
                $this->logger->error('FFmpeg command failed', [
                    'exitCode' => $process->exitCode(),
                    'error' => $process->errorOutput(),
                ]);
            }
        }

        return $process;
    }

    /**
     * Encode video with FFmpeg
     */
    public function encode(
        string $input,
        string $output,
        array $options = []
    ): ProcessResult {
        $args = [];

        // Add hardware acceleration for decoding if enabled
        if ($this->useHardwareAcceleration && $this->hwaccelMethod) {
            $args[] = '-hwaccel';
            $args[] = $this->hwaccelMethod;
        }

        // Input file
        $args[] = '-i';
        $args[] = $input;

        // Video codec
        $args[] = '-c:v';
        $args[] = $this->getEncoder();

        // Audio codec (default to opus for AV1)
        if (! isset($options['audio_codec'])) {
            $args[] = '-c:a';
            $args[] = 'libopus';
        }

        // CRF (quality)
        if (isset($options['crf'])) {
            $args[] = $this->getCrfOption();
            $args[] = (string) $options['crf'];
        }

        // Preset (speed/quality tradeoff)
        if (isset($options['preset'])) {
            $args[] = $this->getPresetOption();
            $args[] = (string) $options['preset'];
        }

        // Pixel format
        if (isset($options['pix_fmt'])) {
            $args[] = '-pix_fmt';
            $args[] = $options['pix_fmt'];
        }

        // Video filters
        if (isset($options['vf'])) {
            $args[] = '-vf';
            $args[] = $options['vf'];
        }

        // Custom FFmpeg options
        if (isset($options['custom_args'])) {
            $args = array_merge($args, $options['custom_args']);
        }

        // Overwrite output file
        $args[] = '-y';

        // Output file
        $args[] = $output;

        return $this->run($args);
    }

    /**
     * Get CRF option name for current encoder
     */
    protected function getCrfOption(): string
    {
        $encoder = $this->getEncoder();

        // Different encoders use different CRF option names
        return match ($encoder) {
            'av1_qsv', 'av1_amf', 'av1_nvenc' => '-q:v',  // Hardware encoders use quality
            default => '-crf',  // Software encoders use CRF
        };
    }

    /**
     * Get preset option name for current encoder
     */
    protected function getPresetOption(): string
    {
        $encoder = $this->getEncoder();

        return match ($encoder) {
            'av1_qsv' => '-preset',
            'av1_amf' => '-quality',
            'av1_nvenc' => '-preset',
            default => '-preset',
        };
    }

    /**
     * Check if FFmpeg is available
     */
    public function isAvailable(): bool
    {
        try {
            /** @var ProcessFactory $factory */
            $factory = app(ProcessFactory::class);
            $process = $factory->timeout(5)->run([$this->binaryPath, '-version']);

            return $process->exitCode() === 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get FFmpeg version
     */
    public function version(): string
    {
        /** @var ProcessFactory $factory */
        $factory = app(ProcessFactory::class);
        $process = $factory->timeout(5)->run([$this->binaryPath, '-version']);

        if ($process->exitCode() !== 0) {
            throw new \RuntimeException('Failed to get FFmpeg version: '.$process->errorOutput());
        }

        // Extract version from output (e.g., "ffmpeg version 6.0")
        $output = trim($process->output());

        if (preg_match('/ffmpeg version ([\d.]+)/', $output, $matches)) {
            return $matches[1];
        }

        if (preg_match('/ffmpeg version ([^\s]+)/', $output, $matches)) {
            return $matches[1];
        }

        return explode("\n", $output)[0] ?? $output;
    }

    /**
     * Alias for version() for backward compatibility
     */
    public function getVersion(): string
    {
        return $this->version();
    }
}
