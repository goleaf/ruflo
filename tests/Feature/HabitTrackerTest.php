<?php

use App\Actions\Habits\LinkTodoToHabit;
use App\Actions\Habits\ToggleHabitCheckIn;
use App\Data\Habits\HabitProgress;
use App\Livewire\Habits\Index as HabitsIndex;
use App\Models\Goal;
use App\Models\Habit;
use App\Models\HabitCheckIn;
use App\Models\Todo;
use App\Models\User;
use App\Queries\Habits\HabitListQuery;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

test('habits route redirects guests and unverified users', function () {
    $this->get(route('habits.index'))
        ->assertRedirect(route('login'));

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('habits.index'))
        ->assertRedirect(route('verification.notice'));
});

test('habits render owner scoped check ins linked tasks and honest streak progress', function () {
    $this->travelTo(Carbon::parse('2026-06-10 10:00:00'));

    $user = User::factory()->create();
    $other = User::factory()->create();
    $goal = Goal::factory()->for($user)->titled('Build calmer routines')->create();
    $daily = Habit::factory()->forGoal($goal)->daily()->titled('Plan every day')->create([
        'description' => 'Owner visible habit',
    ]);
    $weekly = Habit::factory()->for($user)->weekly(2)->titled('Run weekly review')->create();

    foreach (['2026-06-10', '2026-06-09', '2026-06-08'] as $date) {
        HabitCheckIn::factory()->forHabit($daily)->occurredOn($date)->create();
    }

    foreach (['2026-06-10', '2026-06-08', '2026-06-04', '2026-06-02'] as $date) {
        HabitCheckIn::factory()->forHabit($weekly)->occurredOn($date)->create();
    }

    Todo::factory()->forHabit($daily)->active()->create(['title' => 'Linked habit task']);
    Todo::factory()->forHabit($daily)->deleted()->create(['title' => 'Deleted habit task']);
    Habit::factory()->for($other)->titled('Foreign habit')->create();

    $dailyProgress = HabitProgress::forHabit(app(HabitListQuery::class)->findFor($user, $daily->id));
    $weeklyProgress = HabitProgress::forHabit(app(HabitListQuery::class)->findFor($user, $weekly->id));

    expect($dailyProgress->completedInPeriod)->toBe(1)
        ->and($dailyProgress->targetInPeriod)->toBe(1)
        ->and($dailyProgress->percent)->toBe(100)
        ->and($dailyProgress->currentStreak)->toBe(3)
        ->and($dailyProgress->bestStreak)->toBe(3)
        ->and($weeklyProgress->completedInPeriod)->toBe(2)
        ->and($weeklyProgress->targetInPeriod)->toBe(2)
        ->and($weeklyProgress->currentStreak)->toBe(2)
        ->and($weeklyProgress->bestStreak)->toBe(2);

    Livewire::actingAs($user)->test(HabitsIndex::class)
        ->assertSee('Plan every day')
        ->assertSee('Build calmer routines')
        ->assertSee('Run weekly review')
        ->assertSee('Linked habit task')
        ->assertSee(__('habits.progress.text', ['completed' => 1, 'target' => 1, 'period' => __('habits.period.daily'), 'percent' => 100]))
        ->assertSee(__('habits.progress.text', ['completed' => 2, 'target' => 2, 'period' => __('habits.period.weekly'), 'percent' => 100]))
        ->assertSee(__('habits.progress.streak', ['count' => 3]))
        ->assertDontSee('Foreign habit')
        ->assertDontSee('Deleted habit task');
});

