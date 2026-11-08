<?php

use App\Models\PengambilanBahan;
use App\Models\PeminjamanAlat;
use App\Models\PeminjamanLab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $jenis = '';
    public string $status = '';
    public string $tanggalMulai = '';
    public string $tanggalSelesai = '';

    public ?string $detailJenis = null;
    public ?int $detailId = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'jenis' => ['except' => ''],
        'status' => ['except' => ''],
        'tanggalMulai' => ['except' => ''],
        'tanggalSelesai' => ['except' => ''],
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedJenis(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedTanggalMulai(): void
    {
        $this->resetPage();
    }

    public function updatedTanggalSelesai(): void
    {
        $this->resetPage();
    }

    private function hanyaMilikSendiri(Builder $query): Builder
    {
        $user = Auth::user();

        if (in_array($user->role, ['mahasiswa', 'dosen'], true)) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    private function terapkanFilter(Builder $query): Builder
    {
        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        if ($this->tanggalMulai !== '') {
            $query->whereDate('tanggal_pengajuan', '>=', $this->tanggalMulai);
        }

        if ($this->tanggalSelesai !== '') {
            $query->whereDate('tanggal_pengajuan', '<=', $this->tanggalSelesai);
        }

        return $query;
    }

    private function terapkanPencarian(Builder $query): Builder
    {
        if (trim($this->search) === '') {
            return $query;
        }

        $search = trim($this->search);

        $query->where(function (Builder $q) use ($search) {
            $q->where('tujuan', 'like', "%{$search}%")
                ->orWhereHas('user', function (Builder $user) use ($search) {
                    $user->where('name', 'like', "%{$search}%")
                        ->orWhere('identity_number', 'like', "%{$search}%");
                });
        });

        return $query;
    }

    private function queryTransaksi(string $model): Builder
    {
        return $this->terapkanPencarian(
            $this->terapkanFilter(
                $this->hanyaMilikSendiri($model::query())
            )
        );
    }

    private function semuaQuery(): Collection
    {
        $transaksi = collect();

        $jenisYangDicari = $this->jenis !== ''
            ? [$this->jenis]
            : ['bahan', 'alat', 'lab'];

        if (in_array('bahan', $jenisYangDicari, true)) {
            $bahan = $this->queryTransaksi(PengambilanBahan::class)
                ->with(['user', 'pemroses'])
                ->get()
                ->map(fn ($item) => [
                    'id' => $item->id,
                    'jenis' => 'bahan',
                    'nama_jenis' => 'Pengambilan Bahan',
                    'kode' => 'BHN-' . str_pad($item->id, 5, '0', STR_PAD_LEFT),
                    'pemohon' => $item->user?->name ?? '-',
                    'identity' => $item->user?->identity_number ?? '-',
                    'tujuan' => $item->tujuan,
                    'status' => $item->status,
                    'tanggal' => $item->tanggal_pengajuan,
                    'pemroses' => $item->pemroses?->name ?? '-',
                ]);

            $transaksi = $transaksi->concat($bahan);
        }

        if (in_array('alat', $jenisYangDicari, true)) {
            $alat = $this->queryTransaksi(PeminjamanAlat::class)
                ->with(['user', 'pemroses'])
                ->get()
                ->map(fn ($item) => [
                    'id' => $item->id,
                    'jenis' => 'alat',
                    'nama_jenis' => 'Peminjaman Alat',
                    'kode' => 'ALT-' . str_pad($item->id, 5, '0', STR_PAD_LEFT),
                    'pemohon' => $item->user?->name ?? '-',
                    'identity' => $item->user?->identity_number ?? '-',
                    'tujuan' => $item->tujuan,
                    'status' => $item->status,
                    'tanggal' => $item->tanggal_pengajuan,
                    'pemroses' => $item->pemroses?->name ?? '-',
                ]);

            $transaksi = $transaksi->concat($alat);
        }

        if (in_array('lab', $jenisYangDicari, true)) {
            $lab = $this->queryTransaksi(PeminjamanLab::class)
                ->with(['user', 'pemroses'])
                ->get()
                ->map(fn ($item) => [
                    'id' => $item->id,
                    'jenis' => 'lab',
                    'nama_jenis' => 'Peminjaman Laboratorium',
                    'kode' => 'LAB-' . str_pad($item->id, 5, '0', STR_PAD_LEFT),
                    'pemohon' => $item->user?->name ?? '-',
                    'identity' => $item->user?->identity_number ?? '-',
                    'tujuan' => $item->tujuan,
                    'status' => $item->status,
                    'tanggal' => $item->tanggal_pengajuan,
                    'pemroses' => $item->pemroses?->name ?? '-',
                ]);

            $transaksi = $transaksi->concat($lab);
        }

        return $transaksi
            ->sortByDesc(fn ($item) => $item['tanggal']?->timestamp ?? 0)
            ->values();
    }

    public function getStatistikProperty(): array
    {
        $user = Auth::user();

        $query = function (string $model) {
            return $this->hanyaMilikSendiri($model::query());
        };

        $semua = $query(PengambilanBahan::class)->count()
            + $query(PeminjamanAlat::class)->count()
            + $query(PeminjamanLab::class)->count();

        $selesai = $query(PengambilanBahan::class)->where('status', 'selesai')->count()
            + $query(PeminjamanAlat::class)->where('status', 'selesai')->count()
            + $query(PeminjamanLab::class)->where('status', 'selesai')->count();

        $menunggu = $query(PengambilanBahan::class)->whereIn('status', ['menunggu', 'diajukan'])->count()
            + $query(PeminjamanAlat::class)->whereIn('status', ['menunggu', 'diajukan'])->count()
            + $query(PeminjamanLab::class)->whereIn('status', ['menunggu', 'diajukan'])->count();

        return [
            'total' => $semua,
            'selesai' => $selesai,
            'menunggu' => $menunggu,
        ];
    }

    public function bukaDetail(string $jenis, int $id): void
    {
        abort_unless(in_array($jenis, ['bahan', 'alat', 'lab'], true), 404);

        $model = match ($jenis) {
            'bahan' => PengambilanBahan::class,
            'alat' => PeminjamanAlat::class,
            'lab' => PeminjamanLab::class,
        };

        $query = $this->hanyaMilikSendiri($model::query());

        abort_unless($query->whereKey($id)->exists(), 404);

        $this->detailJenis = $jenis;
        $this->detailId = $id;
    }

    public function tutupDetail(): void
    {
        $this->detailJenis = null;
        $this->detailId = null;
    }

    public function getDetailTransaksiProperty(): mixed
    {
        if (!$this->detailJenis || !$this->detailId) {
            return null;
        }

        return match ($this->detailJenis) {
            'bahan' => PengambilanBahan::with([
                'user',
                'pemroses',
                'detail.bahan',
                'pengembalian.pemroses',
                'pengembalian.detail.detailPengambilanBahan.bahan',
            ])->find($this->detailId),

            'alat' => PeminjamanAlat::with([
                'user',
                'pemroses',
                'detail.alat',
                'detail.unitPeminjaman.unitAlat',
                'detail.unitPeminjaman.detailPengembalian',
                'pengembalian.pemroses',
                'pengembalian.detail.unitPeminjaman.unitAlat',
            ])->find($this->detailId),

            'lab' => PeminjamanLab::with([
                'user',
                'pemroses',
                'laboratorium',
                'dokumentasi.pengunggah',
            ])->find($this->detailId),

            default => null,
        };
    }

    public function render()
    {
        $semua = $this->semuaQuery();

        $total = $semua->count();
        $perHalaman = 10;
        $halaman = $this->getPage();

        $riwayat = $semua
            ->slice(($halaman - 1) * $perHalaman, $perHalaman)
            ->values();

        return $this->view([
            'riwayat' => $riwayat,
            'total' => $total,
            'halaman' => $halaman,
            'perHalaman' => $perHalaman,
            'totalHalaman' => max(1, (int) ceil($total / $perHalaman)),
        ]);
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                Riwayat Transaksi
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Pantau seluruh aktivitas pengambilan bahan, peminjaman alat, dan peminjaman laboratorium.
            </p>
        </div>
    </div>

    {{-- Statistik --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
            <p class="text-sm text-gray-500 dark:text-gray-400">Total Transaksi</p>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                {{ number_format($this->statistik['total']) }}
            </p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
            <p class="text-sm text-gray-500 dark:text-gray-400">Transaksi Selesai</p>
            <p class="mt-2 text-3xl font-bold text-green-600">
                {{ number_format($this->statistik['selesai']) }}
            </p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
            <p class="text-sm text-gray-500 dark:text-gray-400">Menunggu Proses</p>
            <p class="mt-2 text-3xl font-bold text-amber-600">
                {{ number_format($this->statistik['menunggu']) }}
            </p>
        </div>
    </div>

    {{-- Filter --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <div class="xl:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Pencarian
                </label>
                <input
                    type="search"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Nama, nomor identitas, atau tujuan..."
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                >
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Jenis Transaksi
                </label>
                <select
                    wire:model.live="jenis"
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                >
                    <option value="">Semua Jenis</option>
                    <option value="bahan">Pengambilan Bahan</option>
                    <option value="alat">Peminjaman Alat</option>
                    <option value="lab">Peminjaman Laboratorium</option>
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Status
                </label>
                <select
                    wire:model.live="status"
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                >
                    <option value="">Semua Status</option>
                    <option value="menunggu">Menunggu</option>
                    <option value="diajukan">Diajukan</option>
                    <option value="diproses">Diproses</option>
                    <option value="disetujui">Disetujui</option>
                    <option value="ditolak">Ditolak</option>
                    <option value="diserahkan">Diserahkan</option>
                    <option value="dipinjam">Dipinjam</option>
                    <option value="selesai">Selesai</option>
                    <option value="dibatalkan">Dibatalkan</option>
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Tanggal Mulai
                </label>
                <input
                    type="date"
                    wire:model.live="tanggalMulai"
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                >
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Tanggal Selesai
                </label>
                <input
                    type="date"
                    wire:model.live="tanggalSelesai"
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                >
            </div>
        </div>

        <div class="mt-4 flex justify-end">
            <button
                type="button"
                wire:click="$set('search', ''); $set('jenis', ''); $set('status', ''); $set('tanggalMulai', ''); $set('tanggalSelesai', '')"
                class="text-sm font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
            >
                Reset Filter
            </button>
        </div>
    </div>

    {{-- Tabel --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-700">
            <h2 class="font-semibold text-gray-900 dark:text-white">Daftar Transaksi</h2>
            <span class="text-sm text-gray-500 dark:text-gray-400">
                {{ number_format($total) }} transaksi ditemukan
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[850px] text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                    <tr>
                        <th class="px-5 py-3">Transaksi</th>
                        <th class="px-5 py-3">Pemohon</th>
                        <th class="px-5 py-3">Tujuan</th>
                        <th class="px-5 py-3">Tanggal</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($riwayat as $item)
                        @php
                            $warnaStatus = match ($item['status']) {
                                'selesai' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                'ditolak', 'dibatalkan' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                'menunggu', 'diajukan' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                default => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                            };
                        @endphp

                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    {{ $item['nama_jenis'] }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500">
                                    {{ $item['kode'] }}
                                </p>
                            </td>

                            <td class="px-5 py-4">
                                <p class="font-medium text-gray-800 dark:text-gray-200">
                                    {{ $item['pemohon'] }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500">
                                    {{ $item['identity'] }}
                                </p>
                            </td>

                            <td class="max-w-xs px-5 py-4 text-gray-600 dark:text-gray-300">
                                <span class="line-clamp-2">{{ $item['tujuan'] ?: '-' }}</span>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-gray-600 dark:text-gray-300">
                                {{ $item['tanggal']?->format('d M Y, H:i') ?? '-' }}
                            </td>

                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $warnaStatus }}">
                                    {{ ucfirst(str_replace('_', ' ', $item['status'])) }}
                                </span>
                            </td>

                            <td class="px-5 py-4 text-right">
                                <button
                                    type="button"
                                    wire:click="bukaDetail('{{ $item['jenis'] }}', {{ $item['id'] }})"
                                    class="font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400"
                                >
                                    Detail
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-14 text-center">
                                <p class="font-medium text-gray-700 dark:text-gray-300">
                                    Tidak ada transaksi ditemukan
                                </p>
                                <p class="mt-1 text-sm text-gray-500">
                                    Coba ubah kata kunci atau filter pencarian.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination manual --}}
        @if ($totalHalaman > 1)
            <div class="flex flex-col gap-3 border-t border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Halaman {{ $halaman }} dari {{ $totalHalaman }}
                </p>

                <div class="flex gap-2">
                    <button
                        type="button"
                        wire:click="previousPage"
                        @disabled($halaman <= 1)
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-600"
                    >
                        Sebelumnya
                    </button>

                    <button
                        type="button"
                        wire:click="nextPage"
                        @disabled($halaman >= $totalHalaman)
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-600"
                    >
                        Berikutnya
                    </button>
                </div>
            </div>
        @endif
    </div>

    {{-- Modal detail --}}
    @if ($this->detailTransaksi)
        @php
            $detail = $this->detailTransaksi;
        @endphp

        <div
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 p-4"
            wire:click.self="tutupDetail"
            x-data
            @keydown.escape.window="$wire.tutupDetail()"
        >
            <div class="my-auto max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white shadow-xl dark:bg-gray-900">
                <div class="sticky top-0 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-900">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                            Detail Transaksi
                        </h2>
                        <p class="text-sm text-gray-500">
                            {{ $detail->user?->name ?? '-' }}
                        </p>
                    </div>

                    <button
                        type="button"
                        wire:click="tutupDetail"
                        class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800"
                    >
                        ✕
                    </button>
                </div>

                <div class="space-y-6 p-6">
                    {{-- Informasi umum --}}
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-xs text-gray-500">Nomor Transaksi</p>
                            <p class="mt-1 font-semibold text-gray-900 dark:text-white">
                                {{ strtoupper($detailJenis) }}-{{ str_pad($detail->id, 5, '0', STR_PAD_LEFT) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-gray-500">Status</p>
                            <p class="mt-1 font-semibold text-gray-900 dark:text-white">
                                {{ ucfirst(str_replace('_', ' ', $detail->status)) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-gray-500">Pemohon</p>
                            <p class="mt-1 font-medium text-gray-900 dark:text-white">
                                {{ $detail->user?->name ?? '-' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-gray-500">Nomor Identitas</p>
                            <p class="mt-1 font-medium text-gray-900 dark:text-white">
                                {{ $detail->user?->identity_number ?? '-' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-gray-500">Tanggal Pengajuan</p>
                            <p class="mt-1 font-medium text-gray-900 dark:text-white">
                                {{ $detail->tanggal_pengajuan?->format('d M Y, H:i') ?? '-' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-gray-500">Diproses Oleh</p>
                            <p class="mt-1 font-medium text-gray-900 dark:text-white">
                                {{ $detail->pemroses?->name ?? '-' }}
                            </p>
                        </div>

                        <div class="sm:col-span-2">
                            <p class="text-xs text-gray-500">Tujuan</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">
                                {{ $detail->tujuan ?: '-' }}
                            </p>
                        </div>

                        @if ($detail->catatan_admin)
                            <div class="sm:col-span-2">
                                <p class="text-xs text-gray-500">Catatan Admin</p>
                                <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">
                                    {{ $detail->catatan_admin }}
                                </p>
                            </div>
                        @endif
                    </div>

                    {{-- Detail bahan --}}
                    @if ($detailJenis === 'bahan')
                        <div>
                            <h3 class="mb-3 font-semibold text-gray-900 dark:text-white">
                                Bahan yang Diambil
                            </h3>

                            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50 dark:bg-gray-800">
                                        <tr>
                                            <th class="px-4 py-3 text-left">Bahan</th>
                                            <th class="px-4 py-3 text-center">Diambil</th>
                                            <th class="px-4 py-3 text-center">Dikembalikan</th>
                                            <th class="px-4 py-3 text-center">Layak</th>
                                            <th class="px-4 py-3 text-center">Tidak Layak</th>
                                        </tr>
                                    </thead>

                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                        @foreach ($detail->detail as $item)
                                            @php
                                                $pengembalian = $detail->pengembalian
                                                    ->flatMap(fn ($p) => $p->detail)
                                                    ->firstWhere('detail_pengambilan_bahan_id', $item->id);

                                                $jumlahDikembalikan = (int) ($pengembalian?->jumlah_dikembalikan ?? 0);
                                                $jumlahLayak = (int) ($pengembalian?->jumlah_layak ?? 0);
                                            @endphp

                                            <tr>
                                                <td class="px-4 py-3">
                                                    <p class="font-medium text-gray-900 dark:text-white">
                                                        {{ $item->bahan?->nama ?? '-' }}
                                                    </p>
                                                    <p class="text-xs text-gray-500">
                                                        {{ $item->bahan?->kode_bahan ?? '-' }}
                                                    </p>
                                                </td>
                                                <td class="px-4 py-3 text-center">{{ $item->jumlah }}</td>
                                                <td class="px-4 py-3 text-center">{{ $jumlahDikembalikan }}</td>
                                                <td class="px-4 py-3 text-center text-green-600">{{ $jumlahLayak }}</td>
                                                <td class="px-4 py-3 text-center text-red-600">{{ max(0, $jumlahDikembalikan - $jumlahLayak) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            @foreach ($detail->pengembalian as $pengembalian)
                                <div class="mt-3 rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                        Pengembalian: {{ $pengembalian->tanggal_pengembalian?->format('d M Y, H:i') ?? '-' }}
                                    </p>
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                        Diproses oleh: {{ $pengembalian->pemroses?->name ?? '-' }}
                                    </p>
                                    @if ($pengembalian->catatan)
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                            Catatan: {{ $pengembalian->catatan }}
                                        </p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Detail alat --}}
                    @if ($detailJenis === 'alat')
                        <div>
                            <h3 class="mb-3 font-semibold text-gray-900 dark:text-white">
                                Alat yang Dipinjam
                            </h3>

                            <div class="space-y-3">
                                @foreach ($detail->detail as $item)
                                    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                        <div class="flex items-center justify-between gap-3">
                                            <div>
                                                <p class="font-semibold text-gray-900 dark:text-white">
                                                    {{ $item->alat?->nama ?? '-' }}
                                                </p>
                                                <p class="text-xs text-gray-500">
                                                    {{ $item->alat?->kode_alat ?? '-' }}
                                                </p>
                                            </div>
                                            <span class="text-sm text-gray-500">
                                                {{ $item->jumlah }} unit
                                            </span>
                                        </div>

                                        <div class="mt-3 space-y-2">
                                            @foreach ($item->unitPeminjaman as $unit)
                                                <div class="flex flex-col justify-between gap-1 rounded-lg bg-gray-50 p-3 text-sm sm:flex-row dark:bg-gray-800">
                                                    <div>
                                                        <p class="font-medium text-gray-900 dark:text-white">
                                                            {{ $unit->unitAlat?->kode_inventaris ?? 'Unit tidak ditemukan' }}
                                                        </p>
                                                        <p class="text-xs text-gray-500">
                                                            Kondisi awal: {{ ucfirst($unit->unitAlat?->kondisi ?? '-') }}
                                                        </p>
                                                    </div>

                                                    <div class="text-left sm:text-right">
                                                        <p class="font-medium text-gray-700 dark:text-gray-200">
                                                            {{ ucfirst(str_replace('_', ' ', $unit->detailPengembalian?->kondisi_saat_kembali ?? 'Belum dikembalikan')) }}
                                                        </p>
                                                        @if ($unit->detailPengembalian?->catatan)
                                                            <p class="text-xs text-gray-500">
                                                                {{ $unit->detailPengembalian->catatan }}
                                                            </p>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if ($detail->pengembalian)
                                <div class="mt-4 rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                                    <p class="font-semibold text-gray-900 dark:text-white">
                                        Informasi Pengembalian
                                    </p>
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                        Tanggal:
                                        {{ $detail->pengembalian->tanggal_pengembalian?->format('d M Y, H:i') ?? '-' }}
                                    </p>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">
                                        Diproses oleh: {{ $detail->pengembalian->pemroses?->name ?? '-' }}
                                    </p>
                                    @if ($detail->pengembalian->catatan)
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                            Catatan: {{ $detail->pengembalian->catatan }}
                                        </p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Detail laboratorium --}}
                    @if ($detailJenis === 'lab')
                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <h3 class="font-semibold text-gray-900 dark:text-white">
                                Informasi Laboratorium
                            </h3>

                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                <div>
                                    <p class="text-xs text-gray-500">Laboratorium</p>
                                    <p class="mt-1 font-medium text-gray-900 dark:text-white">
                                        {{ $detail->laboratorium?->nama ?? '-' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Lokasi</p>
                                    <p class="mt-1 font-medium text-gray-900 dark:text-white">
                                        {{ $detail->laboratorium?->lokasi ?? '-' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Waktu Mulai</p>
                                    <p class="mt-1 font-medium text-gray-900 dark:text-white">
                                        {{ $detail->waktu_mulai?->format('d M Y, H:i') ?? '-' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Waktu Selesai</p>
                                    <p class="mt-1 font-medium text-gray-900 dark:text-white">
                                        {{ $detail->waktu_selesai?->format('d M Y, H:i') ?? '-' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        @if ($detail->dokumentasi->isNotEmpty())
                            <div>
                                <h3 class="mb-3 font-semibold text-gray-900 dark:text-white">
                                    Dokumentasi
                                </h3>

                                <div class="grid gap-3 sm:grid-cols-2">
                                    @foreach ($detail->dokumentasi as $dokumen)
                                        <a
                                            href="{{ \Illuminate\Support\Facades\Storage::url($dokumen->file_path) }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="block rounded-lg border border-gray-200 p-3 text-sm text-blue-600 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
                                        >
                                            {{ ucfirst($dokumen->jenis) }}
                                            <span class="mt-1 block text-xs text-gray-500">
                                                Diunggah oleh {{ $dokumen->pengunggah?->name ?? '-' }}
                                            </span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endif
                </div>

                <div class="flex justify-end border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                    <button
                        type="button"
                        wire:click="tutupDetail"
                        class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700 dark:bg-white dark:text-gray-900"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
