<?php

/**
 * Laravel AV1 - Usage Examples
 *
 * This file contains practical examples of using the laravel-av1 package
 * for encoding videos to AV1 format using ab-av1.
 */

use Foxws\AV1\Facades\AV1;
use Illuminate\Support\Facades\Log;

// =============================================================================
// BASIC ENCODING EXAMPLES
// =============================================================================

/**
 * Example 1: Auto-encode with VMAF targeting
 *
 * Automatically finds the best CRF value to achieve target VMAF score
 */
function autoEncodeExample()
{
    $result = AV1::open('videos/input.mp4')
        ->autoEncode()
        ->preset('6')          // Encoder preset (0-13 for svt-av1)
        ->minVmaf(95)          // Target VMAF score
        ->export()
        ->save('output.mp4');

    if ($result->isSuccessful()) {
        echo 'Encoded successfully!';
    }
}

/**
 * Example 2: CRF Search
 *
 * Find optimal CRF without encoding the full video
 */
function crfSearchExample()
{
    $result = AV1::open('videos/input.mp4')
        ->crfSearch()
        ->preset('6')
        ->minVmaf(95)
        ->minCrf(20)           // Search range
        ->maxCrf(40)
        ->export()
        ->save();

    // Output contains recommended CRF value
    echo 'Recommended CRF: '.$result->getOutput();
}

/**
 * Example 3: Direct encoding with known CRF
 */
function directEncodeExample()
{
    $result = AV1::open('input.mp4')
        ->encode()
        ->crf(30)
        ->preset('6')
        ->export()
        ->save('output.mp4');
}

/**
 * Example 4: Sample encoding for testing
 */
function sampleEncodeExample()
{
    $result = AV1::open('input.mp4')
        ->sampleEncode()
        ->crf(30)
        ->preset('6')
        ->sample(60)           // Encode 60 seconds
        ->export()
        ->save('sample.mp4');
}

// =============================================================================
// QUALITY ANALYSIS EXAMPLES
// =============================================================================

/**
 * Example 5: Calculate VMAF score
 */
function vmafExample()
{
    $result = AV1::vmaf()
        ->reference('original.mp4')
        ->distorted('encoded.mp4')
        ->export()
        ->save();

    $vmafScore = $result->getOutput();
    echo "VMAF Score: {$vmafScore}";
}

/**
 * Example 6: Calculate XPSNR score
 */
function xpsnrExample()
{
    $result = AV1::xpsnr()
        ->reference('original.mp4')
        ->distorted('encoded.mp4')
        ->export()
        ->save();

    echo 'XPSNR Score: '.$result->getOutput();
}

// =============================================================================
// CLOUD STORAGE EXAMPLES
// =============================================================================

/**
 * Example 7: Encode from S3 to S3
 */
function s3ToS3Example()
{
    $result = AV1::fromDisk('s3')
        ->open('videos/input.mp4')
        ->autoEncode()
        ->preset('6')
        ->minVmaf(95)
        ->export()
        ->toDisk('s3')
        ->toPath('encoded')
        ->save('output.mp4');
}

/**
 * Example 8: Encode from local to S3
 */
function localToS3Example()
{
    $result = AV1::open('local/video.mp4')
        ->encode()
        ->crf(30)
        ->preset('6')
        ->export()
        ->toDisk('s3')
        ->toPath('videos/encoded')
        ->withVisibility('public')
        ->save('output.mp4');
}

/**
 * Example 9: Batch processing from S3
 */
function batchProcessingExample()
{
    $videos = ['video1.mp4', 'video2.mp4', 'video3.mp4'];

    AV1::fromDisk('s3')
        ->each($videos, function ($av1, $video) {
            $av1->open("source/{$video}")
                ->autoEncode()
                ->preset('6')
                ->minVmaf(95)
                ->export()
                ->toDisk('s3')
                ->toPath('encoded')
                ->afterSaving(function ($result, $path) use ($video) {
                    Log::info("Encoded {$video} to {$path}");
                })
                ->save();
        });
}

// =============================================================================
// ADVANCED CONFIGURATION EXAMPLES
// =============================================================================

/**
 * Example 10: Custom encoder and advanced options
 */
