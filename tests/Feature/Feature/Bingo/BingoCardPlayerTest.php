<?php

use App\Models\BingoCard;
use App\Models\BingoCellValue;
use App\Models\BingoSubject;
use App\Models\User;
use App\Services\BingoCardGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('guest is redirected to login when visiting card page', function () {
    $user = User::factory()->create();
    $subject = BingoSubject::factory()->create();
    BingoCellValue::factory()->count(5)->create(['bingo_subject_id' => $subject->id]);
    $card = app(BingoCardGenerator::class)->generateOrGetCard($subject, $user->id);

    $response = $this->get(route('bingo.card', $card->uuid));

    $response->assertRedirect(route('login'));
});

test('invalid UUID format returns 404', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('bingo.card', 'not-a-valid-uuid'));

    $response->assertNotFound();
});

test('non-existent card UUID returns 404', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $uuid = \Illuminate\Support\Str::uuid()->toString();

    $response = $this->get(route('bingo.card', $uuid));

    $response->assertNotFound();
});

test('owner can view card and sees subject name and grid', function () {
    $user = User::factory()->create();
    $subject = BingoSubject::factory()->create();
    BingoCellValue::factory()->count(5)->create(['bingo_subject_id' => $subject->id]);
    $card = app(BingoCardGenerator::class)->generateOrGetCard($subject, $user->id);
    $this->actingAs($user);

    $response = $this->get(route('bingo.card', $card->uuid));

    $response->assertOk();
    $response->assertSee($subject->name);
    $response->assertSee(__('Back to lobby'));
});

test('another user cannot view card and gets 403', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $subject = BingoSubject::factory()->create();
    BingoCellValue::factory()->count(5)->create(['bingo_subject_id' => $subject->id]);
    $card = app(BingoCardGenerator::class)->generateOrGetCard($subject, $owner->id);
    $this->actingAs($other);

    $response = $this->get(route('bingo.card', $card->uuid));

    $response->assertForbidden();
});

test('owner can toggle a cell', function () {
    $user = User::factory()->create();
    $subject = BingoSubject::factory()->create();
    BingoCellValue::factory()->count(5)->create(['bingo_subject_id' => $subject->id]);
    $card = app(BingoCardGenerator::class)->generateOrGetCard($subject, $user->id);
    $cell = $card->bingoCardCells()->where('position', 0)->first();
    expect($cell->is_marked)->toBeFalse();

    Livewire::actingAs($user)
        ->test(\App\Livewire\BingoCardPlayer::class, ['uuid' => $card->uuid])
        ->call('toggleCell', 0);

    expect($cell->fresh()->is_marked)->toBeTrue();
});

test('user can have multiple cards for different subjects', function () {
    $user = User::factory()->create();
    $subject1 = BingoSubject::factory()->create();
    $subject2 = BingoSubject::factory()->create();
    BingoCellValue::factory()->count(5)->create(['bingo_subject_id' => $subject1->id]);
    BingoCellValue::factory()->count(5)->create(['bingo_subject_id' => $subject2->id]);
    $generator = app(BingoCardGenerator::class);
    $card1 = $generator->generateOrGetCard($subject1, $user->id);
    $card2 = $generator->generateOrGetCard($subject2, $user->id);

    expect($card1->id)->not->toBe($card2->id)
        ->and(BingoCard::where('user_id', $user->id)->count())->toBe(2);

    $this->actingAs($user);
    $this->get(route('bingo.card', $card1->uuid))->assertOk();
    $this->get(route('bingo.card', $card2->uuid))->assertOk();
});
