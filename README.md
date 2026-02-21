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

4. **Run the development server**

   ```bash
   composer dev
   ```

   This starts the Laravel server, queue worker, and Vite dev server. Open [http://localhost:8000](http://localhost:8000) in your browser.

## Other commands

- **Tests:** `composer test`
- **Lint:** `composer lint`
- **PHPStan:** `composer phpstan`
