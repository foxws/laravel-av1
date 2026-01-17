<?php

declare(strict_types=1);

namespace Foxws\AV1\Filesystem;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * @method bool exists(string $path)
 * @method resource|false readStream(string $path)
 * @method bool put(string $path, mixed $contents, mixed $options = [])
 * @method bool putFileAs(string $path, \Illuminate\Http\UploadedFile|\Illuminate\Http\File|string $file, string $name, mixed $options = [])
 * @method string|false putFile(string $path, \Illuminate\Http\UploadedFile|\Illuminate\Http\File|string $file, mixed $options = [])
 * @method string get(string $path)
 * @method array files(string $directory = null, bool $recursive = false)
 * @method array allFiles(string $directory = null)
 * @method array directories(string $directory = null, bool $recursive = false)
 * @method array allDirectories(string $directory = null)
 * @method bool makeDirectory(string $path)
 * @method bool deleteDirectory(string $directory)
 * @method bool delete(string|array $paths)
 * @method bool copy(string $from, string $to)
 * @method bool move(string $from, string $to)
 * @method int size(string $path)
 * @method int lastModified(string $path)
 * @method string url(string $path)
 * @method string path(string $path)
 * @method bool setVisibility(string $path, string $visibility)
 * @method string getVisibility(string $path)
 */
class Disk
{
    protected Filesystem $filesystem;

    protected string $name;

    public function __construct(Filesystem $filesystem, string $name = 'local')
    {
        $this->filesystem = $filesystem;
        $this->name = $name;
    }

    public static function make(self|Filesystem|string $disk): self
    {
        if ($disk instanceof self) {
            return $disk;
        }

        if ($disk instanceof Filesystem) {
            return new self($disk);
        }

        return new self(Storage::disk($disk), $disk);
    }

    public function clone(): self
    {
        return new self($this->filesystem, $this->name);
    }

    public function filesystem(): Filesystem
    {
        return $this->filesystem;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function makeMedia(string $path): Media
    {
        return Media::make($this, $path);
    }

    public function __call($method, $arguments)
    {
        return $this->filesystem->$method(...$arguments);
    }
}
