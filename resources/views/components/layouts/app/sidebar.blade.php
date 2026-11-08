
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>

    <body class="min-h-screen bg-white dark:bg-zinc-800">

        @php
            $user = auth()->user();
            $role = $user?->role;

            $isGuest = $user === null;
            $isAdmin = in_array($role, ['admin', 'superadmin'], true);
            $isSuperadmin = $role === 'superadmin';
            $isMahasiswa = $role === 'mahasiswa';
            $isDosen = $role === 'dosen';

            $dashboardRoute = match ($role) {
                'mahasiswa' => 'mahasiswa.dashboard',
                'dosen' => 'dosen.dashboard',
                'admin' => 'admin.dashboard',
                'superadmin' => 'superadmin.dashboard',
                default => null,
            };

            $layananRoutePrefix = $isAdmin ? 'admin.' : ($role ? $role . '.' : null);
        @endphp

        {{-- SIDEBAR --}}
        <flux:sidebar
            sticky
            collapsible="mobile"
            class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900"
        >

            {{-- HEADER SIDEBAR --}}
            <flux:sidebar.header>
                <x-app-logo
                    :sidebar="true"
                    :href="route('home')"
                    wire:navigate
                />

                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>

                {{-- MENU UTAMA --}}
                <flux:sidebar.group heading="Menu Utama" class="grid">

                    <flux:sidebar.item
                        icon="home"
                        :href="route('home')"
                        :current="request()->routeIs('home')"
                        wire:navigate
                    >
                        Beranda
                    </flux:sidebar.item>

                    @if ($user)
                        <flux:sidebar.item
                            icon="layout-dashboard"
                            :href="route($dashboardRoute)"
                            :current="request()->routeIs(
                                'dashboard',
                                'mahasiswa.dashboard',
                                'dosen.dashboard',
                                'admin.dashboard',
                                'superadmin.dashboard'
                            )"
                            wire:navigate
                        >
                            Dashboard
                        </flux:sidebar.item>
                    @endif

                    <flux:sidebar.item
                        icon="package-search"
                        :href="route('inventaris.index')"
                        :current="request()->routeIs('inventaris.*')"
                        wire:navigate
                    >
                        Katalog Inventaris
                    </flux:sidebar.item>

                </flux:sidebar.group>

                {{-- MENU MAHASISWA DAN DOSEN --}}
                @if ($isMahasiswa || $isDosen)

                    <flux:sidebar.group heading="Layanan" class="grid">

                        <flux:sidebar.item
                            icon="flask-conical"
                            :href="route($role . '.pengambilan-bahan.create')"
                            :current="request()->routeIs($role . '.pengambilan-bahan.*')"
                            wire:navigate
                        >
                            Pengambilan Bahan
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="wrench"
                            :href="route($role . '.peminjaman-alat.create')"
                            :current="request()->routeIs($role . '.peminjaman-alat.*')"
                            wire:navigate
                        >
                            Peminjaman Alat
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="building"
                            :href="route($role . '.peminjaman-lab.create')"
                            :current="request()->routeIs($role . '.peminjaman-lab.*')"
                            wire:navigate
                        >
                            Peminjaman Laboratorium
                        </flux:sidebar.item>

                    </flux:sidebar.group>

                    <flux:sidebar.group heading="Aktivitas Saya" class="grid">

                        <flux:sidebar.item
                            icon="clock"
                            :href="route($role . '.riwayat-transaksi.index')"
                            :current="request()->routeIs($role . '.riwayat-transaksi.*')"
                            wire:navigate
                        >
                            Riwayat Saya
                        </flux:sidebar.item>

                    </flux:sidebar.group>

                @endif

                {{-- MENU ADMIN DAN SUPERADMIN --}}
                @if ($isAdmin)

                    <flux:sidebar.group heading="Manajemen Inventaris" class="grid">

                        <flux:sidebar.item
                            icon="flask-conical"
                            :href="route('admin.bahan.index')"
                            :current="request()->routeIs('admin.bahan.*')"
                            wire:navigate
                        >
                            Data Bahan
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="wrench"
                            :href="route('admin.alat.index')"
                            :current="request()->routeIs('admin.alat.*')"
                            wire:navigate
                        >
                            Data Alat
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="boxes"
                            :href="route('admin.unit-alat.index')"
                            :current="request()->routeIs('admin.unit-alat.*')"
                            wire:navigate
                        >
                            Unit Inventaris
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="building"
                            :href="route('admin.laboratorium.index')"
                            :current="request()->routeIs('admin.laboratorium.*')"
                            wire:navigate
                        >
                            Laboratorium
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="calendar-days"
                            :href="route('admin.jadwal-laboratorium.index')"
                            :current="request()->routeIs('admin.jadwal-laboratorium.*')"
                            wire:navigate
                        >
                            Jadwal Laboratorium
                        </flux:sidebar.item>

                    </flux:sidebar.group>

                    <flux:sidebar.group heading="Transaksi" class="grid">

                        <flux:sidebar.item
                            icon="clipboard-list"
                            :href="route('admin.pengambilan-bahan.index')"
                            :current="request()->routeIs('admin.pengambilan-bahan.*')"
                            wire:navigate
                        >
                            Pengambilan Bahan
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="package"
                            :href="route('admin.peminjaman-alat.index')"
                            :current="request()->routeIs('admin.peminjaman-alat.*')"
                            wire:navigate
                        >
                            Peminjaman Alat
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="building"
                            :href="route('admin.peminjaman-lab.index')"
                            :current="request()->routeIs('admin.peminjaman-lab.*')"
                            wire:navigate
                        >
                            Peminjaman Laboratorium
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="package-check"
                            :href="route('admin.pengembalian-bahan.index')"
                            :current="request()->routeIs('admin.pengembalian-bahan.*')"
                            wire:navigate
                        >
                            Pengembalian Bahan
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="undo-2"
                            :href="route('admin.pengembalian-alat.index')"
                            :current="request()->routeIs('admin.pengembalian-alat.*')"
                            wire:navigate
                        >
                            Pengembalian Alat
                        </flux:sidebar.item>

                    </flux:sidebar.group>

                    <flux:sidebar.group heading="Laporan" class="grid">

                        @if ($role === 'admin')
                            <flux:sidebar.item
                                icon="clock"
                                :href="route('admin.riwayat-transaksi.index')"
                                :current="request()->routeIs('admin.riwayat-transaksi.*')"
                                wire:navigate
                            >
                                Riwayat Transaksi
                            </flux:sidebar.item>
                        @elseif ($isSuperadmin)
                            <flux:sidebar.item
                                icon="clock"
                                :href="route('superadmin.riwayat-transaksi.index')"
                                :current="request()->routeIs('superadmin.riwayat-transaksi.*')"
                                wire:navigate
                            >
                                Riwayat Transaksi
                            </flux:sidebar.item>
                        @endif

                    </flux:sidebar.group>

                @endif

            </flux:sidebar.nav>

            <flux:spacer />

            {{-- BAGIAN BAWAH SIDEBAR --}}
            @auth
                <x-desktop-user-menu
                    class="hidden lg:block"
                    :name="$user->name"
                />
            @else
                <div class="hidden lg:block p-2">
                    <flux:button
                        href="{{ route('login') }}"
                        variant="primary"
                        class="w-full"
                        icon="log-in"
                    >
                        Masuk
                    </flux:button>
                </div>
            @endauth

        </flux:sidebar>

        {{-- HEADER MOBILE --}}
        <flux:header class="lg:hidden">

            <flux:sidebar.toggle
                class="lg:hidden"
                inset="left"
            />

            <flux:spacer />

            @auth
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
                            wire:navigate
                        >
                            Pengaturan
                        </flux:menu.item>

                        <flux:menu.separator />

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <flux:menu.item
                                as="button"
                                type="submit"
                                class="w-full cursor-pointer"
                            >
                                Keluar
                            </flux:menu.item>
                        </form>

                    </flux:menu>
                </flux:dropdown>
            @else
                <flux:button
                    href="{{ route('login') }}"
                    size="sm"
                    variant="primary"
                >
                    Masuk
                </flux:button>
            @endauth

        </flux:header>

        {{-- KONTEN HALAMAN --}}
        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts

    </body>
</html>
