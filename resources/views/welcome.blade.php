    <main class="min-h-screen bg-zinc-900 text-zinc-100 antialiased">
        {{-- HERO --}}
        <section class="relative isolate overflow-hidden border-b border-white/10">
            <div class="absolute inset-0 -z-10 opacity-30"
                 style="background-image: radial-gradient(#60a5fa 1px, transparent 1px); background-size: 28px 28px;">
            </div>
            <div class="absolute -right-32 -top-32 -z-10 h-96 w-96 rounded-full bg-blue-600/10 blur-3xl"></div>

            <div class="mx-auto grid max-w-7xl items-center gap-12 px-6 py-20 lg:grid-cols-2 lg:px-10 lg:py-28">
                <div>
                    <span class="inline-flex items-center rounded-full border border-blue-400/30 bg-blue-400/10 px-3 py-1.5 text-xs font-semibold tracking-wider text-blue-300">
                        SISTEM INFORMASI LABORATORIUM
                    </span>

                    <h1 class="mt-7 max-w-2xl text-4xl font-bold leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl">
                        Kelola kebutuhan laboratorium dengan lebih
                        <span class="text-blue-400">mudah dan teratur.</span>
                    </h1>

                    <p class="mt-6 max-w-xl text-base leading-8 text-zinc-400">
                        Temukan alat, bahan, dan laboratorium yang tersedia.
                        Ajukan peminjaman atau pengambilan inventaris melalui
                        satu sistem informasi terpadu.
                    </p>

                    <div class="mt-9 flex flex-wrap gap-3">
                        <a href="{{ route('inventaris.index') }}"
                           class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-blue-500">
                            Jelajahi Inventaris
                            <span aria-hidden="true">→</span>
                        </a>

                        @guest
                            <a href="{{ route('login') }}"
                               class="rounded-xl border border-white/15 bg-white/5 px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-white/10">
                                Masuk ke Sistem
                            </a>
                        @endguest

                        @auth
                            @php
                                $dashboardRoute = match (auth()->user()->role) {
                                    'mahasiswa' => 'mahasiswa.dashboard',
                                    'dosen' => 'dosen.dashboard',
                                    'admin' => 'admin.dashboard',
                                    'superadmin' => 'superadmin.dashboard',
                                    default => 'dashboard',
                                };
                            @endphp
                            <a href="{{ route($dashboardRoute) }}"
                               class="rounded-xl border border-white/15 bg-white/5 px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-white/10">
                                Buka Dashboard
                            </a>
                        @endauth
                    </div>
                </div>

                <div class="hidden lg:block">
                    <div class="rounded-3xl border border-white/10 bg-zinc-800/80 p-5 shadow-2xl shadow-black/30 backdrop-blur">
                        <div class="rounded-2xl border border-white/5 bg-zinc-900 p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-zinc-400">Portal Inventaris</p>
                                    <p class="mt-1 text-xl font-bold text-white">Laboratorium UNM</p>
                                </div>
                                <div class="rounded-xl bg-blue-500/10 p-3 text-blue-400">
                                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                              d="M9 3v2m6-2v2M8 5h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Zm1 5h6m-6 4h6m-6 4h3"/>
                                    </svg>
                                </div>
                            </div>

                            <div class="mt-8 grid grid-cols-2 gap-3">
                                <a href="{{ route('inventaris.index', ['kategori' => 'alat']) }}"
                                   class="rounded-xl border border-white/10 bg-white/[0.03] p-5 transition hover:border-blue-400/40 hover:bg-white/[0.06]">
                                    <p class="text-sm text-zinc-400">Kategori</p>
                                    <p class="mt-2 text-lg font-bold text-white">Alat</p>
                                    <p class="mt-1 text-xs text-zinc-500">Inventaris laboratorium</p>
                                </a>
                                <a href="{{ route('inventaris.index', ['kategori' => 'bahan']) }}"
                                   class="rounded-xl border border-white/10 bg-white/[0.03] p-5 transition hover:border-blue-400/40 hover:bg-white/[0.06]">
                                    <p class="text-sm text-zinc-400">Kategori</p>
                                    <p class="mt-2 text-lg font-bold text-white">Bahan</p>
                                    <p class="mt-1 text-xs text-zinc-500">Kebutuhan praktikum</p>
                                </a>
                                <a href="{{ route('inventaris.index', ['kategori' => 'laboratorium']) }}"
                                   class="col-span-2 rounded-xl border border-white/10 bg-white/[0.03] p-5 transition hover:border-blue-400/40 hover:bg-white/[0.06]">
                                    <p class="text-sm text-zinc-400">Layanan</p>
                                    <p class="mt-2 text-lg font-bold text-white">Peminjaman Laboratorium</p>
                                    <p class="mt-1 text-xs text-zinc-500">Informasi lokasi, kapasitas, dan jadwal operasional</p>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- LAYANAN --}}
        <section class="mx-auto max-w-7xl px-6 py-20 lg:px-10">
            <div class="max-w-2xl">
                <p class="text-sm font-bold uppercase tracking-wider text-blue-400">Layanan sistem</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-white">
                    Semua kebutuhan inventaris dalam satu tempat.
                </h2>
                <p class="mt-4 leading-7 text-zinc-400">
                    Akses informasi inventaris laboratorium dan lakukan pengajuan
                    sesuai kebutuhan kegiatan akademik.
                </p>
            </div>

            <div class="mt-10 grid gap-5 md:grid-cols-3">
                @foreach ([
                    ['01', 'Peminjaman Alat', 'Temukan alat laboratorium, periksa jumlah unit yang tersedia, dan ajukan peminjaman.', 'alat'],
                    ['02', 'Pengambilan Bahan', 'Lihat informasi stok bahan dan ajukan pengambilan sesuai kebutuhan.', 'bahan'],
                    ['03', 'Peminjaman Laboratorium', 'Lihat lokasi, kapasitas, dan jadwal operasional laboratorium.', 'laboratorium'],
                ] as $layanan)
                    <article class="rounded-2xl border border-white/10 bg-zinc-800/60 p-7 transition hover:-translate-y-1 hover:border-blue-400/40 hover:bg-zinc-800">
                        <p class="text-sm font-bold text-blue-400">{{ $layanan[0] }}</p>
                        <h3 class="mt-5 text-xl font-bold text-white">{{ $layanan[1] }}</h3>
                        <p class="mt-3 min-h-20 text-sm leading-7 text-zinc-400">{{ $layanan[2] }}</p>
                        <a href="{{ route('inventaris.index', ['kategori' => $layanan[3]]) }}"
                           class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-blue-400 hover:text-blue-300">
                            Lihat katalog <span aria-hidden="true">→</span>
                        </a>
                    </article>
                @endforeach
            </div>
        </section>

        {{-- CTA --}}
        <section class="mx-auto max-w-7xl px-6 pb-16 lg:px-10">
            <div class="flex flex-col justify-between gap-6 rounded-2xl border border-blue-400/20 bg-blue-500/10 px-7 py-9 sm:flex-row sm:items-center sm:px-10">
                <div>
                    <h2 class="text-2xl font-bold text-white">Butuh inventaris laboratorium?</h2>
                    <p class="mt-2 text-sm text-zinc-400">Jelajahi katalog dan temukan kebutuhan kegiatan laboratorium.</p>
                </div>
                <a href="{{ route('inventaris.index') }}"
                   class="inline-flex shrink-0 items-center justify-center rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white transition hover:bg-blue-500">
                    Buka Katalog
                </a>
            </div>
        </section>

        <footer class="border-t border-white/10">
            <div class="mx-auto flex max-w-7xl flex-col gap-2 px-6 py-7 text-sm text-zinc-500 sm:flex-row sm:justify-between lg:px-10">
                <p>© {{ date('Y') }} Inventaris Laboratorium UNM.</p>
                <p>Universitas Negeri Makassar</p>
            </div>
        </footer>
    </main>