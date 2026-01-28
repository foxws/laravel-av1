<?php

declare(strict_types=1);

namespace Foxws\AV1\AbAV1;

use Illuminate\Process\Factory as ProcessFactory;
use Illuminate\Process\ProcessResult;
use Illuminate\Support\Facades\Config;
use Psr\Log\LoggerInterface;

/**
 * Finds optimal CRF value using ab-av1
 */
class CrfFinder
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
        $this->timeout = $timeout ?? Config::integer('av1.ab-av1.timeout', 14400);
    }

    /**
     * Find optimal CRF for target VMAF score
     */
    public function find(
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
            ]);
        }

        $args = [
            $this->binaryPath,
            'crf-search',
            '-i', $inputPath,
            '--preset', (string) $preset,
            '--min-vmaf', (string) $targetVmaf,
            '--min-crf', (string) $minCrf,
            '--max-crf', (string) $maxCrf,
        ];

        $result = $this->execute($args);

        if (! $result->successful()) {
            return Config::integer('av1.ffmpeg.default_crf', 30);
        }

        return $this->parseCrfFromOutput($result->output());
    }

    /**
     * Execute ab-av1 command
     */
    protected function execute(array $command): ProcessResult
    {
        /** @var ProcessFactory $factory */
        $factory = app(ProcessFactory::class);

        return $factory
            ->timeout($this->timeout)
            ->run($command);
    }

    /**
     * Parse CRF value from ab-av1 output
     */
    protected function parseCrfFromOutput(string $output): int
    {
        if (preg_match('/Suggested CRF:\s*(\d+)/i', $output, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/crf\s+(\d+)/i', $output, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/CRF=(\d+)/i', $output, $matches)) {
            return (int) $matches[1];
        }

        preg_match_all('/\b(\d+)\b/', $output, $matches);
        if (! empty($matches[1])) {
            foreach (array_reverse($matches[1]) as $number) {
                $num = (int) $number;
                if ($num >= 15 && $num <= 50) {
                    return $num;
                }
            }
        }

        return Config::integer('av1.ffmpeg.default_crf', 30);
    }
}
