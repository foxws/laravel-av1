<?php

declare(strict_types=1);

namespace Foxws\AV1\Filesystem;

use Illuminate\Support\Collection;
use Illuminate\Support\Traits\ForwardsCalls;

class MediaCollection
{
    use ForwardsCalls;

    protected Collection $collection;

    public function __construct()
    {
        $this->collection = Collection::make();
    }

    public function push(Media $media): self
    {
        $this->collection->push($media);

        return $this;
    }

    public function first(): ?Media
    {
        return $this->collection->first();
    }

    public function last(?callable $callback = null, mixed $default = null): mixed
    {
        return $this->collection->last($callback, $default);
    }

    public function collection(): Collection
    {
        return $this->collection;
    }

    public function count(): int
    {
        return $this->collection->count();
    }

    public function findByPath(string $path): ?Media
    {
        return $this->collection->first(function (Media $media) use ($path) {
            return $media->getPath() === $path;
        });
    }

    public function getLocalPaths(): array
    {
        return $this->collection->map(fn (Media $media) => $media->getLocalPath())->toArray();
    }

    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->collection(), $method, $parameters);
    }
}
