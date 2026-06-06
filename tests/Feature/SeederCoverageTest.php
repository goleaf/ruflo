<?php

use App\Models\Goal;
use App\Models\GoalMilestone;
use App\Models\Habit;
use App\Models\HabitCheckIn;
use App\Models\Project;
use App\Models\Reminder;
use App\Models\SavedTodoView;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\TodoChecklistItem;
use App\Models\TodoTemplate;
use App\Models\User;
use App\Queries\Todos\TodoFocusQuery;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;

test('database seeder creates safe demo users and complete private workspaces', function () {
    $this->seed(DatabaseSeeder::class);

    $users = User::query()->orderBy('email')->get();

    expect($users)->toHaveCount(2)
        ->and($users->pluck('email')->all())->toBe(['second@example.com', 'test@example.com'])
        ->and($users->firstWhere('email', 'test@example.com')->is_admin)->toBeTrue()
        ->and($users->firstWhere('email', 'second@example.com')->is_admin)->toBeFalse()
        ->and(Hash::check('password', $users->firstWhere('email', 'test@example.com')->password))->toBeTrue()
        ->and(Project::query()->count())->toBe(6)
        ->and(Goal::query()->count())->toBe(4)
        ->and(GoalMilestone::query()->count())->toBe(6)
        ->and(Habit::query()->count())->toBe(4)
        ->and(HabitCheckIn::query()->count())->toBe(12)
        ->and(Reminder::query()->count())->toBe(0)
        ->and(SavedTodoView::query()->count())->toBe(6)
        ->and(Tag::query()->count())->toBe(4)
        ->and(TodoChecklistItem::query()->count())->toBe(18)
        ->and(TodoTemplate::query()->count())->toBe(6)
        ->and(Todo::query()->count())->toBe(16)
        ->and(Todo::withTrashed()->count())->toBe(18);

    $users->each(function (User $user): void {
        expect($user->projects()->whereNull('archived_at')->count())->toBe(2)
            ->and($user->projects()->whereNotNull('archived_at')->count())->toBe(1)
            ->and($user->goals()->pluck('title')->sort()->values()->all())->toBe([
                'Launch the personal command center',
                'Plan a calmer weekend',
            ])
            ->and($user->goalMilestones()->count())->toBe(3)
            ->and($user->goalMilestones()->whereNotNull('completed_at')->count())->toBe(1)
            ->and($user->habits()->pluck('title')->sort()->values()->all())->toBe([
                'Plan the day',
                'Run the weekly review',
            ])
            ->and($user->habitCheckIns()->count())->toBe(6)
            ->and($user->tags()->pluck('name')->sort()->values()->all())->toBe(['urgent', 'waiting'])
            ->and($user->todos()->active()->count())->toBe(5)
            ->and($user->todos()->completed()->count())->toBe(1)
            ->and($user->todos()->archived()->count())->toBe(2)
            ->and($user->todos()->onlyTrashed()->count())->toBe(1)
            ->and($user->todos()->inInbox()->count())->toBe(2)
            ->and($user->todoChecklistItems()->count())->toBe(9)
            ->and($user->todoChecklistItems()->where('is_completed', true)->count())->toBe(3)
            ->and($user->todoTemplates()->pluck('name')->sort()->values()->all())->toBe([
                'Bug triage checklist',
                'Daily planning routine',
                'Project kickoff',
            ])
            ->and($user->todos()->overdue()->count())->toBe(1)
            ->and($user->todos()->dueToday()->count())->toBe(1)
            ->and($user->todos()->upcoming()->count())->toBe(1)
            ->and($user->savedTodoViews()->pluck('name')->sort()->values()->all())->toBe([
                'Today focus',
                'Urgent work',
                'Waiting on others',
            ])
            ->and(app(TodoFocusQuery::class)->for($user)->pluck('title')->all())
            ->toContain('Review the current flow', 'Send the overdue report');
    });
});

test('database seeder is idempotent for the current demo catalog', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->count())->toBe(2)
        ->and(Project::query()->count())->toBe(6)
        ->and(Goal::query()->count())->toBe(4)
        ->and(GoalMilestone::query()->count())->toBe(6)
        ->and(Habit::query()->count())->toBe(4)
        ->and(HabitCheckIn::query()->count())->toBe(12)
        ->and(Reminder::query()->count())->toBe(0)
        ->and(SavedTodoView::query()->count())->toBe(6)
        ->and(Tag::query()->count())->toBe(4)
        ->and(TodoChecklistItem::query()->count())->toBe(18)
        ->and(TodoTemplate::query()->count())->toBe(6)
        ->and(Todo::query()->count())->toBe(16)
        ->and(Todo::withTrashed()->count())->toBe(18)
        ->and(Todo::query()->where('title', 'Review the current flow')->count())->toBe(2);
});

test('database seeder does not create known demo credentials in production environment', function () {
    config(['app.env' => 'production']);

    $this->seed(DatabaseSeeder::class);

    expect(User::query()->count())->toBe(0)
        ->and(Project::query()->count())->toBe(0)
        ->and(Goal::query()->count())->toBe(0)
        ->and(GoalMilestone::query()->count())->toBe(0)
        ->and(Habit::query()->count())->toBe(0)
        ->and(HabitCheckIn::query()->count())->toBe(0)
        ->and(Reminder::query()->count())->toBe(0)
        ->and(SavedTodoView::query()->count())->toBe(0)
        ->and(Tag::query()->count())->toBe(0)
        ->and(TodoChecklistItem::query()->count())->toBe(0)
        ->and(TodoTemplate::query()->count())->toBe(0)
        ->and(Todo::query()->count())->toBe(0);
});
