<?php

declare(strict_types=1);

namespace Foxws\AV1;

use Foxws\AV1\Filesystem\Disk;
use Foxws\AV1\Filesystem\Media;
use Foxws\AV1\Filesystem\MediaCollection;
use Foxws\AV1\Filesystem\TemporaryDirectories;
use Foxws\AV1\Support\AbAV1Encoder;
use Foxws\AV1\Support\Encoder;
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
     * Chainable methods for ab-av1 commands
     */

    /**
     * Set command to auto-encode with defaults from configuration
     */
    public function vmafEncode(): self
    {
        $this->encoder->builder()->command('auto-encode');

        // Apply default configuration values
        $abAv1Config = $this->config['ab-av1'] ?? [];

        if (isset($abAv1Config['preset'])) {
            $this->encoder->builder()->preset((string) $abAv1Config['preset']);
        }

        if (isset($abAv1Config['min_vmaf'])) {
            $this->encoder->builder()->minVmaf($abAv1Config['min_vmaf']);
        }

        if (isset($abAv1Config['max_encoded_percent'])) {
            $this->encoder->builder()->maxEncodedPercent($abAv1Config['max_encoded_percent']);
        }

        return $this;
    }

    /**
     * Set command to crf-search with defaults from configuration
     */
    public function crfSearch(): self
    {
        $this->encoder->builder()->command('crf-search');

        // Apply default configuration values
        $abAv1Config = $this->config['ab-av1'] ?? [];

        if (isset($abAv1Config['preset'])) {
            $this->encoder->builder()->preset((string) $abAv1Config['preset']);
        }

        if (isset($abAv1Config['min_vmaf'])) {
            $this->encoder->builder()->minVmaf($abAv1Config['min_vmaf']);
        }

        if (isset($abAv1Config['max_encoded_percent'])) {
            $this->encoder->builder()->maxEncodedPercent($abAv1Config['max_encoded_percent']);
        }

        return $this;
    }

    /**
     * Set command to sample-encode
     */
    public function sampleEncode(): self
    {
        $this->encoder->builder()->command('sample-encode');

        return $this;
    }

    /**
     * Set command to encode
     */
    public function encode(): self
    {
        $this->encoder->builder()->command('encode');

        return $this;
    }

    /**
     * Set command to vmaf
     */
    public function vmaf(): self
    {
        $this->encoder->builder()->command('vmaf');

        return $this;
    }

    /**
     * Set command to xpsnr
     */
    public function xpsnr(): self
    {
        $this->encoder->builder()->command('xpsnr');

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
     * Set output file
     */
    public function output(string $path): self
    {
        $this->encoder->builder()->output($path);

        return $this;
    }

    /**
     * Set reference file (for vmaf/xpsnr)
     */
    public function reference(string $path): self
    {
        $this->encoder->builder()->reference($path);

        return $this;
    }

    /**
     * Set distorted file (for vmaf/xpsnr)
     */
    public function distorted(string $path): self
    {
        $this->encoder->builder()->distorted($path);

        return $this;
    }

    /**
     * Set encoder preset
     */
    public function preset(string $preset): self
    {
        $this->encoder->builder()->preset($preset);

        return $this;
    }

    /**
     * Set minimum VMAF score
     */
    public function minVmaf(float|int $vmaf): self
    {
        $this->encoder->builder()->minVmaf($vmaf);

        return $this;
    }

    /**
     * Set CRF value
     */
    public function crf(int $crf): self
    {
        $this->encoder->builder()->crf($crf);

        return $this;
    }

    /**
     * Set maximum encoded file size percent
     */
    public function maxEncodedPercent(int $percent): self
    {
        $this->encoder->builder()->maxEncodedPercent($percent);

        return $this;
    }

    /**
     * Set minimum CRF value for searching
     */
    public function minCrf(int $crf): self
    {
        $this->encoder->builder()->minCrf($crf);

        return $this;
    }

    /**
     * Set maximum CRF value for searching
     */
    public function maxCrf(int $crf): self
    {
        $this->encoder->builder()->maxCrf($crf);

        return $this;
    }

    /**
     * Set sample duration in seconds
     */
    public function sample(int $seconds): self
    {
        $this->encoder->builder()->sample($seconds);

        return $this;
    }

    /**
     * Set VMAF model path
     */
    public function vmafModel(string $path): self
    {
        $this->encoder->builder()->vmafModel($path);

        return $this;
    }

    /**
     * Enable full VMAF calculation
     */
    public function fullVmaf(bool $enabled = true): self
    {
        $this->encoder->builder()->fullVmaf($enabled);

        return $this;
    }

    /**
     * Set pixel format
     */
    public function pixFmt(string $format): self
    {
        $this->encoder->builder()->pixFmt($format);

        return $this;
    }

    /**
     * Set verbose output
     */
    public function verbose(bool $enabled = true): self
    {
        $this->encoder->builder()->verbose($enabled);

        return $this;
    }

    /**
     * Forward other calls to encoder and return $this if the result is the encoder,
     * allowing for method chaining.
     */
    public function __call($method, $arguments)
    {
        $result = $this->forwardCallTo($encoder = $this->getEncoder(), $method, $arguments);

        return ($result === $encoder) ? $this : $result;
    }
}
