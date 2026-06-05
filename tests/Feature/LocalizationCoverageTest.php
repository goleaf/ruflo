<?php

use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;

test('source files do not use literal english copy in translation APIs', function () {
    $patterns = [
        '/__\(\s*[\'"](?!(?:[a-z0-9_]+\.)+[a-z0-9_]+|[a-z0-9_]+::)[^\'"]*[A-Z][^\'"]*[\'"]\s*(?:,|\))/',
        '/@lang\(\s*[\'"](?!(?:[a-z0-9_]+\.)+[a-z0-9_]+|[a-z0-9_]+::)[^\'"]*[A-Z][^\'"]*[\'"]\s*(?:,|\))/',
        '/Flux::toast\([^;\n]*[\'"][A-Z][^\'"]*[\'"]/',
        '/addError\([^;\n]*[\'"][A-Z][^\'"]*[\'"]/',
        '/#\[Title\([\'"](?!(?:[a-z0-9_]+\.)+[a-z0-9_]+|[a-z0-9_]+::)[^\'"]*[A-Z][^\'"]*[\'"]\)\]/',
    ];

    $violations = [];

    foreach (localizationSourceFiles() as $path) {
        foreach (file($path) ?: [] as $lineNumber => $line) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $line) === 1) {
                    $violations[] = sprintf('%s:%d: %s', relativePath($path), $lineNumber + 1, trim($line));
                }
            }
        }
    }

    expect($violations)->toBeEmpty();
});

test('public and authenticated landing pages render localized copy', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee(__('welcome.heading'))
        ->assertSee(__('welcome.paths.cli.heading'))
        ->assertDontSee('welcome.');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('dashboard.heading'))
        ->assertSee(__('dashboard.install.heading'))
        ->assertSee(__('dashboard.workspace.heading'))
        ->assertDontSee('dashboard.heading')
        ->assertDontSee('dashboard.install.heading')
        ->assertDontSee('dashboard.workspace.heading');
});

test('static application translation keys referenced by source files exist', function () {
    $patterns = [
        '/__\(\s*[\'"]((?:[a-z0-9_]+\.)+[a-z0-9_]+)[\'"]/',
        '/@lang\(\s*[\'"]((?:[a-z0-9_]+\.)+[a-z0-9_]+)[\'"]/',
        '/#\[Title\([\'"]((?:[a-z0-9_]+\.)+[a-z0-9_]+)[\'"]\)\]/',
    ];

    $missing = [];

    foreach (localizationSourceFiles() as $path) {
        $source = file_get_contents($path) ?: '';

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $source, $matches) === false) {
                continue;
            }

            foreach ($matches[1] as $key) {
                if (! Lang::has($key)) {
                    $missing[] = sprintf('%s: %s', relativePath($path), $key);
                }
            }
        }
    }

    sort($missing);

    expect($missing)->toBeEmpty();
});

/**
 * @return list<string>
 */
function localizationSourceFiles(): array
{
    $roots = [
        app_path(),
        resource_path('views'),
    ];

    $paths = [];

    foreach ($roots as $root) {
        foreach (File::allFiles($root) as $file) {
            if (in_array($file->getExtension(), ['php'], true) || str_ends_with($file->getFilename(), '.blade.php')) {
                $paths[] = $file->getPathname();
            }
        }
    }

    sort($paths);

    return $paths;
}

function relativePath(string $path): string
{
    return str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
}
