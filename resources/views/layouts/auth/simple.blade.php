
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>

    <body class="min-h-dvh bg-white text-zinc-900 antialiased transition-colors dark:bg-zinc-950 dark:text-zinc-100">

        {{-- Full width authentication layout --}}
        <main class="min-h-dvh w-full">
            {{ $slot }}
        </main>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
