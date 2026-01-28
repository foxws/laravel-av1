<?php

declare(strict_types=1);

use Foxws\AV1\AV1Manager;
use Foxws\AV1\Facades\AV1;
use Foxws\AV1\MediaOpener;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

it('can create AV1 manager instance', function () {
    $manager = app('laravel-av1');

    expect($manager)->toBeInstanceOf(AV1Manager::class);
});

it('can access AV1 manager via facade', function () {
    $manager = AV1::getFacadeRoot();

    expect($manager)->toBeInstanceOf(AV1Manager::class);
});

it('can create video encoder from manager', function () {
    $encoder = AV1::encoder();

    expect($encoder)->toBeInstanceOf(\Foxws\AV1\FFmpeg\VideoEncoder::class);
});

it('can create crf finder from manager', function () {
    $finder = AV1::crfFinder();

    expect($finder)->toBeInstanceOf(\Foxws\AV1\AbAV1\CrfFinder::class);
});

// MediaOpener tests (legacy API)
it('can create media opener instance directly', function () {
    $opener = app(MediaOpener::class);

    expect($opener)->toBeInstanceOf(MediaOpener::class);
});

it('can chain options on encoder', function () {
    $encoder = AV1::encoder()
        ->crf(30)
        ->preset(6);

    expect($encoder)->toBeInstanceOf(\Foxws\AV1\FFmpeg\VideoEncoder::class);
});

it('can enable hardware acceleration', function () {
    $encoder = AV1::encoder()
        ->useHwAccel(true)
        ->crf(28);

    expect($encoder)->toBeInstanceOf(\Foxws\AV1\FFmpeg\VideoEncoder::class);
});

it('can set pixel format', function () {
    $encoder = AV1::encoder()
        ->pixelFormat('yuv420p10le')
        ->crf(30);

    expect($encoder)->toBeInstanceOf(\Foxws\AV1\FFmpeg\VideoEncoder::class);
});

it('can set audio codec', function () {
    $encoder = AV1::encoder()
        ->audioCodec('libopus')
        ->crf(30);

    expect($encoder)->toBeInstanceOf(\Foxws\AV1\FFmpeg\VideoEncoder::class);
});

it('can set custom encoder', function () {
    $encoder = AV1::encoder()
        ->encoder('libsvtav1')
        ->crf(30);

    expect($encoder)->toBeInstanceOf(\Foxws\AV1\FFmpeg\VideoEncoder::class);
});

it('can set video filter', function () {
    $encoder = AV1::encoder()
        ->videoFilter('scale=1920:1080')
        ->crf(30);

    expect($encoder)->toBeInstanceOf(\Foxws\AV1\FFmpeg\VideoEncoder::class);
});

it('can access hardware detector', function () {
    $encoder = AV1::encoder();
    $detector = $encoder->hardwareDetector();

    expect($detector)->toBeInstanceOf(\Foxws\AV1\FFmpeg\HardwareDetector::class);
});
