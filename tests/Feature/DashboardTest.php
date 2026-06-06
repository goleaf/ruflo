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
        ->assertSeeText('npx ruflo@latest mcp start')
        ->assertSeeText('/plugin marketplace add ruvnet/ruflo');
});
