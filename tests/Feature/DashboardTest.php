<?php

use App\Models\Project;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    Todo::factory()->for($user)->overdue()->create();
    Todo::factory()->for($user)->deleted()->create();
    Project::factory()->for($user)->create();
    Tag::factory()->for($user)->create();

    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText('RuFlo Control Deck')
        ->assertSeeText('Private workspace')
        ->assertSeeText('Trash')
        ->assertSeeText('Open todos')
        ->assertSeeText('Track time')
        ->assertSeeText('Blocked')
        ->assertSeeText('Cleanup')
        ->assertSeeText('Automations')
        ->assertSee('data-test="dashboard-workspace-row"', false)
        ->assertSee('data-test="dashboard-workspace-actions"', false)
        ->assertSee('min-w-max', false)
        ->assertSee('whitespace-nowrap', false)
        ->assertSee('data-test="dashboard-summary-widgets"', false)
        ->assertSee('grid-cols-11', false)
        ->assertSee('bg-sky-50', false)
        ->assertSee('bg-rose-50', false)
        ->assertSee('bg-emerald-50', false)
        ->assertSeeInOrder([
            __('dashboard.workspace.label'),
            __('dashboard.workspace.heading'),
            __('dashboard.workspace.description'),
            __('dashboard.workspace.today_action'),
            __('dashboard.workspace.overdue_action'),
            __('dashboard.workspace.upcoming_action'),
            __('dashboard.workspace.focus_action'),
            __('dashboard.workspace.time_action'),
            __('dashboard.workspace.blocked_action'),
            __('dashboard.workspace.cleanup_action'),
            __('dashboard.workspace.automations_action'),
            __('dashboard.workspace.goals_action'),
            __('dashboard.workspace.habits_action'),
            __('dashboard.workspace.action'),
        ])
        ->assertSeeInOrder([
            __('dashboard.summary.active'),
            __('dashboard.summary.overdue'),
            __('dashboard.summary.completed'),
            __('dashboard.summary.archived'),
            __('dashboard.summary.trash'),
            __('dashboard.summary.projects'),
            __('dashboard.summary.tags'),
            __('dashboard.summary.goals'),
            __('dashboard.summary.milestones'),
            __('dashboard.summary.habits'),
            __('dashboard.summary.habit_check_ins'),
        ])
        ->assertDontSeeText('Install paths')
        ->assertDontSeeText('npx ruflo@latest mcp start')
        ->assertDontSeeText('/plugin marketplace add ruvnet/ruflo');
});

test('workspace pages use the shared page container width', function () {
    $containerSource = file_get_contents(resource_path('views/components/ui/page-container.blade.php'));

    expect($containerSource)
        ->toContain('max-w-6xl')
        ->toContain('$attributes->class');

    foreach ([
        'dashboard/index.blade.php',
        'todos/index.blade.php',
        'goals/index.blade.php',
        'habits/index.blade.php',
    ] as $view) {
        $source = file_get_contents(resource_path("views/livewire/{$view}"));

        expect($source)
            ->toContain('<x-ui.page-container')
            ->not->toContain('max-w-5xl')
            ->not->toContain('max-w-6xl');
    }
});
