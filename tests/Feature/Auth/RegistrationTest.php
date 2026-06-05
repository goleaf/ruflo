<?php

use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('registration validates through the dedicated request rules', function () {
    User::factory()->create([
        'email' => 'taken@example.com',
    ]);

    $response = $this
        ->from(route('register'))
        ->post(route('register.store'), [
            'name' => '',
            'email' => 'taken@example.com',
            'password' => 'short',
            'password_confirmation' => 'different',
        ]);

    $response
        ->assertRedirect(route('register', absolute: false))
        ->assertSessionHasErrors([
            'name',
            'email' => __('auth.validation.email_unique'),
            'password',
        ]);
});
