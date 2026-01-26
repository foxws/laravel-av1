<?php

declare(strict_types=1);

use Foxws\AV1\Facades\AV1;
use Illuminate\Support\Facades\Config;

it('can access configuration values', function () {
    $tempRoot = config('av1.temporary_files_root');

    expect($tempRoot)->toBeString();
    expect($tempRoot)->toContain('av1');
});

it('respects configured binary path', function () {
    Config::set('av1.binaries.ab-av1', '/usr/local/bin/ab-av1');

    $path = config('av1.binaries.ab-av1');

    expect($path)->toBe('/usr/local/bin/ab-av1');
});

it('respects configured default preset', function () {
    Config::set('av1.ab-av1.preset', '6');

    $preset = config('av1.ab-av1.preset');

    expect($preset)->toBe('6');
});

it('respects configured timeout value', function () {
    Config::set('av1.ab-av1.timeout', 7200);

    $timeout = config('av1.ab-av1.timeout');

    expect($timeout)->toBe(7200);
});

it('uses environment variable for binary path', function () {
    // Environment variable AB_AV1_BINARY_PATH should override config
    $path = config('av1.binaries.ab-av1');

    expect($path)->toBeString();
});

it('has default configuration values', function () {
    $config = config('av1');

    expect($config)->toHaveKey('binaries');
    expect($config)->toHaveKey('ab-av1');
    expect($config)->toHaveKey('temporary_files_root');

    expect($config['binaries'])->toHaveKey('ab-av1');
    expect($config['binaries'])->toHaveKey('ffmpeg');
    expect($config['ab-av1'])->toHaveKey('timeout');
    expect($config['ab-av1'])->toHaveKey('preset');
    expect($config['ab-av1'])->toHaveKey('min_vmaf');
});

it('can create media opener with configured defaults', function () {
    $opener = AV1::encode();

    expect($opener)->not->toBeNull();
});

it('can override configured preset', function () {
    Config::set('av1.preset', '4');

    $opener = AV1::encode()
        ->input('input.mp4')
        ->output('output.mp4')
        ->preset('6');

    $options = $opener->getEncoder()->builder()->getOptions();

    expect($options['preset'])->toBe('6');
});

it('can override configured min-vmaf', function () {
    Config::set('av1.min_vmaf', 90);

    $opener = AV1::vmafEncode()
        ->minVmaf(95);

    $options = $opener->getEncoder()->builder()->getOptions();

    expect($options['min-vmaf'])->toBe(95);
});
// Auto Config Tests

describe('auto config for vmafEncode', function () {
    it('applies configured preset to vmafEncode', function () {
        Config::set('av1.ab-av1.preset', '8');

        $opener = AV1::vmafEncode();

        $options = $opener->getEncoder()->builder()->getOptions();

        expect($options['preset'])->toBe('8');
    });

    it('applies configured min_vmaf to vmafEncode', function () {
        Config::set('av1.ab-av1.min_vmaf', 85);

        $opener = AV1::vmafEncode();

        $options = $opener->getEncoder()->builder()->getOptions();

        expect($options['min-vmaf'])->toBe(85);
    });

    it('applies configured max_encoded_percent to vmafEncode', function () {
        Config::set('av1.ab-av1.max_encoded_percent', 250);

        $opener = AV1::vmafEncode();

        $options = $opener->getEncoder()->builder()->getOptions();

        expect($options['max-encoded-percent'])->toBe(250);
    });

    it('applies all auto config values to vmafEncode', function () {
        Config::set('av1.ab-av1.preset', '7');
        Config::set('av1.ab-av1.min_vmaf', 90);
        Config::set('av1.ab-av1.max_encoded_percent', 275);

        $opener = AV1::vmafEncode();

        $options = $opener->getEncoder()->builder()->getOptions();

        expect($options['preset'])->toBe('7');
        expect($options['min-vmaf'])->toBe(90);
        expect($options['max-encoded-percent'])->toBe(275);
    });

    it('allows overriding auto config values in vmafEncode', function () {
        Config::set('av1.ab-av1.preset', '6');
        Config::set('av1.ab-av1.min_vmaf', 80);
        Config::set('av1.ab-av1.max_encoded_percent', 300);

        $opener = AV1::vmafEncode()
            ->preset('9')
            ->minVmaf(95)
            ->maxEncodedPercent(200);

        $options = $opener->getEncoder()->builder()->getOptions();

        expect($options['preset'])->toBe('9');
        expect($options['min-vmaf'])->toBe(95);
        expect($options['max-encoded-percent'])->toBe(200);
    });

    it('handles missing config values gracefully in vmafEncode', function () {
        Config::set('av1.ab-av1.preset', null);
        Config::set('av1.ab-av1.min_vmaf', null);

        $opener = AV1::vmafEncode();

        $options = $opener->getEncoder()->builder()->getOptions();

        expect($options)->not->toHaveKey('preset');
        expect($options)->not->toHaveKey('min-vmaf');
    });
});

