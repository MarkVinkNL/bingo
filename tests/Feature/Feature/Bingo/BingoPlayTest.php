<?php

use App\Models\BingoCard;
use App\Models\BingoCellValue;
use App\Models\BingoSubject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest is redirected to login when playing a subject', function () {
    $subject = BingoSubject::factory()->create();
    BingoCellValue::factory()->count(5)->create(['bingo_subject_id' => $subject->id]);

    $response = $this->get(route('bingo.play', $subject->slug));

    $response->assertRedirect(route('login'));
});

test('guest visiting bingo play has intended URL stored for redirect after login', function () {
    $subject = BingoSubject::factory()->create();
    BingoCellValue::factory()->count(5)->create(['bingo_subject_id' => $subject->id]);

    $response = $this->get(route('bingo.play', $subject->slug));

    $response->assertRedirect(route('login'));
    $this->assertNotNull(session('url.intended'));
    expect(session('url.intended'))->toContain('/bingo/play/');
});

test('authenticated user is redirected to card when playing a subject', function () {
    $user = User::factory()->create();
    $subject = BingoSubject::factory()->create();
    BingoCellValue::factory()->count(5)->create(['bingo_subject_id' => $subject->id]);
    $this->actingAs($user);

    $response = $this->get(route('bingo.play', $subject->slug));

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('/bingo/card/');
    $card = BingoCard::where('user_id', $user->id)->where('bingo_subject_id', $subject->id)->first();
    expect($card)->not->toBeNull()
        ->and($response->headers->get('Location'))->toEndWith($card->uuid);
});

test('playing same subject again redirects to existing card', function () {
    $user = User::factory()->create();
    $subject = BingoSubject::factory()->create();
    BingoCellValue::factory()->count(5)->create(['bingo_subject_id' => $subject->id]);
    $this->actingAs($user);

    $first = $this->get(route('bingo.play', $subject->slug));
    $second = $this->get(route('bingo.play', $subject->slug));

    $first->assertRedirect();
    $second->assertRedirect();
    expect($first->headers->get('Location'))->toBe($second->headers->get('Location'));
    expect(BingoCard::where('user_id', $user->id)->where('bingo_subject_id', $subject->id)->count())->toBe(1);
});

test('play by subject id resolves and redirects to card', function () {
    $user = User::factory()->create();
    $subject = BingoSubject::factory()->create();
    BingoCellValue::factory()->count(5)->create(['bingo_subject_id' => $subject->id]);
    $this->actingAs($user);

    $response = $this->get(route('bingo.play', (string) $subject->id));

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('/bingo/card/');
});

test('play returns 404 for inactive subject', function () {
    $user = User::factory()->create();
    $subject = BingoSubject::factory()->inactive()->create();
    BingoCellValue::factory()->count(5)->create(['bingo_subject_id' => $subject->id]);
    $this->actingAs($user);

    $response = $this->get(route('bingo.play', $subject->slug));

    $response->assertNotFound();
    expect(BingoCard::where('user_id', $user->id)->where('bingo_subject_id', $subject->id)->count())->toBe(0);
});

test('play redirects back with error when subject has no cell values', function () {
    $user = User::factory()->create();
    $subject = BingoSubject::factory()->create();
    $this->actingAs($user);
    $this->from(route('bingo.index'));

    $response = $this->get(route('bingo.play', $subject->slug));

    $response->assertRedirect(route('bingo.index'));
    $response->assertSessionHasErrors('subject');
    expect(BingoCard::where('user_id', $user->id)->where('bingo_subject_id', $subject->id)->count())->toBe(0);
});

test('play returns 404 for non-existent subject', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('bingo.play', 'non-existent-slug'));

    $response->assertNotFound();
});
