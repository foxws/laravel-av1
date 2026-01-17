# Laravel AV1 Quick Test Reference

## Test Files Summary

### Root Test Files

- **tests/ExampleTest.php** - Placeholder test file (can be removed or used for custom tests)
- **tests/ArchTest.php** - Architecture validation tests for package structure
- **tests/TestCase.php** - Base test case extending Orchestra\Testbench\TestCase with service provider
- **tests/Pest.php** - Pest configuration file with ab-av1 availability helpers

### Unit Tests

**tests/Unit/ExampleTest.php** - 30+ tests covering core functionality:

- 10 CommandBuilder tests (all 6 commands)
- 5 validation tests (required parameters)
- 7 option setting tests (encoder, vmaf, verbose, crf)
- 3 encoder instance tests
- 5 filesystem/collection tests

### Feature Tests (5 files)

**1. EncodingWorkflowTest.php** - 21 tests

Command creation and workflow tests:

- All 6 ab-av1 command instantiation
- Option chaining (preset, minVmaf, crf, etc.)
- File operations (input, output, reference, distorted)
- Export and debugging
- Multi-disk support
- Advanced options (sample, pixFmt, maxEncodedPercent)

**2. MediaExporterTest.php** - 9 tests

Export and storage functionality:

- Export to different disks (local, s3)
- Path specification
- File visibility control
- Command retrieval
- Multiple export destinations

**3. ErrorHandlingTest.php** - 23 tests

Validation and error scenarios:

- Missing required parameters throw exceptions
- Special characters in paths
- Multiple option overrides
- Encoder selection (rav1e, vpx, svt-av1)
- Valid command arrays for all commands

**4. ConfigurationTest.php** - 12 tests

Configuration system validation:

- Access configuration values
- Environment variable support
- Default configuration values
- Configuration overrides
- Configuration with facade

**5. LaravelIntegrationTest.php** - 11 tests

Laravel-specific integration:

- Service container registration
- Facade functionality
- Service provider verification
- Fresh instance creation
- Static method access

## Running Tests

All tests:

```bash
vendor/bin/pest
```

Specific test file:

```bash
vendor/bin/pest tests/Feature/EncodingWorkflowTest.php
```

Specific test:

```bash
vendor/bin/pest --filter "can set encode command"
```

With code coverage:

```bash
vendor/bin/pest --coverage
```

## Test Statistics

- **Total Test Files**: 6
- **Total Tests**: ~106
- **Unit Tests**: 30+
- **Feature Tests**: 76

## Common Test Patterns

### Testing Command Builder

```php
$builder = new CommandBuilder('encode');
$builder->input('input.mp4')
    ->output('output.mp4')
    ->crf(30);

$array = $builder->buildArray();
expect($array)->toContain('ab-av1');
```

### Testing Facade

```php
$opener = AV1::encode()
    ->input('input.mp4')
    ->output('output.mp4')
    ->crf(30);

expect($opener->getEncoder()->builder()->getInput())->toBe('input.mp4');
```

### Testing Exceptions

```php
$builder = new CommandBuilder('encode');
expect(fn () => $builder->buildArray())
    ->toThrow(Exception::class, 'Input file is required');
```

### Testing Configuration

```php
Config::set('av1.encoder', 'rav1e');
$encoder = config('av1.encoder');
expect($encoder)->toBe('rav1e');
```

## Test Coverage Areas

✅ **Fully Tested**:

- Command building for all 6 ab-av1 commands
- Parameter validation and error handling
- Fluent API and method chaining
- Configuration system
- Laravel service provider and facade
- Export functionality
- Disk operations

📋 **For Future Enhancement**:

- Process execution mocking
- Real media file integration tests
- Performance benchmarks
- Edge cases (UTF-8 paths, long commands)
- Custom encoder implementations

## Writing New Tests

### Unit Tests

Place in `tests/Unit/` for testing single classes in isolation.

### Feature Tests

Place in `tests/Feature/` for testing complete workflows.

### Test Template

```php
<?php

declare(strict_types=1);

use Foxws\AV1\Facades\AV1;

it('can perform operation', function () {
    $result = AV1::encode()->input('test.mp4');

    expect($result)->not->toBeNull();
});
```

## Debugging Tests

### See test output

```bash
vendor/bin/pest tests/Feature/EncodingWorkflowTest.php -v
```

### Stop on first failure

```bash
vendor/bin/pest --stop-on-failure
```

### Run with xdebug

```bash
vendor/bin/pest tests/Feature/EncodingWorkflowTest.php --verbose
```

## CI/CD Integration

Tests run automatically on every commit via GitHub Actions:

**.github/workflows/run-tests.yml**

- PHP 8.2, 8.3, 8.4
- Laravel 11.x, 12.x
- PHPStan level 5
- Code style fixes

Push tests before committing:

```bash
vendor/bin/pest && vendor/bin/phpstan analyse src --level=5
```

## More Information

See [TESTING.md](./TESTING.md) for detailed test documentation.
