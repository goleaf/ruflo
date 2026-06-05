<?php

use App\Actions\Maintenance\BuildMaintenanceSnapshot;
use App\Actions\Maintenance\ClearCompiledViews;
use App\Livewire\Settings\MaintenanceCenter;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

test('maintenance center requires authentication', function () {
    $this->get(route('maintenance.center'))
        ->assertRedirect(route('login'));
});

test('maintenance center requires password confirmation', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('maintenance.center'))
        ->assertRedirect(route('password.confirm'));
});

test('maintenance center can be rendered after password confirmation', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('maintenance.center'))
        ->assertOk()
        ->assertSee(__('maintenance.pages.center.heading'))
        ->assertSee(__('maintenance.sections.safe_controls'));
});

test('maintenance snapshot reports setup processing and runtime state', function () {
    $snapshot = app(BuildMaintenanceSnapshot::class)();

    expect($snapshot)->toHaveKeys(['setup', 'processing', 'runtime'])
        ->and($snapshot['processing'])->toHaveKeys(['chunk_size', 'max_runtime_seconds', 'retry_cooldown_seconds', 'resume_after_failure'])
        ->and($snapshot['runtime'])->toHaveKeys(['cache_store', 'session_driver', 'queue_connection', 'compiled_views', 'storage_writable']);
});

test('maintenance center can flush application cache', function () {
    Cache::put('maintenance-test-key', 'value');

    Livewire::test(MaintenanceCenter::class)
        ->call('flushApplicationCache')
        ->assertSet('lastAction', __('maintenance.messages.cache_flushed'));

    expect(Cache::get('maintenance-test-key'))->toBeNull();
});

test('maintenance center can clear compiled views through a bounded web action', function () {
    $compiledView = storage_path('framework/views/maintenance-test-view.php');

    File::put($compiledView, '<?php echo "test";');

    expect(File::exists($compiledView))->toBeTrue();

    $component = Livewire::test(MaintenanceCenter::class)
        ->call('clearCompiledViews');

    expect($component->get('lastAction'))->toContain('compiled view');

    expect(File::exists($compiledView))->toBeFalse();
});

test('compiled view cleanup action returns deleted file count', function () {
    $compiledView = storage_path('framework/views/maintenance-action-test-view.php');

    File::put($compiledView, '<?php echo "test";');

    expect(app(ClearCompiledViews::class)())->toBeGreaterThanOrEqual(1)
        ->and(File::exists($compiledView))->toBeFalse();
});
