<?php

declare(strict_types=1);

use Foxws\AV1\Filesystem\Disk;
use Foxws\AV1\Filesystem\Media;
use Foxws\AV1\Filesystem\MediaCollection;
use Foxws\AV1\Support\CommandBuilder;
use Foxws\AV1\Support\Encoder;

// CommandBuilder Tests
it('can build auto-encode command', function () {
    $builder = CommandBuilder::make()
        ->command('auto-encode')
        ->input('input.mp4')
        ->preset('6')
        ->minVmaf(95);

    $args = $builder->buildArray();

    expect($args[0])->toBe('ab-av1');
    expect($args[1])->toBe('auto-encode');
    expect($args)->toContain('-i');
    expect($args)->toContain('input.mp4');
    expect($args)->toContain('--preset');
    expect($args)->toContain('6');
    expect($args)->toContain('--min-vmaf');
    expect($args)->toContain('95');
});

it('can build crf-search command', function () {
    $builder = CommandBuilder::make()
        ->command('crf-search')
        ->input('input.mp4')
        ->preset('6')
        ->minVmaf(95)
        ->minCrf(20)
        ->maxCrf(40);

    $args = $builder->buildArray();

    expect($args)->toContain('crf-search');
    expect($args)->toContain('--min-crf');
    expect($args)->toContain('20');
    expect($args)->toContain('--max-crf');
    expect($args)->toContain('40');
});

it('can build sample-encode command', function () {
    $builder = CommandBuilder::make()
        ->command('sample-encode')
        ->input('input.mp4')
        ->crf(30)
        ->preset('6')
        ->sample(60)
        ->output('sample.mp4');

    $args = $builder->buildArray();

    expect($args)->toContain('sample-encode');
    expect($args)->toContain('--crf');
    expect($args)->toContain('30');
    expect($args)->toContain('--sample');
    expect($args)->toContain('60');
    expect($args)->toContain('-o');
    expect($args)->toContain('sample.mp4');
});

it('can build encode command', function () {
    $builder = CommandBuilder::make()
        ->command('encode')
        ->input('input.mp4')
        ->crf(30)
        ->preset('6')
        ->output('output.mp4');

    $args = $builder->buildArray();

    expect($args)->toContain('encode');
    expect($args)->toContain('--crf');
    expect($args)->toContain('30');
});

it('can build vmaf command', function () {
    $builder = CommandBuilder::make()
        ->command('vmaf')
        ->reference('original.mp4')
        ->distorted('encoded.mp4');

    $args = $builder->buildArray();

    expect($args)->toContain('vmaf');
    expect($args)->toContain('--reference');
    expect($args)->toContain('original.mp4');
    expect($args)->toContain('--distorted');
    expect($args)->toContain('encoded.mp4');
});

it('can build xpsnr command', function () {
    $builder = CommandBuilder::make()
        ->command('xpsnr')
        ->reference('original.mp4')
        ->distorted('encoded.mp4');

    $args = $builder->buildArray();

    expect($args)->toContain('xpsnr');
    expect($args)->toContain('--reference');
    expect($args)->toContain('--distorted');
});

it('throws when auto-encode missing required preset', function () {
    $builder = CommandBuilder::make()
        ->command('auto-encode')
        ->input('input.mp4')
        ->minVmaf(95);

    $builder->buildArray();
})->throws(InvalidArgumentException::class, 'Preset required');

it('throws when crf-search missing required minVmaf', function () {
    $builder = CommandBuilder::make()
        ->command('crf-search')
        ->input('input.mp4')
        ->preset('6');

    $builder->buildArray();
})->throws(InvalidArgumentException::class, 'Min VMAF required');

it('throws when encode missing required crf', function () {
    $builder = CommandBuilder::make()
        ->command('encode')
        ->input('input.mp4')
        ->preset('6');

    $builder->buildArray();
})->throws(InvalidArgumentException::class, 'CRF required');

it('throws when vmaf missing reference', function () {
    $builder = CommandBuilder::make()
        ->command('vmaf')
        ->distorted('encoded.mp4');

    $builder->buildArray();
})->throws(InvalidArgumentException::class, 'Reference file required');

