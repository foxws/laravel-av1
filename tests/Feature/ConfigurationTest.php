<?php

declare(strict_types=1);

use Foxws\AV1\Facades\AV1;
use Illuminate\Support\Facades\Config;

it('can access configuration values', function () {
    $tempRoot = config('av1.temporary_files_root');

    expect($tempRoot)->toBeString();
    expect($tempRoot)->toContain('av1');
});

it('respects configured binary path', function () {
    Config::set('av1.binaries.ab-av1', '/usr/local/bin/ab-av1');

    $path = config('av1.binaries.ab-av1');

    expect($path)->toBe('/usr/local/bin/ab-av1');
});

it('respects configured default preset', function () {
    Config::set('av1.ab-av1.preset', '6');

    $preset = config('av1.ab-av1.preset');

    expect($preset)->toBe('6');
});

it('respects configured timeout value', function () {
    Config::set('av1.ab-av1.timeout', 7200);

    $timeout = config('av1.ab-av1.timeout');

    expect($timeout)->toBe(7200);
});

it('uses environment variable for binary path', function () {
    // Environment variable AB_AV1_BINARY_PATH should override config
    $path = config('av1.binaries.ab-av1');

    expect($path)->toBeString();
});

it('has default configuration values', function () {
    $config = config('av1');

    expect($config)->toHaveKey('binaries');
    expect($config)->toHaveKey('ab-av1');
    expect($config)->toHaveKey('temporary_files_root');

    expect($config['binaries'])->toHaveKey('ab-av1');
    expect($config['binaries'])->toHaveKey('ffmpeg');
    expect($config['ab-av1'])->toHaveKey('timeout');
    expect($config['ab-av1'])->toHaveKey('preset');
    expect($config['ab-av1'])->toHaveKey('min_vmaf');
});

it('can create media opener with configured defaults', function () {
    $opener = AV1::encode();

    expect($opener)->not->toBeNull();
});

it('can override configured preset', function () {
    Config::set('av1.preset', '4');

    $opener = AV1::encode()
        ->input('input.mp4')
        ->output('output.mp4')
        ->preset('6');

    $options = $opener->getEncoder()->builder()->getOptions();

    expect($options['preset'])->toBe('6');
});

it('can override configured min-vmaf', function () {
    Config::set('av1.min_vmaf', 90);

    $opener = AV1::vmafEncode()
        ->minVmaf(95);

    $options = $opener->getEncoder()->builder()->getOptions();

    expect($options['min-vmaf'])->toBe(95);
});
