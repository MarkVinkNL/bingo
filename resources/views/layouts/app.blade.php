<x-layouts::app.header :title="$title ?? null">
    <flux:main>
        <div class="mx-auto w-full max-w-7xl px-6 py-6 lg:px-8">
            {{ $slot }}
        </div>
    </flux:main>
    @auth
        <livewire:friendlist-button />
    @endauth
</x-layouts::app.header>
