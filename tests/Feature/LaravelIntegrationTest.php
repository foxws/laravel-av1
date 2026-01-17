<?php

declare(strict_types=1);

use Foxws\AV1\Facades\AV1;
use Foxws\AV1\MediaOpener;
use Illuminate\Support\Facades\Container;

it('registers media opener in service container', function () {
    $opener = app(MediaOpener::class);

    expect($opener)->toBeInstanceOf(MediaOpener::class);
});

it('can resolve media opener via container', function () {
    $opener = Container::getInstance()->make(MediaOpener::class);

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

it('facade methods return fresh instances', function () {
    $opener1 = AV1::encode();
    $opener2 = AV1::encode();

    // Each should be a fresh instance
    expect($opener1->getEncoder())->not->toBe($opener2->getEncoder());
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
    $autoEncode = AV1::autoEncode();
    $crfSearch = AV1::crfSearch();
    $sampleEncode = AV1::sampleEncode();
    $encode = AV1::encode();
    $vmaf = AV1::vmaf();
    $xpsnr = AV1::xpsnr();

    expect($autoEncode->getEncoder()->builder()->getCommand())->toBe('auto-encode');
    expect($crfSearch->getEncoder()->builder()->getCommand())->toBe('crf-search');
    expect($sampleEncode->getEncoder()->builder()->getCommand())->toBe('sample-encode');
    expect($encode->getEncoder()->builder()->getCommand())->toBe('encode');
    expect($vmaf->getEncoder()->builder()->getCommand())->toBe('vmaf');
    expect($xpsnr->getEncoder()->builder()->getCommand())->toBe('xpsnr');
});
