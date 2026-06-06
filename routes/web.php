<?php

use App\Livewire\Goals\Index as GoalsIndex;
use App\Livewire\Habits\Index as HabitsIndex;
use App\Livewire\Projects\Show as ProjectsShow;
use App\Livewire\Todos\Board as TodosBoard;
use App\Livewire\Todos\Calendar as TodosCalendar;
use App\Livewire\Todos\Focus as TodosFocus;
use App\Livewire\Todos\Inbox as TodosInbox;
use App\Livewire\Todos\Index as TodosIndex;
use App\Livewire\Todos\Overdue as TodosOverdue;
use App\Livewire\Todos\Show as TodosShow;
use App\Livewire\Todos\Templates as TodosTemplates;
use App\Livewire\Todos\Today as TodosToday;
use App\Livewire\Todos\Upcoming as TodosUpcoming;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('goals', GoalsIndex::class)->name('goals.index');
    Route::livewire('habits', HabitsIndex::class)->name('habits.index');
    Route::livewire('todos', TodosIndex::class)->name('todos.index');
    Route::livewire('todos/today', TodosToday::class)->name('todos.today');
    Route::livewire('todos/overdue', TodosOverdue::class)->name('todos.overdue');
    Route::livewire('todos/upcoming', TodosUpcoming::class)->name('todos.upcoming');
    Route::livewire('todos/board', TodosBoard::class)->name('todos.board');
    Route::livewire('todos/calendar', TodosCalendar::class)->name('todos.calendar');
    Route::livewire('todos/templates', TodosTemplates::class)->name('todos.templates');
    Route::livewire('todos/inbox', TodosInbox::class)->name('todos.inbox');
    Route::livewire('todos/focus', TodosFocus::class)->name('todos.focus');
    Route::livewire('todos/{todo}', TodosShow::class)->whereNumber('todo')->name('todos.show');
    Route::livewire('projects/{project}', ProjectsShow::class)->whereNumber('project')->name('projects.show');
});

require __DIR__.'/settings.php';
