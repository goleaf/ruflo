<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

test('tracked defaults target the secured ruflo test domain', function () {
    expect(config('app.url'))->toBe('https://ruflo.test')
        ->and(config('filesystems.disks.public.url'))->toBe('https://ruflo.test/storage')
        ->and(config('mail.mailers.smtp.local_domain'))->toBe('ruflo.test')
        ->and(File::get(base_path('.env.example')))->toContain('APP_URL=https://ruflo.test')
        ->and(File::get(config_path('app.php')))->toContain("env('APP_URL', 'https://ruflo.test')")
        ->and(File::get(config_path('filesystems.php')))->toContain("env('APP_URL', 'https://ruflo.test')")
        ->and(File::get(config_path('mail.php')))->toContain("env('APP_URL', 'https://ruflo.test')")
        ->and(File::get(base_path('phpunit.xml')))->toContain('<env name="APP_URL" value="https://ruflo.test"/>');
});

test('generated named routes use the configured https root', function () {
    expect(route('home'))->toBe('https://ruflo.test')
        ->and(route('login'))->toBe('https://ruflo.test/login')
        ->and(route('dashboard'))->toBe('https://ruflo.test/dashboard')
        ->and(route('todos.index'))->toBe('https://ruflo.test/todos')
        ->and(route('todos.today'))->toBe('https://ruflo.test/todos/today')
        ->and(route('todos.overdue'))->toBe('https://ruflo.test/todos/overdue')
        ->and(route('todos.upcoming'))->toBe('https://ruflo.test/todos/upcoming')
        ->and(route('todos.board'))->toBe('https://ruflo.test/todos/board')
        ->and(route('todos.calendar'))->toBe('https://ruflo.test/todos/calendar')
        ->and(route('todos.templates'))->toBe('https://ruflo.test/todos/templates')
        ->and(route('todos.show', 123))->toBe('https://ruflo.test/todos/123')
        ->and(route('setup.status'))->toBe('https://ruflo.test/settings/setup')
        ->and(route('maintenance.center'))->toBe('https://ruflo.test/settings/maintenance');
});

test('protected redirects resolve against the configured https root', function () {
    $this->get(route('dashboard'))
        ->assertRedirect('https://ruflo.test/login');
});

test('signed and storage urls use the configured https root', function () {
    $signedUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(10),
        ['id' => 1, 'hash' => sha1('user@example.com')],
    );

    expect($signedUrl)->toStartWith('https://ruflo.test/email/verify/1/')
        ->and(Storage::disk('public')->url('exports/tasks.csv'))->toBe('https://ruflo.test/storage/exports/tasks.csv');
});
