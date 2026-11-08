    <main class="min-h-screen bg-zinc-900 px-6 py-10 text-zinc-100 antialiased lg:px-10">
        <div class="mx-auto max-w-7xl">
            <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
                <div>
                    <p class="text-sm font-bold uppercase tracking-wider text-blue-400">Katalog publik</p>
                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-white sm:text-4xl">Inventaris Laboratorium</h1>
                    <p class="mt-3 max-w-2xl leading-7 text-zinc-400">
                        Jelajahi alat, bahan, dan laboratorium yang terdaftar dalam sistem inventaris UNM.
                    </p>
                </div>
                <a href="{{ route('home') }}"
                   class="text-sm font-medium text-zinc-400 transition hover:text-blue-400">
                    ← Kembali ke Beranda
                </a>
            </div>

            <form method="GET" action="{{ route('inventaris.index') }}"
                  class="mt-8 rounded-2xl border border-white/10 bg-zinc-800/70 p-4 shadow-lg shadow-black/10">
                <div class="grid gap-3 md:grid-cols-[1fr_220px_auto]">
                    <input
                        type="search"
                        name="q"
                        value="{{ $kataKunci }}"
                        placeholder="Cari nama atau kategori inventaris..."
                        class="w-full rounded-xl border border-white/10 bg-zinc-900 px-4 py-3 text-sm text-white outline-none placeholder:text-zinc-500 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                    >

                    <select name="kategori"
                            class="w-full rounded-xl border border-white/10 bg-zinc-900 px-4 py-3 text-sm text-zinc-200 outline-none focus:border-blue-500">
                        <option value="semua" @selected($kategori === 'semua')>Semua kategori</option>
                        <option value="alat" @selected($kategori === 'alat')>Alat</option>
                        <option value="bahan" @selected($kategori === 'bahan')>Bahan</option>
                        <option value="laboratorium" @selected($kategori === 'laboratorium')>Laboratorium</option>
                    </select>

                    <button type="submit"
                            class="rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-blue-500">
                        Cari Inventaris
                    </button>
                </div>
            </form>

            @if (session('info'))
                <div class="mt-6 rounded-xl border border-blue-400/20 bg-blue-500/10 p-4 text-sm text-blue-200">
                    {{ session('info') }}
                </div>
            @endif

            @php
                $jumlahHasil = $alats->count() + $bahans->count() + $laboratoriums->count();
            @endphp

            <div class="mt-7 flex items-center justify-between">
                <p class="text-sm text-zinc-400">
                    Menampilkan <strong class="text-white">{{ $jumlahHasil }}</strong> hasil inventaris.
                </p>
                @if ($kataKunci !== '' || $kategori !== 'semua')
                    <a href="{{ route('inventaris.index') }}" class="text-xs font-medium text-blue-400 hover:text-blue-300">
                        Reset filter
                    </a>
                @endif
            </div>

            @if ($kategori === 'semua' || $kategori === 'alat')
                <section class="mt-10">
                    <div class="mb-5 flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-white">Alat Laboratorium</h2>
                            <p class="mt-1 text-sm text-zinc-400">Peralatan yang dapat diajukan untuk dipinjam.</p>
                        </div>
                        <span class="text-sm text-zinc-500">{{ $alats->count() }} alat</span>
                    </div>

                    @if ($alats->isEmpty())
                        <p class="rounded-xl border border-dashed border-white/15 bg-zinc-800/40 p-8 text-center text-sm text-zinc-400">
                            Tidak ada alat yang ditemukan.
                        </p>
                    @else
                        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($alats as $alat)
                                <a href="{{ route('inventaris.alat.show', $alat) }}"
                                   class="group rounded-2xl border border-white/10 bg-zinc-800/60 p-6 transition hover:-translate-y-1 hover:border-blue-400/40 hover:bg-zinc-800">
                                    <div class="flex items-start justify-between gap-3">
                                        <span class="rounded-lg bg-blue-500/10 px-3 py-1.5 text-xs font-semibold text-blue-300">
                                            {{ $alat->kategori ?: 'Alat' }}
                                        </span>
                                        <span class="text-xs text-zinc-500">{{ $alat->kode_alat }}</span>
                                    </div>
                                    <h3 class="mt-5 text-lg font-bold text-white transition group-hover:text-blue-300">{{ $alat->nama }}</h3>
                                    <p class="mt-2 line-clamp-2 min-h-12 text-sm leading-6 text-zinc-400">
                                        {{ $alat->deskripsi ?: 'Tidak ada deskripsi.' }}
                                    </p>
                                    <div class="mt-5 flex items-center justify-between border-t border-white/10 pt-4">
                                        <span class="text-sm text-zinc-400">Unit tersedia</span>
                                        <strong class="{{ $alat->unit_tersedia_count > 0 ? 'text-emerald-400' : 'text-red-400' }}">
                                            {{ $alat->unit_tersedia_count }} / {{ $alat->unit_total_count }}
                                        </strong>
                                    </div>
                                    <p class="mt-4 text-xs font-semibold text-blue-400">Lihat detail →</p>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endif

            @if ($kategori === 'semua' || $kategori === 'bahan')
                <section class="mt-14">
                    <div class="mb-5 flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-white">Bahan Laboratorium</h2>
                            <p class="mt-1 text-sm text-zinc-400">Bahan dan kebutuhan kegiatan praktikum.</p>
                        </div>
                        <span class="text-sm text-zinc-500">{{ $bahans->count() }} bahan</span>
                    </div>

                    @if ($bahans->isEmpty())
                        <p class="rounded-xl border border-dashed border-white/15 bg-zinc-800/40 p-8 text-center text-sm text-zinc-400">
                            Tidak ada bahan yang ditemukan.
                        </p>
                    @else
                        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($bahans as $bahan)
                                <a href="{{ route('inventaris.bahan.show', $bahan) }}"
                                   class="group rounded-2xl border border-white/10 bg-zinc-800/60 p-6 transition hover:-translate-y-1 hover:border-violet-400/40 hover:bg-zinc-800">
                                    <div class="flex items-start justify-between gap-3">
                                        <span class="rounded-lg bg-violet-500/10 px-3 py-1.5 text-xs font-semibold text-violet-300">
                                            {{ $bahan->kategori ?: 'Bahan' }}
                                        </span>
                                        <span class="text-xs text-zinc-500">{{ $bahan->kode_bahan }}</span>
                                    </div>
                                    <h3 class="mt-5 text-lg font-bold text-white transition group-hover:text-violet-300">{{ $bahan->nama }}</h3>
                                    <p class="mt-2 line-clamp-2 min-h-12 text-sm leading-6 text-zinc-400">
                                        {{ $bahan->deskripsi ?: 'Tidak ada deskripsi.' }}
                                    </p>
                                    <div class="mt-5 flex items-center justify-between border-t border-white/10 pt-4">
                                        <span class="text-sm text-zinc-400">Stok tersedia</span>
                                        <strong class="{{ $bahan->stok > 0 ? 'text-emerald-400' : 'text-red-400' }}">
                                            {{ $bahan->stok }} {{ $bahan->satuan }}
                                        </strong>
                                    </div>
                                    <p class="mt-4 text-xs font-semibold text-blue-400">Lihat detail →</p>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endif

            @if ($kategori === 'semua' || $kategori === 'laboratorium')
                <section class="mt-14">
                    <div class="mb-5 flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-white">Laboratorium</h2>
                            <p class="mt-1 text-sm text-zinc-400">Ruangan laboratorium dan jadwal operasionalnya.</p>
                        </div>
                        <span class="text-sm text-zinc-500">{{ $laboratoriums->count() }} laboratorium</span>
                    </div>

                    @if ($laboratoriums->isEmpty())
                        <p class="rounded-xl border border-dashed border-white/15 bg-zinc-800/40 p-8 text-center text-sm text-zinc-400">
                            Tidak ada laboratorium yang ditemukan.
                        </p>
                    @else
                        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($laboratoriums as $lab)
                                <a href="{{ route('inventaris.laboratorium.show', $lab) }}"
                                   class="group rounded-2xl border border-white/10 bg-zinc-800/60 p-6 transition hover:-translate-y-1 hover:border-amber-400/40 hover:bg-zinc-800">
                                    <span class="rounded-lg bg-amber-500/10 px-3 py-1.5 text-xs font-semibold text-amber-300">
                                        Laboratorium
                                    </span>
                                    <h3 class="mt-5 text-lg font-bold text-white transition group-hover:text-amber-300">{{ $lab->nama }}</h3>
                                    <p class="mt-2 min-h-12 text-sm leading-6 text-zinc-400">
                                        {{ $lab->lokasi ?: 'Lokasi belum dicantumkan.' }}
                                    </p>
                                    <div class="mt-5 flex items-center justify-between border-t border-white/10 pt-4">
                                        <span class="text-sm text-zinc-400">Kapasitas</span>
                                        <strong class="text-white">{{ $lab->kapasitas }} orang</strong>
                                    </div>
                                    <p class="mt-4 text-xs font-semibold text-blue-400">Lihat detail →</p>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endif
        </div>

        <footer class="mx-auto mt-20 max-w-7xl border-t border-white/10 px-6 py-7 text-sm text-zinc-500 lg:px-10">
            © {{ date('Y') }} Inventaris Laboratorium UNM.
        </footer>
    </main>