<?php

use App\Livewire\Todos\Index as TodosIndex;
use App\Livewire\Todos\Show as TodosShow;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('todos', TodosIndex::class)->name('todos.index');
    Route::livewire('todos/{todo}', TodosShow::class)->whereNumber('todo')->name('todos.show');
});

require __DIR__.'/settings.php';
