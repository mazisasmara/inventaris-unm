<x-layouts::app :title="__('Dashboard')">
    @php
        $user = auth()->user();
        $isAdmin = in_array($user->role, ['admin', 'superadmin']);

        $totalAlat = \App\Models\Alat::count();
        $totalUnit = \App\Models\UnitAlat::count();
        $totalBahan = \App\Models\Bahan::count();
        $totalLab = \App\Models\Laboratorium::count();

        $menungguAlat = \App\Models\PeminjamanAlat::where('status', 'menunggu')->count();
        $menungguLab = \App\Models\PeminjamanLab::where('status', 'menunggu')->count();
        $menungguBahan = \App\Models\PengambilanBahan::where('status', 'menunggu')->count();

        $totalMenunggu = $menungguAlat + $menungguLab + $menungguBahan;
    @endphp

    <div class="flex flex-col gap-6">

        {{-- Header --}}
        <div class="flex flex-col gap-1">
            <p class="text-sm text-neutral-500 dark:text-neutral-400">
                Sistem Informasi Inventaris dan Peminjaman Laboratorium
            </p>

            <h1 class="text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">
                Selamat datang, {{ $user->name }}!
            </h1>

            <p class="text-sm text-neutral-500 dark:text-neutral-400">
                @if ($isAdmin)
                    Berikut ringkasan kondisi inventaris dan aktivitas laboratorium.
                @else
                    Selamat datang di sistem Inventaris dan Peminjaman Lab UNM.
                @endif
            </p>
        </div>

        @if ($isAdmin)

            {{-- Statistik --}}
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

                <div class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-neutral-900">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                            Jenis Alat
                        </p>
                        <div class="rounded-lg bg-blue-100 p-2 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.5 6h3m-7.5 4.5h12M6 15h12M8 3h8a2 2 0 012 2v14a2 2 0 01-2 2H8a2 2 0 01-2-2V5a2 2 0 012-2z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="mt-4 text-3xl font-bold text-neutral-900 dark:text-white">
                        {{ $totalAlat }}
                    </p>
                    <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                        {{ $totalUnit }} unit inventaris
                    </p>
                </div>

                <div class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-neutral-900">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                            Jenis Bahan
                        </p>
                        <div class="rounded-lg bg-emerald-100 p-2 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                    </div>
                    <p class="mt-4 text-3xl font-bold text-neutral-900 dark:text-white">
                        {{ $totalBahan }}
                    </p>
                    <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                        Data bahan inventaris
                    </p>
                </div>

                <div class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-neutral-900">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                            Laboratorium
                        </p>
                        <div class="rounded-lg bg-violet-100 p-2 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9v.01M9 12v.01M9 15v.01M9 18v.01"/>
                            </svg>
                        </div>
                    </div>
                    <p class="mt-4 text-3xl font-bold text-neutral-900 dark:text-white">
                        {{ $totalLab }}
                    </p>
                    <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                        Laboratorium terdaftar
                    </p>
                </div>

                <div class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-neutral-900">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                            Menunggu Persetujuan
                        </p>
                        <div class="rounded-lg bg-amber-100 p-2 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="mt-4 text-3xl font-bold text-neutral-900 dark:text-white">
                        {{ $totalMenunggu }}
                    </p>
                    <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                        {{ $menungguAlat }} alat · {{ $menungguLab }} lab · {{ $menungguBahan }} bahan
                    </p>
                </div>

            </div>

            {{-- Informasi persetujuan --}}
            <div class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-neutral-900">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="font-semibold text-neutral-900 dark:text-white">
                            Permintaan membutuhkan perhatian
                        </h2>
                        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                            Periksa permintaan peminjaman dan pengambilan bahan yang belum diproses.
                        </p>
                    </div>

                    <div class="inline-flex w-fit items-center gap-2 rounded-lg bg-amber-50 px-3 py-2 text-sm font-medium text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">
                        <span class="size-2 rounded-full bg-amber-500"></span>
                        {{ $totalMenunggu }} permintaan menunggu
                    </div>
                </div>
            </div>

            {{-- Informasi inventaris --}}
            <div class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-neutral-900">
                <h2 class="font-semibold text-neutral-900 dark:text-white">
                    Ringkasan sistem
                </h2>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                    Data inventaris yang sudah terdaftar di dalam sistem.
                </p>

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-lg bg-neutral-50 p-4 dark:bg-neutral-800">
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">
                            Total unit alat
                        </p>
                        <p class="mt-1 text-xl font-semibold text-neutral-900 dark:text-white">
                            {{ $totalUnit }} unit
                        </p>
                    </div>

                    <div class="rounded-lg bg-neutral-50 p-4 dark:bg-neutral-800">
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">
                            Total jenis inventaris
                        </p>
                        <p class="mt-1 text-xl font-semibold text-neutral-900 dark:text-white">
                            {{ $totalAlat + $totalBahan }} jenis
                        </p>
                    </div>
                </div>
            </div>

        @else

            <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-neutral-900">
                <h2 class="font-semibold text-neutral-900 dark:text-white">
                    Dashboard {{ ucfirst($user->role) }}
                </h2>
                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">
                    Halaman ini akan menampilkan informasi peminjaman dan aktivitas akun Anda.
                    Fitur tersebut akan kita bangun pada tahap transaksi.
                </p>
            </div>

        @endif

    </div>
</x-layouts::app>
