<?php

declare(strict_types=1);

namespace Foxws\AV1\Support;

use InvalidArgumentException;

/**
 * Builder for constructing ab-av1 command arguments
 */
class CommandBuilder
{
    protected ?string $command = null;

    protected array $options = [];

    protected ?string $input = null;

    protected ?string $output = null;

    protected ?string $reference = null;

    protected ?string $distorted = null;

    public function __construct()
    {
    }

    public static function make(): self
    {
        return new self;
    }

    /**
     * Set the ab-av1 command to use
     */
    public function command(string $command): self
    {
        $validCommands = ['auto-encode', 'crf-search', 'sample-encode', 'encode', 'vmaf', 'xpsnr'];

        if (! in_array($command, $validCommands)) {
            throw new InvalidArgumentException("Invalid command: {$command}");
        }

        $this->command = $command;

        return $this;
    }

    /**
     * Set input file
     */
    public function input(string $path): self
    {
        $this->input = $path;

        return $this;
    }

    /**
     * Set output file
     */
    public function output(string $path): self
    {
        $this->output = $path;

        return $this;
    }

    /**
     * Set reference file (for vmaf/xpsnr)
     */
    public function reference(string $path): self
    {
        $this->reference = $path;

        return $this;
    }

    /**
     * Set distorted file (for vmaf/xpsnr)
     */
    public function distorted(string $path): self
    {
        $this->distorted = $path;

        return $this;
    }

    /**
     * Set encoder preset
     */
    public function preset(string $preset): self
    {
        $this->options['preset'] = $preset;

        return $this;
    }

    /**
     * Set minimum VMAF score
     */
    public function minVmaf(float|int $vmaf): self
    {
        $this->options['min-vmaf'] = $vmaf;

        return $this;
    }

    /**
     * Set CRF value
     */
    public function crf(int $crf): self
    {
        $this->options['crf'] = $crf;

        return $this;
    }

    /**
     * Set encoder (default: svt-av1)
     */
    public function encoder(string $encoder): self
    {
        $this->options['encoder'] = $encoder;

        return $this;
    }

    /**
     * Set maximum encoded file size in bytes
     */
    public function maxEncodedPercent(int $percent): self
    {
        $this->options['max-encoded-percent'] = $percent;

        return $this;
    }

    /**
     * Set minimum CRF value for searching
     */
    public function minCrf(int $crf): self
    {
        $this->options['min-crf'] = $crf;

        return $this;
    }

    /**
     * Set maximum CRF value for searching
     */
    public function maxCrf(int $crf): self
    {
        $this->options['max-crf'] = $crf;

        return $this;
    }

    /**
     * Set sample duration in seconds
     */
    public function sample(int $seconds): self
    {
        $this->options['sample'] = $seconds;

        return $this;
    }

    /**
     * Set VMAF model path
     */
    public function vmafModel(string $path): self
    {
        $this->options['vmaf-model'] = $path;

        return $this;
    }

    /**
     * Set number of VMAF threads
     */
    public function vmafThreads(int $threads): self
    {
        $this->options['vmaf-threads'] = $threads;

        return $this;
    }

    /**
     * Set pixel format
     */
    public function pixFmt(string $format): self
    {
        $this->options['pix-fmt'] = $format;

        return $this;
    }

    /**
     * Enable full VMAF calculation
     */
    public function fullVmaf(bool $enabled = true): self
    {
        if ($enabled) {
            $this->options['full-vmaf'] = true;
        } else {
            unset($this->options['full-vmaf']);
        }

        return $this;
    }

    /**
     * Set temporary directory
     */
    public function tempDir(string $path): self
    {
        $this->options['temp-dir'] = $path;

        return $this;
    }

    /**
     * Set verbose output
     */
    public function verbose(bool $enabled = true): self
    {
        if ($enabled) {
            $this->options['verbose'] = true;
        } else {
            unset($this->options['verbose']);
        }

        return $this;
    }

    /**
     * Add a custom option
     */
    public function withOption(string $key, mixed $value): self
    {
        $this->options[$key] = $value;

        return $this;
    }

    /**
     * Build command array for process execution
     */
    public function buildArray(): array
    {
        if (! $this->command) {
            throw new InvalidArgumentException('Command not set');
        }

        $arguments = ['ab-av1', $this->command];

        // Add command-specific required arguments
        switch ($this->command) {
            case 'auto-encode':
            case 'crf-search':
                if (! $this->input) {
                    throw new InvalidArgumentException('Input file required');
                }
                if (! isset($this->options['preset'])) {
                    throw new InvalidArgumentException('Preset required');
                }
                if (! isset($this->options['min-vmaf'])) {
                    throw new InvalidArgumentException('Min VMAF required');
                }
                $arguments[] = '-i';
                $arguments[] = $this->input;
                break;

            case 'sample-encode':
            case 'encode':
                if (! $this->input) {
                    throw new InvalidArgumentException('Input file required');
                }
                if (! isset($this->options['crf'])) {
                    throw new InvalidArgumentException('CRF required');
                }
                if (! isset($this->options['preset'])) {
                    throw new InvalidArgumentException('Preset required');
                }
                $arguments[] = '-i';
                $arguments[] = $this->input;
                break;

            case 'vmaf':
            case 'xpsnr':
                if (! $this->reference) {
                    throw new InvalidArgumentException('Reference file required');
                }
                if (! $this->distorted) {
                    throw new InvalidArgumentException('Distorted file required');
                }
                $arguments[] = '--reference';
                $arguments[] = $this->reference;
                $arguments[] = '--distorted';
                $arguments[] = $this->distorted;
                break;
        }

        // Add output for encode commands
        if ($this->output && in_array($this->command, ['auto-encode', 'sample-encode', 'encode'])) {
            $arguments[] = '-o';
            $arguments[] = $this->output;
        }

        // Add options
        foreach ($this->options as $key => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $arguments[] = "--{$key}";
                }
            } else {
                $arguments[] = "--{$key}";
                $arguments[] = (string) $value;
            }
        }

        return $arguments;
    }

    /**
     * Build command string (for debugging)
     */
    public function build(): string
    {
        return implode(' ', array_map(fn ($arg) => escapeshellarg($arg), $this->buildArray()));
    }

    /**
     * Reset builder
     */
    public function reset(): self
    {
        $this->command = null;
        $this->options = [];
        $this->input = null;
        $this->output = null;
        $this->reference = null;
        $this->distorted = null;

        return $this;
    }

    public function getCommand(): ?string
    {
        return $this->command;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getInput(): ?string
    {
        return $this->input;
    }

    public function getOutput(): ?string
    {
        return $this->output;
    }
}
