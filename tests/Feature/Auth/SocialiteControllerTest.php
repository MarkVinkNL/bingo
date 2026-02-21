<?php

test('redirect rejects unknown provider with 404', function (): void {
    $response = $this->get(route('auth.socialite.redirect', ['provider' => 'unknown']));

    $response->assertStatus(404);
});

test('redirect rejects invalid provider name with 404', function (): void {
    $response = $this->get(route('auth.socialite.redirect', ['provider' => 'evil/redirect']));

    $response->assertStatus(404);
});

test('callback rejects unknown provider with 404', function (): void {
    $response = $this->get(route('auth.socialite.callback', ['provider' => 'unknown']));

    $response->assertStatus(404);
});
