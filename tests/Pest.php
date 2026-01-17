<?php

declare(strict_types=1);

use Foxws\AV1\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

/**
 * Check if ab-av1 binary is available for testing
 */
function hasAbAV1(): bool
{
    $binary = config('av1.binary_path', 'ab-av1');

    return is_executable($binary) || (is_executable(trim(shell_exec('which ab-av1') ?? '')));
}

/**
 * Skip test if ab-av1 is not installed
 */
function skipIfNoAbAV1(): void
{
    if (! hasAbAV1()) {
        test()->markTestSkipped('ab-av1 binary not available');
    }
}
