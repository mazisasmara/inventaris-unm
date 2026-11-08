
@php
    use App\Models\Alat;
    use App\Models\Bahan;
    use App\Models\Laboratorium;
    use App\Models\UnitAlat;
    use App\Models\PeminjamanAlat;
    use App\Models\PeminjamanLab;
    use App\Models\PengambilanBahan;
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Collection;

    $user = auth()->user();
    $role = $user->role;
    $isAdmin = in_array($role, ['admin', 'superadmin'], true);

    $tanggalMulai = now()->startOfMonth()->subMonths(11);
    $tanggalAkhir = now()->endOfMonth();

    $labelsBulan = collect(range(0, 11))
        ->map(fn ($i) => $tanggalMulai->copy()->addMonths($i)->format('M Y'))
        ->values()
        ->all();

    /*
    |--------------------------------------------------------------------------
    | Helper statistik
    |--------------------------------------------------------------------------
    */


    $hitungBulanan = function ($query) use ($tanggalMulai, $tanggalAkhir) {
        return $query
            ->whereBetween('tanggal_pengajuan', [$tanggalMulai, $tanggalAkhir])
            ->get(['tanggal_pengajuan'])
            ->groupBy(fn ($item) => $item->tanggal_pengajuan->format('Y-m'))
            ->map(fn ($items) => $items->count());
    };

    $susunBulanan = function (Collection $data) use ($tanggalMulai) {
        return collect(range(0, 11))
            ->map(function ($i) use ($data, $tanggalMulai) {
                $key = $tanggalMulai->copy()->addMonths($i)->format('Y-m');

                return $data->get($key, 0);
            })
            ->values()
            ->all();
    };

    $statusAktifAlat = ['disetujui', 'dipinjam'];
    $statusAktifLab = ['disetujui', 'berlangsung'];
    $statusAktifBahan = ['disetujui', 'diserahkan'];

    /*
    |--------------------------------------------------------------------------
    | Data untuk admin dan superadmin
    |--------------------------------------------------------------------------
    */

    if ($isAdmin) {
        $totalAlat = Alat::count();
        $totalUnit = UnitAlat::count();
        $totalBahan = Bahan::count();
        $totalLab = Laboratorium::count();

        $menungguAlat = PeminjamanAlat::where('status', 'menunggu')->count();
        $menungguLab = PeminjamanLab::where('status', 'menunggu')->count();
        $menungguBahan = PengambilanBahan::where('status', 'menunggu')->count();

        $totalMenunggu = $menungguAlat + $menungguLab + $menungguBahan;

        $totalTransaksi =
            PeminjamanAlat::count()
            + PeminjamanLab::count()
            + PengambilanBahan::count();

        $totalAktif =
            PeminjamanAlat::whereIn('status', $statusAktifAlat)->count()
            + PeminjamanLab::whereIn('status', $statusAktifLab)->count()
            + PengambilanBahan::whereIn('status', $statusAktifBahan)->count();

        $totalSelesai =
            PeminjamanAlat::where('status', 'selesai')->count()
            + PeminjamanLab::where('status', 'selesai')->count()
            + PengambilanBahan::where('status', 'selesai')->count();

        $totalDitolak =
            PeminjamanAlat::where('status', 'ditolak')->count()
            + PeminjamanLab::where('status', 'ditolak')->count()
            + PengambilanBahan::where('status', 'ditolak')->count();

        /*
        |--------------------------------------------------------------------------
        | Grafik transaksi bulanan
        |--------------------------------------------------------------------------
        */

        $bulananAlat = $hitungBulanan(PeminjamanAlat::query());
        $bulananLab = $hitungBulanan(PeminjamanLab::query());
        $bulananBahan = $hitungBulanan(PengambilanBahan::query());

        $chartTransaksi = [
            'labels' => $labelsBulan,
            'datasets' => [
                [
                    'label' => 'Peminjaman Alat',
                    'data' => $susunBulanan($bulananAlat),
                    'backgroundColor' => '#3b82f6',
                    'borderRadius' => 5,
                ],
                [
                    'label' => 'Peminjaman Lab',
                    'data' => $susunBulanan($bulananLab),
                    'backgroundColor' => '#8b5cf6',
                    'borderRadius' => 5,
                ],
                [
                    'label' => 'Pengambilan Bahan',
                    'data' => $susunBulanan($bulananBahan),
                    'backgroundColor' => '#10b981',
                    'borderRadius' => 5,
                ],
            ],
        ];

        $chartJenis = [
            'labels' => [
                'Peminjaman Alat',
                'Peminjaman Lab',
                'Pengambilan Bahan',
            ],
            'datasets' => [
                [
                    'data' => [
                        PeminjamanAlat::count(),
                        PeminjamanLab::count(),
                        PengambilanBahan::count(),
                    ],
                    'backgroundColor' => [
                        '#3b82f6',
                        '#8b5cf6',
                        '#10b981',
                    ],
                    'borderWidth' => 0,
                ],
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Aktivitas transaksi terbaru
        |--------------------------------------------------------------------------
        */

        $aktivitasAlat = PeminjamanAlat::with('user')
            ->latest('tanggal_pengajuan')
            ->take(8)
            ->get()
            ->map(fn ($item) => [
                'jenis' => 'Peminjaman Alat',
                'peminjam' => $item->user?->name ?? 'Pengguna tidak ditemukan',
                'status' => $item->status,
                'tanggal' => $item->tanggal_pengajuan,
            ]);

        $aktivitasLab = PeminjamanLab::with('user')
            ->latest('tanggal_pengajuan')
            ->take(8)
            ->get()
            ->map(fn ($item) => [
                'jenis' => 'Peminjaman Lab',
                'peminjam' => $item->user?->name ?? 'Pengguna tidak ditemukan',
                'status' => $item->status,
                'tanggal' => $item->tanggal_pengajuan,
            ]);

        $aktivitasBahan = PengambilanBahan::with('user')
            ->latest('tanggal_pengajuan')
            ->take(8)
            ->get()
            ->map(fn ($item) => [
                'jenis' => 'Pengambilan Bahan',
                'peminjam' => $item->user?->name ?? 'Pengguna tidak ditemukan',
                'status' => $item->status,
                'tanggal' => $item->tanggal_pengajuan,
            ]);

        $aktivitasTerbaru = $aktivitasAlat
            ->concat($aktivitasLab)
            ->concat($aktivitasBahan)
            ->sortByDesc('tanggal')
            ->take(8)
            ->values();

        $chartData = [
            'transaksi' => [
                'type' => 'bar',
                'data' => $chartTransaksi,
                'options' => [
                    'plugins' => [
                        'legend' => [
                            'position' => 'bottom',
                        ],
                    ],
                    'scales' => [
                        'y' => [
                            'beginAtZero' => true,
                            'ticks' => [
                                'precision' => 0,
                            ],
                        ],
                    ],
                ],
            ],
            'jenis' => [
                'type' => 'doughnut',
                'data' => $chartJenis,
                'options' => [
                    'cutout' => '65%',
                    'plugins' => [
                        'legend' => [
                            'position' => 'bottom',
                        ],
                    ],
                ],
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Data untuk mahasiswa dan dosen
    |--------------------------------------------------------------------------
    */

    else {
        $queryAlat = PeminjamanAlat::where('user_id', $user->id);
        $queryLab = PeminjamanLab::where('user_id', $user->id);
        $queryBahan = PengambilanBahan::where('user_id', $user->id);

        $totalTransaksi =
            (clone $queryAlat)->count()
            + (clone $queryLab)->count()
            + (clone $queryBahan)->count();

        $totalMenunggu =
            (clone $queryAlat)->where('status', 'menunggu')->count()
            + (clone $queryLab)->where('status', 'menunggu')->count()
            + (clone $queryBahan)->where('status', 'menunggu')->count();

        $totalAktif =
            (clone $queryAlat)->whereIn('status', $statusAktifAlat)->count()
            + (clone $queryLab)->whereIn('status', $statusAktifLab)->count()
            + (clone $queryBahan)->whereIn('status', $statusAktifBahan)->count();

        $totalSelesai =
            (clone $queryAlat)->where('status', 'selesai')->count()
            + (clone $queryLab)->where('status', 'selesai')->count()
            + (clone $queryBahan)->where('status', 'selesai')->count();

        $totalDitolak =
            (clone $queryAlat)->where('status', 'ditolak')->count()
            + (clone $queryLab)->where('status', 'ditolak')->count()
            + (clone $queryBahan)->where('status', 'ditolak')->count();

        $bulananAlat = $hitungBulanan(
            PeminjamanAlat::where('user_id', $user->id)
        );

        $bulananLab = $hitungBulanan(
            PeminjamanLab::where('user_id', $user->id)
        );

        $bulananBahan = $hitungBulanan(
            PengambilanBahan::where('user_id', $user->id)
        );

        $dataBulananAlat = $susunBulanan($bulananAlat);
        $dataBulananLab = $susunBulanan($bulananLab);
        $dataBulananBahan = $susunBulanan($bulananBahan);

        $dataBulananTotal = collect(range(0, 11))
            ->map(fn ($i) =>
                $dataBulananAlat[$i]
                + $dataBulananLab[$i]
                + $dataBulananBahan[$i]
            )
            ->all();

        $chartData = [
            'transaksi' => [
                'type' => 'line',
                'data' => [
                    'labels' => $labelsBulan,
                    'datasets' => [
                        [
                            'label' => 'Transaksi Saya',
                            'data' => $dataBulananTotal,
                            'borderColor' => '#3b82f6',
                            'backgroundColor' => 'rgba(59, 130, 246, 0.12)',
                            'fill' => true,
                            'tension' => 0.35,
                        ],
                    ],
                ],
                'options' => [
                    'plugins' => [
                        'legend' => [
                            'display' => false,
                        ],
                    ],
                    'scales' => [
                        'y' => [
                            'beginAtZero' => true,
                            'ticks' => [
                                'precision' => 0,
                            ],
                        ],
                    ],
                ],
            ],
            'jenis' => [
                'type' => 'doughnut',
                'data' => [
                    'labels' => [
                        'Menunggu',
                        'Sedang diproses / berlangsung',
                        'Selesai',
                        'Ditolak',
                    ],
                    'datasets' => [
                        [
                            'data' => [
                                $totalMenunggu,
                                $totalAktif,
                                $totalSelesai,
                                $totalDitolak,
                            ],
                            'backgroundColor' => [
                                '#f59e0b',
                                '#3b82f6',
                                '#10b981',
                                '#ef4444',
                            ],
                            'borderWidth' => 0,
                        ],
                    ],
                ],
                'options' => [
                    'cutout' => '65%',
                    'plugins' => [
                        'legend' => [
                            'position' => 'bottom',
                        ],
                    ],
                ],
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Aktivitas pribadi
        |--------------------------------------------------------------------------
        */

        $aktivitasAlat = (clone $queryAlat)
            ->latest('tanggal_pengajuan')
            ->take(8)
            ->get()
            ->map(fn ($item) => [
                'jenis' => 'Peminjaman Alat',
                'peminjam' => $user->name,
                'status' => $item->status,
                'tanggal' => $item->tanggal_pengajuan,
            ]);

        $aktivitasLab = (clone $queryLab)
            ->latest('tanggal_pengajuan')
            ->take(8)
            ->get()
            ->map(fn ($item) => [
                'jenis' => 'Peminjaman Lab',
                'peminjam' => $user->name,
                'status' => $item->status,
                'tanggal' => $item->tanggal_pengajuan,
            ]);

        $aktivitasBahan = (clone $queryBahan)
            ->latest('tanggal_pengajuan')
            ->take(8)
            ->get()
            ->map(fn ($item) => [
                'jenis' => 'Pengambilan Bahan',
                'peminjam' => $user->name,
                'status' => $item->status,
                'tanggal' => $item->tanggal_pengajuan,
            ]);

        $aktivitasTerbaru = $aktivitasAlat
            ->concat($aktivitasLab)
            ->concat($aktivitasBahan)
            ->sortByDesc('tanggal')
            ->take(8)
            ->values();
    }

    $urlRiwayat = $isAdmin
        ? route('admin.riwayat-transaksi.index')
        : route($role . '.riwayat-transaksi.index');

    $formatStatus = fn ($status) => ucfirst(str_replace('_', ' ', $status));

    $warnaStatus = function ($status) {
        return match ($status) {
            'menunggu' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
            'disetujui', 'selesai' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400',
            'dipinjam', 'diserahkan', 'berlangsung' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400',
            'ditolak' => 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400',
            default => 'bg-neutral-100 text-neutral-700 dark:bg-neutral-700 dark:text-neutral-300',
        };
    };
@endphp

<x-layouts::app :title="__('Dashboard')">
    <div class="flex flex-col gap-6">

        {{-- HEADER --}}
        <div class="flex flex-col gap-1">
            <p class="text-sm text-neutral-500 dark:text-neutral-400">
                Sistem Informasi Inventaris dan Peminjaman Laboratorium
            </p>

            <h1 class="text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">
                Selamat datang, {{ $user->name }}!
            </h1>

            <p class="text-sm text-neutral-500 dark:text-neutral-400">
                @if ($isAdmin)
                    Berikut ringkasan inventaris dan aktivitas seluruh laboratorium.
                @else
                    Berikut ringkasan aktivitas dan transaksi akun Anda.
                @endif
            </p>
        </div>

        {{-- STATISTIK ADMIN --}}
        @if ($isAdmin)
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

                <div class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-neutral-900">
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">Jenis Alat</p>
                    <p class="mt-3 text-3xl font-bold text-neutral-900 dark:text-white">{{ number_format($totalAlat) }}</p>
                    <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">{{ number_format($totalUnit) }} unit inventaris</p>
                </div>

                <div class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-neutral-900">
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">Jenis Bahan</p>
                    <p class="mt-3 text-3xl font-bold text-neutral-900 dark:text-white">{{ number_format($totalBahan) }}</p>
                    <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">Data bahan terdaftar</p>
                </div>

                <div class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-neutral-900">
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">Laboratorium</p>
                    <p class="mt-3 text-3xl font-bold text-neutral-900 dark:text-white">{{ number_format($totalLab) }}</p>
                    <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">Laboratorium terdaftar</p>
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-500/20 dark:bg-amber-500/5">
                    <p class="text-sm text-amber-700 dark:text-amber-400">Menunggu Persetujuan</p>
                    <p class="mt-3 text-3xl font-bold text-amber-800 dark:text-amber-300">{{ number_format($totalMenunggu) }}</p>
                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-400">
                        {{ $menungguAlat }} alat · {{ $menungguLab }} lab · {{ $menungguBahan }} bahan
                    </p>
                </div>

            </div>

        {{-- STATISTIK MAHASISWA DAN DOSEN --}}
        @else
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

                <div class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-neutral-900">
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">Total Transaksi</p>
                    <p class="mt-3 text-3xl font-bold text-neutral-900 dark:text-white">{{ number_format($totalTransaksi) }}</p>
                    <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">Seluruh transaksi akun Anda</p>
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-500/20 dark:bg-amber-500/5">
                    <p class="text-sm text-amber-700 dark:text-amber-400">Menunggu Persetujuan</p>
                    <p class="mt-3 text-3xl font-bold text-amber-800 dark:text-amber-300">{{ number_format($totalMenunggu) }}</p>
                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-400">Menunggu diproses admin</p>
                </div>

                <div class="rounded-xl border border-blue-200 bg-blue-50 p-5 dark:border-blue-500/20 dark:bg-blue-500/5">
                    <p class="text-sm text-blue-700 dark:text-blue-400">Transaksi Aktif</p>
                    <p class="mt-3 text-3xl font-bold text-blue-800 dark:text-blue-300">{{ number_format($totalAktif) }}</p>
                    <p class="mt-1 text-xs text-blue-700 dark:text-blue-400">Sedang diproses atau berlangsung</p>
                </div>

                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-500/20 dark:bg-emerald-500/5">
                    <p class="text-sm text-emerald-700 dark:text-emerald-400">Transaksi Selesai</p>
                    <p class="mt-3 text-3xl font-bold text-emerald-800 dark:text-emerald-300">{{ number_format($totalSelesai) }}</p>
                    <p class="mt-1 text-xs text-emerald-700 dark:text-emerald-400">Transaksi telah diselesaikan</p>
                </div>

            </div>
        @endif

        {{-- GRAFIK --}}
        <div class="grid gap-6 xl:grid-cols-3">

            <div class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-neutral-900 xl:col-span-2">
                <div class="mb-5">
                    <h2 class="font-semibold text-neutral-900 dark:text-white">
                        {{ $isAdmin ? 'Statistik Transaksi Bulanan' : 'Aktivitas Transaksi Bulanan' }}
                    </h2>
                    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                        {{ $isAdmin ? 'Perbandingan transaksi seluruh sistem dalam 12 bulan terakhir.' : 'Jumlah transaksi pribadi dalam 12 bulan terakhir.' }}
                    </p>
                </div>

                <div class="relative h-72 w-full">
                    <canvas data-dashboard-chart="transaksi"></canvas>
                </div>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-neutral-900">
                <div class="mb-5">
                    <h2 class="font-semibold text-neutral-900 dark:text-white">
                        {{ $isAdmin ? 'Komposisi Transaksi' : 'Status Transaksi' }}
                    </h2>
                    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                        {{ $isAdmin ? 'Perbandingan berdasarkan jenis transaksi.' : 'Ringkasan status transaksi pribadi.' }}
                    </p>
                </div>

                <div class="relative mx-auto h-72 w-full max-w-xs">
                    <canvas data-dashboard-chart="jenis"></canvas>
                </div>
            </div>

        </div>

        {{-- RINGKASAN ADMIN --}}
        @if ($isAdmin)
            <div class="grid gap-4 sm:grid-cols-3">

                <div class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-neutral-900">
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">Seluruh Transaksi</p>
                    <p class="mt-2 text-2xl font-bold text-neutral-900 dark:text-white">{{ number_format($totalTransaksi) }}</p>
                </div>

                <div class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-neutral-900">
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">Transaksi Aktif</p>
                    <p class="mt-2 text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($totalAktif) }}</p>
                </div>

                <div class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-neutral-900">
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">Transaksi Ditolak</p>
                    <p class="mt-2 text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($totalDitolak) }}</p>
                </div>

            </div>
        @else
            <div class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-neutral-900">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="font-semibold text-neutral-900 dark:text-white">Pantau transaksi Anda</h2>
                        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                            Lihat detail pengajuan, status persetujuan, dan informasi pengembalian.
                        </p>
                    </div>

                    <a href="{{ $urlRiwayat }}"
                       wire:navigate
                       class="inline-flex w-fit items-center gap-2 rounded-lg bg-neutral-900 px-4 py-2 text-sm font-medium text-white hover:bg-neutral-700 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-200">
                        Lihat Riwayat
                    </a>
                </div>
            </div>
        @endif

        {{-- AKTIVITAS TERBARU --}}
        <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900">

            <div class="flex flex-col gap-3 border-b border-neutral-200 p-5 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-700">
                <div>
                    <h2 class="font-semibold text-neutral-900 dark:text-white">
                        {{ $isAdmin ? 'Aktivitas Transaksi Terbaru' : 'Transaksi Terbaru Saya' }}
                    </h2>
                    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                        {{ $isAdmin ? 'Transaksi terbaru dari seluruh pengguna.' : 'Aktivitas transaksi terbaru akun Anda.' }}
                    </p>
                </div>

                <a href="{{ $urlRiwayat }}"
                   wire:navigate
                   class="text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                    Lihat semua transaksi →
                </a>
            </div>

            @if ($aktivitasTerbaru->isEmpty())
                <div class="p-10 text-center">
                    <p class="font-medium text-neutral-700 dark:text-neutral-300">Belum ada transaksi</p>
                    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                        Aktivitas transaksi akan muncul di sini.
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-neutral-50 text-xs uppercase text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400">
                            <tr>
                                <th class="px-5 py-3 font-medium">Jenis Transaksi</th>
                                @if ($isAdmin)
                                    <th class="px-5 py-3 font-medium">Peminjam</th>
                                @endif
                                <th class="px-5 py-3 font-medium">Tanggal</th>
                                <th class="px-5 py-3 font-medium">Status</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                            @foreach ($aktivitasTerbaru as $aktivitas)
                                <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-800/50">
                                    <td class="whitespace-nowrap px-5 py-4 font-medium text-neutral-900 dark:text-white">
                                        {{ $aktivitas['jenis'] }}
                                    </td>

                                    @if ($isAdmin)
                                        <td class="whitespace-nowrap px-5 py-4 text-neutral-600 dark:text-neutral-300">
                                            {{ $aktivitas['peminjam'] }}
                                        </td>
                                    @endif

                                    <td class="whitespace-nowrap px-5 py-4 text-neutral-500 dark:text-neutral-400">
                                        {{ $aktivitas['tanggal']?->format('d M Y, H:i') ?? '-' }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $warnaStatus($aktivitas['status']) }}">
                                            {{ $formatStatus($aktivitas['status']) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

        </div>

    </div>

    {{-- DATA GRAFIK UNTUK APP.JS --}}
    <script type="application/json" id="dashboard-chart-data">
        @json($chartData)
    </script>
</x-layouts::app>
