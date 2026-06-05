<?php

use App\Actions\Setup\InspectSetupStatus;
use App\Livewire\Settings\SetupStatus;
use App\Models\User;
use Livewire\Livewire;

test('setup status requires authentication', function () {
    $this->get(route('setup.status'))
        ->assertRedirect(route('login'));
});

test('setup status requires password confirmation', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('setup.status'))
        ->assertRedirect(route('password.confirm'));
});

test('setup status page can be rendered after password confirmation', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('setup.status'))
        ->assertOk()
        ->assertSee(__('setup.pages.status.heading'))
        ->assertSee(__('setup.messages.status_only'))
        ->assertDontSee('Public installer');
});

test('setup status inspector reports deployment checks', function () {
    config([
        'app.url' => 'https://ruflo.test',
        'queue.default' => 'sync',
        'hosting.restricted' => true,
    ]);

    $report = app(InspectSetupStatus::class)()->toArray();

    expect($report['checks'])->not->toBeEmpty()
        ->and(collect($report['checks'])->pluck('key')->all())->toContain(
            'app_key',
            'app_url',
            'database',
            'migrations_table',
            'pending_migrations',
            'queue_connection',
            'restricted_hosting',
            'storage_writable',
        );
});

test('setup status can be refreshed from Livewire', function () {
    Livewire::test(SetupStatus::class)
        ->assertSet('status.ready', false)
        ->call('refreshStatus')
        ->assertHasNoErrors();
});
