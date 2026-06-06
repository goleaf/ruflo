<?php

use App\Livewire\Goals\Create as GoalsCreate;
use App\Livewire\Goals\CreateMilestone as GoalsCreateMilestone;
use App\Livewire\Goals\Index as GoalsIndex;
use App\Livewire\Habits\Create as HabitsCreate;
use App\Livewire\Habits\Index as HabitsIndex;
use App\Livewire\Notifications\Inbox as NotificationsInbox;
use App\Livewire\Projects\Show as ProjectsShow;
use App\Livewire\Todos\Automations as TodosAutomations;
use App\Livewire\Todos\Blocked as TodosBlocked;
use App\Livewire\Todos\Board as TodosBoard;
use App\Livewire\Todos\Calendar as TodosCalendar;
use App\Livewire\Todos\Cleanup as TodosCleanup;
use App\Livewire\Todos\Focus as TodosFocus;
use App\Livewire\Todos\Inbox as TodosInbox;
use App\Livewire\Todos\Index as TodosIndex;
use App\Livewire\Todos\Overdue as TodosOverdue;
use App\Livewire\Todos\Reminders as TodosReminders;
use App\Livewire\Todos\Show as TodosShow;
use App\Livewire\Todos\Templates as TodosTemplates;
use App\Livewire\Todos\Time as TodosTime;
use App\Livewire\Todos\Today as TodosToday;
use App\Livewire\Todos\Upcoming as TodosUpcoming;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('goals/create', GoalsCreate::class)->name('goals.create');
    Route::livewire('goals/milestones/create', GoalsCreateMilestone::class)->name('goals.milestones.create');
    Route::livewire('goals', GoalsIndex::class)->name('goals.index');
    Route::livewire('habits/create', HabitsCreate::class)->name('habits.create');
    Route::livewire('habits', HabitsIndex::class)->name('habits.index');
    Route::livewire('notifications', NotificationsInbox::class)->name('notifications.inbox');
    Route::livewire('todos', TodosIndex::class)->name('todos.index');
    Route::livewire('todos/today', TodosToday::class)->name('todos.today');
    Route::livewire('todos/overdue', TodosOverdue::class)->name('todos.overdue');
    Route::livewire('todos/upcoming', TodosUpcoming::class)->name('todos.upcoming');
    Route::livewire('todos/blocked', TodosBlocked::class)->name('todos.blocked');
    Route::livewire('todos/cleanup', TodosCleanup::class)->name('todos.cleanup');
    Route::livewire('todos/automations', TodosAutomations::class)->name('todos.automations');
    Route::livewire('todos/reminders', TodosReminders::class)->name('todos.reminders');
    Route::livewire('todos/board', TodosBoard::class)->name('todos.board');
    Route::livewire('todos/calendar', TodosCalendar::class)->name('todos.calendar');
    Route::livewire('todos/templates', TodosTemplates::class)->name('todos.templates');
    Route::livewire('todos/inbox', TodosInbox::class)->name('todos.inbox');
    Route::livewire('todos/focus', TodosFocus::class)->name('todos.focus');
    Route::livewire('todos/time', TodosTime::class)->name('todos.time');
    Route::livewire('todos/{todo}', TodosShow::class)->whereNumber('todo')->name('todos.show');
    Route::livewire('projects/{project}', ProjectsShow::class)->whereNumber('project')->name('projects.show');
});

require __DIR__.'/settings.php';
