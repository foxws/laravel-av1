<?php

declare(strict_types=1);

namespace Foxws\AV1;

use Foxws\AV1\Filesystem\Disk;
use Foxws\AV1\Filesystem\Media;
use Foxws\AV1\Filesystem\MediaCollection;
use Foxws\AV1\Filesystem\TemporaryDirectories;
use Foxws\AV1\Support\AbAV1Encoder;
use Foxws\AV1\Support\Encoder;
use Foxws\AV1\Support\FFmpegEncoder;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Traits\ForwardsCalls;

class MediaOpener
{
    use ForwardsCalls;

    protected ?Disk $disk = null;

    protected ?Encoder $encoder = null;

    protected ?MediaCollection $collection = null;

    protected array $config = [];

    public function __construct(
        Disk|string|null $disk = null,
        ?Encoder $encoder = null,
        ?MediaCollection $mediaCollection = null,
        ?array $config = null
    ) {
        $this->fromDisk($disk ?: Config::string('filesystems.default'));

        $this->encoder = $encoder ?: app(Encoder::class);

        $this->collection = $mediaCollection ?: new MediaCollection;

        // Use the provided config, or resolve from container
        $this->config = $config ?? app('laravel-av1-configuration');
    }

    public function clone(): self
    {
        return new MediaOpener(
            $this->disk,
            $this->encoder,
            $this->collection,
            $this->config
        );
    }

    public function fromDisk(Disk|Filesystem|string $disk): self
    {
        $this->disk = Disk::make($disk);

        return $this;
    }

    public function getDisk(): ?Disk
    {
        return $this->disk;
    }

    protected static function makeLocalDiskFromPath(string $path): Disk
    {
        $adapter = (new FilesystemManager(app()))->createLocalDriver([
            'root' => $path,
        ]);

        return Disk::make($adapter);
    }

    /**
     * Instantiates a Media object for each given path.
     */
    public function open($paths): self
    {
        foreach (Arr::wrap($paths) as $path) {
            if ($path instanceof UploadedFile) {
                $disk = static::makeLocalDiskFromPath($path->getPath());

                $media = Media::make($disk, $path->getFilename());
            } else {
                $media = Media::make($this->disk, $path);
            }

            $this->collection->push($media);
        }

        // Initialize the encoder with the collection
        $this->encoder->open($this->collection);

        return $this;
    }

    /**
     * Open files from a specific disk
     */
    public function openFromDisk(Filesystem|string $disk, $paths): self
    {
        return $this->fromDisk($disk)->open($paths);
    }

    public function get(): MediaCollection
    {
        return $this->collection;
    }

    public function each($items, callable $callback): self
    {
        Collection::make($items)->each(function ($item, $key) use ($callback) {
            return $callback($this->clone(), $item, $key);
        });

        return $this;
    }

    public function getEncoder(): Encoder
    {
        return $this->encoder;
    }

    /**
     * Switch to AbAV1 encoder
     */
    public function abav1(): self
    {
        $abav1Encoder = app(AbAV1Encoder::class);
        $this->encoder->setEncoder($abav1Encoder);

        return $this;
    }

    /**
     * Switch to FFmpeg encoder
     */
    public function ffmpeg(): self
    {
        $ffmpegEncoder = app(FFmpegEncoder::class);

        // Apply default FFmpeg configuration
        $ffmpegConfig = $this->config['ffmpeg'] ?? [];

        if (isset($ffmpegConfig['encoder'])) {
            $ffmpegEncoder->setEncoder($ffmpegConfig['encoder']);
        }

        if (isset($ffmpegConfig['hardware_acceleration'])) {
            $ffmpegEncoder->useHardwareAcceleration($ffmpegConfig['hardware_acceleration']);
        }

        $this->encoder->setEncoder($ffmpegEncoder);

        return $this;
    }

