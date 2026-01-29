<?php

declare(strict_types=1);

use Foxws\AV1\AV1Manager;
use Foxws\AV1\Facades\AV1;
use Foxws\AV1\FFmpeg\VideoEncoder;

it('registers AV1 manager in service container', function () {
    $manager = app('laravel-av1');

    expect($manager)->toBeInstanceOf(AV1Manager::class);
});

it('can resolve AV1 manager via container', function () {
    $manager = app()->make('laravel-av1');

    expect($manager)->toBeInstanceOf(AV1Manager::class);
});

it('facade resolves to AV1 manager instance', function () {
    $manager = AV1::getFacadeRoot();

    expect($manager)->toBeInstanceOf(AV1Manager::class);
});

it('can use facade to create encoder', function () {
    $encoder = AV1::encoder();

    expect($encoder)->toBeInstanceOf(VideoEncoder::class);
});

it('can access facade encoder methods', function () {
    $encoder = AV1::encoder()
        ->crf(30)
        ->preset(6);

    expect($encoder)->toBeInstanceOf(VideoEncoder::class);
});

it('service provider is registered', function () {
    $providers = app()->getLoadedProviders();

    expect(in_array('Foxws\\AV1\\AV1ServiceProvider', array_keys($providers)))->toBeTrue();
});

it('can instantiate multiple independent encoders', function () {
    $encoder1 = AV1::encoder()->crf(30);
    $encoder2 = AV1::encoder()->crf(35);

    expect($encoder1)->toBeInstanceOf(VideoEncoder::class);
    expect($encoder2)->toBeInstanceOf(VideoEncoder::class);
});

it('can create CRF finder', function () {
    $finder = AV1::crfFinder();

    expect($finder)->toBeInstanceOf(\Foxws\AV1\AbAV1\CrfFinder::class);
});

it('can chain encoder methods', function () {
    $encoder = AV1::encoder()
        ->crf(30)
        ->preset(6)
        ->useHwAccel(true);

    expect($encoder)->toBeInstanceOf(VideoEncoder::class);
});

it('can access hardware detector', function () {
    $encoder = AV1::encoder();
    $detector = $encoder->hardwareDetector();

    expect($detector)->toBeInstanceOf(\Foxws\AV1\FFmpeg\HardwareDetector::class);
});
