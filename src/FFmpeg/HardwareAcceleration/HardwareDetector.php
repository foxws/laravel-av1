<?php

declare(strict_types=1);

namespace Foxws\AV1\FFmpeg\HardwareAcceleration;

use Foxws\AV1\FFmpeg\HardwareAcceleration\Enums\HardwareEncoder;
use Foxws\AV1\FFmpeg\HardwareAcceleration\Enums\SoftwareEncoder;
use Illuminate\Process\Factory as ProcessFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * Detects available hardware acceleration encoders for AV1
 */
class HardwareDetector
{
    protected string $ffmpegPath;

    protected ProcessFactory $processFactory;

    public function __construct(?string $ffmpegPath = null)
    {
        $this->ffmpegPath = $ffmpegPath ?? Config::string('av1.binaries.ffmpeg', 'ffmpeg');
        $this->processFactory = app(ProcessFactory::class);
    }

    /**
     * Get all available AV1 encoders
     */
    public function getAvailableEncoders(): array
    {
        return Cache::remember('av1.available_encoders', 3600, function () {
            $available = [];

            $encoders = $this->listFFmpegEncoders();

            // Check hardware encoders
            foreach (HardwareEncoder::cases() as $encoder) {
                if (in_array($encoder->value, $encoders)) {
                    $available[$encoder->value] = [
                        'name' => $encoder->label(),
                        'type' => 'hardware',
                        'priority' => $encoder->priority(),
                    ];
                }
            }

            // Check software encoders
            foreach (SoftwareEncoder::cases() as $encoder) {
                if (in_array($encoder->value, $encoders)) {
                    $available[$encoder->value] = [
                        'name' => $encoder->label(),
                        'type' => 'software',
                        'priority' => $encoder->priority(),
                    ];
                }
            }

            return $available;
        });
    }

    /**
     * Get the best available encoder (hardware first, then software)
     */
    public function getBestEncoder(): ?string
    {
        $available = $this->getAvailableEncoders();

        if (empty($available)) {
            return null;
        }

        // Sort by priority
        uasort($available, fn ($a, $b) => $a['priority'] <=> $b['priority']);

        return array_key_first($available);
    }

    /**
     * Get the best hardware encoder available
     */
    public function getBestHardwareEncoder(): ?string
    {
        $available = $this->getAvailableEncoders();

        foreach ($available as $encoder => $info) {
            if ($info['type'] === 'hardware') {
                return $encoder;
            }
        }

        return null;
    }

    /**
     * Check if a specific encoder is available
     */
    public function hasEncoder(string $encoder): bool
    {
        return array_key_exists($encoder, $this->getAvailableEncoders());
    }

    /**
     * Check if any hardware encoder is available
     */
    public function hasHardwareAcceleration(): bool
    {
        return $this->getBestHardwareEncoder() !== null;
    }

    /**
     * Get encoder type (hardware or software)
     */
    public function getEncoderType(string $encoder): ?string
    {
        $available = $this->getAvailableEncoders();

        return $available[$encoder]['type'] ?? null;
    }

    /**
     * List all encoders available in FFmpeg
     */
    protected function listFFmpegEncoders(): array
    {
        try {
            $process = $this->processFactory
                ->timeout(10)
                ->run([$this->ffmpegPath, '-encoders', '-hide_banner']);

            if ($process->exitCode() !== 0) {
                return [];
            }

            $output = $process->output();
            $encoders = [];

            // Parse FFmpeg encoder list
            // Format: " V..... libsvtav1           SVT-AV1(Scalable Video Technology for AV1) encoder (codec av1)"
            foreach (explode("\n", $output) as $line) {
                if (preg_match('/^\s*V\.\.\.\.\.\s+(\S+)\s+/', $line, $matches)) {
                    $encoders[] = $matches[1];
                }
            }

            return $encoders;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get hardware acceleration method for decoding (if available)
     */
    public function getHardwareAccelMethod(): ?string
    {
        // Check for hardware acceleration methods
        $methods = [
            'qsv' => 'Intel Quick Sync',
            'vaapi' => 'VA-API (Linux)',
            'cuda' => 'NVIDIA CUDA',
            'vulkan' => 'Vulkan',
        ];

        try {
            $process = $this->processFactory
                ->timeout(10)
                ->run([$this->ffmpegPath, '-hwaccels', '-hide_banner']);

            if ($process->exitCode() !== 0) {
                return null;
            }

            $output = $process->output();

            foreach ($methods as $method => $name) {
                if (str_contains($output, $method)) {
                    return $method;
                }
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }

    /**
     * Clear the encoder cache
     */
    public function clearCache(): void
    {
        Cache::forget('av1.available_encoders');
    }

    /**
     * Get detailed information about available encoders
     */
    public function getEncoderInfo(): array
    {
        return [
            'encoders' => $this->getAvailableEncoders(),
            'best_encoder' => $this->getBestEncoder(),
            'best_hardware' => $this->getBestHardwareEncoder(),
            'has_hardware' => $this->hasHardwareAcceleration(),
            'hwaccel_method' => $this->getHardwareAccelMethod(),
        ];
    }
}
