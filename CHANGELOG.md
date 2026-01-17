# Changelog

All notable changes to `laravel-av1` will be documented in this file.

## 1.0.0 - 2026-01-17

- Initial release
- Full support for ab-av1 commands:
    - `auto-encode` - Automatic encoding with VMAF targeting
    - `crf-search` - Search for optimal CRF value
    - `sample-encode` - Encode video samples
    - `encode` - Full video encoding
    - `vmaf` - Calculate VMAF scores
    - `xpsnr` - Calculate XPSNR scores
- Fluent API for chainable method calls
- Multi-disk support (local, S3, etc.)
- Laravel 11+ support
- PHP 8.2+ support
