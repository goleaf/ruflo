<?php

use Illuminate\Support\Facades\File;

it('registers the Tailwind and SCSS asset entries', function () {
    expect(File::exists(resource_path('css/app.css')))->toBeTrue()
        ->and(File::exists(resource_path('scss/app.scss')))->toBeTrue()
        ->and(File::exists(resource_path('scss/_tokens.scss')))->toBeTrue()
        ->and(File::exists(resource_path('scss/_accessibility.scss')))->toBeTrue()
        ->and(File::exists(resource_path('scss/_surfaces.scss')))->toBeTrue()
        ->and(File::exists(resource_path('scss/_print.scss')))->toBeTrue();

    $viteConfig = File::get(base_path('vite.config.js'));
    $sharedHead = File::get(resource_path('views/partials/head.blade.php'));
    $welcome = File::get(resource_path('views/welcome.blade.php'));
    $package = json_decode(File::get(base_path('package.json')), true, flags: JSON_THROW_ON_ERROR);

    expect($viteConfig)->toContain('resources/css/app.css')
        ->and($viteConfig)->toContain('resources/scss/app.scss')
        ->and($sharedHead)->toContain('resources/scss/app.scss')
        ->and($welcome)->toContain('resources/scss/app.scss')
        ->and($package['devDependencies'])->toHaveKey('sass-embedded');
});

it('keeps Tailwind CSS 4 configured through the CSS entry', function () {
    $css = File::get(resource_path('css/app.css'));

    expect($css)->toContain("@import 'tailwindcss';")
        ->and($css)->toContain('@theme')
        ->and($css)->toContain('@custom-variant dark')
        ->and($css)->not->toContain('@tailwind base')
        ->and($css)->not->toContain('@tailwind components')
        ->and($css)->not->toContain('@tailwind utilities');
});
