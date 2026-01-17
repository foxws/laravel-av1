<?php

declare(strict_types=1);

use Foxws\AV1\Facades\AV1;
use Foxws\AV1\MediaOpener;

it('registers media opener in service container', function () {
    $opener = app(MediaOpener::class);

    expect($opener)->toBeInstanceOf(MediaOpener::class);
});

it('can resolve media opener via container', function () {
    $opener = app()->make(MediaOpener::class);

    expect($opener)->toBeInstanceOf(MediaOpener::class);
});

it('facade resolves to media opener instance', function () {
    $result = AV1::encode();

    expect($result)->toBeInstanceOf(MediaOpener::class);
});

it('can use facade for all command types', function () {
    expect(AV1::autoEncode())->toBeInstanceOf(MediaOpener::class);
    expect(AV1::crfSearch())->toBeInstanceOf(MediaOpener::class);
    expect(AV1::sampleEncode())->toBeInstanceOf(MediaOpener::class);
    expect(AV1::encode())->toBeInstanceOf(MediaOpener::class);
    expect(AV1::vmaf())->toBeInstanceOf(MediaOpener::class);
    expect(AV1::xpsnr())->toBeInstanceOf(MediaOpener::class);
});

it('can access facade methods statically', function () {
    $opener = AV1::encode()
        ->input('input.mp4')
        ->output('output.mp4')
        ->crf(30);

    expect($opener->getEncoder()->builder()->getInput())->toBe('input.mp4');
    expect($opener->getEncoder()->builder()->getOutput())->toBe('output.mp4');
});

it('service provider is registered', function () {
    $providers = app()->getLoadedProviders();

    expect(in_array('Foxws\\AV1\\AV1ServiceProvider', array_keys($providers)))->toBeTrue();
});

it('can instantiate multiple independent encoders', function () {
    $encoder1 = AV1::encode()
        ->input(fixture('video.mp4'))
        ->output('encoded1.mp4')
        ->crf(30);

    $encoder2 = AV1::encode()
        ->input(fixture('video.mp4'))
        ->output('encoded2.mp4')
        ->crf(35);

    expect($encoder1->getEncoder()->builder()->getInput())->toBe(fixture('video.mp4'));
    expect($encoder2->getEncoder()->builder()->getInput())->toBe(fixture('video.mp4'));
});

it('facade methods return independent state', function () {
    // When resolving through the container, each gets a fresh instance
    $opener1 = app(MediaOpener::class)->encode()->input('video1.mp4');
    $opener2 = app(MediaOpener::class)->encode()->input('video2.mp4');

    // Each call should have independent state
    expect($opener1->getEncoder()->builder()->getInput())->toBe('video1.mp4');
    expect($opener2->getEncoder()->builder()->getInput())->toBe('video2.mp4');
});

it('can chain facade methods with instance methods', function () {
    $result = AV1::encode()
        ->input(fixture('video.mp4'))
        ->output('output.mp4')
        ->crf(30)
        ->preset('6')
        ->verbose();

    expect($result->getEncoder()->builder()->getInput())->toBe(fixture('video.mp4'));
    expect($result->getEncoder()->builder()->getOptions())->toHaveKey('verbose');
});

it('can export from facade chain', function () {
    $exporter = AV1::encode()
        ->input(fixture('video.mp4'))
        ->output('output.mp4')
        ->crf(30)
        ->export();

    expect($exporter)->not->toBeNull();
});

it('facade works with all ab-av1 commands', function () {
    // Resolve fresh instances for each command to avoid shared state
    $autoEncode = app(MediaOpener::class)->autoEncode();
    $crfSearch = app(MediaOpener::class)->crfSearch();
    $sampleEncode = app(MediaOpener::class)->sampleEncode();
    $encode = app(MediaOpener::class)->encode();
    $vmaf = app(MediaOpener::class)->vmaf();
    $xpsnr = app(MediaOpener::class)->xpsnr();

    expect($autoEncode->getEncoder()->builder()->getCommand())->toBe('auto-encode');
    expect($crfSearch->getEncoder()->builder()->getCommand())->toBe('crf-search');
    expect($sampleEncode->getEncoder()->builder()->getCommand())->toBe('sample-encode');
    expect($encode->getEncoder()->builder()->getCommand())->toBe('encode');
    expect($vmaf->getEncoder()->builder()->getCommand())->toBe('vmaf');
    expect($xpsnr->getEncoder()->builder()->getCommand())->toBe('xpsnr');
});
