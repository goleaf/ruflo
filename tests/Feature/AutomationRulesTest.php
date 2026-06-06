<?php

use App\Enums\AutomationRuleKind;
use App\Enums\AutomationRunStatus;
use App\Enums\Priority;
use App\Livewire\Todos\Automations;
use App\Models\AutomationRule;
use App\Models\AutomationRuleRun;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

function automationRouteMiddleware(string $routeName): array
{
    return Route::getRoutes()->getByName($routeName)?->gatherMiddleware() ?? [];
}

test('automation route redirects guests and unverified users', function () {
    $this->get(route('todos.automations'))->assertRedirect(route('login'));

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('todos.automations'))
        ->assertRedirect(route('verification.notice'));
});

test('automation page renders owner scoped rules and class based guardrails', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    AutomationRule::factory()->for($user)->promoteOverdueTasks()->create([
        'name' => 'Promote my overdue tasks',
    ]);
    AutomationRule::factory()->for($other)->promoteOverdueTasks()->create([
        'name' => 'Foreign automation rule',
    ]);

    $this->actingAs($user)
        ->get(route('todos.automations'))
        ->assertOk()
        ->assertSee(__('automation.pages.index.title'))
        ->assertSee('Promote my overdue tasks')
        ->assertDontSee('Foreign automation rule');

    expect(automationRouteMiddleware('todos.automations'))
        ->toContain('auth', 'verified')
        ->and(route('todos.automations'))->toBe('https://ruflo.test/todos/automations')
        ->and(class_exists(Automations::class))->toBeTrue()
        ->and(file_exists(resource_path('views/components/⚡todos/automations.blade.php')))->toBeFalse()
        ->and(file_exists(resource_path('views/livewire/todos/automations.blade.php')))->toBeTrue();
});

test('users can create automation rules with translated validation', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Automations::class)
        ->set('name', '')
        ->set('kind', AutomationRuleKind::PromoteOverdueTasks->value)
        ->call('createRule')
        ->assertHasErrors(['name'])
        ->assertSee(__('automation.validation.rule_name'))
        ->set('name', '  Promote owned overdue  ')
        ->call('createRule')
        ->assertHasNoErrors()
        ->assertSee('Promote owned overdue');

    $rule = AutomationRule::query()->where('user_id', $user->id)->sole();

    expect($rule->name)->toBe('Promote owned overdue')
        ->and($rule->kind)->toBe(AutomationRuleKind::PromoteOverdueTasks)
        ->and($rule->is_enabled)->toBeTrue();

    Livewire::actingAs($user)
        ->test(Automations::class)
        ->set('name', 'Promote owned overdue')
        ->set('kind', AutomationRuleKind::ArchiveCompletedTasks->value)
        ->call('createRule')
        ->assertHasErrors(['name'])
        ->assertSee(__('automation.validation.rule_name_unique'));
});

test('promote overdue automation supports dry run and live run without crossing users', function () {
    Carbon::setTestNow('2026-03-10 12:00:00');

    $user = User::factory()->create();
    $other = User::factory()->create();
    $rule = AutomationRule::factory()->for($user)->promoteOverdueTasks()->create([
        'name' => 'Promote overdue',
    ]);
    $candidate = Todo::factory()->for($user)->normalPriority()->overdue()->create([
        'title' => 'Owned overdue normal task',
    ]);
    $foreign = Todo::factory()->for($other)->normalPriority()->overdue()->create([
        'title' => 'Foreign overdue normal task',
    ]);

    Livewire::actingAs($user)
        ->test(Automations::class)
        ->call('testRule', $rule->id)
        ->assertSet('lastRunReport.matched', 1)
        ->assertSet('lastRunReport.changed', 0)
        ->assertSet('lastRunReport.dry_run', true);

    expect($candidate->refresh()->priority)->toBe(Priority::Normal)
        ->and($foreign->refresh()->priority)->toBe(Priority::Normal);

    $dryRun = AutomationRuleRun::query()->where('automation_rule_id', $rule->id)->latest('id')->firstOrFail();

    expect($dryRun->dry_run)->toBeTrue()
        ->and($dryRun->status)->toBe(AutomationRunStatus::Completed)
        ->and($dryRun->matched_count)->toBe(1)
        ->and($dryRun->changed_count)->toBe(0);

    Livewire::actingAs($user)
        ->test(Automations::class)
        ->call('runRule', $rule->id)
        ->assertSet('lastRunReport.matched', 1)
        ->assertSet('lastRunReport.changed', 1)
        ->assertSet('lastRunReport.dry_run', false);

    expect($candidate->refresh()->priority)->toBe(Priority::High)
        ->and($foreign->refresh()->priority)->toBe(Priority::Normal)
        ->and($rule->refresh()->last_status)->toBe(AutomationRunStatus::Completed);

    Carbon::setTestNow();
});

