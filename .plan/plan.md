# Bingo Game – Development Plan

**Stack:** Laravel 12, Livewire 4, Filament PHP 5, Flux UI

---

## 1. Setup & Dependencies

### 1.1 Install Filament PHP 5

```bash
composer require filament/filament:"^5.0"
php artisan filament:install --panels
```

- Create admin panel at `/admin`
- Configure authentication (use existing Fortify/User model)
- **Restrict panel to admins only:** Allow access only for users with role `admin` or `superadmin` (§1.3); use Filament auth guard or middleware so players cannot access `/admin`
- Optionally add Filament user resource for admin users

### 1.2 Frontend auth: Laravel Socialite

- **Purpose:** Let users log in via social providers so cards are tied to their account and persist across devices/sessions.
- **Install:** `composer require laravel/socialite`
- **Config:** Add provider credentials in `.env` (e.g. `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GITHUB_*`). Configure in `config/services.php`.
- **User model:** Ensure `User` can store provider id/token if needed, or use Socialite only to authenticate and match by email (create user on first login).
- **Routes:** `GET /auth/{provider}/redirect` and `GET /auth/{provider}/callback` (or use a package like Laravel Socialite with Fortify/Socialite providers).
- **Provider whitelist:** Validate `{provider}` against a fixed list (e.g. `['google', 'github']`) before calling `Socialite::driver($provider)`. Reject unknown values with 404/400 to avoid open redirect or invalid driver abuse.
- **OAuth state:** Rely on Socialite’s built-in state generation/verification for the OAuth flow; do not disable it (CSRF protection for the callback).
- **Session regeneration:** After successful Socialite login, call `$request->session()->regenerate()` to prevent session fixation.
- **Rate limiting:** Apply throttle middleware to `/auth/*` routes (e.g. `throttle:6,1` per minute) to limit redirect/callback abuse.
- **Frontend:** Login/logout buttons on bingo pages; protect bingo routes with `auth` middleware so only logged-in users can generate and view their cards. Optionally allow guests to play with session-only cards (no persistence).
- **Decision:** Either (a) require auth for all bingo features and drop session-only cards, or (b) allow guests with session cards and show “Log in to keep your cards” with Socialite buttons. Plan assumes (a) for simplicity: frontend bingo requires auth; cards are always tied to `user_id`.

### 1.3 User roles

Three roles control access to the app. Use a `role` column on `users` (string or enum); default for new users (e.g. Socialite first login) is **player**.

| Role | Frontend bingo (lobby, cards) | Filament admin panel |
|------|------------------------------|----------------------|
| **player** | Yes – can play, generate and view own cards | No access |
| **admin** | Yes (optional; can play like a player) | Yes – manage subjects, cell values, view cards; manage users if you add a User resource |
| **superadmin** | Yes (optional) | Yes – full access; only role that can assign or change roles (e.g. promote admin, demote user), delete admins, or change app-wide settings if you add them later |

- **Implementation:** PHP enum `UserRole` (e.g. `Superadmin`, `Admin`, `Player`) or string column; cast on `User` model. Filament panel: allow access only when `user->role` is `admin` or `superadmin`. Superadmin-only actions (e.g. role assignment) can be gated in a Filament User resource or settings.
- **Seeding:** Create at least one superadmin user (e.g. in a seeder or manually) so the panel is accessible after install.

---

## 2. Database Schema

### 2.1 Models & Migrations

| Model | Purpose |
|-------|---------|
| `BingoSubject` | Bingo themes (e.g. "Movies", "Animals") |
| `BingoCellValue` | Possible cell values per subject (e.g. "Star Wars", "Lion") |
| `BingoCard` | Generated card instance |
| `BingoCardCell` | Individual cells on a card (value + marked state) |

### 2.2 Migration Details

**users (add column)**
- Add `role` (string, default `player`): one of `superadmin`, `admin`, `player`. Use a Laravel enum and cast on `User`, or validate in app. Index for filtering (e.g. list admins).

**bingo_subjects**
- `id`, `name` (string), `slug` (string, unique), `is_active` (boolean), `timestamps`

