<?php

declare(strict_types=1);

namespace Foxws\AV1;

use Foxws\AV1\FFmpeg\VideoEncoder;
use Foxws\AV1\Filesystem\Disk;
use Foxws\AV1\Filesystem\Media;
use Foxws\AV1\Filesystem\MediaCollection;
use Foxws\AV1\Filesystem\TemporaryDirectories;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Traits\ForwardsCalls;

class MediaOpener
{
    use ForwardsCalls;

    protected ?Disk $disk = null;

    protected ?VideoEncoder $encoder = null;

    protected ?MediaCollection $collection = null;

    public function __construct(
        Disk|string|null $disk = null,
        ?VideoEncoder $encoder = null,
        ?MediaCollection $mediaCollection = null
    ) {
        $this->fromDisk($disk ?: Config::string('filesystems.default'));

        $this->encoder = $encoder ?: app(VideoEncoder::class)->fresh();

        $this->collection = $mediaCollection ?: new MediaCollection;
    }

    public function clone(): self
    {
        return new MediaOpener(
            $this->disk,
            $this->encoder,
            $this->collection
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

    public function getEncoder(): VideoEncoder
    {
        return $this->encoder;
    }

    /**
     * Returns an instance of MediaExporter with the encoder.
     */
    public function export(): MediaExporter
    {
        return new MediaExporter($this->encoder);
    }

    public function cleanupTemporaryFiles(): self
    {
        app(TemporaryDirectories::class)->deleteAll();

        return $this;
    }

    public function __call($method, $arguments)
    {
        $result = $this->forwardCallTo($encoder = $this->getEncoder(), $method, $arguments);

        return ($result === $encoder) ? $this : $result;
    }
}
