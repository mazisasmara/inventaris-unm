<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="flex min-h-screen flex-col">

        {{-- HEADER DESKTOP --}}
        <header class="hidden lg:flex items-center justify-between border-b border-zinc-200 px-8 py-2 dark:border-zinc-700"
                style="margin-bottom: 15px;">

            <div>
                <h1 class="text-xl font-semibold text-zinc-900 dark:text-white">
                    {{ $title ?? 'Dashboard' }}
                </h1>

                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Sistem Informasi Inventaris dan Peminjaman Laboratorium
                </p>
            </div>

            @auth
                <div class="text-right">
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">
                        {{ auth()->user()->name }}
                    </p>

                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ ucfirst(auth()->user()->role) }}
                    </p>
                </div>
            @else
                <div>
                    <a href="{{ route('login') }}"
                       class="text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400">
                        Masuk ke Sistem
                    </a>
                </div>
            @endauth

        </header>

        {{-- KONTEN HALAMAN --}}
        <div class="flex-1">
            {{ $slot }}
        </div>

        {{-- FOOTER --}}
        <footer class="mt-8 border-t border-neutral-200 px-6 py-4 text-center text-xs text-neutral-500 dark:border-neutral-700 dark:text-neutral-400">
            &copy; {{ date('Y') }} Inventaris Lab UNM. Seluruh hak cipta dilindungi.
        </footer>

    </flux:main>
</x-layouts::app.sidebar>