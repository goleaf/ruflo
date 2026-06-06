<?php

use App\Actions\Auth\ListDemoLoginUsers;
use App\Data\Auth\DemoLoginUser;
use App\Models\User;

function seedAuthLoginUxDemoUsers(): void
{
    User::factory()->demoPrimary()->create();
    User::factory()->demoSecondary()->create();
}

test('demo login action returns only seeded users in safe environments', function () {
    seedAuthLoginUxDemoUsers();

    $demoUsers = app(ListDemoLoginUsers::class)();

    expect($demoUsers)->toHaveCount(2)
        ->and($demoUsers[0])->toBeInstanceOf(DemoLoginUser::class)
        ->and($demoUsers[0]->email)->toBe('test@example.com')
        ->and($demoUsers[0]->password)->toBe('password')
        ->and($demoUsers[1]->email)->toBe('second@example.com')
        ->and(User::query()->where('email', 'test@example.com')->firstOrFail()->is_admin)->toBeTrue()
        ->and(User::query()->where('email', 'second@example.com')->firstOrFail()->is_admin)->toBeFalse();
});

test('demo login panel is rendered for seeded users in safe environments', function () {
    seedAuthLoginUxDemoUsers();
    $primaryName = (string) config('demo.login_panel.users.0.name', 'Test User');

    $this->get(route('login'))
        ->assertOk()
        ->assertSee(__('auth.demo.heading'))
        ->assertSee(__('auth.demo.description'))
        ->assertSee('test@example.com')
        ->assertSee('second@example.com')
        ->assertSee('password')
        ->assertSee(__('auth.demo.users.test.role'))
        ->assertSee(__('auth.demo.users.test.description'))
        ->assertSee(__('auth.demo.users.second.role'))
        ->assertSee(__('auth.demo.users.second.description'))
        ->assertSee(__('auth.demo.quick_login', ['name' => $primaryName]))
        ->assertSee('data-test="demo-login-panel"', false)
        ->assertSee('data-test="demo-login-button"', false);
});

test('seeded demo credentials authenticate through Fortify login', function (string $email) {
    seedAuthLoginUxDemoUsers();

    $response = $this->post(route('login.store'), [
        'email' => $email,
        'password' => (string) config('demo.login_panel.password'),
        'remember' => '1',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
    expect(auth()->user()->email)->toBe($email);
})->with([
    'primary demo user' => 'test@example.com',
    'secondary demo user' => 'second@example.com',
]);

test('guests are redirected from private pages to login', function (string $routeName) {
    $this->get(route($routeName))
        ->assertRedirect(route('login'));
})->with([
    'dashboard' => 'dashboard',
    'todos' => 'todos.index',
    'profile settings' => 'profile.edit',
]);

test('demo login panel is hidden when users have not been seeded', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertDontSee(__('auth.demo.heading'))
        ->assertDontSee('data-test="demo-login-panel"', false);
});

test('demo login panel is hidden outside safe environments', function () {
    config(['app.env' => 'production']);

    seedAuthLoginUxDemoUsers();

    expect(app(ListDemoLoginUsers::class)())->toBe([]);

    $this->get(route('login'))
        ->assertOk()
        ->assertDontSee(__('auth.demo.heading'))
        ->assertDontSee('test@example.com')
        ->assertDontSee(__('auth.demo.users.test.role'))
        ->assertDontSee('data-test="demo-login-panel"', false);
});

test('demo login panel can be disabled by configuration', function () {
    config(['demo.login_panel.enabled' => false]);

    seedAuthLoginUxDemoUsers();

    expect(app(ListDemoLoginUsers::class)())->toBe([]);

    $this->get(route('login'))
        ->assertOk()
        ->assertDontSee(__('auth.demo.heading'))
        ->assertDontSee('data-test="demo-login-panel"', false);
});