**bingo_cell_values**
- `id`, `bingo_subject_id` (FK), `value` (string), `sort_order` (int, nullable), `timestamps`
- Unique constraint: `(bingo_subject_id, value)`

**bingo_cards**
- `id`, `uuid` (string, unique, for public URLs), `bingo_subject_id` (FK), `user_id` (FK, nullable for transition; frontend auth implies required)
- `grid_size` (int: 3–6, represents N×N), `generated_at` (timestamp), `timestamps`
- Unique index: `(user_id, bingo_subject_id)` so each user has at most one card per subject
- Index on `user_id` for “my cards” list; use `uuid` in card URL for safe, shareable links

**bingo_card_cells**
- `id`, `bingo_card_id` (FK), `bingo_cell_value_id` (FK), `position` (int: 0 to N²-1)
- `is_marked` (boolean, default false), `marked_at` (timestamp, nullable), `timestamps`
- Unique: `(bingo_card_id, position)`

### 2.3 Relationships

- `BingoSubject` hasMany `BingoCellValue`
- `BingoSubject` hasMany `BingoCard`
- `BingoCard` belongsTo `BingoSubject`, belongsTo `User`
- `BingoCard` hasMany `BingoCardCell` (ordered by `position`)
- `BingoCardCell` belongsTo `BingoCard`, belongsTo `BingoCellValue`

---

## 3. Backend (Filament Admin Panel)

### 3.1 BingoSubject Resource

- **List:** Table with name, slug, cell count, active status
- **Form:** name, slug (auto from name), is_active
- **Relation Manager:** `BingoCellValuesRelationManager` for managing cell values
  - Repeater or table: value, sort_order
  - Bulk add (e.g. paste multiple values)
- **Validation:** Subject needs at least 1 cell value (cards with fewer than 9 values use duplicates; see §4.1)

### 3.2 BingoCellValue Relation Manager

- Inline table: value, sort_order
- Ability to reorder (e.g. drag-and-drop or sort_order field)
- Validation: unique value per subject

### 3.3 BingoCard Resource

- **List:** Table with columns:
  - UUID (link to view), Subject name, Grid size (e.g. "4×4"), User (owner), Status (optional), Generated at
- **View:** Read-only display of the card grid with cell values and marked state (resolve by uuid)
- **Filters:** By subject, by date, by user, by status
- No create/edit from admin (cards are generated on frontend only)

---

## 4. Card Generation Logic

### 4.1 Grid Size and Cell Selection

- **Minimum:** Subject must have at least **1** cell value. If fewer than 9 values, the card still uses a 3×3 grid and **duplicate cell values** are allowed (same value can appear multiple times).
- **Grid size** (when `$cellCount >= 9`): same as before: `$gridSize = min(6, max(3, (int) floor(sqrt($cellCount))))`, then `$cellsNeeded = $gridSize * $gridSize`.

| Cell count | Grid size | How values are chosen |
|------------|-----------|------------------------|
| 1–8        | 3×3 (9 cells) | Sample 9 with **replacement** (duplicates allowed) |
| 9–15       | 3×3 (9 cells) | Sample 9 without replacement |
| 16–24      | 4×4 (16 cells) | Sample 16 without replacement |
| 25–35      | 5×5 (25 cells) | Sample 25 without replacement |
| 36+        | 6×6 (36 cells) | Sample 36 without replacement |

- **Implementation:** If `$cellCount >= $cellsNeeded` use `->inRandomOrder()->limit($cellsNeeded)`. If `$cellCount < $cellsNeeded` (e.g. 5 values for 3×3), fetch all cell value IDs, then pick `$cellsNeeded` at random with replacement (e.g. `random_element` in a loop or `Arr::random($ids, $cellsNeeded)` with duplicates allowed).

### 4.2 One card per subject per user

- Before creating a new card, check: does the current user already have a `BingoCard` for this subject?
- **If yes:** Redirect to that existing card (do not create a second one).
- **If no:** Create the new card and redirect to it.
- Enforce in `BingoCardGenerator` and/or with unique index `(user_id, bingo_subject_id)`.

