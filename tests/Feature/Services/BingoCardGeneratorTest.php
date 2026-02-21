<?php

use App\Models\BingoCard;
use App\Models\BingoCellValue;
use App\Models\BingoSubject;
use App\Models\User;
use App\Services\BingoCardGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->generator = new BingoCardGenerator;
});

it('resolves grid size 3 for 1 to 8 cell values', function (int $count) {
    expect($this->generator->resolveGridSize($count))->toBe(3);
})->with([1, 2, 3, 4, 5, 6, 7, 8]);

it('resolves grid size 3 for 9 to 15 cell values', function (int $count) {
    expect($this->generator->resolveGridSize($count))->toBe(3);
})->with([9, 10, 15]);

it('resolves grid size 4 for 16 to 24 cell values', function (int $count) {
    expect($this->generator->resolveGridSize($count))->toBe(4);
})->with([16, 20, 24]);

it('resolves grid size 5 for 25 to 35 cell values', function (int $count) {
    expect($this->generator->resolveGridSize($count))->toBe(5);
})->with([25, 30, 35]);

it('resolves grid size 6 for 36 or more cell values', function (int $count) {
    expect($this->generator->resolveGridSize($count))->toBe(6);
})->with([36, 40, 100]);

it('selects exactly cellsNeeded IDs without replacement when subject has enough values', function () {
    $subject = BingoSubject::factory()->create();
    BingoCellValue::factory()->count(20)->create(['bingo_subject_id' => $subject->id]);

    $ids = $this->generator->selectCellValueIds($subject, 9);

    expect($ids)->toHaveCount(9)
        ->and($ids)->toEqual(array_values(array_unique($ids)));
});

it('selects exactly cellsNeeded IDs with replacement when subject has fewer values', function () {
    $subject = BingoSubject::factory()->create();
    BingoCellValue::factory()->count(5)->create(['bingo_subject_id' => $subject->id]);

    $ids = $this->generator->selectCellValueIds($subject, 9);

    expect($ids)->toHaveCount(9);
});

it('generates a new card with correct grid size and cells', function () {
    $user = User::factory()->create();
    $subject = BingoSubject::factory()->create();
    BingoCellValue::factory()->count(10)->create(['bingo_subject_id' => $subject->id]);

    $card = $this->generator->generateOrGetCard($subject, $user->id);

    expect($card)->toBeInstanceOf(BingoCard::class)
        ->and($card->user_id)->toBe($user->id)
        ->and($card->bingo_subject_id)->toBe($subject->id)
        ->and($card->grid_size)->toBe(3)
        ->and($card->bingoCardCells)->toHaveCount(9)
        ->and($card->uuid)->not->toBeEmpty();
});

it('returns existing card when user already has a card for subject', function () {
    $user = User::factory()->create();
    $subject = BingoSubject::factory()->create();
    BingoCellValue::factory()->count(10)->create(['bingo_subject_id' => $subject->id]);

    $first = $this->generator->generateOrGetCard($subject, $user->id);
    $second = $this->generator->generateOrGetCard($subject, $user->id);

    expect($second->id)->toBe($first->id)
        ->and(BingoCard::where('user_id', $user->id)->where('bingo_subject_id', $subject->id)->count())->toBe(1);
});

it('throws when subject has no cell values', function () {
    $user = User::factory()->create();
    $subject = BingoSubject::factory()->create();

    $this->generator->generateOrGetCard($subject, $user->id);
})->throws(\InvalidArgumentException::class, 'Subject must have at least 1 cell value.');

it('database unique constraint prevents duplicate card per user and subject', function () {
    $user = User::factory()->create();
    $subject = BingoSubject::factory()->create();
    BingoCellValue::factory()->count(5)->create(['bingo_subject_id' => $subject->id]);
    $this->generator->generateOrGetCard($subject, $user->id);

    $duplicate = new BingoCard([
        'bingo_subject_id' => $subject->id,
        'grid_size' => 3,
        'generated_at' => now(),
    ]);
    $duplicate->user_id = $user->id;

    expect(fn () => $duplicate->save())->toThrow(\Illuminate\Database\UniqueConstraintViolationException::class);
});
