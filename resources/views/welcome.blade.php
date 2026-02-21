<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ config('app.name') }}</title>
  <link rel="icon" href="/favicon.ico" sizes="any">
  <link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <link rel="apple-touch-icon" href="/apple-touch-icon.png">
  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body
  class="min-h-dvh flex flex-col items-center justify-center bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 antialiased">
  <main class="flex flex-col items-center gap-10">
    <h1 class="text-5xl sm:text-6xl font-semibold tracking-tight">BINGO</h1>
    <div class="flex flex-col sm:flex-row items-center gap-3">
      @auth
        <a href="{{ route('bingo.index') }}"
          class="inline-flex items-center justify-center px-6 py-2.5 rounded-lg bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 font-medium text-sm hover:opacity-90 transition-opacity">
          Go to Bingo
        </a>
      @else
        @if (Route::has('login'))
          <a href="{{ route('login') }}"
            class="inline-flex items-center justify-center px-6 py-2.5 rounded-lg border border-zinc-300 dark:border-zinc-600 font-medium text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
            Log in
          </a>
        @endif
        @if (Route::has('register'))
          <a href="{{ route('register') }}"
            class="inline-flex items-center justify-center px-6 py-2.5 rounded-lg bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 font-medium text-sm hover:opacity-90 transition-opacity">
            Register
          </a>
        @endif
      @endauth
    </div>
  </main>
</body>

</html>
