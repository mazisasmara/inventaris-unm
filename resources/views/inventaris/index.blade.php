<x-layouts::app.sidebar :title="'Katalog Inventaris'">

    <flux:main class="flex min-h-screen flex-col">

        <div class="w-full space-y-10">

            {{-- HEADER HALAMAN --}}
            <section>
                <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">
                            Katalog Publik
                        </p>

                        <h1 class="mt-2 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white sm:text-4xl">
                            Inventaris Laboratorium
                        </h1>

                        <p class="mt-3 max-w-2xl leading-7 text-zinc-600 dark:text-zinc-400">
                            Jelajahi alat, bahan, dan laboratorium yang terdaftar dalam sistem inventaris UNM.
                        </p>
                    </div>

                    <a href="{{ route('home') }}"
                       class="text-sm font-medium text-zinc-500 transition hover:text-blue-600 dark:text-zinc-400 dark:hover:text-blue-400">
                        ← Kembali ke Beranda
                    </a>
                </div>
            </section>

            {{-- FORM PENCARIAN DAN FILTER --}}
            <form method="GET"
                  action="{{ route('inventaris.index') }}"
                  class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

                <div class="grid gap-3 md:grid-cols-[1fr_220px_auto]">

                    <input
                        type="search"
                        name="q"
                        value="{{ $kataKunci }}"
                        placeholder="Cari nama atau kategori inventaris..."
                        class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 outline-none placeholder:text-zinc-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:placeholder:text-zinc-500"
                    >

                    <select
                        name="kategori"
                        class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 outline-none focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">

                        <option value="semua" @selected($kategori === 'semua')>
                            Semua kategori
                        </option>

                        <option value="alat" @selected($kategori === 'alat')>
                            Alat
                        </option>

                        <option value="bahan" @selected($kategori === 'bahan')>
                            Bahan
                        </option>

                        <option value="laboratorium" @selected($kategori === 'laboratorium')>
                            Laboratorium
                        </option>
                    </select>

                    <button type="submit"
                            class="rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-blue-500">
                        Cari Inventaris
                    </button>

                </div>
            </form>

            {{-- NOTIFIKASI --}}
            @if (session('info'))
                <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-700 dark:border-blue-400/20 dark:bg-blue-500/10 dark:text-blue-300">
                    {{ session('info') }}
                </div>
            @endif

            {{-- JUMLAH HASIL --}}
            @php
                $jumlahHasil = $alats->count() + $bahans->count() + $laboratoriums->count();
            @endphp

            <div class="flex items-center justify-between">
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    Menampilkan
                    <strong class="text-zinc-900 dark:text-white">{{ $jumlahHasil }}</strong>
                    hasil inventaris.
                </p>

                @if ($kataKunci !== '' || $kategori !== 'semua')
                    <a href="{{ route('inventaris.index') }}"
                       class="text-xs font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">
                        Reset filter
                    </a>
                @endif
            </div>

            {{-- ========================================= --}}
            {{-- ALAT LABORATORIUM --}}
            {{-- ========================================= --}}
            @if ($kategori === 'semua' || $kategori === 'alat')
                <section>

                    <div class="mb-5 flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-zinc-900 dark:text-white">
                                Alat Laboratorium
                            </h2>

                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                Peralatan yang dapat diajukan untuk dipinjam.
                            </p>
                        </div>

                        <span class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $alats->count() }} alat
                        </span>
                    </div>

                    @if ($alats->isEmpty())
                        <p class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-8 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900/50 dark:text-zinc-400">
                            Tidak ada alat yang ditemukan.
                        </p>
                    @else
                        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">

                            @foreach ($alats as $alat)
                                <a href="{{ route('inventaris.alat.show', $alat) }}"
                                   class="group rounded-2xl border border-zinc-200 bg-white p-6 transition hover:-translate-y-1 hover:border-blue-400 hover:shadow-lg dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-blue-400/40">

                                    <div class="flex items-start justify-between gap-3">
                                        <span class="rounded-lg bg-blue-500/10 px-3 py-1.5 text-xs font-semibold text-blue-600 dark:text-blue-300">
                                            {{ $alat->kategori ?: 'Alat' }}
                                        </span>

                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $alat->kode_alat }}
                                        </span>
                                    </div>

                                    <h3 class="mt-5 text-lg font-bold text-zinc-900 transition group-hover:text-blue-600 dark:text-white dark:group-hover:text-blue-300">
                                        {{ $alat->nama }}
                                    </h3>

                                    <p class="mt-2 line-clamp-2 min-h-12 text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                        {{ $alat->deskripsi ?: 'Tidak ada deskripsi.' }}
                                    </p>

                                    <div class="mt-5 flex items-center justify-between border-t border-zinc-200 pt-4 dark:border-zinc-700">
                                        <span class="text-sm text-zinc-600 dark:text-zinc-400">
                                            Unit tersedia
                                        </span>

                                        <strong class="{{ $alat->unit_tersedia_count > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ $alat->unit_tersedia_count }} / {{ $alat->unit_total_count }}
                                        </strong>
                                    </div>

                                    <p class="mt-4 text-xs font-semibold text-blue-600 dark:text-blue-400">
                                        Lihat detail →
                                    </p>

                                </a>
                            @endforeach

                        </div>
                    @endif

                </section>
            @endif

            {{-- ========================================= --}}
            {{-- BAHAN LABORATORIUM --}}
            {{-- ========================================= --}}
            @if ($kategori === 'semua' || $kategori === 'bahan')
                <section>

                    <div class="mb-5 flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-zinc-900 dark:text-white">
                                Bahan Laboratorium
                            </h2>

                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                Bahan dan kebutuhan kegiatan praktikum.
                            </p>
                        </div>

                        <span class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $bahans->count() }} bahan
                        </span>
                    </div>

                    @if ($bahans->isEmpty())
                        <p class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-8 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900/50 dark:text-zinc-400">
                            Tidak ada bahan yang ditemukan.
                        </p>
                    @else
                        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">

                            @foreach ($bahans as $bahan)
                                <a href="{{ route('inventaris.bahan.show', $bahan) }}"
                                   class="group rounded-2xl border border-zinc-200 bg-white p-6 transition hover:-translate-y-1 hover:border-violet-400 hover:shadow-lg dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-violet-400/40">

                                    <div class="flex items-start justify-between gap-3">
                                        <span class="rounded-lg bg-violet-500/10 px-3 py-1.5 text-xs font-semibold text-violet-600 dark:text-violet-300">
                                            {{ $bahan->kategori ?: 'Bahan' }}
                                        </span>

                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $bahan->kode_bahan }}
                                        </span>
                                    </div>

                                    <h3 class="mt-5 text-lg font-bold text-zinc-900 transition group-hover:text-violet-600 dark:text-white dark:group-hover:text-violet-300">
                                        {{ $bahan->nama }}
                                    </h3>

                                    <p class="mt-2 line-clamp-2 min-h-12 text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                        {{ $bahan->deskripsi ?: 'Tidak ada deskripsi.' }}
                                    </p>

                                    <div class="mt-5 flex items-center justify-between border-t border-zinc-200 pt-4 dark:border-zinc-700">
                                        <span class="text-sm text-zinc-600 dark:text-zinc-400">
                                            Stok tersedia
                                        </span>

                                        <strong class="{{ $bahan->stok > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ $bahan->stok }} {{ $bahan->satuan }}
                                        </strong>
                                    </div>

                                    <p class="mt-4 text-xs font-semibold text-blue-600 dark:text-blue-400">
                                        Lihat detail →
                                    </p>

                                </a>
                            @endforeach

                        </div>
                    @endif

                </section>
            @endif

            {{-- ========================================= --}}
            {{-- LABORATORIUM --}}
            {{-- ========================================= --}}
            @if ($kategori === 'semua' || $kategori === 'laboratorium')
                <section>

                    <div class="mb-5 flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-zinc-900 dark:text-white">
                                Laboratorium
                            </h2>

                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                Ruangan laboratorium dan jadwal operasionalnya.
                            </p>
                        </div>

                        <span class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $laboratoriums->count() }} laboratorium
                        </span>
                    </div>

                    @if ($laboratoriums->isEmpty())
                        <p class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-8 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900/50 dark:text-zinc-400">
                            Tidak ada laboratorium yang ditemukan.
                        </p>
                    @else
                        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">

                            @foreach ($laboratoriums as $lab)
                                <a href="{{ route('inventaris.laboratorium.show', $lab) }}"
                                   class="group rounded-2xl border border-zinc-200 bg-white p-6 transition hover:-translate-y-1 hover:border-amber-400 hover:shadow-lg dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-amber-400/40">

                                    <span class="rounded-lg bg-amber-500/10 px-3 py-1.5 text-xs font-semibold text-amber-600 dark:text-amber-300">
                                        Laboratorium
                                    </span>

                                    <h3 class="mt-5 text-lg font-bold text-zinc-900 transition group-hover:text-amber-600 dark:text-white dark:group-hover:text-amber-300">
                                        {{ $lab->nama }}
                                    </h3>

                                    <p class="mt-2 min-h-12 text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                        {{ $lab->lokasi ?: 'Lokasi belum dicantumkan.' }}
                                    </p>

                                    <div class="mt-5 flex items-center justify-between border-t border-zinc-200 pt-4 dark:border-zinc-700">
                                        <span class="text-sm text-zinc-600 dark:text-zinc-400">
                                            Kapasitas
                                        </span>

                                        <strong class="text-zinc-900 dark:text-white">
                                            {{ $lab->kapasitas }} orang
                                        </strong>
                                    </div>

                                    <p class="mt-4 text-xs font-semibold text-blue-600 dark:text-blue-400">
                                        Lihat detail →
                                    </p>

                                </a>
                            @endforeach

                        </div>
                    @endif

                </section>
            @endif

        </div>

    </flux:main>

</x-layouts::app.sidebar>