test('habit creation validates translated input and owner scoped goals', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();
    $foreignGoal = Goal::factory()->create();

    Livewire::actingAs($user)->test(HabitsIndex::class)
        ->set('title', '   ')
        ->call('createHabit')
        ->assertHasErrors(['title'])
        ->set('title', '  Run weekly review  ')
        ->set('frequency', 'monthly')
        ->call('createHabit')
        ->assertHasErrors(['frequency'])
        ->set('frequency', 'weekly')
        ->set('targetCount', '8')
        ->call('createHabit')
        ->assertHasErrors(['target_count'])
        ->set('targetCount', '2')
        ->set('goalId', (string) $foreignGoal->id)
        ->call('createHabit')
        ->assertHasErrors(['goal_id'])
        ->set('goalId', (string) $goal->id)
        ->call('createHabit')
        ->assertHasNoErrors();

    $habit = Habit::query()->whereBelongsTo($user)->where('title', 'Run weekly review')->firstOrFail();

    expect($habit->goal_id)->toBe($goal->id)
        ->and($habit->frequency->value)->toBe('weekly')
        ->and($habit->target_count)->toBe(2);
});

test('habits can be checked in and unchecked for today without spoofing another user', function () {
    $this->travelTo(Carbon::parse('2026-06-10 10:00:00'));

    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->daily()->create();
    $archivedHabit = Habit::factory()->for($user)->archived()->create();
    $foreignHabit = Habit::factory()->create();

    Livewire::actingAs($user)->test(HabitsIndex::class)
        ->call('toggleCheckIn', $habit->id);

    expect(HabitCheckIn::query()->whereBelongsTo($user)->where('habit_id', $habit->id)->whereDate('occurred_on', '2026-06-10')->exists())->toBeTrue();

    Livewire::actingAs($user)->test(HabitsIndex::class)
        ->call('toggleCheckIn', $habit->id);

    expect(HabitCheckIn::query()->whereBelongsTo($user)->where('habit_id', $habit->id)->exists())->toBeFalse();

    expect(fn () => Livewire::actingAs($user)->test(HabitsIndex::class)->call('toggleCheckIn', $foreignHabit->id))
        ->toThrow(ModelNotFoundException::class);

    expect(fn () => app(ToggleHabitCheckIn::class)->handle($user, $archivedHabit))
        ->toThrow(ValidationException::class);
});

test('tasks link to habits through owner scoped actions only', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $habit = Habit::factory()->for($user)->daily()->create();
    $todo = Todo::factory()->for($user)->active()->create(['title' => 'Linkable habit task']);
    $foreignTodo = Todo::factory()->for($other)->create();
    $archivedTodo = Todo::factory()->for($user)->archived()->create();

    Livewire::actingAs($user)->test(HabitsIndex::class)
        ->set('linkTodoIds.'.$habit->id, (string) $todo->id)
        ->call('linkTodo', $habit->id)
        ->assertHasNoErrors();

    expect($todo->refresh()->habit_id)->toBe($habit->id);

    expect(fn () => app(LinkTodoToHabit::class)->handle($user, $habit, $foreignTodo))
        ->toThrow(AuthorizationException::class);

    expect(fn () => app(LinkTodoToHabit::class)->handle($user, $habit, $archivedTodo))
        ->toThrow(ValidationException::class);
});

test('habits route component and view follow architecture guardrails', function () {
    $route = Route::getRoutes()->getByName('habits.index');
    $componentSource = file_get_contents(app_path('Livewire/Habits/Index.php'));
    $viewSource = file_get_contents(resource_path('views/livewire/habits/index.blade.php'));

    expect(route('habits.index'))->toBe('https://ruflo.test/habits')
        ->and($route?->gatherMiddleware())->toContain('auth', 'verified')
        ->and($componentSource)
        ->toContain('HabitListQuery')
        ->toContain('CreateHabit')
        ->toContain('ToggleHabitCheckIn')
        ->toContain('LinkTodoToHabit')
        ->toContain('HabitTitle')
        ->toContain('HabitTargetCount')
        ->toContain('$this->authorize')
        ->not->toContain('Habit::query()')
        ->not->toContain('Todo::query()')
        ->not->toContain('->save()')
        ->and($viewSource)
        ->toContain('<flux:progress')
        ->toContain('habits.progress.text')
        ->not->toContain('@php');
});
