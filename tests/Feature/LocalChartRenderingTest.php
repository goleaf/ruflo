<?php

use App\Models\Habit;
use App\Models\HabitCheckIn;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\Todo;
use App\Models\User;

test('dashboard and reports render accessible local charts without paid services', function () {
    $this->travelTo('2026-06-06 09:00:00');

    $user = User::factory()->create();
    $other = User::factory()->create();
    $project = Project::factory()->for($user)->active()->create(['name' => 'Local chart project']);
    $habit = Habit::factory()->for($user)->daily()->create(['title' => 'Review local charts']);

    Todo::factory()->forProject($project)->dueToday()->create(['title' => 'Chart dashboard task']);
    Todo::factory()->forProject($project)->overdue()->create(['title' => 'Chart overdue task']);
    Todo::factory()->forProject($project)->completed()->create(['title' => 'Chart completed task']);
    Todo::factory()->for($other)->overdue()->create(['title' => 'Foreign chart task']);

    HabitCheckIn::factory()->forHabit($habit)->today()->create();
    TimeEntry::factory()->for($user)->manual(45)->create(['entry_date' => today()->toDateString()]);
    TimeEntry::factory()->for($other)->manual(90)->create(['entry_date' => today()->toDateString()]);

    $dashboard = $this->actingAs($user)->get(route('dashboard'));

    $dashboard
        ->assertOk()
        ->assertSee('data-test="dashboard-foundation-chart"', false)
        ->assertSee('data-chart-driver="local-css"', false)
        ->assertSee('data-chart-library="none"', false)
        ->assertSee('data-chart-type="bar"', false)
        ->assertSee('role="img"', false)
        ->assertSee('data-test="dashboard-foundation-chart-today"', false)
        ->assertSeeText(__('dashboard.foundation.chart.item_summary', [
            'label' => __('dashboard.foundation.widgets.today.label'),
            'value' => 1,
        ]))
        ->assertDontSee('data-flux-chart', false)
        ->assertDontSee('<flux:chart', false)
        ->assertDontSee('cdn.jsdelivr.net', false)
        ->assertDontSee('unpkg.com', false)
        ->assertDontSeeText('Foreign chart task');

    $reports = $this->actingAs($user)->get(route('reports.overview'));

    $reports
        ->assertOk()
        ->assertSee('data-test="reports-chart-productivity"', false)
        ->assertSee('data-test="reports-chart-productivity-active"', false)
        ->assertSee('data-test="reports-chart-time-today"', false)
        ->assertSee('data-chart-driver="local-css"', false)
        ->assertSee('data-chart-library="none"', false)
        ->assertSee('data-chart-type="bar"', false)
        ->assertSee('role="img"', false)
        ->assertSeeText(__('reports.values.minutes', ['minutes' => 45]))
        ->assertSeeText(__('reports.charts.item_summary', [
            'label' => __('reports.charts.productivity.active'),
            'value' => 2,
        ]))
        ->assertDontSee('data-flux-chart', false)
        ->assertDontSee('<flux:chart', false)
        ->assertDontSee('cdn.jsdelivr.net', false)
        ->assertDontSee('unpkg.com', false)
        ->assertDontSeeText('Foreign chart task');
});

test('local chart component stays generic and free of framework queries or remote assets', function () {
    $component = file_get_contents(resource_path('views/components/ui/local-bar-chart.blade.php'));
    $dashboard = file_get_contents(resource_path('views/livewire/dashboard/index.blade.php'));
    $reports = file_get_contents(resource_path('views/livewire/reports/overview.blade.php'));

    expect($component)
        ->toContain('data-chart-driver')
        ->toContain('local-css')
        ->toContain('data-chart-library')
        ->toContain('none')
        ->toContain('role')
        ->toContain('sr-only')
        ->not->toContain('<flux:chart')
        ->not->toContain('data-flux-chart')
        ->not->toContain('https://')
        ->not->toContain('http://')
        ->not->toContain('Todo::query')
        ->and($dashboard)
        ->toContain('<x-ui.local-bar-chart')
        ->toContain('dashboard-foundation-chart')
        ->not->toContain('data-flux-chart')
        ->not->toContain('<flux:chart')
        ->and($reports)
        ->toContain('<x-ui.local-bar-chart')
        ->toContain('reports-chart-{{ $chart[\'key\'] }}')
        ->not->toContain('data-flux-chart')
        ->not->toContain('<flux:chart');
});