### 4.3 Generation Steps

1. **Validate subject:** Resolve subject by id/slug; ensure it exists and `is_active` is true. Use e.g. `BingoSubject::where('id', $id)->where('is_active', true)->firstOrFail()`. User may only create a card for themselves (no “create for user X”).
2. Load subject with cell values; ensure `count >= 1`
3. Resolve grid size and `$cellsNeeded` (§4.1); if `$cellCount < 9`, use 3×3 and sampling with replacement
4. Select `$cellsNeeded` cell value IDs (with or without replacement as above)
5. Create `BingoCard` with `uuid` (e.g. `Str::uuid()`), **`user_id` from `auth()->id()` only — never accept `user_id` from the client;** set `grid_size`. Enforce one card per subject (§4.2). Use `$fillable` / `$guarded` so `user_id` is not mass-assignable from input.
6. Create `BingoCardCell` records with `position` 0..N²-1 and `is_marked = false`
7. Redirect to card view (e.g. `route('bingo.card', $bingoCard->uuid)`)

---

## 5. Frontend (Public)

### 5.1 Authentication (Socialite)

- Frontend bingo routes require `auth` middleware so cards are always tied to a user.
- Login page (or inline on lobby): Social login buttons (e.g. “Log in with Google”, “Log in with GitHub”) using Socialite redirect/callback.
- After login, user can create and view their cards; cards persist in DB by `user_id`.

### 5.2 Subject Selection Page (Lobby)

- **Route:** `GET /` or `GET /bingo` (auth required)
- **Livewire component:** `BingoSubjectSelector` or `BingoLobby`
- List active subjects (Flux UI cards/buttons)
- Each subject shows: name, grid size preview (3×3 to 6×6 from cell count), and whether the user **already has a card** for this subject
- **“Play” / “Generate card”:** Creates card if none exists for that subject, or redirects to existing card (one card per subject per user)
- Optional: “My cards” link listing the user’s existing cards (one per subject) with links to play

### 5.3 Card Display Page

- **Route:** `GET /bingo/card/{uuid}` (auth required). Validate `uuid` format (e.g. route constraint or UUID v4 regex) so invalid values return 404.
- **Livewire component:** `BingoCardPlayer`
- **Authorization:** Use a `BingoCardPolicy` (`view`, `update`). On page load: resolve card by `uuid`, then `$this->authorize('view', $bingoCard)`. On toggle cell: resolve card (by uuid from component state), then `$this->authorize('update', $bingoCard)` before updating `is_marked`. Never trust card id from the client without ownership check.
- **Toggle cell validation:** When marking a cell, validate that `position` is in range `0 .. grid_size² - 1` for that card, or that the cell id belongs to a cell of that card (and card belongs to current user).
- Resolve card by `uuid`; ensure ownership (policy above)
- Display N×N grid of cells (Flux UI); each cell: value text, click to toggle `is_marked`
- Optional: “Bingo!” when a line/column/diagonal is complete
- “Back to lobby” / “My cards” to choose another subject or existing card
- **404 vs 403:** Prefer 404 when card not found; 403 when found but not owner (avoids leaking card existence if desired).

### 5.4 Multiple cards, one per subject

- User can have **multiple cards in total** (one per subject). No limit on number of subjects.
- Generating for a subject the user already has a card for does not create a new card; redirect to the existing card for that subject.

---

## 6. Implementation Order

