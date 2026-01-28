# AV1 Encoding Support - Implementation Summary

This document summarizes the AV1 encoding features added to the Laravel AV1 package.

## Overview

The package now supports two encoding methods:

1. **ab-av1**: VMAF-targeted quality optimization (existing)
2. **FFmpeg**: Direct AV1 encoding with GPU hardware acceleration (new)

You can also combine both: use ab-av1 to find the optimal CRF, then encode with FFmpeg using GPU acceleration.

## New Files Added

### 1. `src/Support/FFmpegEncoder.php`
- Implements `EncoderInterface` for direct FFmpeg encoding
- Supports hardware acceleration (Intel QSV, AMD AMF, NVIDIA NVENC)
- Auto-detects best available encoder
- Handles different CRF/preset parameters for different encoders

### 2. `src/Support/HardwareDetector.php`
- Detects available AV1 encoders in FFmpeg
- Identifies hardware vs software encoders
- Prioritizes hardware encoders for best performance
- Caches results for efficiency
- Supports Intel QSV, AMD AMF, NVIDIA NVENC, and CPU encoders (libsvtav1, libaom-av1, librav1e)

### 3. `src/Support/CrfOptimizer.php`
- Uses ab-av1 to find optimal CRF value
- Can be used before FFmpeg encoding
- Parses ab-av1 output to extract recommended CRF
- Enables "best of both worlds" workflow

### 4. `FFMPEG_ENCODING.md`
- Comprehensive FFmpeg encoding examples
- Hardware acceleration guide for Intel, AMD, and NVIDIA GPUs
- Auto CRF optimization examples
- Performance tips and configuration guide

## Modified Files

### 1. `composer.json`
- Added `pbmedia/laravel-ffmpeg` dependency for FFmpeg integration

### 2. `config/av1.php`
- Added FFmpeg configuration section:
  - Encoder selection (auto-detect or manual)
  - Hardware acceleration toggle
  - Default CRF and preset values
  - Audio codec and pixel format settings
  - Auto CRF opt-in

### 3. `src/Support/CommandBuilder.php`
- Added FFmpeg-specific methods:
  - `videoCodec()` - Set video codec
  - `audioCodec()` - Set audio codec
  - `videoFilter()` - Apply video filters
  - `hardwareAcceleration()` - Enable GPU acceleration
  - `customArgs()` - Add custom FFmpeg arguments
  - `autoCrf()` - Enable auto CRF detection
  - `targetVmaf()` - Set target VMAF for auto CRF

### 4. `src/Support/Encoder.php`
- Added FFmpeg encoding support
- Implements auto CRF workflow
- Detects FFmpegEncoder and uses appropriate methods
- Integrates CrfOptimizer for optimal quality

### 5. `src/MediaOpener.php`
- Added new fluent API methods:
  - `ffmpeg()` - Switch to FFmpeg encoder
  - `ffmpegEncode()` - FFmpeg encode with defaults
  - `ffmpegAutoEncode()` - FFmpeg with auto CRF optimization
  - `useHardwareAcceleration()` - Enable GPU encoding
  - `withAutoCrf()` - Enable auto CRF mode

### 6. `README.md`
- Updated features list to include hardware acceleration
- Added FFmpeg quick start examples
- Added hardware acceleration examples
- Updated configuration section with FFmpeg settings
- Added link to detailed FFmpeg guide

## Usage Examples

### Basic FFmpeg Encoding
```php
use Foxws\AV1\Facades\AV1;

$result = AV1::open('input.mp4')
    ->ffmpegEncode()
    ->crf(28)
    ->preset('6')
    ->export()
    ->save('output.mp4');
```

### Hardware Acceleration (GPU)
```php
// Auto-detect best GPU encoder
$result = AV1::open('input.mp4')
    ->ffmpegEncode()
    ->useHardwareAcceleration()
    ->crf(28)
    ->preset('6')
    ->export()
    ->save('output.mp4');
```

### Auto CRF Optimization
```php
// Use ab-av1 to find optimal CRF, then encode with FFmpeg GPU
$result = AV1::open('input.mp4')
    ->ffmpegAutoEncode()
    ->targetVmaf(95)
    ->preset('6')
    ->useHardwareAcceleration()
    ->export()
    ->save('output.mp4');
```

