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
        'ab-av1' => env('AB_AV1_BINARY_PATH', 'ab-av1'),
        'ffmpeg' => env('FFMPEG_BINARY_PATH', 'ffmpeg'),
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
    |
    */
    'ab-av1' => [
        'timeout' => env('AB_AV1_TIMEOUT', 3600),
        'preset' => env('AB_AV1_PRESET', 6),
        'min_vmaf' => env('AB_AV1_MIN_VMAF', 95),
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
