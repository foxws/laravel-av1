<?php

/**
 * Simplified API Examples - Breaking Changes from v1.x
 *
 * This version completely replaces the old MediaOpener-based API
 */

use Foxws\AV1\Facades\AV1;

// ============================================================================
// FIND OPTIMAL CRF
// ============================================================================

// Simple CRF finding
$crf = AV1::findCrf('videos/input.mp4');
echo "Optimal CRF: $crf\n";

// With custom parameters
$crf = AV1::findCrf(
    inputPath: 'videos/input.mp4',
    targetVmaf: 95,
    preset: 6,
    minCrf: 20,
    maxCrf: 40
);

// ============================================================================
// ENCODE VIDEO
// ============================================================================

// Simple encode (uses defaults from config)
AV1::encoder()
    ->encode('input.mp4', 'output.mp4');

// With hardware acceleration
AV1::encoder()
    ->useHwAccel()
    ->encode('input.mp4', 'output.mp4');

// Custom settings
AV1::encoder()
    ->useHwAccel()
    ->crf(28)
    ->preset(6)
    ->pixelFormat('yuv420p10le')
    ->audioCodec('libopus')
    ->encode('input.mp4', 'output.mp4');

// With video filters
AV1::encoder()
    ->useHwAccel()
    ->crf(30)
    ->videoFilter('scale=1920:1080')
    ->encode('input.mp4', 'output.mp4');

// ============================================================================
// AUTO CRF + ENCODE
// ============================================================================

// Find optimal CRF, then encode with GPU
$crf = AV1::findCrf('input.mp4', targetVmaf: 95);

AV1::encoder()
    ->crf($crf)
    ->useHwAccel()
    ->preset(6)
    ->encode('input.mp4', 'output.mp4');

// Or as one-liner
AV1::encoder()
    ->crf(AV1::findCrf('input.mp4', 95))
    ->useHwAccel()
    ->encode('input.mp4', 'output.mp4');

// ============================================================================
// HARDWARE DETECTION
// ============================================================================

use Foxws\AV1\FFmpeg\HardwareAcceleration\HardwareDetector;

$detector = new HardwareDetector();

// Check available encoders
$encoders = $detector->getAvailableEncoders();
foreach ($encoders as $name => $info) {
    echo "{$name}: {$info['name']} ({$info['type']})\n";
}

// Check if GPU available
if ($detector->hasHardwareAcceleration()) {
    echo "GPU encoding available!\n";
    echo "Best hardware encoder: " . $detector->getBestHardwareEncoder() . "\n";
}

// Get all info
$info = $detector->getEncoderInfo();
print_r($info);

// Or via encoder instance
$info = AV1::encoder()
    ->hardwareDetector()
    ->getEncoderInfo();

// ============================================================================
// ADVANCED USAGE
// ============================================================================

// Explicit encoder selection
AV1::encoder()
    ->encoder('av1_qsv')  // Force Intel QSV
    ->crf(28)
    ->encode('input.mp4', 'output.mp4');

// Custom FFmpeg arguments
AV1::encoder()
    ->useHwAccel()
    ->crf(28)
    ->withArgs(['-movflags', '+faststart'])
    ->encode('input.mp4', 'output.mp4');

// Chaining everything
$result = AV1::encoder()
    ->crf(AV1::findCrf('input.mp4', targetVmaf: 95, preset: 6))
    ->preset(6)
    ->useHwAccel()
    ->pixelFormat('yuv420p10le')
    ->audioCodec('libopus')
    ->videoFilter('scale=-2:1080')
    ->withArgs(['-movflags', '+faststart'])
    ->encode('input.mp4', 'output.mp4');

if ($result->successful()) {
    echo "Encoding completed successfully!\n";
} else {
    echo "Encoding failed: " . $result->errorOutput() . "\n";
}
