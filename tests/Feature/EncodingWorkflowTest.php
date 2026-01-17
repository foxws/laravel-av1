<?php

declare(strict_types=1);

use Foxws\AV1\Facades\AV1;
use Foxws\AV1\MediaOpener;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

it('can create media opener instance', function () {
    $opener = AV1::fromDisk('local');

    expect($opener)->toBeInstanceOf(MediaOpener::class);
});

it('can set auto-encode command', function () {
    $opener = AV1::autoEncode();

    expect($opener->getEncoder()->builder()->getCommand())->toBe('auto-encode');
});

it('can set crf-search command', function () {
    $opener = AV1::crfSearch();

    expect($opener->getEncoder()->builder()->getCommand())->toBe('crf-search');
});

it('can set sample-encode command', function () {
    $opener = AV1::sampleEncode();

    expect($opener->getEncoder()->builder()->getCommand())->toBe('sample-encode');
});

it('can set encode command', function () {
    $opener = AV1::encode();

    expect($opener->getEncoder()->builder()->getCommand())->toBe('encode');
});

it('can set vmaf command', function () {
    $opener = AV1::vmaf();

    expect($opener->getEncoder()->builder()->getCommand())->toBe('vmaf');
});

it('can set xpsnr command', function () {
    $opener = AV1::xpsnr();

    expect($opener->getEncoder()->builder()->getCommand())->toBe('xpsnr');
});

it('can chain preset and minVmaf options', function () {
    $opener = AV1::autoEncode()
        ->preset('6')
        ->minVmaf(95);

    $options = $opener->getEncoder()->builder()->getOptions();

    expect($options)->toHaveKey('preset');
    expect($options)->toHaveKey('min-vmaf');
    expect($options['preset'])->toBe('6');
    expect($options['min-vmaf'])->toBe(95);
});

it('can chain crf and preset options', function () {
    $opener = AV1::encode()
        ->crf(30)
        ->preset('6');

    $options = $opener->getEncoder()->builder()->getOptions();

    expect($options)->toHaveKey('crf');
    expect($options)->toHaveKey('preset');
    expect($options['crf'])->toBe(30);
});

it('can set input file', function () {
    $opener = AV1::input(fixture('video.mp4'))
        ->encode()
        ->crf(30)
        ->preset('6');

    expect($opener->getEncoder()->builder()->getInput())->toBe(fixture('video.mp4'));
});

it('can set output file', function () {
    $opener = AV1::encode()
        ->input(fixture('video.mp4'))
        ->output('output.mp4')
        ->crf(30)
        ->preset('6');

    expect($opener->getEncoder()->builder()->getOutput())->toBe('output.mp4');
});

it('can set reference file for vmaf', function () {
    $opener = AV1::vmaf()
        ->reference(fixture('video.mp4'))
        ->distorted('encoded.mp4');

    expect($opener->getEncoder()->builder()->getOptions())->not->toHaveKey('reference');
});

it('can chain multiple options', function () {
    $opener = AV1::autoEncode()
        ->preset('6')
        ->minVmaf(95)
        ->minCrf(20)
        ->maxCrf(40)
        ->withEncoder('rav1e')
        ->verbose()
        ->fullVmaf();

    $options = $opener->getEncoder()->builder()->getOptions();

    expect($options)->toHaveKey('preset');
    expect($options)->toHaveKey('min-vmaf');
    expect($options)->toHaveKey('min-crf');
    expect($options)->toHaveKey('max-crf');
    expect($options)->toHaveKey('encoder');
    expect($options)->toHaveKey('verbose');
    expect($options)->toHaveKey('full-vmaf');
});

it('can get export instance', function () {
    $export = AV1::encode()
        ->input('input.mp4')
        ->crf(30)
        ->preset('6')
        ->export();

    expect($export)->not->toBeNull();
});

it('can access command for debugging', function () {
    $command = AV1::encode()
        ->input(fixture('video.mp4'))
        ->crf(30)
        ->preset('6')
        ->export()
        ->getCommand();

    expect($command)->toContain('ab-av1');
    expect($command)->toContain('encode');
    expect($command)->toContain(fixture('video.mp4'));
    expect($command)->toContain('30');
});

it('can handle multi-disk operations', function () {
    $opener1 = AV1::fromDisk('local');
    $opener2 = $opener1->fromDisk('local');

    expect($opener1)->not->toBe($opener2);
    expect($opener2->getDisk()->getName())->toBe('local');
});

it('can add sample option', function () {
    $opener = AV1::sampleEncode()
        ->sample(60)
        ->crf(30)
        ->preset('6');

    $options = $opener->getEncoder()->builder()->getOptions();

    expect($options)->toHaveKey('sample');
    expect($options['sample'])->toBe(60);
});

it('can add pixel format option', function () {
    $opener = AV1::encode()
        ->input('input.mp4')
        ->crf(30)
        ->preset('6')
        ->pixFmt('yuv420p10le');

    $options = $opener->getEncoder()->builder()->getOptions();

    expect($options)->toHaveKey('pix-fmt');
    expect($options['pix-fmt'])->toBe('yuv420p10le');
});

it('can add max encoded percent option', function () {
    $opener = AV1::autoEncode()
        ->preset('6')
        ->minVmaf(95)
        ->maxEncodedPercent(90);

    $options = $opener->getEncoder()->builder()->getOptions();

    expect($options)->toHaveKey('max-encoded-percent');
    expect($options['max-encoded-percent'])->toBe(90);
});
