<?php

/**
 * Simplified Media Export Examples (v2.0)
 */

use Foxws\AV1\Facades\AV1;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

// Example 1: Using fromDisk and toDisk (recommended)
AV1::fromDisk('media')
    ->path('videos/input.mp4')
    ->encoder()
    ->crf(28)
    ->preset(6)
    ->encode()
    ->export()
    ->toDisk('s3')
    ->toPath('videos/encoded')
    ->save('final.mp4');

// Example 2: Simpler - source and target disks only
AV1::fromDisk('media')
    ->path('videos/input.mp4')
    ->encoder()
    ->crf(28)
    ->encode()
    ->export()
    ->toDisk('transcodes')
    ->save();

// Example 3: Using direct file paths
AV1::encoder()
    ->crf(28)
    ->preset(6)
    ->useHwAccel()
    ->encode(
        storage_path('app/input.mp4'),
        storage_path('app/temp/output.mp4')
    )
    ->export()
    ->toDisk('s3')
    ->toPath('videos/encoded')
    ->save('final-video.mp4');

// Example 4: Encode locally, keep control
$result = AV1::encoder()
    ->crf(28)
    ->encode(
        storage_path('app/input.mp4'),
        storage_path('app/output.mp4')
    );

if ($result->successful()) {
    // Export to multiple destinations
    $result->export()->toDisk('s3')->save('backup.mp4');
    $result->export()->toDisk('local')->toPath('archive')->save();
}

// Example 6: Public file on CDN using disks
AV1::encoder()
    ->crf(30)
    ->fromDisk('uploads')
    ->path('videos/source.mp4')
    ->encode()
    ->export()
    ->toDisk('s3-public')
    ->toPath('videos/public')
    ->withVisibility('public')
    ->save('public-video.mp4');

// Example 7: Local filesystem to S3 workflow
// Step 1: Encode locally for speed
$result = AV1::encoder()
    ->crf(28)
    ->useHwAccel()
    ->encode(
        storage_path('app/input.mp4'),
        storage_path('app/temp/output.mp4')
    );

// Step 2: Upload to S3 after encoding succeeds
if ($result->successful()) {
    $result->export()
        ->toDisk('s3')
        ->toPath('videos/encoded')
        ->save('final.mp4');

    // Clean up local temp file
    unlink($result->path());
}

// Example 8: With CRF optimization and disks
AV1::encoder()
    ->fromDisk('media')
    ->path('videos/input.mp4')
    ->crf(AV1::findCrf(Storage::disk('media')->path('videos/input.mp4'), targetVmaf: 95))
    ->encode()
    ->export()
    ->toDisk('s3')
    ->save();

// Example 9: Check encoding status before exporting
$result = AV1::encoder()
    ->crf(28)
    ->encode(
        storage_path('app/input.mp4'),
        storage_path('app/output.mp4')
    );

if ($result->failed()) {
    Log::error('Encoding failed', [
        'error' => $result->errorOutput(),
    ]);
} else {
    $result->export()
        ->toDisk('s3')
        ->toPath('videos')
        ->save();
}

// Example 10: Just get the encoded file path (no export)
$result = AV1::encoder()
    ->crf(28)
    ->encode(
        storage_path('app/input.mp4'),
        storage_path('app/output.mp4')
    );

$encodedPath = $result->path();
// Do something with the local file

// Example 11: Directory as target path with disks
AV1::encoder()
    ->fromDisk('uploads')
    ->path('videos/input.mp4')
    ->encode()
    ->export()
    ->toDisk('s3')
    ->toPath('videos/2024/january')
    ->save();

// Example 12: Cross-disk encoding (S3 to local transcodes disk)
AV1::encoder()
    ->crf(28)
    ->fromDisk('s3-originals')
    ->path('raw-footage/video.mp4')
    ->encode()
    ->export()
    ->toDisk('transcodes')
    ->toPath('processed/2024')
    ->save();
