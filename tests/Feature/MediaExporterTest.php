<?php

declare(strict_types=1);

use Foxws\AV1\Facades\AV1;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('s3');
});

it('can export encoded file to disk', function () {
    // Create a test output file
    $outputPath = storage_path('app/test-output.mp4');
    file_put_contents($outputPath, 'test video content');

    $exporter = AV1::encoder()
        ->crf(28)
        ->encode(fixture('video.mp4'), $outputPath)
        ->export();

    expect($exporter)->toBeInstanceOf(\Foxws\AV1\MediaExporter::class);

    // Clean up
    @unlink($outputPath);
});

it('can chain toDisk and save', function () {
    $outputPath = storage_path('app/test-output.mp4');
    file_put_contents($outputPath, 'test video content');

    $success = AV1::encoder()
        ->crf(28)
        ->encode(fixture('video.mp4'), $outputPath)
        ->export()
        ->toDisk('s3')
        ->save('encoded.mp4');

    expect($success)->toBeTrue();
    expect(Storage::disk('s3')->exists('encoded.mp4'))->toBeTrue();

    @unlink($outputPath);
});

it('can specify target path', function () {
    $outputPath = storage_path('app/test-output.mp4');
    file_put_contents($outputPath, 'test video content');

    $success = AV1::encoder()
        ->encode(fixture('video.mp4'), $outputPath)
        ->export()
        ->toDisk('s3')
        ->toPath('videos/encoded')
        ->save('final.mp4');

    expect($success)->toBeTrue();
    expect(Storage::disk('s3')->exists('videos/encoded/final.mp4'))->toBeTrue();

    @unlink($outputPath);
});

it('can set file visibility', function () {
    $outputPath = storage_path('app/test-output.mp4');
    file_put_contents($outputPath, 'test video content');

    AV1::encoder()
        ->encode(fixture('video.mp4'), $outputPath)
        ->export()
        ->toDisk('local')
        ->withVisibility('public')
        ->save('public-video.mp4');

    expect(Storage::disk('local')->getVisibility('public-video.mp4'))->toBe('public');

    @unlink($outputPath);
});

it('returns encoding result from export', function () {
    $outputPath = storage_path('app/test-output.mp4');
    file_put_contents($outputPath, 'test video content');

    $result = AV1::encoder()
        ->encode(fixture('video.mp4'), $outputPath)
        ->export()
        ->result();

    expect($result)->toBeInstanceOf(\Illuminate\Process\ProcessResult::class);

    @unlink($outputPath);
});

it('can get source and target paths from exporter', function () {
    $outputPath = storage_path('app/test-output.mp4');
    file_put_contents($outputPath, 'test video content');

    $exporter = AV1::encoder()
        ->encode(fixture('video.mp4'), $outputPath)
        ->export()
        ->toDisk('s3')
        ->toPath('videos/encoded');

    expect($exporter->getSourcePath())->toBe($outputPath);
    expect($exporter->getDisk())->toBe('s3');
    expect($exporter->getTargetPath())->toBe('videos/encoded');

    @unlink($outputPath);
});

it('encoding result provides path access', function () {
    $outputPath = storage_path('app/test-output.mp4');

    $result = AV1::encoder()
        ->encode(fixture('video.mp4'), $outputPath);

    expect($result->path())->toBe($outputPath);
    expect($result)->toBeInstanceOf(\Foxws\AV1\EncodingResult::class);
});