it('throws when vmaf missing distorted', function () {
    $builder = CommandBuilder::make()
        ->command('vmaf')
        ->reference('original.mp4');

    $builder->buildArray();
})->throws(InvalidArgumentException::class, 'Distorted file required');

it('can reset command builder', function () {
    $builder = CommandBuilder::make()
        ->command('auto-encode')
        ->input('input.mp4')
        ->preset('6')
        ->minVmaf(95)
        ->reset();

    expect($builder->getCommand())->toBeNull();
    expect($builder->getInput())->toBeNull();
    expect($builder->getOptions())->toBe([]);
});

it('can set encoder option', function () {
    $builder = CommandBuilder::make()
        ->encoder('rav1e');

    expect($builder->getOptions())->toHaveKey('encoder');
    expect($builder->getOptions()['encoder'])->toBe('rav1e');
});

it('can set full vmaf option', function () {
    $builder = CommandBuilder::make()
        ->fullVmaf();

    expect($builder->getOptions())->toHaveKey('full-vmaf');
    expect($builder->getOptions()['full-vmaf'])->toBeTrue();
});

it('can set verbose option', function () {
    $builder = CommandBuilder::make()
        ->verbose();

    expect($builder->getOptions())->toHaveKey('verbose');
    expect($builder->getOptions()['verbose'])->toBeTrue();
});

it('generates correct command string', function () {
    $builder = CommandBuilder::make()
        ->command('encode')
        ->input('input.mp4')
        ->crf(30)
        ->preset('6')
        ->output('output.mp4');

    $command = $builder->build();

    expect($command)->toContain('ab-av1');
    expect($command)->toContain('encode');
    expect($command)->toContain('input.mp4');
    expect($command)->toContain('30');
    expect($command)->toContain('output.mp4');
});

// Encoder Tests
it('can create encoder instance', function () {
    $encoder = app(Encoder::class);

    expect($encoder)->toBeInstanceOf(Encoder::class);
});

it('validates empty media collection on open', function () {
    $encoder = app(Encoder::class);
    $emptyCollection = new MediaCollection;

    $encoder->open($emptyCollection);
})->throws(InvalidArgumentException::class, 'MediaCollection cannot be empty');

it('can get fresh encoder instance', function () {
    $encoder1 = app(Encoder::class);
    $encoder2 = $encoder1->fresh();

    expect($encoder2)->toBeInstanceOf(Encoder::class);
    expect($encoder2)->not->toBe($encoder1);
});

it('can access builder from encoder', function () {
    $encoder = app(Encoder::class);

    $builder = $encoder->builder();

    expect($builder)->toBeInstanceOf(CommandBuilder::class);
});

// Filesystem Tests
it('can create media instance', function () {
    $disk = new Disk(Storage::disk('local'), 'local');
    $media = Media::make($disk, 'test.mp4');

    expect($media)->toBeInstanceOf(Media::class);
    expect($media->getPath())->toBe('test.mp4');
});

it('can create media collection', function () {
    $collection = new MediaCollection;

    expect($collection)->toBeInstanceOf(MediaCollection::class);
    expect($collection->count())->toBe(0);
});

it('can add media to collection', function () {
    $disk = new Disk(Storage::disk('local'), 'local');
    $media = Media::make($disk, 'test.mp4');
    $collection = new MediaCollection;

    $collection->push($media);

    expect($collection->count())->toBe(1);
    expect($collection->first())->toBe($media);
});

it('can find media in collection by path', function () {
    $disk = new Disk(Storage::disk('local'), 'local');
    $media1 = Media::make($disk, 'video1.mp4');
    $media2 = Media::make($disk, 'video2.mp4');
    $collection = new MediaCollection;

    $collection->push($media1);
    $collection->push($media2);

    $found = $collection->findByPath('video2.mp4');

    expect($found)->toBe($media2);
});

it('returns null when media not found in collection', function () {
    $disk = new Disk(Storage::disk('local'), 'local');
    $media = Media::make($disk, 'video1.mp4');
    $collection = new MediaCollection;

    $collection->push($media);

    $found = $collection->findByPath('nonexistent.mp4');

    expect($found)->toBeNull();
});
