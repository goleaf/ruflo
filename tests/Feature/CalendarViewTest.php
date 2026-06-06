<?php

use App\Enums\Priority;
use App\Livewire\Todos\Calendar;
use App\Models\Project;
use App\Models\Todo;
use App\Models\User;
use App\Rules\Todos\CalendarMonth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;

function calendarRouteMiddleware(string $routeName): array
{
    return Route::getRoutes()->getByName($routeName)?->gatherMiddleware() ?? [];
}

test('calendar route redirects guests and unverified users', function () {
    $this->get(route('todos.calendar'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('todos.calendar'))
        ->assertRedirect(route('verification.notice'));
});

test('calendar month renders current user active due tasks and no due date tasks only', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-10 09:30:00', config('app.timezone')));

    try {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $project = Project::factory()->for($user)->create(['name' => 'Launch']);

        $monthTask = Todo::factory()
            ->forProject($project)
            ->priority(Priority::Urgent)
            ->dueOn('2026-03-10')
            ->create(['title' => 'Calendar owner task']);

        Todo::factory()->for($user)->dueOn('2026-03-15')->create(['title' => 'Later March task']);
        Todo::factory()->for($user)->withoutDueDate()->create(['title' => 'No date active']);
        Todo::factory()->for($user)->dueOn('2026-02-28')->create(['title' => 'Previous month task']);
        Todo::factory()->for($user)->dueOn('2026-04-01')->create(['title' => 'Next month task']);
        Todo::factory()->for($user)->dueOn('2026-03-11')->completed()->create(['title' => 'Completed calendar task']);
        Todo::factory()->for($user)->dueOn('2026-03-11')->archived()->create(['title' => 'Archived calendar task']);
        Todo::factory()->for($user)->dueOn('2026-03-11')->deleted()->create(['title' => 'Deleted calendar task']);
        Todo::factory()->for($other)->dueOn('2026-03-10')->create(['title' => 'Foreign calendar task']);

        $this->actingAs($user)
            ->get(route('todos.calendar', ['month' => '2026-03']))
            ->assertOk()
            ->assertSee(__('todos.pages.calendar.title'))
            ->assertSee('March 2026')
            ->assertSee('Calendar owner task')
            ->assertSee('Later March task')
            ->assertSee('No date active')
            ->assertSee('Launch')
            ->assertSee(Priority::Urgent->label())
            ->assertSee(route('todos.show', $monthTask), false)
            ->assertSee(__('todos.calendar.reminders.heading'))
            ->assertSee(__('todos.calendar.recurrence.heading'))
            ->assertDontSee('Previous month task')
            ->assertDontSee('Next month task')
            ->assertDontSee('Completed calendar task')
            ->assertDontSee('Archived calendar task')
            ->assertDontSee('Deleted calendar task')
            ->assertDontSee('Foreign calendar task');
    } finally {
        Carbon::setTestNow();
    }
});

test('calendar rejects invalid month input and falls back safely for bad query strings', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-05 09:30:00', config('app.timezone')));

    try {
        $user = User::factory()->create();
        Todo::factory()->for($user)->dueOn('2026-04-10')->create(['title' => 'April task']);
        Todo::factory()->for($user)->dueOn('2026-03-10')->create(['title' => 'March task']);

        $this->actingAs($user)
            ->get(route('todos.calendar', ['month' => 'not-a-month']))
            ->assertOk()
            ->assertSee(__('todos.calendar.invalid_month.heading'))
            ->assertSee('April 2026')
            ->assertSee('April task')
            ->assertDontSee('March task');

        Livewire::actingAs($user)
            ->test(Calendar::class)
            ->set('monthInput', '2026-02-31')
            ->call('changeMonth')
            ->assertHasErrors(['monthInput'])
            ->assertSet('month', '2026-04');
    } finally {
        Carbon::setTestNow();
    }
});

test('calendar month navigation updates the url backed month state', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-10 09:30:00', config('app.timezone')));

    try {
        $user = User::factory()->create();
        Todo::factory()->for($user)->dueOn('2026-02-10')->create(['title' => 'February task']);
        Todo::factory()->for($user)->dueOn('2026-03-10')->create(['title' => 'March task']);
        Todo::factory()->for($user)->dueOn('2026-04-10')->create(['title' => 'April task']);

        Livewire::withQueryParams(['month' => '2026-03'])
            ->actingAs($user)
            ->test(Calendar::class)
            ->assertSet('month', '2026-03')
            ->assertSee('March task')
            ->call('previousMonth')
            ->assertSet('month', '2026-02')
            ->assertSee('February task')
            ->assertDontSee('March task')
            ->call('nextMonth')
            ->assertSet('month', '2026-03')
            ->assertSee('March task')
            ->call('nextMonth')
            ->assertSet('month', '2026-04')
            ->assertSee('April task')
            ->call('currentMonth')
            ->assertSet('month', '2026-03');
    } finally {
        Carbon::setTestNow();
    }
});

test('calendar month rule accepts only canonical year month values', function () {
    $valid = Validator::make(
        ['month' => '2026-03'],
        ['month' => [new CalendarMonth]],
    );

    $invalid = Validator::make(
        ['month' => '2026-3'],
        ['month' => [new CalendarMonth]],
    );

    expect($valid->passes())->toBeTrue()
        ->and(CalendarMonth::normalize('2026-03'))->toBe('2026-03')
        ->and($invalid->passes())->toBeFalse()
        ->and($invalid->errors()->first('month'))->toBe(__('todos.validation.calendar_month'));
});

test('calendar route and component keep private view guardrails', function () {
    $source = file_get_contents(app_path('Livewire/Todos/Calendar.php'));

    expect(calendarRouteMiddleware('todos.calendar'))
        ->toContain('auth', 'verified')
        ->and(route('todos.calendar'))->toBe('https://ruflo.test/todos/calendar')
        ->and($source)
        ->toContain('TodoCalendarQuery')
        ->toContain('CalendarMonth')
        ->toContain('$this->authorize')
        ->not->toContain('Todo::query()')
        ->not->toContain('Todo::find');
});
