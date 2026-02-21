<?php

use App\Models\User;

test('guests are redirected to login when visiting lobby', function () {
    $response = $this->get(route('bingo.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the lobby', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('bingo.index'));
    $response->assertOk();
});
