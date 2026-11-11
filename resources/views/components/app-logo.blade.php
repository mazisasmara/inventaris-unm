
@props([
    'sidebar' => false,
])

@if ($sidebar)
    <flux:sidebar.brand
        :name="config('app.name', 'Laravel')"
        {{ $attributes }}
        class="[&_.text-sm.font-medium]:!text-white"
    >
        <x-slot name="logo" class="!h-9 !w-9 !min-w-0 !overflow-visible">
            <img
                src="{{ asset('images/logo.jpeg') }}"
                alt="{{ config('app.name', 'Laravel') }}"
                class="h-full w-full object-contain"
            />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand
        :name="config('app.name', 'Laravel')"
        {{ $attributes }}
    >
        <x-slot name="logo" class="!h-9 !w-9 !min-w-0 !rounded-none !overflow-visible">
            <img
                src="{{ asset('images/logo.jpeg') }}"
                alt="{{ config('app.name', 'Laravel') }}"
                class="h-full w-full object-contain"
            />
        </x-slot>
    </flux:brand>
@endif
