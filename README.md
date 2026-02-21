# Bingo

Laravel application with Livewire, Filament, and Flux UI.

## Requirements

- **PHP** 8.2 or higher
- **Composer**
- **Node.js** 18+ and npm

## Installation

1. **Clone the repository**

   ```bash
   git clone <repository-url>
   cd bingo
   ```

2. **Install dependencies and set up the app**

   ```bash
   composer setup
   ```

   This will:

   - Install PHP dependencies
   - Copy `.env.example` to `.env` if missing
   - Generate an application key
   - Run database migrations
   - Install npm dependencies and build assets

3. **Configure environment (optional)**

   Edit `.env` if you need to change the app URL, database, or other settings. The default uses SQLite (`database/database.sqlite`).

   To set the seeded super admin login for the Filament panel (`/admin`), set:

   - `SEED_SUPERADMIN_EMAIL` — e.g. `admin@example.com`
   - `SEED_SUPERADMIN_PASSWORD` — e.g. `password`

4. **Seed the database (optional)**

   ```bash
   php artisan db:seed
   ```

   This creates the super admin user (from `SEED_SUPERADMIN_EMAIL` and `SEED_SUPERADMIN_PASSWORD` in `.env`) and bingo subjects with cell values. Log in at [http://localhost:8000/admin](http://localhost:8000/admin) with the super admin credentials.

5. **Run the development server**

   ```bash
   composer dev
   ```

   This starts the Laravel server, queue worker, and Vite dev server. Open [http://localhost:8000](http://localhost:8000) in your browser.

## Seeding

To seed or re-seed the database:

```bash
php artisan db:seed
```

Only the super admin user and bingo subjects are seeded (no other users). The super admin is created or updated from your `.env`:

- `SEED_SUPERADMIN_EMAIL` — login email for the Filament panel
- `SEED_SUPERADMIN_PASSWORD` — login password

Use these credentials to access the admin panel at `/admin`.

## Other commands

- **Tests:** `composer test`
- **Lint:** `composer lint`
- **PHPStan:** `composer phpstan`
