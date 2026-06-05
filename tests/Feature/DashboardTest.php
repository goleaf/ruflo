<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText('RuFlo Control Deck')
        ->assertSeeText('npx ruflo@latest mcp start')
        ->assertSeeText('/plugin marketplace add ruvnet/ruflo');
});
