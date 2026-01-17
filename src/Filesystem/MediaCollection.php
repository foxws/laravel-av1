<?php

declare(strict_types=1);

namespace Foxws\AV1\Filesystem;

use Illuminate\Support\Collection;

class MediaCollection
{
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
}
