# Changelog

All notable changes to `laravel-av1` will be documented in this file.

## 0.2.0 - 2026-01-26

### Added

- Auto-configuration for `vmafEncode()` and `crfSearch()` methods
- Automatic application of default `preset`, `min_vmaf`, and `max_encoded_percent` from config
- Comprehensive test coverage for auto-config feature

### Changed

- Simplified configuration singleton to use main `laravel-av1-configuration`
- Updated commands to use nested config keys

**Full Changelog**: https://github.com/foxws/laravel-av1/compare/0.1.0...0.2.0

## 0.1.0 - 2026-01-23

### What's Changed

* Bump actions/checkout from 4 to 6 by @dependabot[bot] in https://github.com/foxws/laravel-av1/pull/1

### New Contributors

* @dependabot[bot] made their first contribution in https://github.com/foxws/laravel-av1/pull/1

**Full Changelog**: https://github.com/foxws/laravel-av1/commits/0.1.0

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
