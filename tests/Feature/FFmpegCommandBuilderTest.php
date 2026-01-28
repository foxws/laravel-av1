<?php

declare(strict_types=1);

use Foxws\AV1\FFmpeg\FFmpegCommandBuilder;

it('can create command builder instance', function () {
    $builder = new FFmpegCommandBuilder;

    expect($builder)->toBeInstanceOf(FFmpegCommandBuilder::class);
});

it('can create builder using static make', function () {
    $builder = FFmpegCommandBuilder::make();

    expect($builder)->toBeInstanceOf(FFmpegCommandBuilder::class);
});

it('can set encoder', function () {
    $builder = FFmpegCommandBuilder::make()
        ->withEncoder('libsvtav1');

    expect($builder)->toBeInstanceOf(FFmpegCommandBuilder::class);
});

it('can set CRF', function () {
    $builder = FFmpegCommandBuilder::make()
        ->withCrf(28);

    expect($builder)->toBeInstanceOf(FFmpegCommandBuilder::class);
});

it('can set preset', function () {
    $builder = FFmpegCommandBuilder::make()
        ->withPreset(8);

    expect($builder)->toBeInstanceOf(FFmpegCommandBuilder::class);
});

it('can set threads', function () {
    $builder = FFmpegCommandBuilder::make()
        ->withThreads(4);

    expect($builder)->toBeInstanceOf(FFmpegCommandBuilder::class);
});

it('can set hardware acceleration', function () {
    $builder = FFmpegCommandBuilder::make()
        ->withHwaccel('qsv');

    expect($builder)->toBeInstanceOf(FFmpegCommandBuilder::class);
});

it('can set audio codec', function () {
    $builder = FFmpegCommandBuilder::make()
        ->withAudioCodec('libopus');

    expect($builder)->toBeInstanceOf(FFmpegCommandBuilder::class);
});

it('can set pixel format', function () {
    $builder = FFmpegCommandBuilder::make()
        ->withPixelFormat('yuv420p10le');

    expect($builder)->toBeInstanceOf(FFmpegCommandBuilder::class);
});

it('can set video filter', function () {
    $builder = FFmpegCommandBuilder::make()
        ->withVideoFilter('scale=1920:1080');

    expect($builder)->toBeInstanceOf(FFmpegCommandBuilder::class);
});

it('can add custom arguments', function () {
    $builder = FFmpegCommandBuilder::make()
        ->withCustomArgs(['-movflags', '+faststart']);

    expect($builder)->toBeInstanceOf(FFmpegCommandBuilder::class);
});

it('can build basic command', function () {
    $args = FFmpegCommandBuilder::make()
        ->withEncoder('libsvtav1')
        ->withCrf(30)
        ->withPreset(6)
        ->build('input.mp4', 'output.mp4');

    expect($args)->toBeArray();
    expect($args)->toContain('ffmpeg');
    expect($args)->toContain('-i');
    expect($args)->toContain('input.mp4');
    expect($args)->toContain('-c:v');
    expect($args)->toContain('libsvtav1');
    expect($args)->toContain('-crf');
    expect($args)->toContain('30');
    expect($args)->toContain('-preset');
    expect($args)->toContain('6');
    expect($args)->toContain('-y');
    expect($args)->toContain('output.mp4');
});

it('can build command with hardware acceleration', function () {
    $args = FFmpegCommandBuilder::make()
        ->withHwaccel('qsv')
        ->withEncoder('av1_qsv')
        ->withCrf(30)
        ->build('input.mp4', 'output.mp4');

    expect($args)->toContain('-hwaccel');
    expect($args)->toContain('qsv');
    expect($args)->toContain('av1_qsv');
});

it('uses correct CRF option for hardware encoders', function () {
    $args = FFmpegCommandBuilder::make()
        ->withEncoder('av1_qsv')
        ->withCrf(30)
        ->build('input.mp4', 'output.mp4');

    expect($args)->toContain('-q:v');
    expect($args)->not->toContain('-crf');
});

it('uses correct preset option for AMD encoder', function () {
    $args = FFmpegCommandBuilder::make()
        ->withEncoder('av1_amf')
        ->withPreset(6)
        ->build('input.mp4', 'output.mp4');

    expect($args)->toContain('-quality');
    expect($args)->not->toContain('-preset');
});

it('can chain all options', function () {
    $args = FFmpegCommandBuilder::make('/usr/bin/ffmpeg')
        ->withHwaccel('cuda')
        ->withEncoder('av1_nvenc')
        ->withAudioCodec('libopus')
        ->withCrf(28)
        ->withPreset(8)
        ->withThreads(4)
        ->withPixelFormat('yuv420p10le')
        ->withVideoFilter('scale=1920:1080')
        ->withCustomArgs(['-movflags', '+faststart'])
        ->build('input.mp4', 'output.mp4');

    expect($args)->toBeArray();
    expect($args[0])->toBe('/usr/bin/ffmpeg');
    expect($args)->toContain('-hwaccel');
    expect($args)->toContain('cuda');
    expect($args)->toContain('-c:v');
    expect($args)->toContain('av1_nvenc');
    expect($args)->toContain('-c:a');
    expect($args)->toContain('libopus');
    expect($args)->toContain('-threads');
    expect($args)->toContain('4');
    expect($args)->toContain('-pix_fmt');
    expect($args)->toContain('yuv420p10le');
    expect($args)->toContain('-vf');
    expect($args)->toContain('scale=1920:1080');
    expect($args)->toContain('-movflags');
    expect($args)->toContain('+faststart');
});
