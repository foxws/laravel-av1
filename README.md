# Laravel AV1

[![Latest Version on Packagist](https://img.shields.io/packagist/v/foxws/laravel-av1.svg?style=flat-square)](https://packagist.org/packages/foxws/laravel-av1)
[![Tests](https://img.shields.io/github/actions/workflow/status/foxws/laravel-av1/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/foxws/laravel-av1/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/foxws/laravel-av1.svg?style=flat-square)](https://packagist.org/packages/foxws/laravel-av1)

A Laravel package for [ab-av1](https://github.com/alexheretic/ab-av1), enabling you to encode videos to AV1 format with automatic quality optimization using VMAF scoring.

```php
use Foxws\AV1\Facades\AV1;

// Automatic encoding with VMAF targeting
$result = AV1::open('videos/input.mp4')
    ->autoEncode()
    ->preset('6')
    ->minVmaf(95)
    ->export()
    ->toDisk('s3')
    ->save('output.mp4');
```

## Features

- 🎬 **Fluent API** - Laravel-style chainable methods
- 📁 **Multiple Disks** - Works with local, S3, and custom filesystems
- 🎯 **Quality Optimization** - Automatic CRF search targeting VMAF scores
- 📊 **VMAF & XPSNR** - Built-in quality metrics
- 🔄 **Multiple Commands** - Support for auto-encode, crf-search, sample-encode, encode, vmaf, and xpsnr
- 🧪 **Testable** - Clean architecture with mockable components
- 📝 **Type-Safe** - Full PHP 8.2+ type declarations

## Requirements

- PHP 8.2 or higher
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

## Quick Start

### Auto Encode

Automatically find the best CRF value to meet a target VMAF score:

```php
use Foxws\AV1\Facades\AV1;

$result = AV1::open('input.mp4')
    ->autoEncode()
    ->preset('6')
    ->minVmaf(95)
    ->export()
    ->save('output.mp4');
```

### CRF Search

Search for the optimal CRF value without encoding the full video:

```php
$result = AV1::open('input.mp4')
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
    ->autoEncode()
    ->preset('6')
    ->minVmaf(95)
    ->export()
    ->toDisk('s3')
    ->save('videos/output.mp4');
```

### From Local to S3

```php
$result = AV1::open('local/input.mp4')
    ->encode()
    ->crf(30)
    ->preset('6')
    ->export()
    ->toDisk('s3')
    ->toPath('videos/encoded')
    ->save('output.mp4');
```

## Advanced Options

### Encoder Selection

```php
$result = AV1::open('input.mp4')
    ->autoEncode()
    ->withEncoder('svt-av1') // or 'rav1e', 'aom'
    ->preset('6')
    ->minVmaf(95)
    ->export()
    ->save('output.mp4');
```

### CRF Range

```php
$result = AV1::open('input.mp4')
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
    ->autoEncode()
    ->preset('6')
    ->minVmaf(95)
    ->maxEncodedPercent(95) // Max 95% of original size
    ->export()
    ->save('output.mp4');
```

### Pixel Format

```php
$result = AV1::open('input.mp4')
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
    ->autoEncode()
    ->preset('6')
    ->minVmaf(95)
    ->fullVmaf() // Calculate VMAF for entire video
    ->export()
    ->save('output.mp4');
```

### VMAF Model

```php
$result = AV1::open('input.mp4')
    ->autoEncode()
    ->preset('6')
    ->minVmaf(95)
    ->vmafModel('/path/to/vmaf_model.json')
    ->export()
    ->save('output.mp4');
```

### Verbose Output

```php
$result = AV1::open('input.mp4')
    ->autoEncode()
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
            ->autoEncode()
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
    ->autoEncode()
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
    ->autoEncode()
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
    ->autoEncode()
    ->preset('6')
    ->minVmaf(95)
    ->export()
    ->dd();
```

## Configuration

The config file (`config/av1.php`) allows you to set defaults:

```php
return [
    'binary_path' => env('AB_AV1_BINARY_PATH', 'ab-av1'),
    'timeout' => env('AB_AV1_TIMEOUT', 3600),
    'encoder' => env('AB_AV1_ENCODER', 'svt-av1'),
    'preset' => env('AB_AV1_PRESET', 6),
    'min_vmaf' => env('AB_AV1_MIN_VMAF', 95),
    'temp_dir' => env('AB_AV1_TEMP_DIR'),
];
```

## Error Handling

```php
try {
    $result = AV1::open('input.mp4')
        ->autoEncode()
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

### auto-encode
Automatically encode video targeting a specific VMAF score.

**Required options:**
- `preset(string)` - Encoder preset
- `minVmaf(float)` - Minimum VMAF score

**Optional:**
- `withEncoder(string)` - Encoder to use (svt-av1, rav1e, aom)
- `maxEncodedPercent(int)` - Max size as percentage of input
- `sample(int)` - Sample duration in seconds
- `minCrf(int)` - Minimum CRF to try
- `maxCrf(int)` - Maximum CRF to try

### crf-search
Search for optimal CRF without encoding full video.

**Required options:**
- `preset(string)` - Encoder preset
- `minVmaf(float)` - Minimum VMAF score

**Optional:** Same as auto-encode

### sample-encode
Encode a sample of the video.

**Required options:**
- `crf(int)` - CRF value
- `preset(string)` - Encoder preset

**Optional:**
- `sample(int)` - Sample duration in seconds

### encode
Encode the entire video.

**Required options:**
- `crf(int)` - CRF value
- `preset(string)` - Encoder preset

### vmaf
Calculate VMAF score between two videos.

**Required options:**
- `reference(string)` - Reference video path
- `distorted(string)` - Distorted video path

**Optional:**
- `vmafModel(string)` - Path to VMAF model

### xpsnr
Calculate XPSNR score between two videos.

**Required options:**
- `reference(string)` - Reference video path
- `distorted(string)` - Distorted video path

## Testing

```bash
composer test
```

## Credits

- [ab-av1](https://github.com/alexheretic/ab-av1) - The underlying encoding tool
- [laravel-shaka](https://github.com/foxws/laravel-shaka) - Architecture inspiration

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
