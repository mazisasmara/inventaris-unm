
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>

    <body class="min-h-screen bg-white dark:bg-zinc-800">

        @php
            $user = auth()->user();
            $role = $user->role;
            $isAdmin = in_array($role, ['admin', 'superadmin']);
            $isSuperadmin = $role === 'superadmin';
        @endphp

        {{-- SIDEBAR --}}
        <flux:sidebar sticky collapsible="mobile"
            class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">

            <flux:sidebar.header>
                <x-app-logo :sidebar="true"
                    href="{{ route('dashboard') }}"
                    wire:navigate />

                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>

                {{-- MENU UTAMA --}}
                <flux:sidebar.group heading="Menu Utama" class="grid">

                    <flux:sidebar.item
                        icon="home"
                        :href="$isAdmin ? route('admin.dashboard') : route('dashboard')"
                        :current="request()->routeIs('dashboard', 'admin.dashboard')"
                        wire:navigate>
                        Dashboard
                    </flux:sidebar.item>

                </flux:sidebar.group>

                @if ($isAdmin)

                    {{-- MENU ADMIN --}}
                    <flux:sidebar.group heading="Manajemen Inventaris" class="grid">

                        <flux:sidebar.item
                            :href="route('admin.bahan.index')"
                            :current="request()->routeIs('admin.bahan.*')"
                            wire:navigate
                        >
                            Data Bahan
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            :href="route('admin.alat.index')"
                            :current="request()->routeIs('admin.alat.*')"
                            wire:navigate
                        >
                            Data Alat
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            :href="route('admin.unit-alat.index')"
                            :current="request()->routeIs('admin.unit-alat.*')"
                            wire:navigate
                        >
                            Unit Inventaris
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            :href="route('admin.laboratorium.index')"
                            :current="request()->routeIs('admin.laboratorium.*')"
                            wire:navigate
                        >
                            Laboratorium
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            :href="route('admin.jadwal-laboratorium.index')"
                            :current="request()->routeIs('admin.jadwal-laboratorium.*')"
                            wire:navigate
                        >
                            Jadwal Laboratorium
                        </flux:sidebar.item>

                    </flux:sidebar.group>

                    <flux:sidebar.group heading="Transaksi" class="grid">

                        <flux:sidebar.item
                            :href="route('admin.pengambilan-bahan.index')"
                            :current="request()->routeIs('admin.pengambilan-bahan.*')"
                            wire:navigate>
                            Pengambilan Bahan
                        </flux:sidebar.item>

                        <flux:sidebar.item href="#">
                            Peminjaman Alat
                        </flux:sidebar.item>

                        <flux:sidebar.item href="#">
                            Peminjaman Lab
                        </flux:sidebar.item>

                        <flux:sidebar.item href="#">
                            Pengembalian Alat
                        </flux:sidebar.item>

                    </flux:sidebar.group>

                    <flux:sidebar.group heading="Laporan" class="grid">

                        <flux:sidebar.item href="#">
                            Riwayat Transaksi
                        </flux:sidebar.item>

                    </flux:sidebar.group>

                @else

                    {{-- MENU MAHASISWA DAN DOSEN --}}
                    <flux:sidebar.group heading="Layanan" class="grid">

                        <flux:sidebar.item href="#">
                            Lihat Inventaris
                        </flux:sidebar.item>

                        @if ($role === 'mahasiswa')
                            <flux:sidebar.item
                                :href="route('mahasiswa.pengambilan-bahan.create')"
                                :current="request()->routeIs('mahasiswa.pengambilan-bahan.*')"
                                wire:navigate>
                                Pengambilan Bahan
                            </flux:sidebar.item>
                        @elseif ($role === 'dosen')
                            <flux:sidebar.item
                                :href="route('dosen.pengambilan-bahan.create')"
                                :current="request()->routeIs('dosen.pengambilan-bahan.*')"
                                wire:navigate>
                                Pengambilan Bahan
                            </flux:sidebar.item>
                        @endif

                        <flux:sidebar.item href="#">
                            Peminjaman Alat
                        </flux:sidebar.item>

                        <flux:sidebar.item href="#">
                            Peminjaman Lab
                        </flux:sidebar.item>

                    </flux:sidebar.group>

                    <flux:sidebar.group heading="Aktivitas Saya" class="grid">

                        <flux:sidebar.item href="#">
                            Riwayat Saya
                        </flux:sidebar.item>

                    </flux:sidebar.group>

                @endif

                @if ($isSuperadmin)

                    {{-- MENU KHUSUS SUPERADMIN --}}
                    <flux:sidebar.group heading="Administrasi Sistem" class="grid">

                        <flux:sidebar.item href="#">
                            Manajemen Pengguna
                        </flux:sidebar.item>

                        <flux:sidebar.item href="#">
                            Activity Log
                        </flux:sidebar.item>

                    </flux:sidebar.group>

                @endif

            </flux:sidebar.nav>

            <flux:spacer />

            {{-- PROFIL PENGGUNA --}}
            <x-desktop-user-menu
                class="hidden lg:block"
                :name="$user->name"
            />

        </flux:sidebar>

        {{-- HEADER MOBILE --}}
        <flux:header class="lg:hidden">

            <flux:sidebar.toggle
                class="lg:hidden"
                inset="left"
            />

            <flux:spacer />

            <flux:dropdown position="top" align="end">

                <flux:profile
                    :initials="$user->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>

                    <flux:menu.radio.group>
                        <div class="p-2 text-sm">
                            <div class="flex items-center gap-2">
                                <flux:avatar
                                    :name="$user->name"
                                    :initials="$user->initials()"
                                />

                                <div class="min-w-0">
                                    <flux:heading class="truncate">
                                        {{ $user->name }}
                                    </flux:heading>

                                    <flux:text class="truncate">
                                        {{ $user->email }}
                                    </flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.item
                        :href="route('profile.edit')"
                        wire:navigate>
                        Pengaturan
                    </flux:menu.item>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <flux:menu.item
                            as="button"
                            type="submit"
                            class="w-full cursor-pointer">
                            Keluar
                        </flux:menu.item>
                    </form>

                </flux:menu>
            </flux:dropdown>

        </flux:header>

        {{-- KONTEN HALAMAN --}}
        {{ $slot }}

        {{-- FOOTER DIPINDAHKAN KE APP.BLADE.PHP --}}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts

    </body>
</html>
