# FFmpeg Command Builder

The `FFmpegCommandBuilder` class provides a reusable, fluent interface for building FFmpeg command arrays. This builder is used internally by `VideoEncoder` but can also be used directly in console commands, jobs, or anywhere you need to construct FFmpeg commands.

## Basic Usage

```php
use Foxws\AV1\FFmpeg\FFmpegCommandBuilder;

// Build a simple command
$args = FFmpegCommandBuilder::make()
    ->withEncoder('libsvtav1')
    ->withCrf(30)
    ->withPreset(6)
    ->build('input.mp4', 'output.mp4');

// Execute with Process
Process::run($args);
```

## Available Methods

### Encoder Configuration

```php
$builder = FFmpegCommandBuilder::make('/usr/bin/ffmpeg')
    ->withEncoder('libsvtav1')          // Video encoder (libsvtav1, av1_qsv, etc.)
    ->withAudioCodec('libopus')         // Audio codec
    ->withCrf(28)                       // CRF value (quality)
    ->withPreset(6)                     // Preset (0-13)
    ->withThreads(4);                   // Number of threads (0 = auto)
```

### Hardware Acceleration

```php
$builder->withHwaccel('qsv');  // Hardware accel method (qsv, cuda, vaapi, vulkan)
```

### Video Options

```php
$builder
    ->withPixelFormat('yuv420p10le')    // Pixel format
    ->withVideoFilter('scale=1920:1080'); // Video filter
```

### Custom Arguments

```php
$builder->withCustomArgs(['-movflags', '+faststart', '-map_metadata', '-1']);
```

## Complete Example

```php
use Foxws\AV1\FFmpeg\FFmpegCommandBuilder;
use Illuminate\Support\Facades\Process;

// Build command with all options
$args = FFmpegCommandBuilder::make('/usr/local/bin/ffmpeg')
    ->withHwaccel('cuda')                      // NVIDIA hardware accel
    ->withEncoder('av1_nvenc')                 // NVIDIA encoder
    ->withAudioCodec('libopus')                // Opus audio
    ->withCrf(28)                              // Quality level
    ->withPreset(8)                            // Speed preset
    ->withThreads(0)                           // Auto threads
    ->withPixelFormat('yuv420p10le')           // 10-bit color
    ->withVideoFilter('scale=1920:1080')       // Resize
    ->withCustomArgs(['-movflags', '+faststart']) // Web optimization
    ->build('input.mp4', 'output.mp4');

// Execute
$result = Process::timeout(3600)->run($args);

if ($result->successful()) {
    echo "Encoding complete!";
}
```

## Encoder-Specific Options

The builder automatically adjusts option names based on the encoder:

### Hardware Encoders

```php
// Intel QSV
$builder->withEncoder('av1_qsv')->withCrf(30);
// Generates: -q:v 30 (not -crf)

// AMD AMF
$builder->withEncoder('av1_amf')->withPreset(6);
// Generates: -quality 6 (not -preset)

// NVIDIA NVENC
$builder->withEncoder('av1_nvenc')->withCrf(28);
// Generates: -q:v 28 (not -crf)
```

### Software Encoders

```php
// SVT-AV1 (default behavior)
$builder->withEncoder('libsvtav1')->withCrf(30)->withPreset(6);
// Generates: -crf 30 -preset 6
```

## Using in Console Commands

```php
use Foxws\AV1\FFmpeg\FFmpegCommandBuilder;
use Illuminate\Console\Command;

class EncodeVideoCommand extends Command
{
    public function handle()
    {
        $args = FFmpegCommandBuilder::make()
            ->withEncoder($this->option('encoder') ?? 'libsvtav1')
            ->withCrf((int) $this->option('crf') ?? 30)
            ->withPreset((int) $this->option('preset') ?? 6)
            ->build(
                $this->argument('input'),
                $this->argument('output')
            );

        $result = Process::timeout(7200)
            ->run($args, function ($type, $output) {
                $this->info($output);
            });

        return $result->successful() ? 0 : 1;
    }
}
```

## Testing

The builder is easy to test since it just returns an array:

```php
test('builds correct command', function () {
    $args = FFmpegCommandBuilder::make()
        ->withEncoder('libsvtav1')
        ->withCrf(30)
        ->build('in.mp4', 'out.mp4');

    expect($args)->toContain('libsvtav1');
    expect($args)->toContain('-crf');
    expect($args)->toContain('30');
});
```

## Why Use the Builder?

✅ **Reusable** - Use in VideoEncoder, console commands, jobs, tests
✅ **Type-safe** - Fluent API prevents invalid command construction
✅ **Encoder-aware** - Automatically uses correct options per encoder
✅ **Testable** - Returns array, no execution side effects
✅ **Maintainable** - FFmpeg command logic in one place

## See Also

- [VideoEncoder](../src/FFmpeg/VideoEncoder.php) - High-level encoding API that uses this builder
- [HardwareDetector](../src/FFmpeg/HardwareDetector.php) - Auto-detect encoders and hardware acceleration