1. **Migrations & models** – Add `role` to `users`; all bingo tables including `uuid` and unique `(user_id, bingo_subject_id)` on `bingo_cards`; `UserRole` enum; factories, seeders; seed at least one superadmin
2. **Laravel Socialite** – Install, configure providers (e.g. Google/GitHub), auth routes with provider whitelist, session regenerate, state verification, rate limit on `/auth/*`; protect bingo routes with `auth`
3. **Filament panel** – Install and configure Filament 5; restrict panel to `admin` and `superadmin` roles (§1.3)
4. **BingoSubject resource** – CRUD + relation manager for cell values (min 1 value)
5. **BingoCard resource** – List (by user, subject, uuid) and view only
6. **Card generation service** – `BingoCardGenerator`: grid size + sampling with/without replacement; one card per subject (reuse or create); return existing or new card
7. **Frontend: lobby** – Subject selection Livewire component; show “Play” or “You have a card” per subject; “My cards” optional
8. **Frontend: generate or open card** – Livewire action: ensure one per subject, redirect to card by uuid
9. **Frontend: card display** – Resolve by uuid, validate UUID format; `BingoCardPolicy` (view/update) on load and on toggle; validate position/cell; Livewire component with markable cells
10. **Security** – Ensure `user_id` never from request; rate limiting on auth and optionally on generate/mark; 404/403 behaviour for card access
11. **Tests** – Feature tests for generation (with/without replacement, one per subject), marking, auth, policy; Filament resource tests

---

## 7. Optional Enhancements (Post-MVP)

- **Bingo detection:** Check rows, columns, diagonals when marking
- **Card sharing:** Shareable link (by uuid) or QR code; optional “view only” for non-owners
- **Caller mode:** Admin draws values one by one (separate feature)
- **Export/print:** PDF or print-friendly view of the card

---

## 8. File Structure (New Files)

```
app/
├── Enums/
│   └── UserRole.php             # Superadmin, Admin, Player
├── Http/Controllers/Auth/
│   └── SocialiteController.php   # redirect + callback for Socialite
├── Models/
│   ├── BingoSubject.php
│   ├── BingoCellValue.php
│   ├── BingoCard.php
│   └── BingoCardCell.php
├── Policies/
│   └── BingoCardPolicy.php
├── Services/
│   └── BingoCardGenerator.php
├── Livewire/
│   ├── BingoSubjectSelector.php
│   └── BingoCardPlayer.php
app/Filament/Resources/
├── BingoSubjectResource.php
├── BingoSubjectResource/
│   └── RelationManagers/
│       └── BingoCellValuesRelationManager.php
└── BingoCardResource.php
database/
├── migrations/
│   ├── add_role_to_users_table.php
│   ├── create_bingo_subjects_table.php
│   ├── create_bingo_cell_values_table.php
│   ├── create_bingo_cards_table.php
│   └── create_bingo_card_cells_table.php
├── factories/
│   ├── BingoSubjectFactory.php
│   ├── BingoCellValueFactory.php
│   ├── BingoCardFactory.php
│   └── BingoCardCellFactory.php
└── seeders/
    └── BingoSeeder.php
resources/views/livewire/
├── bingo-subject-selector.blade.php
└── bingo-card-player.blade.php
```

---

## 9. Routes Summary

| Method | URI | Name | Purpose |
|--------|-----|------|---------|
| GET | `/auth/{provider}/redirect` | – | Socialite redirect (whitelist provider; rate limited) |
| GET | `/auth/{provider}/callback` | – | Socialite callback (rate limited; session regenerate on success) |
| GET | `/` or `/bingo` | bingo.index | Lobby / subject selection (auth) |
| GET | `/bingo/card/{uuid}` | bingo.card | View/play card (auth; policy: owner only) |

Card generation is a Livewire action from the lobby (no dedicated POST route). Admin: Filament at `/admin`.

---

## 10. Security Checklist

- **Socialite:** Whitelist provider name; use OAuth state; regenerate session on login; rate limit `/auth/*`.
- **Filament:** Restrict `/admin` to `admin` and `superadmin` roles only (§1.1, §1.3).
- **Card ownership:** Use `BingoCardPolicy` on view and on every update (e.g. toggle cell); resolve card by uuid then authorize.
- **Input validation:** UUID format on route/load; subject exists and active when generating; position/cell in range when marking.
- **Mass assignment:** Set `user_id` server-side only; do not accept from request; protect via `$fillable`/`$guarded` (§4.3).
- **Rate limiting:** Auth routes; optionally throttle card generation or toggle-mark actions per user.
- **Card sharing (post-MVP):** If adding “view only” for non-owners, use a separate share token or permission rather than overloading the same UUID.
