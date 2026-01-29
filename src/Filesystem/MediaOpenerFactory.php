<?php

declare(strict_types=1);

namespace Foxws\AV1\Filesystem;

use Closure;
use Foxws\AV1\FFmpeg\VideoEncoder;
use Foxws\AV1\MediaOpener;
use Illuminate\Support\Traits\ForwardsCalls;

class MediaOpenerFactory
{
    use ForwardsCalls;

    protected ?string $defaultDisk = null;

    protected ?VideoEncoder $encoder = null;

    protected ?Closure $encoderResolver = null;

    public function __construct(
        ?string $defaultDisk = null,
        ?VideoEncoder $encoder = null,
        ?Closure $encoderResolver = null
    ) {
        $this->defaultDisk = $defaultDisk;
        $this->encoder = $encoder;
        $this->encoderResolver = $encoderResolver;
    }

    protected function encoder(): VideoEncoder
    {
        if ($this->encoder) {
            return $this->encoder;
        }

        $resolver = $this->encoderResolver;

        return $this->encoder = $resolver();
    }

    public function new(): MediaOpener
    {
        return new MediaOpener($this->defaultDisk, $this->encoder());
    }

    /**
     * Handle dynamic method calls into AV1.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->new(), $method, $parameters);
    }
}