    /**
     * Use FFmpeg with hardware acceleration
     */
    public function useHardwareAcceleration(bool $enabled = true): self
    {
        $encoder = $this->encoder->getEncoder();

        if ($encoder instanceof FFmpegEncoder) {
            $encoder->useHardwareAcceleration($enabled);
        } else {
            // Switch to FFmpeg encoder if not already using it
            $this->ffmpeg();
            $ffmpegEncoder = $this->encoder->getEncoder();
            if ($ffmpegEncoder instanceof FFmpegEncoder) {
                $ffmpegEncoder->useHardwareAcceleration($enabled);
            }
        }

        return $this;
    }

    /**
     * Enable automatic CRF optimization using ab-av1
     */
    public function withAutoCrf(bool $enabled = true): self
    {
        $this->encoder->builder()->autoCrf($enabled);

        return $this;
    }

    /**
     * FFmpeg encode with optional auto CRF
     */
    public function ffmpegEncode(): self
    {
        // Switch to FFmpeg encoder if not already
        $encoder = $this->encoder->getEncoder();
        if (! $encoder instanceof FFmpegEncoder) {
            $this->ffmpeg();
        }

        // Apply default FFmpeg configuration
        $ffmpegConfig = $this->config['ffmpeg'] ?? [];

        if (isset($ffmpegConfig['default_crf']) && ! $this->encoder->builder()->getOptions()['crf'] ?? null) {
            $this->encoder->builder()->crf($ffmpegConfig['default_crf']);
        }

        if (isset($ffmpegConfig['default_preset'])) {
            $this->encoder->builder()->preset((string) $ffmpegConfig['default_preset']);
        }

        if (isset($ffmpegConfig['pixel_format'])) {
            $this->encoder->builder()->pixFmt($ffmpegConfig['pixel_format']);
        }

        if (isset($ffmpegConfig['audio_codec'])) {
            $this->encoder->builder()->audioCodec($ffmpegConfig['audio_codec']);
        }

        // Enable auto CRF if configured
        if (($ffmpegConfig['auto_crf'] ?? false) && ! isset($this->encoder->builder()->getOptions()['crf'])) {
            $this->encoder->builder()->autoCrf(true);
        }

        return $this;
    }

    /**
     * FFmpeg encode with auto CRF optimization
     */
    public function ffmpegAutoEncode(): self
    {
        $this->ffmpegEncode();
        $this->encoder->builder()->autoCrf(true);

        // Set target VMAF if not already set
        $options = $this->encoder->builder()->getOptions();
        if (! isset($options['target_vmaf']) && ! isset($options['min-vmaf'])) {
            $targetVmaf = $this->config['ab-av1']['min_vmaf'] ?? 95;
            $this->encoder->builder()->targetVmaf($targetVmaf);
        }

        return $this;
    }

    /**
     * Returns an instance of MediaExporter with the encoder.
     */
    public function export(): Exporters\MediaExporter
    {
        return new Exporters\MediaExporter($this->encoder);
    }

    public function cleanupTemporaryFiles(): self
    {
        app(TemporaryDirectories::class)->deleteAll();

        return $this;
    }

    /**
     * Set input file (overrides opened file)
     */
    public function input(string $path): self
    {
        // Try to resolve from collection first
        if ($this->collection) {
            $media = $this->collection->findByPath($path);
            if ($media) {
                $path = $media->getSafeInputPath();
            }
        }

        $this->encoder->builder()->input($path);

        return $this;
    }

    /**
     * Forward calls to the encoder's builder for direct option access,
     * or to the encoder itself for encoder-specific methods.
     * Returns $this to maintain fluent chaining.
     */
    public function __call($method, $arguments)
    {
        $encoder = $this->getEncoder();
        $actualEncoder = $encoder->getEncoder();

        // Check if the method exists on the actual encoder (AbAV1Encoder, FFmpegEncoder)
        if (method_exists($actualEncoder, $method)) {
            // Pass the builder as the first argument for encoder methods
            $actualEncoder->$method($encoder->builder(), ...$arguments);

            return $this;
        }

        // Otherwise try the encoder wrapper
        $result = $this->forwardCallTo($encoder, $method, $arguments);

        return ($result === $encoder) ? $this : $result;
    }
}
