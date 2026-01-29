<?php

declare(strict_types=1);

namespace Foxws\AV1;

use Foxws\AV1\FFmpeg\VideoEncoder;
use Foxws\AV1\Filesystem\Disk;
use Foxws\AV1\Filesystem\Media;
use Foxws\AV1\Support\EncodingResult;
use Illuminate\Support\Traits\ForwardsCalls;

class MediaExporter
{
    use ForwardsCalls;

    protected ?VideoEncoder $encoder = null;

    protected ?Disk $toDisk = null;

    protected ?string $visibility = null;

    protected ?string $toPath = null;

    protected ?array $afterSavingCallbacks = [];

    public function __construct(VideoEncoder $encoder)
    {
        $this->encoder = $encoder;
    }

    protected function getDisk(): Disk
    {
        if ($this->toDisk) {
            return $this->toDisk;
        }

        $media = $this->encoder->getCollection();

        /** @var Disk $disk */
        $disk = $media->first()->getDisk();

        return $this->toDisk = $disk->clone();
    }

    public function toDisk($disk): self
    {
        $this->toDisk = Disk::make($disk);

        return $this;
    }

    public function toPath(string $path): self
    {
        $this->toPath = rtrim($path, '/').'/';

        return $this;
    }

    public function withVisibility(string $visibility): self
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Adds a callable to the callbacks array.
     */
    public function afterSaving(callable $callback): self
    {
        $this->afterSavingCallbacks[] = $callback;

        return $this;
    }

    protected function prepareSaving(?string $path = null): ?Media
    {
        $outputMedia = $path ? $this->getDisk()->makeMedia($path) : null;

        return $outputMedia;
    }

    protected function runAfterSavingCallbacks(EncodingResult $result)
    {
        if (empty($this->afterSavingCallbacks)) {
            return;
        }

        foreach ($this->afterSavingCallbacks as $key => $callback) {
            call_user_func($callback, $this, $result);

            unset($this->afterSavingCallbacks[$key]);
        }
    }

    public function save(?string $path = null): MediaOpener
    {
        // Execute the encoding operation (writes to temporary directory)
        $result = $this->encoder->encode();

        // Determine target disk
        $targetDisk = $this->toDisk ?: $this->getDisk();

        // Copy outputs from temporary directory to target disk and cleanup
        $result->toDisk($targetDisk, $this->visibility, true, $this->toPath);

        $this->runAfterSavingCallbacks($result);

        return $this->getMediaOpener();
    }

    protected function getMediaOpener(): MediaOpener
    {
        return new MediaOpener(
            $this->encoder->getCollection()->last()->getDisk()->getName(),
            $this->encoder,
            $this->encoder->getCollection()
        );
    }

    /**
     * Forwards the call to the encoder object and returns the result
     * if it's something different than the encoder object itself.
     */
    public function __call($method, $arguments)
    {
        $result = $this->forwardCallTo($encoder = $this->encoder, $method, $arguments);

        return ($result === $encoder) ? $this : $result;
    }
}
