<?php

declare(strict_types=1);

namespace Foxws\AV1\Filesystem;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Traits\ForwardsCalls;

/**
 * @method bool exists(string $path)
 * @method string|null get(string $path)
 * @method resource|null readStream(string $path)
 * @method bool put(string $path, mixed $contents, array $options = [])
 * @method bool writeStream(string $path, $resource, array $options = [])
 * @method bool makeDirectory(string $path)
 * @method bool delete(string|array $paths)
 * @method bool setVisibility(string $path, string $visibility)
 * @method string path(string $path)
 * @method array allFiles(string|null $directory = null)
 */
class Disk
{
    use ForwardsCalls;

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
        return $this->forwardDecoratedCallTo($this->filesystem, $method, $arguments);
    }
}
