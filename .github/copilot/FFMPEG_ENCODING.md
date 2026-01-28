# FFmpeg AV1 Encoding Examples

This file contains examples of using FFmpeg for AV1 encoding with hardware acceleration support.

## Basic FFmpeg Encoding

### Simple FFmpeg encode with default settings

```php
use Foxws\AV1\Facades\AV1;

$result = AV1::open('videos/input.mp4')
    ->ffmpegEncode()
    ->export()
    ->save('output.mp4');
```

### FFmpeg encode with custom CRF and preset

```php
$result = AV1::open('videos/input.mp4')
    ->ffmpegEncode()
    ->crf(28)              // Quality: lower = better quality (23-35 recommended)
    ->preset('6')          // Speed: 0-13 for SVT-AV1 (higher = faster)
    ->export()
    ->save('output.mp4');
```

## Hardware Acceleration

### Auto-detect and use best available hardware encoder

```php
$result = AV1::open('videos/input.mp4')
    ->ffmpegEncode()
    ->useHardwareAcceleration()  // Enables GPU encoding
    ->crf(28)
    ->export()
    ->save('output.mp4');
```

### Intel Quick Sync Video (QSV)

```php
use Foxws\AV1\Facades\AV1;

$result = AV1::open('videos/input.mp4')
    ->ffmpeg()
    ->useHardwareAcceleration()
    ->crf(28)
    ->preset('6')
    ->export()
    ->save('output.mp4');

// The encoder will automatically use av1_qsv if available
```

### AMD AMF

```php
// AMD hardware encoding is automatically detected
$result = AV1::open('videos/input.mp4')
    ->ffmpeg()
    ->useHardwareAcceleration()
    ->crf(28)
    ->export()
    ->save('output.mp4');

// The encoder will automatically use av1_amf if available
```

### NVIDIA NVENC

```php
// NVIDIA hardware encoding is automatically detected
$result = AV1::open('videos/input.mp4')
    ->ffmpeg()
    ->useHardwareAcceleration()
    ->crf(28)
    ->export()
    ->save('output.mp4');

// The encoder will automatically use av1_nvenc if available
```

### Check available encoders

```php
use Foxws\AV1\FFmpeg\HardwareDetector;

$detector = new HardwareDetector();

// Get all available encoders
$encoders = $detector->getAvailableEncoders();
/*
[
    'av1_qsv' => [
        'name' => 'Intel Quick Sync Video',
        'type' => 'hardware',
        'priority' => 0
    ],
    'libsvtav1' => [
        'name' => 'SVT-AV1 (CPU)',
        'type' => 'software',
        'priority' => 100
    ]
]
*/

// Get best available encoder
$best = $detector->getBestEncoder();  // 'av1_qsv'

// Check if hardware acceleration is available
$hasHardware = $detector->hasHardwareAcceleration();  // true

// Get hardware acceleration method for decoding
$hwaccel = $detector->getHardwareAccelMethod();  // 'qsv'
```

## Automatic CRF Optimization

The package can use `ab-av1` to find the optimal CRF value for your target quality, then encode with FFmpeg using that CRF.

### Auto CRF with target VMAF

```php
$result = AV1::open('videos/input.mp4')
    ->ffmpegAutoEncode()           // Enable auto CRF optimization
    ->targetVmaf(95)               // Target VMAF score
    ->preset('6')
    ->useHardwareAcceleration()
    ->export()
    ->save('output.mp4');

// This will:
// 1. Use ab-av1 to find optimal CRF for VMAF 95
// 2. Encode with FFmpeg using that CRF with hardware acceleration
```

### Manual auto CRF control

```php
$result = AV1::open('videos/input.mp4')
    ->ffmpegEncode()
    ->withAutoCrf()                // Enable auto CRF
    ->targetVmaf(95)
    ->preset('6')
    ->export()
    ->save('output.mp4');
```

### Skip auto CRF (use fixed CRF)

```php
$result = AV1::open('videos/input.mp4')
    ->ffmpegEncode()
    ->crf(28)                      // Fixed CRF (auto CRF disabled)
    ->preset('6')
    ->export()
    ->save('output.mp4');
```

## Advanced Encoding Options

### 10-bit encoding

```php
$result = AV1::open('videos/input.mp4')
    ->ffmpegEncode()
    ->pixFmt('yuv420p10le')        // 10-bit color
    ->crf(28)
    ->preset('6')
    ->export()
    ->save('output.mp4');
```

### Custom audio codec

```php
$result = AV1::open('videos/input.mp4')
    ->ffmpegEncode()
    ->audioCodec('libopus')        // Opus audio (default for AV1)
    ->crf(28)
    ->export()
    ->save('output.mp4');
```

