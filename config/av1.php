<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Binaries
    |--------------------------------------------------------------------------
    |
    | Paths to encoder binaries and their dependencies.
    |
    | ab-av1: Path to ab-av1 binary (can be string or array, first is used)
    |         Requires ffmpeg with libsvtav1, libvmaf, libopus enabled
    | ffmpeg: Path to ffmpeg binary with libsvtav1, libvmaf, libopus enabled
    |
    */
    'binaries' => [
        'ab-av1' => env('AB_AV1_BINARY_PATH', '/usr/local/bin/ab-av1'),
        'ffmpeg' => env('FFMPEG_BINARY_PATH', '/usr/local/bin/ffmpeg'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channel
    |--------------------------------------------------------------------------
    |
    | The log channel to use for encoding command output.
    | Set to false to disable logging, or null to use the default channel.
    |
    */
    'log_channel' => env('AB_AV1_LOG_CHANNEL', null),

    /*
    |--------------------------------------------------------------------------
    | AbAV1 Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the ab-av1 encoder tool.
    |
    | timeout: Maximum time in seconds to wait for commands (null for no timeout)
    | preset: Default encoder preset (0-13 for svt-av1, higher = faster/larger)
    | min_vmaf: Minimum VMAF score to target (0-100, higher = better quality)
    | max_encoded_percent: Maximum size of encoded file as percentage of source
    |
    */
    'ab-av1' => [
        'timeout' => env('AB_AV1_TIMEOUT', 14400), // 4 hours
        'preset' => env('AB_AV1_PRESET', 6),
        'min_vmaf' => env('AB_AV1_MIN_VMAF', 80),
        'max_encoded_percent' => env('AB_AV1_MAX_PERCENT', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | FFmpeg Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for direct FFmpeg AV1 encoding.
    |
    | timeout: Maximum time in seconds to wait for encoding (null for no timeout)
    | threads: Number of threads to use (0 = auto-detect CPU cores)
    | encoder: Default encoder (auto-detect if null)
    |   - Hardware: av1_qsv (Intel), av1_amf (AMD), av1_nvenc (NVIDIA)
    |   - Software: libsvtav1, libaom-av1, librav1e
    | hardware_acceleration: Enable hardware acceleration for decoding/encoding
    | hwaccel_priority: Priority order for hardware acceleration methods
    |   - Available: qsv, cuda, vaapi, vulkan (lower index = higher priority)
    | encoder_priority: Priority order for encoder selection
    |   - Mix of hardware and software encoders (lower index = higher priority)
    | default_crf: Default CRF value (quality, lower = better, 23-35 recommended)
    | default_preset: Default preset (0-13 for svt-av1, varies by encoder)
    | audio_codec: Default audio codec (libopus recommended for AV1)
    | pixel_format: Default pixel format (yuv420p or yuv420p10le for 10-bit)
    | auto_crf: Use ab-av1 to automatically find best CRF before encoding
    |
    */
    'ffmpeg' => [
        'timeout' => env('FFMPEG_TIMEOUT', 7200), // 2 hours
        'threads' => env('FFMPEG_THREADS', 0), // 0 = auto-detect CPU cores
        'encoder' => env('FFMPEG_ENCODER', null), // null = auto-detect
        'hardware_acceleration' => env('FFMPEG_HARDWARE_ACCEL', true),
        'hwaccel_priority' => ['qsv', 'cuda', 'vaapi', 'vulkan'], // Priority order for hardware acceleration methods
        'hwaccel_device' => env('FFMPEG_HWACCEL_DEVICE', null), // Hardware device path (e.g., /dev/dri/renderD128 for VAAPI, null = auto-detect)
        'encoder_priority' => [
            // Hardware encoders (lower index = higher priority)
            'av1_qsv',      // Intel Quick Sync Video
            'av1_nvenc',    // NVIDIA NVENC
            'av1_amf',      // AMD AMF
            // Software encoders
            'libsvtav1',    // SVT-AV1 (fastest, best quality/speed balance)
            'libaom-av1',   // AOM AV1 (reference implementation)
            'librav1e',     // rav1e (Rust implementation)
        ],
        'default_crf' => env('FFMPEG_DEFAULT_CRF', 30),
        'default_preset' => env('FFMPEG_DEFAULT_PRESET', 6),
        'audio_codec' => env('FFMPEG_AUDIO_CODEC', 'libopus'),
        'pixel_format' => env('FFMPEG_PIXEL_FORMAT', 'yuv420p'),
        'auto_crf' => env('FFMPEG_AUTO_CRF', false), // Use ab-av1 for CRF detection
    ],

    /*
    |--------------------------------------------------------------------------
    | Temporary Files Root
    |--------------------------------------------------------------------------
    |
    | Root directory for temporary files used during encoding.
    | By default, this is stored in storage/app/av1/temp.
    |
    */
    'temporary_files_root' => env('AB_AV1_TEMPORARY_FILES_ROOT', storage_path('app/av1/temp')),

];
