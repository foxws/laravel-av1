# Laravel AV1 - Package Architecture

## Overview

The laravel-av1 package is modeled after laravel-shaka's architecture and provides a fluent API for encoding videos using ab-av1. It supports all major ab-av1 commands with a Laravel-friendly interface.

## Package Structure

```
laravel-av1/
├── config/
│   └── av1.php                          # Configuration file
├── examples/
│   └── UsageExamples.php                # Comprehensive usage examples
└── src/
    ├── AV1ServiceProvider.php           # Laravel service provider
    ├── MediaOpener.php                  # Main entry point, fluent API
    ├── Facades/
    │   └── AV1.php                      # Laravel facade
    ├── Exporters/
    │   └── MediaExporter.php            # Handles exporting to disks
    ├── Filesystem/
    │   ├── Disk.php                     # Disk abstraction
    │   ├── Media.php                    # Media file representation
    │   ├── MediaCollection.php          # Collection of media files
    │   └── TemporaryDirectories.php     # Temporary directory management
    ├── Support/
    │   ├── AbAV1Encoder.php             # ab-av1 binary wrapper
    │   ├── CommandBuilder.php           # Command builder for ab-av1
    │   ├── Encoder.php                  # Main encoder orchestration
    │   ├── EncoderResult.php            # Encoding result
    │   └── ProcessOutput.php            # Process execution output
    └── Exceptions/
        └── MediaNotFoundException.php   # Custom exception
```

## Core Components

### 1. MediaOpener (Entry Point)

The main entry point that provides the fluent API. Handles:

- Opening media files from various disks
- Chainable method calls for all ab-av1 commands
- Forwarding calls to the encoder

**Usage:**

```php
AV1::open('input.mp4')->vmafEncode()->preset('6')->minVmaf(95)->export()->save('output.mp4');
```

### 2. CommandBuilder

Builds ab-av1 command arguments for all supported commands:

- `auto-encode` - Automatic encoding with VMAF targeting
- `crf-search` - Search for optimal CRF
- `sample-encode` - Encode samples
- `encode` - Full encoding
- `vmaf` - Calculate VMAF scores
- `xpsnr` - Calculate XPSNR scores

### 3. Encoder

Orchestrates the encoding process:

- Manages media collections
- Resolves input/output paths
- Executes commands via AbAV1Encoder

### 4. AbAV1Encoder

Wrapper around the ab-av1 binary:

- Executes ab-av1 commands via Laravel's Process facade
- Handles timeouts and error handling
- Provides binary availability checking

### 5. MediaExporter

Handles exporting encoded files:

- Saves to different disks (local, S3, etc.)
- Manages file visibility
- Supports after-saving callbacks

### 6. Filesystem Components

**Disk**: Abstraction over Laravel's filesystem
**Media**: Represents a media file with disk operations
**MediaCollection**: Collection of media files
**TemporaryDirectories**: Manages temporary directories for encoding

## Supported ab-av1 Commands

### auto-encode

```php
AV1::open('input.mp4')
    ->vmafEncode()
    ->preset('6')
    ->minVmaf(95)
    ->export()
    ->save('output.mp4');
```

### crf-search

```php
AV1::open('input.mp4')
    ->crfSearch()
    ->preset('6')
    ->minVmaf(95)
    ->minCrf(20)
    ->maxCrf(40)
    ->export()
    ->save();
```

### sample-encode

```php
AV1::open('input.mp4')
    ->sampleEncode()
    ->crf(30)
    ->preset('6')
    ->sample(60)
    ->export()
    ->save('sample.mp4');
```

### encode

```php
AV1::open('input.mp4')
    ->encode()
    ->crf(30)
    ->preset('6')
    ->export()
    ->save('output.mp4');
```

### vmaf

```php
AV1::vmaf()
    ->reference('original.mp4')
    ->distorted('encoded.mp4')
    ->export()
    ->save();
```

### xpsnr

```php
AV1::xpsnr()
    ->reference('original.mp4')
    ->distorted('encoded.mp4')
    ->export()
    ->save();
```

## Available Options

### Common Options

