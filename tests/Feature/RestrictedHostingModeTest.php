<?php

use App\Data\Hosting\WebProcessingProfile;
use Illuminate\Support\Facades\File;

it('uses restricted hosting defaults for normal runtime', function () {
    expect(config('hosting.restricted'))->toBeTrue()
        ->and(config('queue.default'))->toBe('sync');

    $profile = WebProcessingProfile::fromConfig();

    expect($profile->restricted)->toBeTrue()
        ->and($profile->chunkSize)->toBe(25)
        ->and($profile->maxRuntimeSeconds)->toBe(8)
        ->and($profile->retryCooldownSeconds)->toBe(30)
        ->and($profile->resumeAfterFailure)->toBeTrue()
        ->and($profile->detailLimit)->toBe(10)
        ->and($profile->shouldChunk())->toBeTrue()
        ->and($profile->boundedChunkSize())->toBe(25)
        ->and($profile->boundedMaxRuntimeSeconds())->toBe(8)
        ->and($profile->boundedDetailLimit())->toBe(10)
        ->and($profile->forbiddenRuntimeDependencies)->toContain('cron', 'queue-worker', 'supervisor', 'shell', 'artisan');
});

it('does not define terminal-only application workflows', function () {
    expect(File::isDirectory(app_path('Jobs')))->toBeFalse()
        ->and(File::get(base_path('routes/console.php')))->not->toContain('Artisan::command');
});

it('keeps local development scripts compatible with Herd and web-only runtime', function () {
    $composer = json_decode(File::get(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);
    $devScript = implode(' ', $composer['scripts']['dev']);

    expect($devScript)->toContain('npm run dev')
        ->and($devScript)->not->toContain('artisan serve')
        ->and($devScript)->not->toContain('queue:listen')
        ->and($devScript)->not->toContain('pail');
});

it('documents the restricted hosting contract', function () {
    $documentation = File::get(base_path('docs/restricted-hosting.md'));

    expect($documentation)->toContain('QUEUE_CONNECTION=sync')
        ->and($documentation)->toContain('process bounded chunks')
        ->and($documentation)->toContain('cannot promise exact-time automation');
});
