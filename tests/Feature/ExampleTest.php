<?php

test('shows the RuFlo landing page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSeeText(__('welcome.heading'))
        ->assertSeeText(__('welcome.features.heading'))
        ->assertSeeText(__('welcome.feature_groups.4.title'))
        ->assertSeeText(__('welcome.cta.heading'));
});
