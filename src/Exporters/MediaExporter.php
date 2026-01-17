<?php

declare(strict_types=1);

namespace Foxws\AV1\Exporters;

use Foxws\AV1\Filesystem\Disk;
use Foxws\AV1\Filesystem\Media;
use Foxws\AV1\Support\Encoder;
use Foxws\AV1\Support\EncoderResult;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Traits\ForwardsCalls;

class MediaExporter
{
    use ForwardsCalls;

    protected ?Encoder $encoder = null;

    protected ?Disk $toDisk = null;

    protected ?string $visibility = null;

    protected ?string $toPath = null;

    protected ?array $afterSavingCallbacks = [];

    public function __construct(Encoder $encoder)
    {
        $this->encoder = $encoder;
    }

    public function getDisk(): Disk
    {
        if ($this->toDisk) {
            return $this->toDisk;
        }

        $media = $this->encoder->getMediaCollection();

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
     * Get the current visibility setting
     */
    public function getVisibility(): ?string
    {
        return $this->visibility;
    }

    /**
     * Get the current target path
     */
    public function getPath(): ?string
    {
        return $this->toPath;
    }

    /**
     * Returns the final command, useful for debugging purposes.
     */
    public function getCommand(): string
    {
        return $this->encoder->getCommand();
    }

    /**
     * Dump the final command and end the script.
     */
    public function dd(): void
    {
        dd($this->getCommand());
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

    /**
     * Execute encoding and save output
     */
    public function save(?string $path = null): EncoderResult
    {
        // Set output path in builder if not already set
        $builder = $this->encoder->builder();

        if (! $builder->getOutput()) {
            $outputPath = $path ?? 'output.mp4';

            if ($this->toPath) {
                $outputPath = $this->toPath.$outputPath;
            }

            $builder->output($outputPath);
        }

        // Execute the encoder
        $result = $this->encoder->run();

        if (! $result->isSuccessful()) {
            throw new \RuntimeException(
                "Encoding failed: {$result->getErrorOutput()}"
            );
        }

        // Get the output file path
        $outputPath = $result->getOutputPath();

        if ($outputPath && File::exists($outputPath)) {
            // Upload to destination disk if different
            $destinationPath = $path ?? basename($outputPath);

            if ($this->toPath) {
                $destinationPath = $this->toPath.basename($destinationPath);
            }

            $disk = $this->getDisk();
            $contents = File::get($outputPath);

            if ($this->visibility) {
                $disk->put($destinationPath, $contents, ['visibility' => $this->visibility]);
            } else {
                $disk->put($destinationPath, $contents);
            }

            // Call after-saving callbacks
            foreach ($this->afterSavingCallbacks as $callback) {
                $callback($result, $destinationPath);
            }
        }

        return $result;
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
