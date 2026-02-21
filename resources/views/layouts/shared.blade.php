<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  @include('partials.head', ['title' => trim(view()->yieldContent('title')) ?: config('app.name')])
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
  <div class="mx-auto min-h-screen max-w-4xl px-4 py-8">
    @yield('content')
  </div>
  @fluxScripts
</body>

</html>
