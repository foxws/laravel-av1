<?php

declare(strict_types=1);

namespace Foxws\AV1\FFmpeg;

/**
 * Builds FFmpeg command arrays for AV1 encoding
 */
class FFmpegCommandBuilder
{
    protected string $ffmpegPath;

    protected ?string $hwaccelMethod = null;

    protected string $encoder = 'libsvtav1';

    protected string $audioCodec = 'libopus';

    protected int $crf = 30;

    protected int $preset = 6;

    protected int $threads = 0;

    protected ?string $pixelFormat = null;

    protected ?string $videoFilter = null;

    protected array $customArgs = [];

    public function __construct(string $ffmpegPath = 'ffmpeg')
    {
        $this->ffmpegPath = $ffmpegPath;
    }

    public static function make(string $ffmpegPath = 'ffmpeg'): self
    {
        return new self($ffmpegPath);
    }

    public function withHwaccel(?string $method): self
    {
        $this->hwaccelMethod = $method;

        return $this;
    }

    public function withEncoder(string $encoder): self
    {
        $this->encoder = $encoder;

        return $this;
    }

    public function withAudioCodec(string $codec): self
    {
        $this->audioCodec = $codec;

        return $this;
    }

    public function withCrf(int $crf): self
    {
        $this->crf = $crf;

        return $this;
    }

    public function withPreset(int $preset): self
    {
        $this->preset = $preset;

        return $this;
    }

    public function withThreads(int $threads): self
    {
        $this->threads = $threads;

        return $this;
    }

    public function withPixelFormat(?string $format): self
    {
        $this->pixelFormat = $format;

        return $this;
    }

    public function withVideoFilter(?string $filter): self
    {
        $this->videoFilter = $filter;

        return $this;
    }

    public function withCustomArgs(array $args): self
    {
        $this->customArgs = $args;

        return $this;
    }

    /**
     * Build the complete FFmpeg command array
     */
    public function build(string $input, string $output): array
    {
        $args = [$this->ffmpegPath];

        // Hardware acceleration for decoding
        if ($this->hwaccelMethod) {
            $args[] = '-hwaccel';
            $args[] = $this->hwaccelMethod;
        }

        // Input
        $args[] = '-i';
        $args[] = $input;

        // Video codec
        $args[] = '-c:v';
        $args[] = $this->encoder;

        // Audio codec
        $args[] = '-c:a';
        $args[] = $this->audioCodec;

        // CRF
        $args[] = $this->getCrfOption();
        $args[] = (string) $this->crf;

        // Preset
        $args[] = $this->getPresetOption();
        $args[] = (string) $this->preset;

        // Threads
        $args[] = '-threads';
        $args[] = (string) $this->threads;

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
     * Get CRF option name for current encoder
     */
    protected function getCrfOption(): string
    {
        return match ($this->encoder) {
            'av1_qsv', 'av1_amf', 'av1_nvenc' => '-q:v',
            default => '-crf',
        };
    }

    /**
     * Get preset option name for current encoder
     */
    protected function getPresetOption(): string
    {
        return match ($this->encoder) {
            'av1_amf' => '-quality',
            default => '-preset',
        };
    }
}
