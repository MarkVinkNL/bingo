<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
  <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
    <flux:navbar class="-mb-px">
      <flux:navbar.item icon="layout-grid" :href="route('bingo.index')" :current="request()->routeIs('bingo.*')"
        wire:navigate>
        {{ __('Bingo') }}
      </flux:navbar.item>
    </flux:navbar>

    <flux:spacer />

    <x-desktop-user-menu />
  </flux:header>

  {{ $slot }}

  @fluxScripts
</body>

</html>
