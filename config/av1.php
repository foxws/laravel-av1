<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Binary Path
    |--------------------------------------------------------------------------
    |
    | The path to the ab-av1 binary. If ab-av1 is in your PATH, you can
    | leave this as 'ab-av1'. Otherwise, specify the full path to the binary.
    |
    */
    'binary_path' => env('AB_AV1_BINARY_PATH', 'ab-av1'),

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | The maximum time (in seconds) to wait for ab-av1 commands to complete.
    | Set to null for no timeout. Default is 1 hour (3600 seconds).
    |
    */
    'timeout' => env('AB_AV1_TIMEOUT', 3600),

    /*
    |--------------------------------------------------------------------------
    | Log Channel
    |--------------------------------------------------------------------------
    |
    | The log channel to use for ab-av1 command output.
    | Set to false to disable logging, or null to use the default channel.
    |
    */
    'log_channel' => env('AB_AV1_LOG_CHANNEL', null),

    /*
    |--------------------------------------------------------------------------
    | Default Encoder
    |--------------------------------------------------------------------------
    |
    | The default encoder to use. Supported values: svt-av1, rav1e, aom
    |
    */
    'encoder' => env('AB_AV1_ENCODER', 'svt-av1'),

    /*
    |--------------------------------------------------------------------------
    | Default Preset
    |--------------------------------------------------------------------------
    |
    | The default encoder preset. Higher values are faster but produce
    | larger files. Range depends on encoder (e.g., 0-13 for svt-av1).
    |
    */
    'preset' => env('AB_AV1_PRESET', 6),

    /*
    |--------------------------------------------------------------------------
    | Default Min VMAF
    |--------------------------------------------------------------------------
    |
    | The default minimum VMAF score to target for auto-encode and crf-search.
    | Range: 0-100. Higher values mean better quality.
    |
    */
    'min_vmaf' => env('AB_AV1_MIN_VMAF', 95),

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
