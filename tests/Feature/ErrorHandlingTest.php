<?php

declare(strict_types=1);

use Foxws\AV1\Facades\AV1;
use Foxws\AV1\Support\CommandBuilder;

it('throws when encode command missing input', function () {
    $builder = new CommandBuilder('encode');
    $builder->output('output.mp4');

    expect(fn () => $builder->buildArray())
        ->toThrow(Exception::class, 'Input file is required');
});

it('throws when encode command missing output', function () {
    $builder = new CommandBuilder('encode');
    $builder->input('input.mp4');

    expect(fn () => $builder->buildArray())
        ->toThrow(Exception::class, 'Output file is required');
});

it('throws when auto-encode missing input', function () {
    $builder = new CommandBuilder('auto-encode');
    $builder->output('output.mp4');

    expect(fn () => $builder->buildArray())
        ->toThrow(Exception::class, 'Input file is required');
});

it('throws when auto-encode missing output', function () {
    $builder = new CommandBuilder('auto-encode');
    $builder->input('input.mp4');

    expect(fn () => $builder->buildArray())
        ->toThrow(Exception::class, 'Output file is required');
});

it('throws when crf-search missing input', function () {
    $builder = new CommandBuilder('crf-search');
    $builder->output('output.mp4');

    expect(fn () => $builder->buildArray())
        ->toThrow(Exception::class, 'Input file is required');
});

it('throws when crf-search missing output', function () {
    $builder = new CommandBuilder('crf-search');
    $builder->input('input.mp4');

    expect(fn () => $builder->buildArray())
        ->toThrow(Exception::class, 'Output file is required');
});

it('throws when sample-encode missing input', function () {
    $builder = new CommandBuilder('sample-encode');
    $builder->output('output.mp4');

    expect(fn () => $builder->buildArray())
        ->toThrow(Exception::class, 'Input file is required');
});

it('throws when sample-encode missing output', function () {
    $builder = new CommandBuilder('sample-encode');
    $builder->input('input.mp4');

    expect(fn () => $builder->buildArray())
        ->toThrow(Exception::class, 'Output file is required');
});

it('throws when vmaf missing reference file', function () {
    $builder = new CommandBuilder('vmaf');
    $builder->distorted('encoded.mp4');

    expect(fn () => $builder->buildArray())
        ->toThrow(Exception::class, 'Reference file is required');
});

it('throws when vmaf missing distorted file', function () {
    $builder = new CommandBuilder('vmaf');
    $builder->reference('original.mp4');

    expect(fn () => $builder->buildArray())
        ->toThrow(Exception::class, 'Distorted file is required');
});

it('throws when xpsnr missing reference file', function () {
    $builder = new CommandBuilder('xpsnr');
    $builder->distorted('encoded.mp4');

    expect(fn () => $builder->buildArray())
        ->toThrow(Exception::class, 'Reference file is required');
});

it('throws when xpsnr missing distorted file', function () {
    $builder = new CommandBuilder('xpsnr');
    $builder->reference('original.mp4');

    expect(fn () => $builder->buildArray())
        ->toThrow(Exception::class, 'Distorted file is required');
});

it('builds valid encode command array', function () {
    $builder = new CommandBuilder('encode');
    $builder->input(fixture('video.mp4'))
        ->output('output.mp4')
        ->crf(30)
        ->preset('6');

    $array = $builder->buildArray();

    expect($array)->toContain('ab-av1');
    expect($array)->toContain('encode');
    expect($array)->toContain(fixture('video.mp4'));
    expect($array)->toContain('output.mp4');
});

it('builds valid auto-encode command array', function () {
    $builder = new CommandBuilder('auto-encode');
    $builder->input(fixture('video.mp4'))
        ->output('output.mp4')
        ->preset('6')
        ->minVmaf(95);

    $array = $builder->buildArray();

    expect($array)->toContain('ab-av1');
    expect($array)->toContain('auto-encode');
});

it('builds valid vmaf command array', function () {
    $builder = new CommandBuilder('vmaf');
    $builder->reference(fixture('video.mp4'))
        ->distorted('encoded.mp4')
        ->fullVmaf();

    $array = $builder->buildArray();

    expect($array)->toContain('ab-av1');
    expect($array)->toContain('vmaf');
    expect($array)->toContain(fixture('video.mp4'));
    expect($array)->toContain('encoded.mp4');
});

it('can reset builder for reuse', function () {
    $builder = new CommandBuilder('encode');
    $builder->input('input.mp4')
        ->output('output.mp4')
        ->crf(30);

    // Create fresh instance
    $builder2 = CommandBuilder::make('encode');

    expect($builder2->getInput())->toBeNull();
    expect($builder2->getOutput())->toBeNull();
});

it('handles special characters in file paths', function () {
    $builder = new CommandBuilder('encode');
    $builder->input('path/to/my file (2024).mp4')
        ->output('path/to/my output [final].mp4')
        ->crf(30)
        ->preset('6');

    $array = $builder->buildArray();

    expect($array)->toContain('path/to/my file (2024).mp4');
    expect($array)->toContain('path/to/my output [final].mp4');
});

it('handles multiple option overrides', function () {
    $builder = new CommandBuilder('encode');
    $builder->input('input.mp4')
        ->output('output.mp4')
        ->crf(20)
        ->crf(30)  // Override
        ->preset('4')
        ->preset('6');  // Override

    $array = $builder->buildArray();

    // Should contain the latest values
    expect(in_array('--crf', $array))->toBeTrue();
    expect(in_array('--preset', $array))->toBeTrue();
});
