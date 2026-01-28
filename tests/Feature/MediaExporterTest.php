<?php

declare(strict_types=1);

// MediaExporter is part of the deprecated v1.x API
// v2.0 uses direct encoding with VideoEncoder instead of MediaOpener/MediaExporter chain
// This file is kept for reference but all tests are removed

it('media exporter is deprecated in v2.0', function () {
    expect(true)->toBeTrue();
})->skip('MediaExporter functionality has been replaced by direct VideoEncoder usage in v2.0');
