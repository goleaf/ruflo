<?php

use App\Actions\Auth\ListDemoLoginUsers;
use App\Data\Auth\DemoLoginUser;
use App\Models\User;

function seedAuthLoginUxDemoUsers(): void
{
    foreach (config('demo.login_panel.users') as $demoUser) {
        User::factory()->create([
            'name' => $demoUser['name'],
            'email' => $demoUser['email'],
            'password' => (string) config('demo.login_panel.password'),
        ]);
    }
}

test('demo login action returns only seeded users in safe environments', function () {
    seedAuthLoginUxDemoUsers();

    $demoUsers = app(ListDemoLoginUsers::class)();

    expect($demoUsers)->toHaveCount(2)
        ->and($demoUsers[0])->toBeInstanceOf(DemoLoginUser::class)
        ->and($demoUsers[0]->email)->toBe('test@example.com')
        ->and($demoUsers[0]->password)->toBe('password')
        ->and($demoUsers[1]->email)->toBe('second@example.com');
});

test('demo login panel is rendered for seeded users in safe environments', function () {
    seedAuthLoginUxDemoUsers();

    $this->get(route('login'))
        ->assertOk()
        ->assertSee(__('auth.demo.heading'))
        ->assertSee(__('auth.demo.description'))
        ->assertSee('test@example.com')
        ->assertSee('second@example.com')
        ->assertSee('password')
        ->assertSee(__('auth.demo.quick_login', ['name' => 'Test User']))
        ->assertSee('data-test="demo-login-panel"', false)
        ->assertSee('data-test="demo-login-button"', false);
});

test('quick demo credentials authenticate through Fortify login', function () {
    seedAuthLoginUxDemoUsers();

    $response = $this->post(route('login.store'), [
        'email' => 'test@example.com',
        'password' => (string) config('demo.login_panel.password'),
        'remember' => '1',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
    expect(auth()->user()->email)->toBe('test@example.com');
});

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
        ->assertDontSee('Primary demo workspace')
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
