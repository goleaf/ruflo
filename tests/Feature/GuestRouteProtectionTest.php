<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

function routeProtectionMiddleware(string $routeName): array
{
    return Route::getRoutes()->getByName($routeName)?->gatherMiddleware() ?? [];
}

test('guests are redirected from private application pages to login', function (string $url) {
    $this->get($url)
        ->assertRedirect(route('login'));
})->with([
    'dashboard' => fn () => route('dashboard'),
    'reports' => fn () => route('reports.overview'),
    'goals' => fn () => route('goals.index'),
    'create goal' => fn () => route('goals.create'),
    'create goal milestone' => fn () => route('goals.milestones.create'),
    'habits' => fn () => route('habits.index'),
    'create habit' => fn () => route('habits.create'),
    'notifications' => fn () => route('notifications.inbox'),
    'todos' => fn () => route('todos.index'),
    'todo board' => fn () => route('todos.board'),
    'todo calendar' => fn () => route('todos.calendar'),
    'todo templates' => fn () => route('todos.templates'),
    'todo inbox' => fn () => route('todos.inbox'),
    'todo focus' => fn () => route('todos.focus'),
    'todo time' => fn () => route('todos.time'),
    'todo blocked' => fn () => route('todos.blocked'),
    'todo cleanup' => fn () => route('todos.cleanup'),
    'todo automations' => fn () => route('todos.automations'),
    'todo reminders' => fn () => route('todos.reminders'),
    'todo recurring' => fn () => route('todos.recurring'),
    'settings redirect' => '/settings',
    'profile settings' => fn () => route('profile.edit'),
    'appearance settings' => fn () => route('appearance.edit'),
    'security settings' => fn () => route('security.edit'),
    'setup status' => fn () => route('setup.status'),
    'maintenance center' => fn () => route('maintenance.center'),
]);

test('verified private routes redirect unverified users to the verification notice', function (string $routeName) {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route($routeName))
        ->assertRedirect(route('verification.notice'));
})->with([
    'dashboard' => 'dashboard',
    'reports' => 'reports.overview',
    'goals' => 'goals.index',
    'create goal' => 'goals.create',
    'create goal milestone' => 'goals.milestones.create',
    'habits' => 'habits.index',
    'create habit' => 'habits.create',
    'notifications' => 'notifications.inbox',
    'todos' => 'todos.index',
    'todo board' => 'todos.board',
    'todo calendar' => 'todos.calendar',
    'todo templates' => 'todos.templates',
    'todo inbox' => 'todos.inbox',
    'todo focus' => 'todos.focus',
    'todo time' => 'todos.time',
    'todo blocked' => 'todos.blocked',
    'todo cleanup' => 'todos.cleanup',
    'todo automations' => 'todos.automations',
    'todo reminders' => 'todos.reminders',
    'todo recurring' => 'todos.recurring',
    'appearance settings' => 'appearance.edit',
    'security settings' => 'security.edit',
    'setup status' => 'setup.status',
    'maintenance center' => 'maintenance.center',
]);

test('profile settings remain available to unverified authenticated users', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee(__('settings.profile.unverified'));
});

test('sensitive verified settings require password confirmation', function (string $routeName) {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get(route($routeName))
        ->assertRedirect(route('password.confirm'));
})->with([
    'security settings' => 'security.edit',
    'setup status' => 'setup.status',
    'maintenance center' => 'maintenance.center',
]);

test('maintenance center still denies non admin users after password confirmation', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('maintenance.center'))
        ->assertForbidden();
});

test('private route middleware cannot be removed silently', function (string $routeName, array $expectedMiddleware) {
    expect(routeProtectionMiddleware($routeName))
        ->toContain(...$expectedMiddleware);
})->with([
    'dashboard' => ['dashboard', ['auth', 'verified']],
    'reports' => ['reports.overview', ['auth', 'verified']],
    'goals' => ['goals.index', ['auth', 'verified']],
    'create goal' => ['goals.create', ['auth', 'verified']],
    'create goal milestone' => ['goals.milestones.create', ['auth', 'verified']],
    'habits' => ['habits.index', ['auth', 'verified']],
    'create habit' => ['habits.create', ['auth', 'verified']],
    'notifications' => ['notifications.inbox', ['auth', 'verified']],
    'todos' => ['todos.index', ['auth', 'verified']],
    'todo board' => ['todos.board', ['auth', 'verified']],
    'todo calendar' => ['todos.calendar', ['auth', 'verified']],
    'todo templates' => ['todos.templates', ['auth', 'verified']],
    'todo inbox' => ['todos.inbox', ['auth', 'verified']],
    'todo focus' => ['todos.focus', ['auth', 'verified']],
    'todo time' => ['todos.time', ['auth', 'verified']],
    'todo blocked' => ['todos.blocked', ['auth', 'verified']],
    'todo cleanup' => ['todos.cleanup', ['auth', 'verified']],
    'todo automations' => ['todos.automations', ['auth', 'verified']],
    'todo reminders' => ['todos.reminders', ['auth', 'verified']],
    'todo recurring' => ['todos.recurring', ['auth', 'verified']],
    'profile settings' => ['profile.edit', ['auth']],
    'appearance settings' => ['appearance.edit', ['auth', 'verified']],
    'security settings' => ['security.edit', ['auth', 'verified', 'password.confirm']],
    'setup status' => ['setup.status', ['auth', 'verified', 'password.confirm']],
    'maintenance center' => ['maintenance.center', ['auth', 'verified', 'password.confirm', 'can:access-maintenance-center']],
]);

test('demo login panel never exposes stored password hashes', function () {
    $demoUser = User::factory()->demoPrimary()->create();

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('data-test="demo-login-panel"', false)
        ->assertSee((string) config('demo.login_panel.password'))
        ->assertDontSee($demoUser->password, false);
});
