
    <main class="min-h-screen bg-zinc-900 px-6 py-10 text-zinc-100 antialiased lg:px-10">
        <div class="mx-auto max-w-6xl">
            <a href="{{ route('inventaris.index', ['kategori' => $jenis]) }}"
               class="inline-flex items-center gap-2 text-sm font-medium text-blue-400 transition hover:text-blue-300">
                <span aria-hidden="true">←</span> Kembali ke katalog
            </a>

            @if (session('info'))
                <div class="mt-6 rounded-xl border border-blue-400/20 bg-blue-500/10 p-4 text-sm text-blue-200">
                    {{ session('info') }}
                </div>
            @endif

            <div class="mt-7 overflow-hidden rounded-3xl border border-white/10 bg-zinc-800/60 shadow-xl shadow-black/10">
                <div class="relative overflow-hidden border-b border-white/10 bg-zinc-950 px-7 py-10 sm:px-10">
                    <div class="absolute -right-16 -top-24 h-72 w-72 rounded-full bg-blue-600/10 blur-3xl"></div>
                    <div class="relative">
                        <span class="inline-flex rounded-full border border-blue-400/20 bg-blue-500/10 px-3 py-1.5 text-xs font-semibold uppercase tracking-wider text-blue-300">
                            {{ $jenis === 'laboratorium' ? 'Laboratorium' : ucfirst($jenis) }}
                        </span>

                        <h1 class="mt-5 text-3xl font-bold tracking-tight text-white sm:text-4xl">
                            {{ $item->nama }}
                        </h1>

                        <p class="mt-3 text-sm text-zinc-400">
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

                <div class="grid gap-8 p-7 sm:p-10 lg:grid-cols-[1fr_300px]">
                    <section>
                        <h2 class="text-lg font-bold text-white">Informasi Inventaris</h2>

                        <div class="mt-5 space-y-6">
                            @if ($jenis === 'alat')
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Kategori</p>
                                    <p class="mt-1 font-medium text-zinc-200">{{ $item->kategori ?: 'Tidak ditentukan' }}</p>
                                </div>

                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Deskripsi</p>
                                    <p class="mt-2 whitespace-pre-line text-sm leading-7 text-zinc-300">
                                        {{ $item->deskripsi ?: 'Belum ada deskripsi untuk alat ini.' }}
                                    </p>
                                </div>

                                <div class="rounded-2xl border border-white/10 bg-zinc-900/70 p-5">
                                    <p class="text-sm font-semibold text-white">Kondisi dan ketersediaan unit</p>
                                    <div class="mt-4 grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-xs text-zinc-500">Total unit</p>
                                            <p class="mt-1 text-2xl font-bold text-white">{{ $item->unit_total_count }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-zinc-500">Unit tersedia</p>
                                            <p class="mt-1 text-2xl font-bold {{ $item->unit_tersedia_count > 0 ? 'text-emerald-400' : 'text-red-400' }}">
                                                {{ $item->unit_tersedia_count }}
                                            </p>
                                        </div>
                                    </div>
                                    <p class="mt-3 text-xs leading-5 text-zinc-500">
                                        Unit tersedia dihitung dari unit dengan status tersedia dan kondisi baik.
                                    </p>
                                </div>

                            @elseif ($jenis === 'bahan')
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Kategori</p>
                                    <p class="mt-1 font-medium text-zinc-200">{{ $item->kategori ?: 'Tidak ditentukan' }}</p>
                                </div>

                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Deskripsi</p>
                                    <p class="mt-2 whitespace-pre-line text-sm leading-7 text-zinc-300">
                                        {{ $item->deskripsi ?: 'Belum ada deskripsi untuk bahan ini.' }}
                                    </p>
                                </div>

                                <div class="rounded-2xl border border-white/10 bg-zinc-900/70 p-5">
                                    <p class="text-sm font-semibold text-white">Informasi stok</p>
                                    <p class="mt-2 text-3xl font-bold {{ $item->stok > 0 ? 'text-emerald-400' : 'text-red-400' }}">
                                        {{ $item->stok }}
                                        <span class="text-base font-medium text-zinc-400">{{ $item->satuan }}</span>
                                    </p>
                                    <p class="mt-2 text-xs text-zinc-500">
                                        Stok minimum: {{ $item->stok_minimum }} {{ $item->satuan }}
                                    </p>
                                </div>

                            @else
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Lokasi</p>
                                    <p class="mt-1 font-medium text-zinc-200">{{ $item->lokasi ?: 'Belum dicantumkan' }}</p>
                                </div>

                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Kapasitas</p>
                                    <p class="mt-1 font-medium text-zinc-200">{{ $item->kapasitas }} orang</p>
                                </div>

                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Deskripsi</p>
                                    <p class="mt-2 whitespace-pre-line text-sm leading-7 text-zinc-300">
                                        {{ $item->deskripsi ?: 'Belum ada deskripsi untuk laboratorium ini.' }}
                                    </p>
                                </div>

                                <div>
                                    <h3 class="font-semibold text-white">Jadwal Operasional</h3>

                                    @if ($jadwal->isEmpty())
                                        <p class="mt-3 rounded-xl border border-amber-400/20 bg-amber-500/10 p-4 text-sm text-amber-200">
                                            Belum ada jadwal operasional aktif untuk laboratorium ini.
                                        </p>
                                    @else
                                        <div class="mt-3 divide-y divide-white/10 overflow-hidden rounded-xl border border-white/10">
                                            @foreach ($jadwal as $slot)
                                                <div class="flex items-center justify-between gap-4 bg-zinc-900/50 px-4 py-3 text-sm">
                                                    <span class="font-medium capitalize text-zinc-200">{{ $slot->hari }}</span>
                                                    <span class="text-zinc-400">
                                                        {{ substr($slot->jam_mulai, 0, 5) }}
                                                        – {{ substr($slot->jam_selesai, 0, 5) }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    <p class="mt-3 text-xs leading-5 text-zinc-500">
                                        Jadwal operasional bukan jaminan slot kosong. Sistem akan memeriksa bentrok waktu saat pengajuan dikirim.
                                    </p>
                                </div>
                            @endif
                        </div>
                    </section>

                    <aside>
                        <div class="rounded-2xl border border-white/10 bg-zinc-900/70 p-5">
                            <p class="text-sm font-semibold text-white">Status inventaris</p>

                            @if ($jenis === 'alat')
                                @if ($item->unit_tersedia_count > 0)
                                    <p class="mt-3 inline-flex rounded-full border border-emerald-400/20 bg-emerald-500/10 px-3 py-1.5 text-xs font-semibold text-emerald-300">
                                        Tersedia
                                    </p>
                                @else
                                    <p class="mt-3 inline-flex rounded-full border border-red-400/20 bg-red-500/10 px-3 py-1.5 text-xs font-semibold text-red-300">
                                        Tidak tersedia
                                    </p>
                                @endif
                            @elseif ($jenis === 'bahan')
                                @if ($item->stok > 0)
                                    <p class="mt-3 inline-flex rounded-full border border-emerald-400/20 bg-emerald-500/10 px-3 py-1.5 text-xs font-semibold text-emerald-300">
                                        Stok tersedia
                                    </p>
                                @else
                                    <p class="mt-3 inline-flex rounded-full border border-red-400/20 bg-red-500/10 px-3 py-1.5 text-xs font-semibold text-red-300">
                                        Stok habis
                                    </p>
                                @endif
                            @else
                                @if ($jadwal->isNotEmpty())
                                    <p class="mt-3 inline-flex rounded-full border border-emerald-400/20 bg-emerald-500/10 px-3 py-1.5 text-xs font-semibold text-emerald-300">
                                        Jadwal operasional aktif
                                    </p>
                                @else
                                    <p class="mt-3 inline-flex rounded-full border border-amber-400/20 bg-amber-500/10 px-3 py-1.5 text-xs font-semibold text-amber-300">
                                        Jadwal belum tersedia
                                    </p>
                                @endif
                            @endif

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
                                    <p class="mt-5 rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 text-center text-xs leading-5 text-zinc-400">
                                        {{ $jenis === 'laboratorium' ? 'Pengajuan belum dapat dilakukan karena jadwal operasional belum tersedia.' : 'Inventaris ini belum tersedia untuk diajukan.' }}
                                    </p>
                                @endif

                                @guest
                                    <p class="mt-3 text-xs leading-5 text-zinc-500">
                                        Kamu perlu masuk ke akun terlebih dahulu untuk mengajukan permohonan.
                                    </p>
                                @endguest
                            @else
                                <p class="mt-5 rounded-xl border border-white/10 bg-white/[0.03] p-3 text-xs leading-5 text-zinc-400">
                                    Akun admin dan superadmin dapat melihat katalog, tetapi tidak mengajukan peminjaman melalui halaman publik.
                                </p>
                            @endif
                        </div>
                    </aside>
                </div>
            </div>

            <footer class="mt-16 border-t border-white/10 pt-7 text-sm text-zinc-500">
                © {{ date('Y') }} Inventaris Laboratorium UNM.
            </footer>
        </div>
    </main>