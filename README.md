# Laravel AV1

[![Latest Version on Packagist](https://img.shields.io/packagist/v/foxws/laravel-av1.svg?style=flat-square)](https://packagist.org/packages/foxws/laravel-av1)
[![Tests](https://img.shields.io/github/actions/workflow/status/foxws/laravel-av1/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/foxws/laravel-av1/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/foxws/laravel-av1.svg?style=flat-square)](https://packagist.org/packages/foxws/laravel-av1)

A Laravel package for [ab-av1](https://github.com/alexheretic/ab-av1), enabling you to encode videos to AV1 format with VMAF-targeted quality optimization.

```php
use Foxws\AV1\Facades\AV1;

// VMAF-targeted encoding
$result = AV1::open('videos/input.mp4')
    ->abav1() // Use ab-av1 encoder
    ->vmafEncode()
    ->preset('6')
    ->minVmaf(95)
    ->export()
    ->toDisk('s3')
    ->save('output.mp4');
```

## Features

- 🎬 **Fluent API** - Laravel-style chainable methods
- 📁 **Multiple Disks** - Works with local, S3, and custom filesystems
- 🎯 **VMAF-Targeted Encoding** - Automatically finds optimal CRF for target quality
- 📊 **Quality Metrics** - Built-in VMAF & XPSNR scoring
- 🔄 **Multiple Encoders** - Designed to support ab-av1, av1an, svt-av1, and more
- 🧪 **Testable** - Clean architecture with mockable components
- 📝 **Type-Safe** - Full PHP 8.4+ type declarations

## Requirements

- PHP 8.3 or higher
- Laravel 11.x or higher
- [ab-av1](https://github.com/alexheretic/ab-av1) binary installed on your system

## Installation

Install the package via composer:

```bash
composer require foxws/laravel-av1
```

Publish the config file:

```bash
php artisan vendor:publish --tag="av1-config"
```

### Installing ab-av1

Install ab-av1 using cargo:

```bash
cargo install ab-av1
```

Or download a prebuilt binary from the [releases page](https://github.com/alexheretic/ab-av1/releases).

### Verify Installation

After installation, verify that ab-av1 is properly configured:

```bash
php artisan av1:verify
```

This will check:

- Binary exists and is executable
- Can retrieve version information
- Configuration is properly set up
- Temporary directory is accessible

### Package Information

View package and binary information:

```bash
php artisan av1:info
```

## Quick Start

### Using ab-av1 Encoder

This package uses ab-av1 as its primary encoder. Select the encoder explicitly:

```php
use Foxws\AV1\Facades\AV1;

$result = AV1::open('input.mp4')
    ->abav1() // Use ab-av1 encoder
    ->vmafEncode()
    ->preset('6')
    ->minVmaf(95)
    ->export()
    ->save('output.mp4');
```

> **Note**: The package is designed to support multiple encoders (av1an, svt-av1, etc.) in future versions using the same fluent API pattern.

### VMAF Encode

Automatically find the optimal CRF value to achieve a target VMAF quality score and encode the full video:

```php
use Foxws\AV1\Facades\AV1;

$result = AV1::open('input.mp4')
    ->abav1() // Use ab-av1 encoder
    ->vmafEncode()
    ->preset('6')
    ->minVmaf(95)
    ->export()
    ->save('output.mp4');
```

### CRF Search

Search for the optimal CRF value without encoding the full video:

```php
$result = AV1::open('input.mp4')
    ->abav1()
    ->crfSearch()
    ->preset('6')
    ->minVmaf(95)
    ->export()
    ->save();

// The output will contain the recommended CRF value
echo $result->getOutput();
```

### Sample Encode

Encode a sample of the video to test settings:

```php
$result = AV1::open('input.mp4')
    ->abav1()
    ->sampleEncode()
    ->crf(30)
    ->preset('6')
    ->sample(60) // Sample duration in seconds
    ->export()
    ->save('sample.mp4');
```

### Full Encode

Encode the entire video with a specific CRF:

```php
$result = AV1::open('input.mp4')
    ->abav1()
    ->encode()
    ->crf(30)
    ->preset('6')
    ->export()
    ->save('output.mp4');
```

### VMAF Score

Calculate VMAF score between two videos:

```php
$result = AV1::vmaf()
    ->abav1()
    ->reference('original.mp4')
    ->distorted('encoded.mp4')
    ->export()
    ->save();

echo $result->getOutput(); // VMAF score
```

### XPSNR Score

Calculate XPSNR score between two videos:

```php
$result = AV1::xpsnr()
    ->abav1()
    ->reference('original.mp4')
    ->distorted('encoded.mp4')
    ->export()
    ->save();

echo $result->getOutput(); // XPSNR score
```

## Working with Different Disks

### From S3

```php
$result = AV1::fromDisk('s3')
    ->open('videos/input.mp4')
    ->abav1()
    ->vmafEncode()
    ->preset('6')
    ->minVmaf(95)
    ->export()
    ->toDisk('s3')
    ->save('videos/output.mp4');
```

### From Local to S3

```php
$result = AV1::open('local/input.mp4')
    ->abav1()
    ->encode()
    ->crf(30)
    ->preset('6')
    ->export()
    ->toDisk('s3')
    ->toPath('videos/encoded')
    ->save('output.mp4');
```

## Advanced Options

### CRF Range

```php
$result = AV1::open('input.mp4')
    ->abav1()
    ->crfSearch()
    ->minCrf(20)
    ->maxCrf(40)
    ->preset('6')
    ->minVmaf(95)
    ->export()
    ->save();
```

### Max Encoded Size

```php
$result = AV1::open('input.mp4')
    ->abav1()
    ->vmafEncode()
    ->preset('6')
    ->minVmaf(95)
    ->maxEncodedPercent(95) // Max 95% of original size
    ->export()
    ->save('output.mp4');
```

### Pixel Format

```php
$result = AV1::open('input.mp4')
    ->abav1()
    ->encode()
    ->crf(30)
    ->preset('6')
    ->pixFmt('yuv420p10le')
    ->export()
    ->save('output.mp4');
```

### Full VMAF

```php
$result = AV1::open('input.mp4')
    ->abav1()
    ->vmafEncode()
    ->preset('6')
    ->minVmaf(95)
    ->fullVmaf() // Calculate VMAF for entire video
    ->export()
    ->save('output.mp4');
```

### VMAF Model

```php
$result = AV1::open('input.mp4')
    ->abav1()
    ->vmafEncode()
    ->preset('6')
    ->minVmaf(95)
    ->vmafModel('/path/to/vmaf_model.json')
    ->export()
    ->save('output.mp4');
```

### Verbose Output

```php
$result = AV1::open('input.mp4')
    ->abav1()
    ->vmafEncode()
    ->preset('6')
    ->minVmaf(95)
    ->verbose()
    ->export()
    ->save('output.mp4');

echo $result->getOutput();
```

## Batch Processing

Process multiple files:

```php
$files = ['video1.mp4', 'video2.mp4', 'video3.mp4'];

AV1::fromDisk('s3')
    ->each($files, function ($av1, $file) {
        $av1->open($file)
            ->abav1()
            ->vmafEncode()
            ->preset('6')
            ->minVmaf(95)
            ->export()
            ->toDisk('s3')
            ->toPath('encoded')
            ->save();
    });
```

## Callbacks

Add callbacks after encoding:

```php
$result = AV1::open('input.mp4')
    ->abav1()
    ->vmafEncode()
    ->preset('6')
    ->minVmaf(95)
    ->export()
    ->afterSaving(function ($result, $path) {
        Log::info("Encoded to: {$path}");
        Log::info("VMAF score: {$result->getOutput()}");
    })
    ->save('output.mp4');
```

## Debugging

Get the command that will be executed:

```php
$command = AV1::open('input.mp4')
    ->abav1()
    ->vmafEncode()
    ->preset('6')
    ->minVmaf(95)
    ->export()
    ->getCommand();

echo $command;
// Output: ab-av1 auto-encode -i input.mp4 --preset 6 --min-vmaf 95
```

Or dump and die:

```php
AV1::open('input.mp4')
    ->abav1()
    ->vmafEncode()
    ->preset('6')
    ->minVmaf(95)
    ->export()
    ->dd();
```

## Configuration

### Environment Variables

- `AB_AV1_BINARY_PATH` - Path to ab-av1 binary (default: 'ab-av1')
- `FFMPEG_BINARY_PATH` - Path to ffmpeg binary with libsvtav1, libvmaf, libopus (default: 'ffmpeg')
- `AB_AV1_LOG_CHANNEL` - Log channel to use (false to disable, null for default)
- `AB_AV1_TIMEOUT` - Maximum time in seconds for encoding commands (default: 14400)
- `AB_AV1_PRESET` - Default encoder preset 0-13 for svt-av1 (default: 6)
- `AB_AV1_MIN_VMAF` - Minimum VMAF score to target (default: 80)
- `AB_AV1_MAX_PERCENT` - Maximum encoded file size as percentage (default: 300)
- `AB_AV1_TEMPORARY_FILES_ROOT` - Directory for temporary files (default: storage/app/av1/temp)

## Error Handling

```php
try {
    $result = AV1::open('input.mp4')
        ->abav1()
        ->vmafEncode()
        ->preset('6')
        ->minVmaf(95)
        ->export()
        ->save('output.mp4');

    if ($result->isSuccessful()) {
        echo "Encoding successful!";
    } else {
        echo "Encoding failed: " . $result->getErrorOutput();
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## Available Commands

### vmafEncode()

Automatically finds the optimal CRF value to achieve a target VMAF score, then encodes the full video at that quality level.

**Required options:**

- `preset(string)` - Encoder preset
- `minVmaf(float)` - Minimum VMAF score

**Optional:**

- `maxEncodedPercent(int)` - Max size as percentage of input
- `sample(int)` - Sample duration in seconds
- `minCrf(int)` - Minimum CRF to try
- `maxCrf(int)` - Maximum CRF to try

### crfSearch()

Searches for the optimal CRF value to achieve a target VMAF score without encoding the full video. Useful for testing before full encoding.

**Required options:**

- `preset(string)` - Encoder preset (0-13 for svt-av1)
- `minVmaf(float)` - Target VMAF score (0-100)

**Optional:** Same as vmafEncode()

### sampleEncode()

Encodes a sample portion of the video for testing settings before committing to a full encode.

**Required options:**

- `crf(int)` - CRF value to use for encoding
- `preset(string)` - Encoder preset (0-13 for svt-av1)

**Optional:**

- `sample(int)` - Sample duration in seconds (default varies by encoder)

### encode()

Encodes the entire video using a manually specified CRF value. Use this when you know the exact CRF you want.

**Required options:**

- `crf(int)` - CRF value (lower = higher quality, larger file)
- `preset(string)` - Encoder preset (0-13 for svt-av1, higher = faster/larger)

### vmaf()

Calculates the VMAF (Video Multimethod Assessment Fusion) quality score between a reference and distorted video.

**Required options:**

- `reference(string)` - Reference (original) video path
- `distorted(string)` - Distorted (encoded) video path

**Optional:**

- `vmafModel(string)` - Path to custom VMAF model file

### xpsnr()

Calculates the XPSNR (Extended Peak Signal-to-Noise Ratio) quality score between a reference and distorted video.

**Required options:**

- `reference(string)` - Reference (original) video path
- `distorted(string)` - Distorted (encoded) video path

## Testing

```bash
composer test
```

## Credits

- [ab-av1](https://github.com/alexheretic/ab-av1) - The underlying encoding tool
- [Laravel FFMpeg](https://github.com/pbmedia/laravel-ffmpeg) - Architecture inspiration

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