describe('auto config for crfSearch', function () {
    it('applies configured preset to crfSearch', function () {
        Config::set('av1.ab-av1.preset', '5');

        $opener = AV1::crfSearch();

        $options = $opener->getEncoder()->builder()->getOptions();

        expect($options['preset'])->toBe('5');
    });

    it('applies configured min_vmaf to crfSearch', function () {
        Config::set('av1.ab-av1.min_vmaf', 88);

        $opener = AV1::crfSearch();

        $options = $opener->getEncoder()->builder()->getOptions();

        expect($options['min-vmaf'])->toBe(88);
    });

    it('applies configured max_encoded_percent to crfSearch', function () {
        Config::set('av1.ab-av1.max_encoded_percent', 260);

        $opener = AV1::crfSearch();

        $options = $opener->getEncoder()->builder()->getOptions();

        expect($options['max-encoded-percent'])->toBe(260);
    });

    it('applies all auto config values to crfSearch', function () {
        Config::set('av1.ab-av1.preset', '4');
        Config::set('av1.ab-av1.min_vmaf', 92);
        Config::set('av1.ab-av1.max_encoded_percent', 280);

        $opener = AV1::crfSearch();

        $options = $opener->getEncoder()->builder()->getOptions();

        expect($options['preset'])->toBe('4');
        expect($options['min-vmaf'])->toBe(92);
        expect($options['max-encoded-percent'])->toBe(280);
    });

    it('allows overriding auto config values in crfSearch', function () {
        Config::set('av1.ab-av1.preset', '5');
        Config::set('av1.ab-av1.min_vmaf', 85);
        Config::set('av1.ab-av1.max_encoded_percent', 270);

        $opener = AV1::crfSearch()
            ->preset('10')
            ->minVmaf(88)
            ->maxEncodedPercent(220);

        $options = $opener->getEncoder()->builder()->getOptions();

        expect($options['preset'])->toBe('10');
        expect($options['min-vmaf'])->toBe(88);
        expect($options['max-encoded-percent'])->toBe(220);
    });

    it('handles missing config values gracefully in crfSearch', function () {
        Config::set('av1.ab-av1.preset', null);
        Config::set('av1.ab-av1.min_vmaf', null);

        $opener = AV1::crfSearch();

        $options = $opener->getEncoder()->builder()->getOptions();

        expect($options)->not->toHaveKey('preset');
        expect($options)->not->toHaveKey('min-vmaf');
    });
});

describe('auto config behavior', function () {
    it('does not apply auto config to sampleEncode command', function () {
        Config::set('av1.ab-av1.preset', '6');
        Config::set('av1.ab-av1.min_vmaf', 80);

        $opener = AV1::sampleEncode();

        $options = $opener->getEncoder()->builder()->getOptions();

        // sampleEncode should not auto-apply these configs
        expect($options)->not->toHaveKey('preset');
        expect($options)->not->toHaveKey('min-vmaf');
    });

    it('does not apply auto config to encode command', function () {
        Config::set('av1.ab-av1.preset', '6');

        $opener = AV1::encode();

        $options = $opener->getEncoder()->builder()->getOptions();

        expect($options)->not->toHaveKey('preset');
    });

    it('does not apply auto config to vmaf command', function () {
        Config::set('av1.ab-av1.preset', '6');

        $opener = AV1::vmaf();

        $options = $opener->getEncoder()->builder()->getOptions();

        expect($options)->not->toHaveKey('preset');
    });

    it('applies auto config when chaining from AV1 facade', function () {
        Config::set('av1.ab-av1.preset', '7');
        Config::set('av1.ab-av1.min_vmaf', 86);

        $opener = AV1::vmafEncode()->input('test.mp4')->output('output.mp4');

        $options = $opener->getEncoder()->builder()->getOptions();

        expect($options['preset'])->toBe('7');
        expect($options['min-vmaf'])->toBe(86);
        expect($options['input'])->toBe('test.mp4');
        expect($options['output'])->toBe('output.mp4');
    });

    it('preserves auto config through clone operation', function () {
        Config::set('av1.ab-av1.preset', '8');

        $opener1 = AV1::vmafEncode();
        $opener2 = $opener1->clone();

        $options2 = $opener2->getEncoder()->builder()->getOptions();

        expect($options2['preset'])->toBe('8');
    });
});
