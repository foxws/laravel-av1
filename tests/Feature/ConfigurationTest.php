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
    Config::set('av1.binary_path', '/usr/local/bin/ab-av1');

    $path = config('av1.binary_path');

    expect($path)->toBe('/usr/local/bin/ab-av1');
});

it('respects configured default encoder', function () {
    Config::set('av1.encoder', 'rav1e');

    $encoder = config('av1.encoder');

    expect($encoder)->toBe('rav1e');
});

it('respects configured default preset', function () {
    Config::set('av1.preset', '6');

    $preset = config('av1.preset');

    expect($preset)->toBe('6');
});

it('respects configured timeout value', function () {
    Config::set('av1.timeout', 7200);

    $timeout = config('av1.timeout');

    expect($timeout)->toBe(7200);
});

it('uses environment variable for binary path', function () {
    // Environment variable AB_AV1_BINARY_PATH should override config
    $path = config('av1.binary_path');

    expect($path)->toBeString();
});

it('has default configuration values', function () {
    $config = config('av1');

    expect($config)->toHaveKey('binary_path');
    expect($config)->toHaveKey('timeout');
    expect($config)->toHaveKey('encoder');
    expect($config)->toHaveKey('preset');
    expect($config)->toHaveKey('min_vmaf');
    expect($config)->toHaveKey('temporary_files_root');
});

it('can create media opener with configured defaults', function () {
    $opener = AV1::encode();

    expect($opener)->not->toBeNull();
});

it('can override configured encoder', function () {
    Config::set('av1.encoder', 'svt-av1');

    $opener = AV1::encode()
        ->input('input.mp4')
        ->output('output.mp4')
        ->withEncoder('rav1e');

    $options = $opener->getEncoder()->builder()->getOptions();

    expect($options['encoder'])->toBe('rav1e');
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

    $opener = AV1::autoEncode()
        ->minVmaf(95);

    $options = $opener->getEncoder()->builder()->getOptions();

    expect($options['min-vmaf'])->toBe(95);
});