test('disabled automation rules record a skipped run and change nothing', function () {
    Carbon::setTestNow('2026-03-10 12:00:00');

    $user = User::factory()->create();
    $rule = AutomationRule::factory()->for($user)->promoteOverdueTasks()->disabled()->create();
    $candidate = Todo::factory()->for($user)->lowPriority()->overdue()->create();

    Livewire::actingAs($user)
        ->test(Automations::class)
        ->call('runRule', $rule->id)
        ->assertSet('lastRunReport.status', __('automation.run_status.disabled'))
        ->assertSet('lastRunReport.changed', 0);

    $run = AutomationRuleRun::query()->where('automation_rule_id', $rule->id)->sole();

    expect($run->status)->toBe(AutomationRunStatus::Disabled)
        ->and($run->changed_count)->toBe(0)
        ->and($candidate->refresh()->priority)->toBe(Priority::Low);

    Carbon::setTestNow();
});

test('automation chunks can be retried to resume remaining work', function () {
    Carbon::setTestNow('2026-03-10 12:00:00');
    config(['hosting.web_processing.chunk_size' => 1]);

    $user = User::factory()->create();
    $rule = AutomationRule::factory()->for($user)->promoteOverdueTasks()->create();
    $first = Todo::factory()->for($user)->normalPriority()->overdue()->create([
        'title' => 'First chunk task',
    ]);
    $second = Todo::factory()->for($user)->normalPriority()->overdue()->create([
        'title' => 'Second chunk task',
    ]);

    Livewire::actingAs($user)
        ->test(Automations::class)
        ->call('runRule', $rule->id)
        ->assertSet('lastRunReport.matched', 2)
        ->assertSet('lastRunReport.changed', 1)
        ->assertSet('lastRunReport.skipped', 1);

    expect([$first->refresh()->priority, $second->refresh()->priority])
        ->toContain(Priority::High)
        ->toContain(Priority::Normal);

    Livewire::actingAs($user)
        ->test(Automations::class)
        ->call('runRule', $rule->id)
        ->assertSet('lastRunReport.matched', 1)
        ->assertSet('lastRunReport.changed', 1)
        ->assertSet('lastRunReport.skipped', 0);

    expect($first->refresh()->priority)->toBe(Priority::High)
        ->and($second->refresh()->priority)->toBe(Priority::High);

    Carbon::setTestNow();
});

test('archive completed automation only archives old owned completed tasks', function () {
    Carbon::setTestNow('2026-03-10 12:00:00');

    $user = User::factory()->create();
    $other = User::factory()->create();
    $rule = AutomationRule::factory()->for($user)->archiveCompletedTasks(days: 7)->create();
    $oldCompleted = Todo::factory()->for($user)->completed()->create([
        'title' => 'Old completed task',
        'created_at' => now()->subDays(12),
        'updated_at' => now()->subDays(10),
    ]);
    $freshCompleted = Todo::factory()->for($user)->completed()->create([
        'title' => 'Fresh completed task',
        'created_at' => now()->subDays(2),
        'updated_at' => now()->subDays(2),
    ]);
    $foreignOldCompleted = Todo::factory()->for($other)->completed()->create([
        'title' => 'Foreign old completed task',
        'created_at' => now()->subDays(12),
        'updated_at' => now()->subDays(10),
    ]);

    Livewire::actingAs($user)
        ->test(Automations::class)
        ->call('testRule', $rule->id)
        ->assertSet('lastRunReport.matched', 1)
        ->assertSet('lastRunReport.changed', 0);

    expect($oldCompleted->refresh()->archived_at)->toBeNull();

    Livewire::actingAs($user)
        ->test(Automations::class)
        ->call('runRule', $rule->id)
        ->assertSet('lastRunReport.matched', 1)
        ->assertSet('lastRunReport.changed', 1);

    expect($oldCompleted->refresh()->archived_at)->not->toBeNull()
        ->and($freshCompleted->refresh()->archived_at)->toBeNull()
        ->and($foreignOldCompleted->refresh()->archived_at)->toBeNull();

    Carbon::setTestNow();
});

test('automation actions fail safely for unauthorized rule ids', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $foreignRule = AutomationRule::factory()->for($intruder)->promoteOverdueTasks()->create([
        'name' => 'Foreign automation',
    ]);

    Livewire::actingAs($owner)
        ->test(Automations::class)
        ->assertDontSee('Foreign automation');

    expect(fn () => Livewire::actingAs($owner)->test(Automations::class)->call('runRule', $foreignRule->id))
        ->toThrow(ModelNotFoundException::class);

    expect(fn () => Livewire::actingAs($owner)->test(Automations::class)->call('toggleRule', $foreignRule->id))
        ->toThrow(ModelNotFoundException::class);
});
