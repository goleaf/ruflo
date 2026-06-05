<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\MaintenanceCenter;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\Security;
use App\Livewire\Settings\SetupStatus;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', Profile::class)->name('profile.edit');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('settings/appearance', Appearance::class)->name('appearance.edit');

    Route::livewire('settings/security', Security::class)
        ->middleware([
            'password.confirm',
        ])
        ->name('security.edit');

    Route::livewire('settings/setup', SetupStatus::class)
        ->middleware([
            'password.confirm',
        ])
        ->name('setup.status');

    Route::livewire('settings/maintenance', MaintenanceCenter::class)
        ->middleware([
            'password.confirm',
            'can:access-maintenance-center',
        ])
        ->name('maintenance.center');
});
