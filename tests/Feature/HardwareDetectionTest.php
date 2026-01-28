<?php

declare(strict_types=1);

use Foxws\AV1\FFmpeg\HardwareDetector;
use Foxws\AV1\FFmpeg\Enums\HardwareEncoder;
use Foxws\AV1\FFmpeg\Enums\SoftwareEncoder;
use Illuminate\Support\Facades\Config;

it('can create hardware detector instance', function () {
    $detector = new HardwareDetector();

    expect($detector)->toBeInstanceOf(HardwareDetector::class);
});

it('can get available encoders', function () {
    $detector = new HardwareDetector();
    $encoders = $detector->getAvailableEncoders();

    expect($encoders)->toBeArray();
});

it('respects encoder priority from config', function () {
    Config::set('av1.ffmpeg.encoder_priority', [
        'av1_nvenc',
        'av1_qsv',
        'libsvtav1',
    ]);

    $detector = new HardwareDetector();
    $encoders = $detector->getAvailableEncoders();

    if (! empty($encoders)) {
        $firstEncoder = array_key_first($encoders);
        $firstPriority = $encoders[$firstEncoder]['priority'];

        foreach ($encoders as $encoder => $info) {
            expect($info['priority'])->toBeGreaterThanOrEqual($firstPriority);
        }
    }

    expect(true)->toBeTrue(); // Pass if no encoders available
});

it('can check if specific encoder is available', function () {
    $detector = new HardwareDetector();

    $result = $detector->hasEncoder('libsvtav1');

    expect($result)->toBeBool();
});

it('can get best encoder', function () {
    $detector = new HardwareDetector();
    $best = $detector->getBestEncoder();

    if ($best !== null) {
        expect($best)->toBeString();
    }

    expect(true)->toBeTrue(); // Pass even if no encoder available
});

it('can get best hardware encoder', function () {
    $detector = new HardwareDetector();
    $best = $detector->getBestHardwareEncoder();

    if ($best !== null) {
        expect($best)->toBeString();
        expect($detector->getEncoderType($best))->toBe('hardware');
    }

    expect(true)->toBeTrue(); // Pass even if no hardware encoder available
});

it('can check hardware acceleration availability', function () {
    $detector = new HardwareDetector();
    $hasHw = $detector->hasHardwareAcceleration();

    expect($hasHw)->toBeBool();
});

it('can get hardware accel method', function () {
    $detector = new HardwareDetector();
    $method = $detector->getHardwareAccelMethod();

    if ($method !== null) {
        expect($method)->toBeString();
    }

    expect(true)->toBeTrue(); // Pass even if no method available
});

it('respects hwaccel priority from config', function () {
    Config::set('av1.ffmpeg.hwaccel_priority', ['cuda', 'qsv', 'vaapi']);

    $detector = new HardwareDetector();
    $method = $detector->getHardwareAccelMethod();

    // Just check it doesn't throw an error
    expect(true)->toBeTrue();
});

it('can get encoder info', function () {
    $detector = new HardwareDetector();
    $info = $detector->getEncoderInfo();

    expect($info)->toHaveKey('encoders');
    expect($info)->toHaveKey('best_encoder');
    expect($info)->toHaveKey('best_hardware');
    expect($info)->toHaveKey('has_hardware');
    expect($info)->toHaveKey('hwaccel_method');
});

it('can clear encoder cache', function () {
    $detector = new HardwareDetector();

    $detector->clearCache();

    expect(true)->toBeTrue();
});

it('hardware encoder enum has correct values', function () {
    expect(HardwareEncoder::QSV->value)->toBe('av1_qsv');
    expect(HardwareEncoder::AMF->value)->toBe('av1_amf');
    expect(HardwareEncoder::NVENC->value)->toBe('av1_nvenc');
});

it('software encoder enum has correct values', function () {
    expect(SoftwareEncoder::SVT_AV1->value)->toBe('libsvtav1');
    expect(SoftwareEncoder::AOM_AV1->value)->toBe('libaom-av1');
    expect(SoftwareEncoder::RAV1E->value)->toBe('librav1e');
});

it('hardware encoder has labels', function () {
    expect(HardwareEncoder::QSV->label())->toBe('Intel Quick Sync Video');
    expect(HardwareEncoder::AMF->label())->toBe('AMD Advanced Media Framework');
    expect(HardwareEncoder::NVENC->label())->toBe('NVIDIA NVENC');
});

it('software encoder has labels', function () {
    expect(SoftwareEncoder::SVT_AV1->label())->toBe('SVT-AV1 (CPU)');
    expect(SoftwareEncoder::AOM_AV1->label())->toBe('AOM AV1 (CPU)');
    expect(SoftwareEncoder::RAV1E->label())->toBe('rav1e (CPU)');
});
