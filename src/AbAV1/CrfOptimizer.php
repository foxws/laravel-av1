<?php

declare(strict_types=1);

namespace Foxws\AV1\AbAV1;

use Illuminate\Support\Facades\Config;
use Psr\Log\LoggerInterface;

/**
 * Uses ab-av1 to find optimal CRF value for FFmpeg encoding
 */
class CrfOptimizer
{
    protected AbAV1Encoder $abav1Encoder;

    protected ?LoggerInterface $logger;

    public function __construct(
        ?LoggerInterface $logger = null,
        ?AbAV1Encoder $abav1Encoder = null
    ) {
        $this->abav1Encoder = $abav1Encoder ?? app(AbAV1Encoder::class);
        $this->logger = $logger;
    }

    /**
     * Find optimal CRF for target VMAF score
     *
     * @param  string  $inputPath  Path to input video file
     * @param  float|int  $targetVmaf  Target VMAF score (0-100)
     * @param  int  $preset  Encoder preset
     * @param  int|null  $minCrf  Minimum CRF to search
     * @param  int|null  $maxCrf  Maximum CRF to search
     * @return int Optimal CRF value
     */
    public function findOptimalCrf(
        string $inputPath,
        float|int $targetVmaf = 95,
        int $preset = 6,
        ?int $minCrf = null,
        ?int $maxCrf = null
    ): int {
        $minCrf = $minCrf ?? 20;
        $maxCrf = $maxCrf ?? 45;

        if ($this->logger) {
            $this->logger->info('Finding optimal CRF', [
                'input' => $inputPath,
                'target_vmaf' => $targetVmaf,
                'preset' => $preset,
                'min_crf' => $minCrf,
                'max_crf' => $maxCrf,
            ]);
        }

        // Build ab-av1 crf-search command
        $args = [
            'crf-search',
            '-i', $inputPath,
            '--preset', (string) $preset,
            '--min-vmaf', (string) $targetVmaf,
            '--min-crf', (string) $minCrf,
            '--max-crf', (string) $maxCrf,
        ];

        $result = $this->abav1Encoder->run($args);

        if (! $result->successful()) {
            if ($this->logger) {
                $this->logger->error('CRF search failed', [
                    'error' => $result->errorOutput(),
                ]);
            }

            // Return default CRF on failure
            return Config::integer('av1.ffmpeg.default_crf', 30);
        }

        // Parse output to extract CRF value
        $crf = $this->parseCrfFromOutput($result->output());

        if ($this->logger) {
            $this->logger->info('Found optimal CRF', [
                'crf' => $crf,
            ]);
        }

        return $crf;
    }

    /**
     * Parse CRF value from ab-av1 output
     */
    protected function parseCrfFromOutput(string $output): int
    {
        // ab-av1 outputs recommended CRF in various formats
        // Try to extract it using multiple patterns

        // Pattern 1: "Suggested CRF: 32"
        if (preg_match('/Suggested CRF:\s*(\d+)/i', $output, $matches)) {
            return (int) $matches[1];
        }

        // Pattern 2: "crf 32"
        if (preg_match('/crf\s+(\d+)/i', $output, $matches)) {
            return (int) $matches[1];
        }

        // Pattern 3: "CRF=32"
        if (preg_match('/CRF=(\d+)/i', $output, $matches)) {
            return (int) $matches[1];
        }

        // Pattern 4: Look for the last number in expected CRF range
        preg_match_all('/\b(\d+)\b/', $output, $matches);
        if (! empty($matches[1])) {
            foreach (array_reverse($matches[1]) as $number) {
                $num = (int) $number;
                if ($num >= 15 && $num <= 50) {
                    return $num;
                }
            }
        }

        // Return default if parsing fails
        return Config::integer('av1.ffmpeg.default_crf', 30);
    }

    /**
     * Get sample encoding to test quality
     */
    public function getSampleQuality(
        string $inputPath,
        int $crf,
        int $preset = 6,
        int $sampleSeconds = 30
    ): ?float {
        if ($this->logger) {
            $this->logger->info('Testing sample quality', [
                'input' => $inputPath,
                'crf' => $crf,
                'preset' => $preset,
                'sample_seconds' => $sampleSeconds,
            ]);
        }

        $tempOutput = sys_get_temp_dir().'/av1_sample_'.uniqid().'.mp4';

        try {
            // Build ab-av1 sample-encode command
            $args = [
                'sample-encode',
                '-i', $inputPath,
                '-o', $tempOutput,
                '--preset', (string) $preset,
                '--crf', (string) $crf,
                '--sample', (string) $sampleSeconds,
            ];

            $result = $this->abav1Encoder->run($args);

            if (! $result->successful()) {
                return null;
            }

            // Parse VMAF score from output
            return $this->parseVmafFromOutput($result->output());
        } finally {
            if (file_exists($tempOutput)) {
                @unlink($tempOutput);
            }
        }
    }

    /**
     * Parse VMAF score from output
     */
    protected function parseVmafFromOutput(string $output): ?float
    {
        // Look for VMAF score in output
        if (preg_match('/VMAF\s*[:\-]?\s*([\d.]+)/i', $output, $matches)) {
            return (float) $matches[1];
        }

        if (preg_match('/([\d.]+)\s*VMAF/i', $output, $matches)) {
            return (float) $matches[1];
        }

        return null;
    }
}
