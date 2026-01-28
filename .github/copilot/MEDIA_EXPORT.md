# Media Export Guide (v2.0)

The simplified v2.0 API makes it easy to encode videos and export them to different storage locations.

## Basic Export

```php
use Foxws\AV1\Facades\AV1;

// Encode and export to S3
AV1::encoder()
    ->crf(28)
    ->encode('input.mp4', 'output.mp4')
    ->export()
    ->toDisk('s3')
    ->save();
```

## Export to Custom Path

```php
// Specify target directory
AV1::encoder()
    ->crf(28)
    ->encode('input.mp4', 'output.mp4')
    ->export()
    ->toDisk('s3')
    ->toPath('videos/encoded')
    ->save('final-video.mp4');
// Saves to: s3://videos/encoded/final-video.mp4
```

## Local to Cloud Workflow (Recommended)

For best performance, encode locally then upload to cloud storage:

```php
// Step 1: Encode locally for fast I/O
$result = AV1::encoder()
    ->crf(28)
    ->useHwAccel()
    ->encode('input.mp4', storage_path('app/temp/output.mp4'));

// Step 2: Upload to S3 after encoding succeeds
if ($result->successful()) {
    $result->export()
        ->toDisk('s3')
        ->toPath('videos/encoded')
        ->save();

    // Clean up local temp file
    unlink($result->path());
}
```

## Public Files

```php
// Make file publicly accessible
AV1::encoder()
    ->crf(30)
    ->encode('input.mp4', 'output.mp4')
    ->export()
    ->toDisk('s3-public')
    ->withVisibility('public')
    ->save('public-video.mp4');
```

## Multiple Destinations

```php
$result = AV1::encoder()
    ->crf(28)
    ->encode('input.mp4', 'output.mp4');

if ($result->successful()) {
    // Save to S3
    $result->export()
        ->toDisk('s3')
        ->save('production.mp4');

    // Also save backup locally
    $result->export()
        ->toDisk('local')
        ->toPath('backups')
        ->save();
}
```

## With CRF Optimization

```php
// Find optimal CRF first
$optimalCrf = AV1::findCrf('input.mp4', targetVmaf: 95);

// Encode and export
AV1::encoder()
    ->crf($optimalCrf)
    ->encode('input.mp4', 'output.mp4')
    ->export()
    ->toDisk('s3')
    ->toPath('videos/optimized')
    ->save();
```

## Direct Path Control

```php
// Directory path - uses original filename
AV1::encoder()
    ->encode('input.mp4', 'output.mp4')
    ->export()
    ->toDisk('s3')
    ->toPath('videos/2024/january')
    ->save();
// Result: s3://videos/2024/january/output.mp4

// Full path - explicit filename
AV1::encoder()
    ->encode('input.mp4', 'output.mp4')
    ->export()
    ->toDisk('s3')
    ->toPath('videos/final.mp4')  // Has extension
    ->save();
// Result: s3://videos/final.mp4
```

## Error Handling

```php
$result = AV1::encoder()
    ->crf(28)
    ->encode('input.mp4', 'output.mp4');

if ($result->failed()) {
    Log::error('Encoding failed', [
        'error' => $result->errorOutput(),
        'exit_code' => $result->exitCode(),
    ]);
    return;
}

// Export if successful
$result->export()
    ->toDisk('s3')
    ->save();
```

## EncodingResult Methods

```php
$result = AV1::encoder()->encode('input.mp4', 'output.mp4');

$result->successful();     // Check if encoding succeeded
$result->failed();         // Check if encoding failed
$result->path();           // Get output file path
$result->exitCode();       // Get process exit code
$result->output();         // Get process output
$result->errorOutput();    // Get error output
$result->export();         // Start export chain
```

## MediaExporter Methods

```php
$exporter = $result->export();

$exporter->toDisk('s3');              // Set target disk
$exporter->toPath('videos/encoded');   // Set target path
$exporter->withVisibility('public');   // Set file visibility
$exporter->save('filename.mp4');       // Save file (optional filename)

// Getters
$exporter->getSourcePath();   // Get encoded file path
$exporter->getDisk();         // Get target disk
$exporter->getTargetPath();   // Get target path
$exporter->result();          // Get ProcessResult
```