### Video filters

```php
$result = AV1::open('videos/input.mp4')
    ->ffmpegEncode()
    ->videoFilter('scale=1920:1080')  // Scale to 1080p
    ->crf(28)
    ->export()
    ->save('output.mp4');
```

### Custom FFmpeg arguments

```php
$result = AV1::open('videos/input.mp4')
    ->ffmpegEncode()
    ->customArgs(['-movflags', '+faststart'])
    ->crf(28)
    ->export()
    ->save('output.mp4');
```

## Cloud Storage with Hardware Encoding

### Encode from S3 with hardware acceleration

```php
$result = AV1::fromDisk('s3')
    ->open('videos/input.mp4')
    ->ffmpegEncode()
    ->useHardwareAcceleration()
    ->crf(28)
    ->preset('6')
    ->export()
    ->toDisk('s3')
    ->save('encoded/output.mp4');
```

### Batch processing with hardware encoding

```php
$videos = ['video1.mp4', 'video2.mp4', 'video3.mp4'];

AV1::fromDisk('s3')
    ->each($videos, function ($av1, $video) {
        $av1->open("source/{$video}")
            ->ffmpegAutoEncode()           // Auto CRF optimization
            ->targetVmaf(95)
            ->preset('6')
            ->useHardwareAcceleration()    // GPU encoding
            ->export()
            ->toDisk('s3')
            ->toPath('encoded')
            ->save();
    });
```

## Comparison: ab-av1 vs FFmpeg

### ab-av1 with VMAF-targeted encoding

```php
// Uses ab-av1's built-in VMAF optimization and encoding
$result = AV1::open('input.mp4')
    ->abav1()                      // Use ab-av1 encoder
    ->vmafEncode()
    ->preset('6')
    ->minVmaf(95)
    ->export()
    ->save('output.mp4');

// Pros: Integrated VMAF optimization, simple
// Cons: CPU-only encoding, slower
```

### FFmpeg with auto CRF (best of both worlds)

```php
// Uses ab-av1 for CRF search, FFmpeg for encoding
$result = AV1::open('input.mp4')
    ->ffmpegAutoEncode()           // Use FFmpeg with auto CRF
    ->targetVmaf(95)
    ->preset('6')
    ->useHardwareAcceleration()    // GPU acceleration
    ->export()
    ->save('output.mp4');

// Pros: VMAF optimization + GPU acceleration, faster
// Cons: Requires both ab-av1 and FFmpeg
```

### Pure FFmpeg with fixed CRF

```php
// Direct FFmpeg encoding with known CRF
$result = AV1::open('input.mp4')
    ->ffmpegEncode()
    ->crf(28)
    ->preset('6')
    ->useHardwareAcceleration()
    ->export()
    ->save('output.mp4');

// Pros: Fastest, simple
// Cons: No VMAF optimization
```

## Configuration

Update your `config/av1.php`:

```php
'ffmpeg' => [
    'timeout' => env('FFMPEG_TIMEOUT', 7200),
    'encoder' => env('FFMPEG_ENCODER', null), // null = auto-detect
    'hardware_acceleration' => env('FFMPEG_HARDWARE_ACCEL', true),
    'default_crf' => env('FFMPEG_DEFAULT_CRF', 30),
    'default_preset' => env('FFMPEG_DEFAULT_PRESET', 6),
    'audio_codec' => env('FFMPEG_AUDIO_CODEC', 'libopus'),
    'pixel_format' => env('FFMPEG_PIXEL_FORMAT', 'yuv420p'),
    'auto_crf' => env('FFMPEG_AUTO_CRF', false),
],
```

Environment variables:

```bash
# FFmpeg settings
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
# FFMPEG_ENCODER=libsvtav1    # CPU (default)
```

## Performance Tips

1. **Hardware Acceleration**: Always enable when available for 3-10x faster encoding
2. **Preset**: Use 6-8 for good balance of speed/quality
3. **CRF**:
    - 23-28: High quality
    - 28-32: Balanced
    - 32-35: Lower quality, smaller file
4. **Auto CRF**: Use for consistent quality across different videos
5. **Batch Processing**: Process multiple videos in parallel using Laravel queues

## Laravel Queue Example

```php
use Foxws\AV1\Facades\AV1;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class EncodeVideoJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public function __construct(
        public string $inputPath,
        public string $outputPath
    ) {}

    public function handle()
    {
        AV1::fromDisk('s3')
            ->open($this->inputPath)
            ->ffmpegAutoEncode()
            ->targetVmaf(95)
            ->preset('6')
            ->useHardwareAcceleration()
            ->export()
            ->toDisk('s3')
            ->save($this->outputPath);
    }
}
```
