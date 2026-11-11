<x-layouts::app.sidebar :title="'Detail Inventaris'">

    <flux:main class="flex min-h-screen flex-col">

        <div class="w-full">

            {{-- KEMBALI KE KATALOG --}}
            <a href="{{ route('inventaris.index', ['kategori' => $jenis]) }}"
               class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 transition hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">
                <span aria-hidden="true">←</span>
                Kembali ke katalog
            </a>

            {{-- NOTIFIKASI --}}
            @if (session('info'))
                <div class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-700 dark:border-blue-400/20 dark:bg-blue-500/10 dark:text-blue-300">
                    {{ session('info') }}
                </div>
            @endif

            {{-- DETAIL INVENTARIS --}}
            <div class="mt-7 overflow-hidden rounded-3xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

                {{-- HEADER DETAIL --}}
                <div class="relative overflow-hidden border-b border-zinc-200 bg-zinc-50 px-7 py-10 dark:border-zinc-700 dark:bg-zinc-950 sm:px-10">

                    <div class="absolute -right-16 -top-24 h-72 w-72 rounded-full bg-blue-600/10 blur-3xl"></div>

                    <div class="relative">

                        <span class="inline-flex rounded-full border border-blue-500/20 bg-blue-500/10 px-3 py-1.5 text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-300">
                            {{ $jenis === 'laboratorium' ? 'Laboratorium' : ucfirst($jenis) }}
                        </span>

                        <h1 class="mt-5 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white sm:text-4xl">
                            {{ $item->nama }}
                        </h1>

                        <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">
                            @if ($jenis === 'alat')
                                Kode alat: {{ $item->kode_alat }}
                            @elseif ($jenis === 'bahan')
                                Kode bahan: {{ $item->kode_bahan }}
                            @else
                                Kode laboratorium: {{ $item->kode_lab }}
                            @endif
                        </p>

                    </div>
                </div>

                {{-- KONTEN DETAIL --}}
                <div class="grid gap-8 p-7 sm:p-10 lg:grid-cols-[1fr_300px]">

                    {{-- INFORMASI INVENTARIS --}}
                    <section>

                        <h2 class="text-lg font-bold text-zinc-900 dark:text-white">
                            Informasi Inventaris
                        </h2>

                        <div class="mt-5 space-y-6">

                            {{-- DETAIL ALAT --}}
                            @if ($jenis === 'alat')

                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                        Kategori
                                    </p>

                                    <p class="mt-1 font-medium text-zinc-800 dark:text-zinc-200">
                                        {{ $item->kategori ?: 'Tidak ditentukan' }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                        Deskripsi
                                    </p>

                                    <p class="mt-2 whitespace-pre-line text-sm leading-7 text-zinc-700 dark:text-zinc-300">
                                        {{ $item->deskripsi ?: 'Belum ada deskripsi untuk alat ini.' }}
                                    </p>
                                </div>

                                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-5 dark:border-zinc-700 dark:bg-zinc-800/70">

                                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">
                                        Kondisi dan ketersediaan unit
                                    </p>

                                    <div class="mt-4 grid grid-cols-2 gap-4">

                                        <div>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                                Total unit
                                            </p>

                                            <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">
                                                {{ $item->unit_total_count }}
                                            </p>
                                        </div>

                                        <div>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                                Unit tersedia
                                            </p>

                                            <p class="mt-1 text-2xl font-bold {{ $item->unit_tersedia_count > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                                {{ $item->unit_tersedia_count }}
                                            </p>
                                        </div>

                                    </div>

                                    <p class="mt-3 text-xs leading-5 text-zinc-500 dark:text-zinc-400">
                                        Unit tersedia dihitung dari unit dengan status tersedia dan kondisi baik.
                                    </p>

                                </div>

                            {{-- DETAIL BAHAN --}}
                            @elseif ($jenis === 'bahan')

                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                        Kategori
                                    </p>

                                    <p class="mt-1 font-medium text-zinc-800 dark:text-zinc-200">
                                        {{ $item->kategori ?: 'Tidak ditentukan' }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                        Deskripsi
                                    </p>

                                    <p class="mt-2 whitespace-pre-line text-sm leading-7 text-zinc-700 dark:text-zinc-300">
                                        {{ $item->deskripsi ?: 'Belum ada deskripsi untuk bahan ini.' }}
                                    </p>
                                </div>

                                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-5 dark:border-zinc-700 dark:bg-zinc-800/70">

                                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">
                                        Informasi stok
                                    </p>

                                    <p class="mt-2 text-3xl font-bold {{ $item->stok > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $item->stok }}

                                        <span class="text-base font-medium text-zinc-500 dark:text-zinc-400">
                                            {{ $item->satuan }}
                                        </span>
                                    </p>

                                    <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                        Stok minimum: {{ $item->stok_minimum }} {{ $item->satuan }}
                                    </p>

                                </div>

                            {{-- DETAIL LABORATORIUM --}}
                            @else

                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                        Lokasi
                                    </p>

                                    <p class="mt-1 font-medium text-zinc-800 dark:text-zinc-200">
                                        {{ $item->lokasi ?: 'Belum dicantumkan' }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                        Kapasitas
                                    </p>

                                    <p class="mt-1 font-medium text-zinc-800 dark:text-zinc-200">
                                        {{ $item->kapasitas }} orang
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                        Deskripsi
                                    </p>

                                    <p class="mt-2 whitespace-pre-line text-sm leading-7 text-zinc-700 dark:text-zinc-300">
                                        {{ $item->deskripsi ?: 'Belum ada deskripsi untuk laboratorium ini.' }}
                                    </p>
                                </div>

                                {{-- JADWAL OPERASIONAL --}}
                                <div>

                                    <h3 class="font-semibold text-zinc-900 dark:text-white">
                                        Jadwal Operasional
                                    </h3>

                                    @if ($jadwal->isEmpty())

                                        <p class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700 dark:border-amber-400/20 dark:bg-amber-500/10 dark:text-amber-300">
                                            Belum ada jadwal operasional aktif untuk laboratorium ini.
                                        </p>

                                    @else

                                        <div class="mt-3 divide-y divide-zinc-200 overflow-hidden rounded-xl border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">

                                            @foreach ($jadwal as $slot)

                                                <div class="flex items-center justify-between gap-4 bg-white px-4 py-3 text-sm dark:bg-zinc-800/50">

                                                    <span class="font-medium capitalize text-zinc-800 dark:text-zinc-200">
                                                        {{ $slot->hari }}
                                                    </span>

                                                    <span class="text-zinc-600 dark:text-zinc-400">
                                                        {{ substr($slot->jam_mulai, 0, 5) }}
                                                        – {{ substr($slot->jam_selesai, 0, 5) }}
                                                    </span>

                                                </div>

                                            @endforeach

                                        </div>

                                    @endif

                                    <p class="mt-3 text-xs leading-5 text-zinc-500 dark:text-zinc-400">
                                        Jadwal operasional bukan jaminan slot kosong. Sistem akan memeriksa bentrok waktu saat pengajuan dikirim.
                                    </p>

                                </div>

                            @endif

                        </div>

                    </section>

                    {{-- SIDEBAR STATUS DAN PENGAJUAN --}}
                    <aside>

                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-5 dark:border-zinc-700 dark:bg-zinc-800/70">

                            <p class="text-sm font-semibold text-zinc-900 dark:text-white">
                                Status inventaris
                            </p>

                            {{-- STATUS ALAT --}}
                            @if ($jenis === 'alat')

                                @if ($item->unit_tersedia_count > 0)

                                    <p class="mt-3 inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 dark:border-emerald-400/20 dark:bg-emerald-500/10 dark:text-emerald-300">
                                        Tersedia
                                    </p>

                                @else

                                    <p class="mt-3 inline-flex rounded-full border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 dark:border-red-400/20 dark:bg-red-500/10 dark:text-red-300">
                                        Tidak tersedia
                                    </p>

                                @endif

                            {{-- STATUS BAHAN --}}
                            @elseif ($jenis === 'bahan')

                                @if ($item->stok > 0)

                                    <p class="mt-3 inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 dark:border-emerald-400/20 dark:bg-emerald-500/10 dark:text-emerald-300">
                                        Stok tersedia
                                    </p>

                                @else

                                    <p class="mt-3 inline-flex rounded-full border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 dark:border-red-400/20 dark:bg-red-500/10 dark:text-red-300">
                                        Stok habis
                                    </p>

                                @endif

                            {{-- STATUS LABORATORIUM --}}
                            @else

                                @if ($jadwal->isNotEmpty())

                                    <p class="mt-3 inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 dark:border-emerald-400/20 dark:bg-emerald-500/10 dark:text-emerald-300">
                                        Jadwal operasional aktif
                                    </p>

                                @else

                                    <p class="mt-3 inline-flex rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 dark:border-amber-400/20 dark:bg-amber-500/10 dark:text-amber-300">
                                        Jadwal belum tersedia
                                    </p>

                                @endif

                            @endif

                            {{-- TOMBOL PENGAJUAN --}}
                            @if (! auth()->check() || in_array(auth()->user()->role, ['mahasiswa', 'dosen'], true))

                                @php
                                    $routeAjukan = match ($jenis) {
                                        'alat' => 'inventaris.ajukan.alat',
                                        'bahan' => 'inventaris.ajukan.bahan',
                                        'laboratorium' => 'inventaris.ajukan.laboratorium',
                                    };

                                    $bolehAjukan = match ($jenis) {
                                        'alat' => $item->unit_tersedia_count > 0,
                                        'bahan' => $item->stok > 0,
                                        'laboratorium' => $jadwal->isNotEmpty(),
                                    };
                                @endphp

                                @if ($bolehAjukan)

                                    <a href="{{ route($routeAjukan, $item) }}"
                                       class="mt-5 block rounded-xl bg-blue-600 px-4 py-3 text-center text-sm font-semibold text-white transition hover:bg-blue-500">
                                        Ajukan {{ $jenis === 'bahan' ? 'Pengambilan' : 'Permohonan' }}
                                    </a>

                                @else

                                    <p class="mt-5 rounded-xl border border-zinc-200 bg-white px-4 py-3 text-center text-xs leading-5 text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400">
                                        {{ $jenis === 'laboratorium' ? 'Pengajuan belum dapat dilakukan karena jadwal operasional belum tersedia.' : 'Inventaris ini belum tersedia untuk diajukan.' }}
                                    </p>

                                @endif

                                @guest

                                    <p class="mt-3 text-xs leading-5 text-zinc-500 dark:text-zinc-400">
                                        Kamu perlu masuk ke akun terlebih dahulu untuk mengajukan permohonan.
                                    </p>

                                @endguest

                            @else

                                <p class="mt-5 rounded-xl border border-zinc-200 bg-white p-3 text-xs leading-5 text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400">
                                    Akun admin dan superadmin dapat melihat katalog, tetapi tidak mengajukan peminjaman melalui halaman publik.
                                </p>

                            @endif

                        </div>

                    </aside>

                </div>

            </div>

        </div>

    </flux:main>

</x-layouts::app.sidebar>