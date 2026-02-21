<?php

use App\Models\BingoCellValue;
use App\Models\BingoSubject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('guest is redirected to login when visiting lobby', function () {
    $response = $this->get(route('bingo.index'));

    $response->assertRedirect(route('login'));
});

test('authenticated user can see lobby with active subjects', function () {
    $user = User::factory()->create();
    $subject = BingoSubject::factory()->create();
    BingoCellValue::factory()->count(5)->create(['bingo_subject_id' => $subject->id]);
    $this->actingAs($user);

    $response = $this->get(route('bingo.index'));

    $response->assertOk();
    $response->assertSee($subject->name);
    $response->assertSee('3×3');
    $response->assertSee(__('Play'));
});

test('lobby shows open card link when user has a card for subject', function () {
    $user = User::factory()->create();
    $subject = BingoSubject::factory()->create();
    BingoCellValue::factory()->count(5)->create(['bingo_subject_id' => $subject->id]);
    $this->actingAs($user);
    $card = app(\App\Services\BingoCardGenerator::class)->generateOrGetCard($subject, $user->id);

    $response = $this->get(route('bingo.index'));

    $response->assertOk();
    $response->assertSee($subject->name);
    $response->assertSee(__('Open your card'));
    $response->assertSee($card->uuid, false);
});

test('lobby shows my cards section when user has cards', function () {
    $user = User::factory()->create();
    $subject = BingoSubject::factory()->create();
    BingoCellValue::factory()->count(5)->create(['bingo_subject_id' => $subject->id]);
    $this->actingAs($user);
    app(\App\Services\BingoCardGenerator::class)->generateOrGetCard($subject, $user->id);

    Livewire::actingAs($user)
        ->test(\App\Livewire\BingoLobby::class)
        ->assertSee(__('My cards'))
        ->assertSee($subject->name);
});

test('lobby shows empty state when no active subjects', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('bingo.index'));

    $response->assertOk();
    $response->assertSee(__('No active subjects yet.'));
});
