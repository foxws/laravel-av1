# Simplified API (v2.0)

**Breaking Changes**: This API completely replaces the old MediaOpener-based API.

## Find Optimal CRF

```php
use Foxws\AV1\Facades\AV1;

// Find optimal CRF for target VMAF score
$crf = AV1::findCrf('input.mp4', targetVmaf: 95, preset: 6);
echo "Optimal CRF: $crf";
```

## Encode Video

### Basic encoding

```php
use Foxws\AV1\Facades\AV1;

// Simple encode
$result = AV1::encode('input.mp4', 'output.mp4');
```

### With hardware acceleration

```php
// Encode with GPU acceleration
AV1::encode('input.mp4')
    ->useHwAccel()
    ->crf(28)
    ->preset(6)
    ->encode('input.mp4', 'output.mp4');
```

### Fluent API

```php
$encoder = AV1::encoder()
    ->useHwAccel()
    ->crf(28)
    ->preset(6)
    ->pixelFormat('yuv420p10le')
    ->audioCodec('libopus')
    ->videoFilter('scale=1920:1080');

$result = $encoder->encode('input.mp4', 'output.mp4');
```

### With auto CRF

```php
// Find CRF first, then encode
$crf = AV1::findCrf('input.mp4', targetVmaf: 95);

$result = AV1::encoder()
    ->crf($crf)
    ->useHwAccel()
    ->encode('input.mp4', 'output.mp4');
```

### One-liner with auto CRF

```php
$result = AV1::encoder()
    ->crf(AV1::findCrf('input.mp4', targetVmaf: 95))
    ->useHwAccel()
    ->encode('input.mp4', 'output.mp4');
```

## Check Hardware Support

```php
use Foxws\AV1\FFmpeg\HardwareDetector;

$detector = new HardwareDetector();

// Get available encoders
$encoders = $detector->getAvailableEncoders();

// Check if hardware acceleration is available
if ($detector->hasHardwareAcceleration()) {
    echo "GPU encoding available!";
    echo "Best encoder: " . $detector->getBestHardwareEncoder();
}

// Or via encoder
$info = AV1::encoder()
    ->hardwareDetector()
    ->getEncoderInfo();
```

## Architecture

```
src/
  AbAV1/
    AbAV1Encoder.php           - ab-av1 encoder wrapper
    CrfFinder.php              - Finds optimal CRF using ab-av1
    CrfOptimizer.php           - CRF optimization utilities

  FFmpeg/
    VideoEncoder.php           - Encodes videos with FFmpeg
    FFmpegEncoder.php          - FFmpeg encoder wrapper
    FFmpegCommandBuilder.php   - Builds FFmpeg command arrays
    HardwareDetector.php       - Detects GPU encoders
    Enums/
      HardwareEncoder.php      - Hardware encoder enum
      HardwareAccelMethod.php  - Hardware accel method enum
      SoftwareEncoder.php      - Software encoder enum

  Support/
    CommandBuilder.php         - ab-av1 command builder
    Encoder.php                - Main encoder orchestrator
    EncoderResult.php          - Encoding result wrapper
    ProcessOutput.php          - Process output handler

  AV1Manager.php               - Main manager class
  MediaOpener.php              - Legacy media opener (deprecated)
  Facades/
    AV1.php                    - Main facade
```

## Migration from v1.x

**v1.x (Old - REMOVED):**

```php
$result = AV1::open('input.mp4')
    ->ffmpegAutoEncode()
    ->targetVmaf(95)
    ->useHardwareAcceleration()
    ->export()
    ->save('output.mp4');
```

**v2.0 (New):**

```php
$crf = AV1::findCrf('input.mp4', targetVmaf: 95);

$result = AV1::encoder()
    ->crf($crf)
    ->useHwAccel()
    ->encode('input.mp4', 'output.mp4');
```

**Or simpler:**

```php
AV1::encoder()
    ->crf(AV1::findCrf('input.mp4', 95))
    ->useHwAccel()
    ->encode('input.mp4', 'output.mp4');
```