### Check Available Encoders
```php
use Foxws\AV1\Support\HardwareDetector;

$detector = new HardwareDetector();

// Get all available encoders
$encoders = $detector->getAvailableEncoders();
// Returns: ['av1_qsv' => [...], 'libsvtav1' => [...], ...]

// Check if hardware acceleration is available
$hasHardware = $detector->hasHardwareAcceleration(); // true/false

// Get best encoder (hardware prioritized)
$best = $detector->getBestEncoder(); // 'av1_qsv' or 'libsvtav1'
```

## Hardware Encoder Support

The package automatically detects and uses these encoders:

### Hardware Encoders (Priority Order)
1. **av1_qsv** - Intel Quick Sync Video (Intel GPUs)
2. **av1_amf** - AMD Advanced Media Framework (AMD GPUs)
3. **av1_nvenc** - NVIDIA NVENC (NVIDIA GPUs)

### Software Encoders (Fallback)
1. **libsvtav1** - SVT-AV1 (CPU, recommended)
2. **libaom-av1** - AOM AV1 (CPU, slower but high quality)
3. **librav1e** - rav1e (CPU)

## Configuration

Add to your `.env`:

```bash
# FFmpeg configuration
FFMPEG_BINARY_PATH=/usr/local/bin/ffmpeg
FFMPEG_TIMEOUT=7200
FFMPEG_HARDWARE_ACCEL=true
FFMPEG_DEFAULT_CRF=30
FFMPEG_DEFAULT_PRESET=6
FFMPEG_AUTO_CRF=false

# Optional: Force specific encoder
# FFMPEG_ENCODER=av1_qsv      # Intel
# FFMPEG_ENCODER=av1_amf      # AMD
# FFMPEG_ENCODER=av1_nvenc    # NVIDIA
# FFMPEG_ENCODER=libsvtav1    # CPU
```

## Encoding Workflows

### Workflow 1: Pure ab-av1 (Original)
- Uses ab-av1 for CRF search and encoding
- CPU-only encoding
- Best for quality-targeted encoding
- Slower encoding speed

### Workflow 2: Pure FFmpeg
- Direct FFmpeg encoding with known CRF
- GPU acceleration available
- Fastest encoding
- Requires manual CRF selection

### Workflow 3: Hybrid (Recommended)
- ab-av1 finds optimal CRF for target VMAF
- FFmpeg encodes using that CRF with GPU
- Best quality + fast encoding
- Requires both ab-av1 and FFmpeg

## Performance Comparison

Typical encoding speeds (1080p video):

- **ab-av1 (CPU)**: 5-15 fps
- **FFmpeg CPU (libsvtav1)**: 10-30 fps
- **FFmpeg GPU (av1_qsv/amf/nvenc)**: 30-100+ fps
- **Hybrid (ab-av1 CRF + FFmpeg GPU)**: CRF search time + 30-100+ fps

## Migration Guide

Existing code continues to work without changes. To add hardware acceleration:

**Before:**
```php
$result = AV1::open('input.mp4')
    ->abav1()
    ->vmafEncode()
    ->preset('6')
    ->minVmaf(95)
    ->export()
    ->save('output.mp4');
```

**After (with GPU):**
```php
$result = AV1::open('input.mp4')
    ->ffmpegAutoEncode()
    ->targetVmaf(95)
    ->preset('6')
    ->useHardwareAcceleration()
    ->export()
    ->save('output.mp4');
```

## Testing

To test hardware acceleration support:

```php
use Foxws\AV1\Support\HardwareDetector;

$detector = new HardwareDetector();
dd($detector->getEncoderInfo());

// Output will show:
// - Available encoders
// - Best encoder
// - Hardware acceleration status
// - Hardware acceleration method
```

## Dependencies

The package now depends on:
- `pbmedia/laravel-ffmpeg` - For FFmpeg integration (optional, only if using FFmpeg encoding)
- FFmpeg binary with AV1 encoder support
- Optional: ab-av1 binary for VMAF-targeted optimization

## Next Steps

Users can:
1. Continue using ab-av1 as before (no changes needed)
2. Switch to pure FFmpeg for faster GPU encoding
3. Use hybrid approach for best quality + speed
4. Check available hardware encoders and optimize accordingly

For detailed examples, see `FFMPEG_ENCODING.md`.
