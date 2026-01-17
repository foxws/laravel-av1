<?php

declare(strict_types=1);

use Foxws\AV1\Exporters\MediaExporter;
use Foxws\AV1\Facades\AV1;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('s3');
});

it('can export to local disk', function () {
    $exporter = AV1::encode()
        ->input('input.mp4')
        ->output('output.mp4')
        ->crf(30)
        ->export();

    expect($exporter)->toBeInstanceOf(MediaExporter::class);
});

it('can specify target disk for export', function () {
    $exporter = AV1::encode()
        ->input('input.mp4')
        ->output('output.mp4')
        ->crf(30)
        ->export()
        ->toDisk('s3');

    expect($exporter->getDisk()->getName())->toBe('s3');
});

it('can specify path for export', function () {
    $exporter = AV1::encode()
        ->input('input.mp4')
        ->output('output.mp4')
        ->crf(30)
        ->export()
        ->toPath('encoded/videos');

    expect($exporter->getPath())->toContain('encoded/videos');
});

it('can set file visibility for export', function () {
    $exporter = AV1::encode()
        ->input('input.mp4')
        ->output('output.mp4')
        ->crf(30)
        ->export()
        ->withVisibility('public');

    expect($exporter->getVisibility())->toBe('public');
});

it('can get command for export', function () {
    $command = AV1::encode()
        ->input('input.mp4')
        ->output('output.mp4')
        ->crf(30)
        ->export()
        ->getCommand();

    expect($command)->toBeString();
    expect($command)->toContain('ab-av1');
    expect($command)->toContain('encode');
});

it('can get builder from exporter', function () {
    $builder = AV1::encode()
        ->input('input.mp4')
        ->crf(30)
        ->export()
        ->getBuilder();

    expect($builder)->not->toBeNull();
});

it('can get encoder from exporter', function () {
    $encoder = AV1::encode()
        ->input('input.mp4')
        ->crf(30)
        ->export()
        ->getEncoder();

    expect($encoder)->not->toBeNull();
});

it('can chain path and disk methods', function () {
    $exporter = AV1::encode()
        ->input('input.mp4')
        ->output('output.mp4')
        ->crf(30)
        ->export()
        ->toDisk('local')
        ->toPath('encoded');

    expect($exporter->getDisk()->getName())->toBe('local');
    expect($exporter->getPath())->toContain('encoded');
});

it('can handle multiple export destinations', function () {
    $exporter1 = AV1::encode()
        ->input('input.mp4')
        ->crf(30)
        ->export()
        ->toDisk('local');

    $exporter2 = AV1::encode()
        ->input('input.mp4')
        ->crf(30)
        ->export()
        ->toDisk('s3');

    expect($exporter1->getDisk()->getName())->toBe('local');
    expect($exporter2->getDisk()->getName())->toBe('s3');
});