function advancedEncodingExample()
{
    $result = AV1::open('input.mp4')
        ->autoEncode()
        ->withEncoder('svt-av1')        // or 'rav1e', 'aom'
        ->preset('6')
        ->minVmaf(95)
        ->maxEncodedPercent(90)         // Max 90% of original size
        ->pixFmt('yuv420p10le')         // 10-bit color
        ->sample(30)                     // Sample duration
        ->fullVmaf()                     // Calculate VMAF for full video
        ->verbose()                      // Verbose output
        ->export()
        ->save('output.mp4');
}

/**
 * Example 11: Using custom VMAF model
 */
function customVmafModelExample()
{
    $result = AV1::open('input.mp4')
        ->autoEncode()
        ->preset('6')
        ->minVmaf(95)
        ->vmafModel('/path/to/vmaf_model.json')
        ->export()
        ->save('output.mp4');
}

/**
 * Example 12: With callbacks and error handling
 */
function callbacksExample()
{
    try {
        $result = AV1::open('input.mp4')
            ->autoEncode()
            ->preset('6')
            ->minVmaf(95)
            ->export()
            ->afterSaving(function ($result, $path) {
                Log::info('Encoding completed', [
                    'path' => $path,
                    'output' => $result->getOutput(),
                    'exit_code' => $result->getExitCode(),
                ]);

                // Notify user, update database, etc.
            })
            ->save('output.mp4');

        if ($result->isSuccessful()) {
            echo 'Success!';
        }
    } catch (\Exception $e) {
        Log::error('Encoding failed: '.$e->getMessage());
    }
}

// =============================================================================
// DEBUGGING EXAMPLES
// =============================================================================

/**
 * Example 13: Debugging - view command without executing
 */
function debuggingExample()
{
    $command = AV1::open('input.mp4')
        ->autoEncode()
        ->preset('6')
        ->minVmaf(95)
        ->export()
        ->getCommand();

    echo "Command: {$command}";
    // Output: ab-av1 auto-encode -i input.mp4 --preset 6 --min-vmaf 95
}

/**
 * Example 14: Dump and die for debugging
 */
function dumpAndDieExample()
{
    AV1::open('input.mp4')
        ->autoEncode()
        ->preset('6')
        ->minVmaf(95)
        ->export()
        ->dd();  // Dumps command and exits
}

// =============================================================================
// QUEUE/JOB EXAMPLES
// =============================================================================

/**
 * Example 15: Laravel Queue Job
 */
class EncodeVideoJob implements ShouldQueue
{
    public function __construct(
        public string $inputPath,
        public string $outputPath,
        public int $targetVmaf = 95
    ) {}

    public function handle()
    {
        $result = AV1::fromDisk('s3')
            ->open($this->inputPath)
            ->autoEncode()
            ->preset('6')
            ->minVmaf($this->targetVmaf)
            ->export()
            ->toDisk('s3')
            ->afterSaving(function ($result, $path) {
                // Update database, send notification, etc.
            })
            ->save($this->outputPath);

        if (! $result->isSuccessful()) {
            throw new \RuntimeException(
                "Encoding failed: {$result->getErrorOutput()}"
            );
        }
    }
}

// Dispatch the job
// EncodeVideoJob::dispatch('input.mp4', 'output.mp4', 95);

// =============================================================================
// COMPARISON WORKFLOW
// =============================================================================

/**
 * Example 16: Complete encoding and quality verification workflow
 */
function completeWorkflowExample()
{
    $input = 'input.mp4';
    $output = 'output.mp4';

    // Step 1: Find optimal CRF
    $searchResult = AV1::open($input)
        ->crfSearch()
        ->preset('6')
        ->minVmaf(95)
        ->export()
        ->save();

    // Parse CRF from output (simplified)
    preg_match('/CRF (\d+)/', $searchResult->getOutput(), $matches);
    $optimalCrf = $matches[1] ?? 30;

    // Step 2: Encode with optimal CRF
    $encodeResult = AV1::open($input)
        ->encode()
        ->crf($optimalCrf)
        ->preset('6')
        ->export()
        ->save($output);

    // Step 3: Verify quality with VMAF
    $vmafResult = AV1::vmaf()
        ->reference($input)
        ->distorted($output)
        ->export()
        ->save();

    Log::info('Encoding complete', [
        'optimal_crf' => $optimalCrf,
        'vmaf_score' => $vmafResult->getOutput(),
    ]);
}
