<?php

declare(strict_types=1);

use Foxws\AV1\AV1Manager;
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

it('respects configured ffmpeg binary path', function () {
    Config::set('av1.binaries.ffmpeg', '/usr/local/bin/ffmpeg');

    $path = config('av1.binaries.ffmpeg');

    expect($path)->toBe('/usr/local/bin/ffmpeg');
});

it('respects configured default preset', function () {
    Config::set('av1.ab-av1.preset', 6);

    $preset = config('av1.ab-av1.preset');

    expect($preset)->toBe(6);
});

it('respects configured timeout value', function () {
    Config::set('av1.ab-av1.timeout', 7200);

    $timeout = config('av1.ab-av1.timeout');

    expect($timeout)->toBe(7200);
});

it('has default configuration values', function () {
    $config = config('av1');

    expect($config)->toHaveKey('binaries');
    expect($config)->toHaveKey('ab-av1');
    expect($config)->toHaveKey('ffmpeg');
    expect($config)->toHaveKey('temporary_files_root');

    expect($config['binaries'])->toHaveKey('ab-av1');
    expect($config['binaries'])->toHaveKey('ffmpeg');
    expect($config['ab-av1'])->toHaveKey('timeout');
    expect($config['ab-av1'])->toHaveKey('preset');
    expect($config['ab-av1'])->toHaveKey('min_vmaf');
});

it('has ffmpeg configuration values', function () {
    $config = config('av1.ffmpeg');

    expect($config)->toHaveKey('timeout');
    expect($config)->toHaveKey('encoder');
    expect($config)->toHaveKey('hardware_acceleration');
    expect($config)->toHaveKey('hwaccel_priority');
    expect($config)->toHaveKey('encoder_priority');
    expect($config)->toHaveKey('default_crf');
    expect($config)->toHaveKey('default_preset');
    expect($config)->toHaveKey('audio_codec');
    expect($config)->toHaveKey('pixel_format');
});

it('can customize encoder priority order', function () {
    $priority = config('av1.ffmpeg.encoder_priority');

    expect($priority)->toBeArray();
    expect($priority)->toContain('av1_qsv');
    expect($priority)->toContain('libsvtav1');
});

it('can customize hwaccel priority order', function () {
    $priority = config('av1.ffmpeg.hwaccel_priority');

    expect($priority)->toBeArray();
    expect($priority)->toContain('qsv');
    expect($priority)->toContain('cuda');
});

it('can create AV1 manager instance', function () {
    $manager = app('laravel-av1');

    expect($manager)->toBeInstanceOf(AV1Manager::class);
});

it('can access encoder defaults', function () {
    $encoder = AV1::encoder();

    expect($encoder)->toBeInstanceOf(\Foxws\AV1\FFmpeg\VideoEncoder::class);
});

it('respects ffmpeg timeout configuration', function () {
    Config::set('av1.ffmpeg.timeout', 3600);

    $timeout = config('av1.ffmpeg.timeout');

    expect($timeout)->toBe(3600);
});

it('respects ffmpeg default crf configuration', function () {
    Config::set('av1.ffmpeg.default_crf', 28);

    $crf = config('av1.ffmpeg.default_crf');

    expect($crf)->toBe(28);
});

it('respects hardware acceleration configuration', function () {
    Config::set('av1.ffmpeg.hardware_acceleration', false);

    $hwAccel = config('av1.ffmpeg.hardware_acceleration');

    expect($hwAccel)->toBe(false);
});
