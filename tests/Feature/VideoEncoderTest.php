<?php

declare(strict_types=1);

use Foxws\AV1\FFmpeg\VideoEncoder;
use Illuminate\Support\Facades\Config;

it('can create video encoder instance', function () {
    $encoder = new VideoEncoder;

    expect($encoder)->toBeInstanceOf(VideoEncoder::class);
});

it('can set CRF value', function () {
    $encoder = (new VideoEncoder)->crf(28);

    expect($encoder)->toBeInstanceOf(VideoEncoder::class);
});

it('can set preset value', function () {
    $encoder = (new VideoEncoder)->preset(8);

    expect($encoder)->toBeInstanceOf(VideoEncoder::class);
});

it('can enable hardware acceleration', function () {
    $encoder = (new VideoEncoder)->useHwAccel(true);

    expect($encoder)->toBeInstanceOf(VideoEncoder::class);
});

it('can set custom encoder', function () {
    $encoder = (new VideoEncoder)->encoder('libsvtav1');

    expect($encoder)->toBeInstanceOf(VideoEncoder::class);
});

it('can set pixel format', function () {
    $encoder = (new VideoEncoder)->pixelFormat('yuv420p10le');

    expect($encoder)->toBeInstanceOf(VideoEncoder::class);
});

it('can set audio codec', function () {
    $encoder = (new VideoEncoder)->audioCodec('libopus');

    expect($encoder)->toBeInstanceOf(VideoEncoder::class);
});

it('can set video filter', function () {
    $encoder = (new VideoEncoder)->videoFilter('scale=1920:1080');

    expect($encoder)->toBeInstanceOf(VideoEncoder::class);
});

it('can set number of threads', function () {
    $encoder = (new VideoEncoder)->threads(8);

    expect($encoder)->toBeInstanceOf(VideoEncoder::class);
});

it('can chain multiple options', function () {
    $encoder = (new VideoEncoder)
        ->crf(30)
        ->preset(6)
        ->pixelFormat('yuv420p')
        ->audioCodec('libopus')
        ->useHwAccel(true);

    expect($encoder)->toBeInstanceOf(VideoEncoder::class);
});

it('can add custom arguments', function () {
    $encoder = (new VideoEncoder)->withArgs(['-threads', '4']);

    expect($encoder)->toBeInstanceOf(VideoEncoder::class);
});

it('can access hardware detector', function () {
    $encoder = new VideoEncoder;
    $detector = $encoder->hardwareDetector();

    expect($detector)->toBeInstanceOf(\Foxws\AV1\FFmpeg\HardwareDetector::class);
});

it('encode returns encoding result', function () {
    $encoder = new VideoEncoder;

    $result = $encoder->encode(fixture('video.mp4'), 'output.mp4');

    expect($result)->toBeInstanceOf(\Foxws\AV1\EncodingResult::class);
});

it('respects config defaults', function () {
    Config::set('av1.ffmpeg.default_crf', 28);
    Config::set('av1.ffmpeg.default_preset', 8);

    $encoder = new VideoEncoder;

    expect($encoder)->toBeInstanceOf(VideoEncoder::class);
});
