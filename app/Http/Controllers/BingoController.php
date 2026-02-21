<?php

namespace App\Http\Controllers;

use App\Models\BingoCard;
use App\Models\BingoSubject;
use App\Services\BingoCardGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BingoController extends Controller
{
    public function __construct(
        private BingoCardGenerator $bingoCardGenerator
    ) {}

    /**
     * Generate or get card for the subject, then redirect to the card (one per subject per user).
     * Subject must exist and be active; user_id is always auth()->id() (never from client).
     */
    public function play(string $subject): RedirectResponse
    {
        $subjectModel = is_numeric($subject)
            ? BingoSubject::where('id', (int) $subject)->where('is_active', true)->firstOrFail()
            : BingoSubject::where('slug', $subject)->where('is_active', true)->firstOrFail();

        try {
            $card = $this->bingoCardGenerator->generateOrGetCard($subjectModel, (int) Auth::id());
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['subject' => $e->getMessage()]);
        }

        return redirect()->route('bingo.card', $card->uuid);
    }

    /**
     * View-only shared card. No auth required; access is via valid share token.
     */
    public function showShared(Request $request, string $uuid, string $token): View|RedirectResponse
    {
        $card = BingoCard::query()
            ->where('uuid', $uuid)
            ->with(['bingoCardCells.bingoCellValue', 'bingoSubject'])
            ->first();

        if ($card === null || ! $card->isValidShareToken($token)) {
            abort(404);
        }

        return view('bingo.shared-card', [
            'card' => $card,
        ]);
    }
}
