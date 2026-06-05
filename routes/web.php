<?php

use App\Livewire\Todos\Index as TodosIndex;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('todos', TodosIndex::class)->name('todos.index');
});

require __DIR__.'/settings.php';
