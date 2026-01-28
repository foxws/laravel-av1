# Migration Guide: v1.x → v2.0

## Breaking Changes

v2.0 introduces a completely new, simplified API that breaks backward compatibility with v1.x.

### What Changed

#### 1. Facade API Changed

**v1.x:**
```php
use Foxws\AV1\Facades\AV1;

// Old MediaOpener-based API
AV1::open('input.mp4')
    ->ffmpegEncode()
    ->useHardwareAcceleration()
    ->crf(28)
    ->export()
    ->save('output.mp4');
```

**v2.0:**
```php
use Foxws\AV1\Facades\AV1;

// New direct API
AV1::encoder()
    ->useHwAccel()
    ->crf(28)
    ->encode('input.mp4', 'output.mp4');
```

#### 2. Method Names Simplified

| v1.x | v2.0 |
|------|------|
| `useHardwareAcceleration()` | `useHwAccel()` |
| `ffmpegEncode()` | `encoder()` |
| `ffmpegAutoEncode()` | `findCrf()` + `encoder()` |
| `targetVmaf()` | Second param in `findCrf()` |
| `export()->save()` | `encode()` |

#### 3. CRF Finding

**v1.x:**
```php
// Auto CRF was part of the encoding chain
AV1::open('input.mp4')
    ->ffmpegAutoEncode()
    ->targetVmaf(95)
    ->export()
    ->save('output.mp4');
```

**v2.0:**
```php
// Explicit CRF finding
$crf = AV1::findCrf('input.mp4', targetVmaf: 95);

AV1::encoder()
    ->crf($crf)
    ->encode('input.mp4', 'output.mp4');

// Or inline
AV1::encoder()
    ->crf(AV1::findCrf('input.mp4', 95))
    ->encode('input.mp4', 'output.mp4');
```

#### 4. No More Disk Management

**v1.x:**
```php
// Complex disk management
AV1::fromDisk('s3')
    ->open('input.mp4')
    ->ffmpegEncode()
    ->export()
    ->toDisk('s3')
    ->save('output.mp4');
```

**v2.0:**
```php
// Handle storage in your application layer
use Illuminate\Support\Facades\Storage;

// Download from S3
$localPath = Storage::disk('s3')->path('input.mp4');

// Encode
AV1::encoder()
    ->useHwAccel()
    ->encode($localPath, 'output.mp4');

// Upload to S3
Storage::disk('s3')->put('output.mp4', file_get_contents('output.mp4'));
```

## Complete Migration Examples

### Example 1: Basic Encoding

**v1.x:**
```php
AV1::open('input.mp4')
    ->ffmpegEncode()
    ->crf(28)
    ->preset('6')
    ->export()
    ->save('output.mp4');
```

**v2.0:**
```php
AV1::encoder()
    ->crf(28)
    ->preset(6)
    ->encode('input.mp4', 'output.mp4');
```

### Example 2: Hardware Acceleration

**v1.x:**
```php
AV1::open('input.mp4')
    ->ffmpegEncode()
    ->useHardwareAcceleration()
    ->crf(28)
    ->export()
    ->save('output.mp4');
```

**v2.0:**
```php
AV1::encoder()
    ->useHwAccel()
    ->crf(28)
    ->encode('input.mp4', 'output.mp4');
```

### Example 3: Auto CRF

**v1.x:**
```php
AV1::open('input.mp4')
    ->ffmpegAutoEncode()
    ->targetVmaf(95)
    ->preset('6')
    ->useHardwareAcceleration()
    ->export()
    ->save('output.mp4');
```

**v2.0:**
```php
AV1::encoder()
    ->crf(AV1::findCrf('input.mp4', 95, 6))
    ->preset(6)
    ->useHwAccel()
    ->encode('input.mp4', 'output.mp4');
```

### Example 4: Custom Settings

**v1.x:**
```php
AV1::open('input.mp4')
    ->ffmpegEncode()
    ->crf(28)
    ->pixFmt('yuv420p10le')
    ->audioCodec('libopus')
    ->videoFilter('scale=1920:1080')
    ->export()
    ->save('output.mp4');
```

**v2.0:**
```php
AV1::encoder()
    ->crf(28)
    ->pixelFormat('yuv420p10le')
    ->audioCodec('libopus')
    ->videoFilter('scale=1920:1080')
    ->encode('input.mp4', 'output.mp4');
```

## New Structure

v2.0 reorganizes the codebase into clear domains:

```
src/
  AbAV1/
    CrfFinder.php              # ab-av1 CRF optimization

  FFmpeg/
    VideoEncoder.php           # FFmpeg AV1 encoding
    HardwareAcceleration/
      HardwareDetector.php     # GPU detection
      Enums/
        HardwareEncoder.php    # Hardware encoder enum
        SoftwareEncoder.php    # Software encoder enum

  AV1Manager.php               # Main manager
  Facades/
    AV1.php                    # Single facade
```

## Removed Features

The following v1.x features are removed in v2.0:

- ❌ `MediaOpener` class
- ❌ `MediaCollection` and disk management
- ❌ `MediaExporter` with callbacks
- ❌ `open()` / `export()` / `save()` chain
- ❌ `fromDisk()` / `toDisk()` storage helpers
- ❌ `abav1()` encoder selection
- ❌ Old `CommandBuilder` for ab-av1
- ❌ `Encoder` wrapper class

## New Benefits

✅ **Simpler API** - Direct method calls instead of chains
✅ **Clearer intent** - `findCrf()` and `encoder()` are explicit
✅ **Type safety** - Enums for encoders
✅ **Single responsibility** - Each class does one thing
✅ **Better IDE support** - Clear return types and method hints

## Need Help?

Check the new documentation:
- [SIMPLIFIED_API.md](SIMPLIFIED_API.md) - Complete API reference
- [examples/SimplifiedApiExamples.php](examples/SimplifiedApiExamples.php) - Usage examples
