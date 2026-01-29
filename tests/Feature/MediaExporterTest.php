<?php

declare(strict_types=1);

use Foxws\AV1\Support\EncodingResult;
use Foxws\AV1\Support\MediaExporter;
use Illuminate\Process\ProcessResult;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('s3');
});

it('can create media exporter instance', function () {
    $processResult = \Mockery::mock(ProcessResult::class);
    $processResult->shouldReceive('successful')->andReturn(true);

    $result = new EncodingResult($processResult, '/tmp/output.mp4');
    $exporter = $result->export();

    expect($exporter)->toBeInstanceOf(MediaExporter::class);
});

it('can chain toDisk and save', function () {
    // Create actual temp file
    $outputPath = storage_path('app/test-output.mp4');
    file_put_contents($outputPath, 'test video content');

    $processResult = \Mockery::mock(ProcessResult::class);
    $processResult->shouldReceive('successful')->andReturn(true);

    $result = new EncodingResult($processResult, $outputPath);

    $success = $result->export()
        ->toDisk('s3')
        ->save('encoded.mp4');

    expect($success)->toBeTrue();
    expect(Storage::disk('s3')->exists('encoded.mp4'))->toBeTrue();

    @unlink($outputPath);
});

it('can specify target path', function () {
    $outputPath = storage_path('app/test-output.mp4');
    file_put_contents($outputPath, 'test video content');

    $processResult = \Mockery::mock(ProcessResult::class);
    $processResult->shouldReceive('successful')->andReturn(true);

    $result = new EncodingResult($processResult, $outputPath);

    $success = $result->export()
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

    $processResult = \Mockery::mock(ProcessResult::class);
    $processResult->shouldReceive('successful')->andReturn(true);

    $result = new EncodingResult($processResult, $outputPath);

    $exporter = $result->export()
        ->toDisk('local')
        ->withVisibility('public');

    $success = $exporter->save('public-video.mp4');

    expect($success)->toBeTrue();
    expect(Storage::disk('local')->exists('public-video.mp4'))->toBeTrue();

    @unlink($outputPath);
});

it('returns encoding result from export', function () {
    $outputPath = storage_path('app/test-output.mp4');
    file_put_contents($outputPath, 'test video content');

    $processResult = \Mockery::mock(ProcessResult::class);
    $processResult->shouldReceive('successful')->andReturn(true);

    $result = new EncodingResult($processResult, $outputPath);

    $exportResult = $result->export()->result();

    expect($exportResult)->toBeInstanceOf(ProcessResult::class);

    @unlink($outputPath);
});

it('can get source and target paths from exporter', function () {
    $outputPath = storage_path('app/test-output.mp4');
    file_put_contents($outputPath, 'test video content');

    $processResult = \Mockery::mock(ProcessResult::class);
    $processResult->shouldReceive('successful')->andReturn(true);

    $result = new EncodingResult($processResult, $outputPath);

    $exporter = $result->export()
        ->toDisk('s3')
        ->toPath('videos/encoded');

    expect($exporter->getSourcePath())->toBe($outputPath);
    expect($exporter->getDisk())->toBe('s3');
    expect($exporter->getTargetPath())->toBe('videos/encoded');

    @unlink($outputPath);
});

it('encoding result provides path access', function () {
    $outputPath = storage_path('app/test-output.mp4');

    $processResult = \Mockery::mock(ProcessResult::class);

    $result = new EncodingResult($processResult, $outputPath);

    expect($result->path())->toBe($outputPath);
    expect($result)->toBeInstanceOf(EncodingResult::class);
});

it('returns false when exporting failed encoding', function () {
    $outputPath = storage_path('app/test-output.mp4');
    // Don't create file - simulates failed encoding

    $processResult = \Mockery::mock(ProcessResult::class);
    $processResult->shouldReceive('successful')->andReturn(false);

    $result = new EncodingResult($processResult, $outputPath);

    $success = $result->export()
        ->toDisk('s3')
        ->save('encoded.mp4');

    expect($success)->toBeFalse();
});
