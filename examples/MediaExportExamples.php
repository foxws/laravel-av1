<?php

/**
 * Simplified Media Export Examples (v2.0)
 */

use Foxws\AV1\Facades\AV1;

// Example 1: Encode and export to S3
AV1::encoder()
    ->crf(28)
    ->preset(6)
    ->useHwAccel()
    ->encode('local/input.mp4', 'temp/output.mp4')
    ->export()
    ->toDisk('s3')
    ->toPath('videos/encoded')
    ->save('final-video.mp4');

// Example 2: Encode locally, keep control
$result = AV1::encoder()
    ->crf(28)
    ->encode('input.mp4', 'output.mp4');

if ($result->successful()) {
    // Export to multiple destinations
    $result->export()->toDisk('s3')->save('backup.mp4');
    $result->export()->toDisk('local')->toPath('archive')->save();
}

// Example 3: Public file on CDN
AV1::encoder()
    ->crf(30)
    ->encode('input.mp4', 'output.mp4')
    ->export()
    ->toDisk('s3-public')
    ->toPath('videos/public')
    ->withVisibility('public')
    ->save('public-video.mp4');

// Example 4: Local to S3 workflow (recommended for performance)
// Step 1: Encode locally for speed
$result = AV1::encoder()
    ->crf(28)
    ->useHwAccel()
    ->encode('input.mp4', storage_path('app/temp/output.mp4'));

// Step 2: Upload to S3 after encoding succeeds
if ($result->successful()) {
    $result->export()
        ->toDisk('s3')
        ->toPath('videos/encoded')
        ->save('final.mp4');

    // Clean up local temp file
    unlink($result->path());
}

// Example 5: With CRF optimization
$optimalCrf = AV1::findCrf('input.mp4', targetVmaf: 95);

AV1::encoder()
    ->crf($optimalCrf)
    ->encode('input.mp4', 'output.mp4')
    ->export()
    ->toDisk('s3')
    ->save();

// Example 6: Check encoding status before exporting
$result = AV1::encoder()
    ->crf(28)
    ->encode('input.mp4', 'output.mp4');

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

// Example 7: Just get the encoded file path (no export)
$result = AV1::encoder()
    ->crf(28)
    ->encode('input.mp4', 'output.mp4');

$encodedPath = $result->path();
// Do something with the local file

// Example 8: Directory as target path
AV1::encoder()
    ->encode('input.mp4', 'output.mp4')
    ->export()
    ->toDisk('s3')
    ->toPath('videos/2024/january')  // Directory
    ->save();  // Uses original filename: videos/2024/january/output.mp4

// Example 9: Full path as target
AV1::encoder()
    ->encode('input.mp4', 'output.mp4')
    ->export()
    ->toDisk('s3')
    ->toPath('videos/final-video.mp4')  // Full path with extension
    ->save();  // Saves to: videos/final-video.mp4
