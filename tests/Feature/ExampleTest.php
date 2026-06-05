<?php

test('shows the RuFlo guide on the home page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSeeText('RuFlo')
        ->assertSeeText('npx ruflo@latest init wizard')
        ->assertSeeText('claude mcp add ruflo -- npx ruflo@latest mcp start')
        ->assertSeeText('/plugin marketplace add ruvnet/ruflo');
});