- `preset(string)` - Encoder preset (0-13 for svt-av1)
- `minVmaf(float)` - Minimum VMAF score
- `crf(int)` - CRF value
- `withEncoder(string)` - Encoder selection (svt-av1, rav1e, aom)
- `output(string)` - Output file path

### Advanced Options

- `minCrf(int)` - Minimum CRF for search
- `maxCrf(int)` - Maximum CRF for search
- `maxEncodedPercent(int)` - Max size as percentage
- `sample(int)` - Sample duration in seconds
- `pixFmt(string)` - Pixel format
- `fullVmaf()` - Calculate VMAF for full video
- `vmafModel(string)` - Custom VMAF model path
- `vmafThreads(int)` - VMAF thread count
- `verbose()` - Verbose output

### Quality Comparison Options

- `reference(string)` - Reference video path
- `distorted(string)` - Distorted video path

## Multi-Disk Support

Works seamlessly with Laravel's filesystem:

```php
// From S3 to S3
AV1::fromDisk('s3')
    ->open('videos/input.mp4')
    ->vmafEncode()
    ->preset('6')
    ->minVmaf(95)
    ->export()
    ->toDisk('s3')
    ->toPath('encoded')
    ->save('output.mp4');

// Local to S3
AV1::open('local/input.mp4')
    ->encode()
    ->crf(30)
    ->preset('6')
    ->export()
    ->toDisk('s3')
    ->save('output.mp4');
```

## Configuration

Default configuration in `config/av1.php`:

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

## Key Design Patterns

### 1. Fluent Interface

All methods return `$this` to enable method chaining:

```php
AV1::open('input.mp4')->vmafEncode()->preset('6')->minVmaf(95)->export()->save();
```

### 2. Facade Pattern

`AV1` facade provides clean API access:

```php
use Foxws\AV1\Facades\AV1;
```

### 3. Builder Pattern

`CommandBuilder` constructs complex ab-av1 commands incrementally.

### 4. Strategy Pattern

Different encoding strategies (auto-encode, crf-search, etc.) implemented as commands.

### 5. Dependency Injection

All components use Laravel's service container for dependency injection.

## Error Handling

```php
try {
    $result = AV1::open('input.mp4')
        ->vmafEncode()
        ->preset('6')
        ->minVmaf(95)
        ->export()
        ->save('output.mp4');

    if (!$result->isSuccessful()) {
        Log::error('Encoding failed', [
            'error' => $result->getErrorOutput(),
            'exit_code' => $result->getExitCode(),
        ]);
    }
} catch (\Exception $e) {
    Log::error('Exception: ' . $e->getMessage());
}
```

## Testing Recommendations

1. **Unit Tests**: Test individual components (CommandBuilder, Encoder, etc.)
2. **Integration Tests**: Test complete workflows with mock filesystem
3. **Feature Tests**: Test end-to-end encoding scenarios

## Extending the Package

### Add Custom Encoder Options

```php
// In MediaOpener or CommandBuilder
public function customOption(mixed $value): self
{
    $this->encoder->builder()->withOption('custom-option', $value);
    return $this;
}
```

### Add Custom Callbacks

```php
AV1::open('input.mp4')
    ->vmafEncode()
    ->preset('6')
    ->minVmaf(95)
    ->export()
    ->afterSaving(function ($result, $path) {
        // Custom logic here
    })
    ->save('output.mp4');
```

## Comparison with laravel-shaka

| Feature      | laravel-shaka      | laravel-av1                |
| ------------ | ------------------ | -------------------------- |
| Purpose      | HLS/DASH packaging | AV1 encoding               |
| Binary       | shaka-packager     | ab-av1                     |
| Primary Use  | Adaptive streaming | Quality-optimized encoding |
| Commands     | Package streams    | Encode, analyze quality    |
| Architecture | ✅ Similar         | ✅ Similar                 |
| Fluent API   | ✅ Yes             | ✅ Yes                     |
| Multi-disk   | ✅ Yes             | ✅ Yes                     |

## Dependencies

- PHP 8.2+
- Laravel 11+
- ab-av1 binary
- illuminate/contracts
- illuminate/filesystem
- illuminate/process
- illuminate/support
- spatie/laravel-package-tools

## License

MIT License - See LICENSE.md
