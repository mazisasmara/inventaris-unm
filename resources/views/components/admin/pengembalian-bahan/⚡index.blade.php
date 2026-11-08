<?php

use App\Models\PengambilanBahan;
use App\Models\PengembalianBahan;
use App\Models\DetailPengambilanBahan;
use App\Models\DetailPengembalianBahan;
use App\Models\Bahan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $detailId = null;
    public ?int $returnId = null;

    public array $jumlahLayak = [];
    public array $catatanBahan = [];

    public string $catatanPengembalian = '';

    public ?string $returnError = null;

    public bool $showDetail = false;
    public bool $showReturn = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function statistik(): array
    {
        return [
            'diserahkan' => PengambilanBahan::where('status', 'diserahkan')->count(),

            'selesai' => PengambilanBahan::where('status', 'selesai')->count(),

            'hari_ini' => PengembalianBahan::whereDate(
                'tanggal_pengembalian',
                today()
            )->count(),
        ];
    }

    #[Computed]
    public function transaksi()
    {
        return PengambilanBahan::query()
            ->with(['user', 'detail.bahan'])
            ->where('status', 'diserahkan')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->whereHas('user', function ($userQuery) {
                        $userQuery
                            ->where('name', 'like', '%' . $this->search . '%')
                            ->orWhere('identity_number', 'like', '%' . $this->search . '%')
                            ->orWhere('email', 'like', '%' . $this->search . '%');
                    })
                    ->orWhere('tujuan', 'like', '%' . $this->search . '%');
                });
            })
            ->latest('diserahkan_at')
            ->paginate(10);
    }

    #[Computed]
    public function detailRequest()
    {
        if (! $this->detailId) {
            return null;
        }

        return PengambilanBahan::with([
            'user',
            'detail.bahan',
        ])->find($this->detailId);
    }

    #[Computed]
    public function returnRequest()
    {
        if (! $this->returnId) {
            return null;
        }

        return PengambilanBahan::with([
            'user',
            'detail.bahan',
        ])->find($this->returnId);
    }

    public function lihatDetail(int $id): void
    {
        $transaksi = PengambilanBahan::findOrFail($id);

        if ($transaksi->status !== 'diserahkan') {
            return;
        }

        $this->detailId = $transaksi->id;
        $this->showDetail = true;

        unset($this->detailRequest);
    }

    public function tutupDetail(): void
    {
        $this->showDetail = false;
        $this->detailId = null;

        unset($this->detailRequest);
    }

    public function bukaPengembalian(int $id): void
    {
        $transaksi = PengambilanBahan::with('detail')
            ->findOrFail($id);

        if ($transaksi->status !== 'diserahkan') {
            $this->returnError = 'Transaksi ini tidak dapat dikembalikan.';
            return;
        }

        $this->returnId = $transaksi->id;

        $this->jumlahLayak = [];
        $this->catatanBahan = [];

        $this->catatanPengembalian = '';
        $this->returnError = null;

        foreach ($transaksi->detail as $detail) {
            $this->jumlahLayak[$detail->id] = 0;
            $this->catatanBahan[$detail->id] = '';
        }

        $this->showDetail = false;
        $this->showReturn = true;

        $this->resetValidation();

        unset($this->detailRequest, $this->returnRequest);
    }

    public function tutupPengembalian(): void
    {
        $this->showReturn = false;
        $this->returnId = null;

        $this->jumlahLayak = [];
        $this->catatanBahan = [];

        $this->catatanPengembalian = '';
        $this->returnError = null;

        $this->resetValidation();

        unset($this->returnRequest);
    }

    public function prosesPengembalian(): void
    {
        $this->returnError = null;

        if (! $this->returnId) {
            $this->returnError = 'Transaksi tidak ditemukan.';
            return;
        }

        $id = $this->returnId;

        $error = DB::transaction(function () use ($id) {

            // Kunci transaksi induk agar tidak diproses dua kali.
            $transaksi = PengambilanBahan::whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($transaksi->status !== 'diserahkan') {
                return 'Transaksi ini sudah tidak dapat diproses.';
            }

            // Pastikan belum ada pengembalian sebelumnya.
            if ($transaksi->pengembalian()->exists()) {
                return 'Pengembalian untuk transaksi ini sudah tercatat.';
            }

            // Kunci seluruh detail transaksi.
            $details = DetailPengambilanBahan::where(
                'pengambilan_bahan_id',
                $transaksi->id
            )
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($details->isEmpty()) {
                return 'Transaksi tidak memiliki detail bahan.';
            }

            // Validasi seluruh input berdasarkan jumlah awal.
            $rules = [
                'catatanPengembalian' => ['nullable', 'string', 'max:2000'],
            ];

            foreach ($details as $detail) {
                $rules['jumlahLayak.' . $detail->id] = [
                    'required',
                    'integer',
                    'min:0',
                    'max:' . $detail->jumlah,
                ];

                $rules['catatanBahan.' . $detail->id] = [
                    'nullable',
                    'string',
                    'max:1000',
                ];
            }

            $validated = Validator::make([
                'jumlahLayak' => $this->jumlahLayak,
                'catatanBahan' => $this->catatanBahan,
                'catatanPengembalian' => $this->catatanPengembalian,
            ], $rules, [
                'jumlahLayak.*.required' => 'Jumlah layak wajib diisi.',
                'jumlahLayak.*.integer' => 'Jumlah layak harus berupa bilangan bulat.',
                'jumlahLayak.*.min' => 'Jumlah layak tidak boleh kurang dari 0.',
                'jumlahLayak.*.max' => 'Jumlah layak tidak boleh melebihi jumlah yang diambil.',
            ])->validate();

            // Kunci dan periksa seluruh bahan sebelum mengubah stok.
            $bahanTerkunci = [];

            foreach ($details as $detail) {
                $bahan = Bahan::whereKey($detail->bahan_id)
                    ->lockForUpdate()
                    ->first();

                if (! $bahan) {
                    return 'Data bahan tidak ditemukan.';
                }

                $bahanTerkunci[$detail->id] = $bahan;
            }

            $waktuPengembalian = now();

            // Buat satu transaksi pengembalian untuk seluruh bahan.
            $pengembalian = PengembalianBahan::create([
                'pengambilan_bahan_id' => $transaksi->id,
                'diproses_oleh' => auth()->id(),
                'tanggal_pengembalian' => $waktuPengembalian,
                'catatan' => $validated['catatanPengembalian'] ?: null,
            ]);

            foreach ($details as $detail) {
                $jumlahLayak = (int) $validated['jumlahLayak'][$detail->id];

                $jumlahTidakLayak = $detail->jumlah - $jumlahLayak;

                $catatan = $validated['catatanBahan'][$detail->id] ?? null;

                // Simpan seluruh jumlah yang dikembalikan.
                DetailPengembalianBahan::create([
                    'pengembalian_bahan_id' => $pengembalian->id,
                    'detail_pengambilan_bahan_id' => $detail->id,
                    'jumlah_dikembalikan' => $detail->jumlah,
                    'jumlah_layak' => $jumlahLayak,
                    'catatan' => $catatan ?: null,
                ]);

                // Hanya jumlah yang layak yang masuk kembali ke stok.
                if ($jumlahLayak > 0) {
                    $bahanTerkunci[$detail->id]->increment(
                        'stok',
                        $jumlahLayak
                    );
                }
            }

            // Selesaikan transaksi pengambilan bahan.
            $transaksi->update([
                'status' => 'selesai',
                'selesai_at' => $waktuPengembalian,
            ]);

            return null;
        });

        if ($error !== null) {
            $this->returnError = $error;
            return;
        }

        unset(
            $this->transaksi,
            $this->statistik,
            $this->returnRequest,
            $this->detailRequest
        );

        $this->tutupPengembalian();

        $this->dispatch(
            'toast',
            message: 'Pengembalian bahan berhasil diproses dan stok telah diperbarui.'
        );
    }
};
?>

<div class="mx-auto max-w-7xl space-y-6">

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">
            Pengembalian Bahan
        </h1>

        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            Kelola penerimaan dan pemeriksaan bahan yang telah digunakan oleh mahasiswa dan dosen.
        </p>
    </div>

    {{-- Statistik --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                Menunggu Pengembalian
            </p>

            <p class="mt-2 text-2xl font-semibold text-indigo-600">
                {{ $this->statistik['diserahkan'] }}
            </p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                Pengembalian Hari Ini
            </p>

            <p class="mt-2 text-2xl font-semibold text-green-600">
                {{ $this->statistik['hari_ini'] }}
            </p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                Total Transaksi Selesai
            </p>

            <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-white">
                {{ $this->statistik['selesai'] }}
            </p>
        </div>

    </div>

    {{-- Tabel Transaksi --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h2 class="font-semibold text-zinc-900 dark:text-white">
                    Daftar Pengembalian
                </h2>

                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    Transaksi yang telah diserahkan dan belum dikembalikan.
                </p>
            </div>

            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari nama, NIM/NIP, atau tujuan..."
                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white sm:max-w-sm"
            >

        </div>

        <div class="mt-5 overflow-x-auto">

            <table class="w-full text-left text-sm">

                <thead class="border-b border-zinc-200 text-zinc-500 dark:border-zinc-700">
                    <tr>
                        <th class="px-3 py-3">Pemohon</th>
                        <th class="px-3 py-3">Tujuan</th>
                        <th class="px-3 py-3">Diserahkan</th>
                        <th class="px-3 py-3">Jenis Bahan</th>
                        <th class="px-3 py-3">Status</th>
                        <th class="px-3 py-3 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">

                    @forelse ($this->transaksi as $item)

                        <tr wire:key="transaksi-{{ $item->id }}">

                            <td class="px-3 py-3">
                                <p class="font-medium text-zinc-900 dark:text-white">
                                    {{ $item->user?->name ?? 'Pengguna tidak ditemukan' }}
                                </p>

                                <p class="text-xs text-zinc-500">
                                    {{ $item->user?->identity_number ?? $item->user?->email ?? '-' }}
                                </p>
                            </td>

                            <td class="px-3 py-3">
                                <span class="line-clamp-2">
                                    {{ $item->tujuan }}
                                </span>
                            </td>

                            <td class="px-3 py-3 whitespace-nowrap">
                                {{ $item->diserahkan_at?->format('d/m/Y H:i') ?? '-' }}
                            </td>

                            <td class="px-3 py-3">
                                {{ $item->detail->count() }} jenis
                            </td>

                            <td class="px-3 py-3">
                                <span class="inline-flex rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-medium text-indigo-700">
                                    Diserahkan
                                </span>
                            </td>

                            <td class="px-3 py-3 text-right whitespace-nowrap">

                                <button
                                    type="button"
                                    wire:click="lihatDetail({{ $item->id }})"
                                    class="font-medium text-blue-600 hover:text-blue-700"
                                >
                                    Detail
                                </button>

                                <button
                                    type="button"
                                    wire:click="bukaPengembalian({{ $item->id }})"
                                    class="ml-3 font-medium text-green-600 hover:text-green-700"
                                >
                                    Proses Pengembalian
                                </button>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="6" class="px-3 py-12 text-center">

                                <div class="flex flex-col items-center gap-2">

                                    <svg
                                        class="h-10 w-10 text-zinc-300 dark:text-zinc-600"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.5"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 1.414 0l4 4A2 2 0 0 1 19 8.414V19a2 2 0 0 1-2 2Z"
                                        />
                                    </svg>

                                    <p class="font-medium text-zinc-700 dark:text-zinc-300">
                                        Tidak ada transaksi pengembalian.
                                    </p>

                                    <p class="text-xs text-zinc-500">
                                        Transaksi yang sudah diserahkan akan muncul di sini.
                                    </p>

                                </div>

                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="mt-4">
            {{ $this->transaksi->links() }}
        </div>

    </div>


    {{-- Modal Detail --}}
    @if ($showDetail && $this->detailRequest)

        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 p-4">

            <div class="my-auto w-full max-w-2xl rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-900">

                <div class="flex items-center justify-between">

                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                            Detail Transaksi
                        </h2>

                        <p class="text-xs text-zinc-500">
                            Transaksi #{{ $this->detailRequest->id }}
                        </p>
                    </div>

                    <button
                        type="button"
                        wire:click="tutupDetail"
                        class="text-zinc-500 hover:text-zinc-800 dark:hover:text-white"
                    >
                        Tutup
                    </button>

                </div>

                <div class="mt-5 space-y-4 text-sm">

                    <div class="grid gap-3 sm:grid-cols-2">

                        <div>
                            <p class="text-zinc-500">Pemohon</p>

                            <p class="font-medium text-zinc-900 dark:text-white">
                                {{ $this->detailRequest->user?->name ?? '-' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-zinc-500">NIM/NIP</p>

                            <p class="font-medium text-zinc-900 dark:text-white">
                                {{ $this->detailRequest->user?->identity_number ?? '-' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-zinc-500">Tanggal Pengajuan</p>

                            <p class="font-medium text-zinc-900 dark:text-white">
                                {{ $this->detailRequest->tanggal_pengajuan?->format('d/m/Y H:i') ?? '-' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-zinc-500">Tanggal Penyerahan</p>

                            <p class="font-medium text-zinc-900 dark:text-white">
                                {{ $this->detailRequest->diserahkan_at?->format('d/m/Y H:i') ?? '-' }}
                            </p>
                        </div>

                    </div>

                    <div>
                        <p class="text-zinc-500">Tujuan Pengambilan</p>

                        <p class="mt-1 text-zinc-900 dark:text-white">
                            {{ $this->detailRequest->tujuan }}
                        </p>
                    </div>

                    <div>

                        <p class="mb-2 font-medium text-zinc-900 dark:text-white">
                            Daftar Bahan
                        </p>

                        <div class="overflow-x-auto">

                            <table class="w-full text-left text-sm">

                                <thead class="border-b dark:border-zinc-700">
                                    <tr>
                                        <th class="py-2">Bahan</th>
                                        <th class="py-2">Jumlah Diambil</th>
                                        <th class="py-2">Stok Sekarang</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    @foreach ($this->detailRequest->detail as $detail)

                                        <tr class="border-b dark:border-zinc-800">

                                            <td class="py-3">
                                                <p class="font-medium text-zinc-900 dark:text-white">
                                                    {{ $detail->bahan?->nama ?? 'Bahan tidak ditemukan' }}
                                                </p>

                                                <p class="text-xs text-zinc-500">
                                                    {{ $detail->bahan?->kode_bahan ?? '-' }}
                                                </p>
                                            </td>

                                            <td class="py-3">
                                                {{ $detail->jumlah }}
                                                {{ $detail->bahan?->satuan }}
                                            </td>

                                            <td class="py-3">
                                                {{ $detail->bahan?->stok ?? '-' }}
                                                {{ $detail->bahan?->satuan }}
                                            </td>

                                        </tr>

                                    @endforeach

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

                <div class="mt-6 flex justify-end">

                    <button
                        type="button"
                        wire:click="bukaPengembalian({{ $this->detailRequest->id }})"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    >
                        Proses Pengembalian
                    </button>

                </div>

            </div>

        </div>

    @endif


    {{-- Modal Pengembalian --}}
    @if ($showReturn && $this->returnRequest)

        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 p-4">

            <div class="my-auto w-full max-w-3xl rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-900">

                <div class="flex items-center justify-between gap-3">

                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                            Proses Pengembalian
                        </h2>

                        <p class="text-xs text-zinc-500">
                            Transaksi #{{ $this->returnRequest->id }}
                        </p>
                    </div>

                    <button
                        type="button"
                        wire:click="tutupPengembalian"
                        class="text-zinc-500 hover:text-zinc-800 dark:hover:text-white"
                    >
                        Tutup
                    </button>

                </div>

                <div class="mt-4 rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-800">

                    <p class="text-zinc-500 dark:text-zinc-400">
                        Pemohon
                    </p>

                    <p class="font-medium text-zinc-900 dark:text-white">
                        {{ $this->returnRequest->user?->name ?? '-' }}
                    </p>

                    <p class="mt-2 text-zinc-500 dark:text-zinc-400">
                        Tujuan
                    </p>

                    <p class="text-zinc-900 dark:text-white">
                        {{ $this->returnRequest->tujuan }}
                    </p>

                </div>

                @if ($returnError)

                    <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300">
                        {{ $returnError }}
                    </div>

                @endif

                <form wire:submit="prosesPengembalian" class="mt-5 space-y-5">

                    <div>

                        <div class="mb-3">
                            <h3 class="font-medium text-zinc-900 dark:text-white">
                                Pemeriksaan Bahan
                            </h3>

                            <p class="mt-1 text-xs text-zinc-500">
                                Pastikan seluruh bahan telah dikembalikan. Masukkan jumlah bahan yang masih layak digunakan.
                            </p>
                        </div>

                        <div class="space-y-4">

                            @foreach ($this->returnRequest->detail as $detail)

                                <div
                                    wire:key="return-detail-{{ $detail->id }}"
                                    class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700"
                                >

                                    <div class="flex flex-wrap items-start justify-between gap-2">

                                        <div>
                                            <p class="font-medium text-zinc-900 dark:text-white">
                                                {{ $detail->bahan?->nama ?? 'Bahan tidak ditemukan' }}
                                            </p>

                                            <p class="text-xs text-zinc-500">
                                                {{ $detail->bahan?->kode_bahan ?? '-' }}
                                            </p>
                                        </div>

                                        <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                            {{ $detail->jumlah }} {{ $detail->bahan?->satuan }}
                                        </span>

                                    </div>

                                    <div class="mt-4 grid gap-3 sm:grid-cols-2">

                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">
                                                Jumlah Dikembalikan
                                            </label>

                                            <input
                                                type="number"
                                                value="{{ $detail->jumlah }}"
                                                disabled
                                                class="w-full rounded-lg border border-zinc-200 bg-zinc-100 px-3 py-2 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
                                            >

                                            <p class="mt-1 text-xs text-zinc-500">
                                                Wajib mengembalikan seluruh jumlah.
                                            </p>
                                        </div>

                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">
                                                Jumlah Layak Digunakan
                                            </label>

                                            <input
                                                type="number"
                                                min="0"
                                                max="{{ $detail->jumlah }}"
                                                step="1"
                                                wire:model.live="jumlahLayak.{{ $detail->id }}"
                                                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                                                required
                                            >

                                            @error('jumlahLayak.' . $detail->id)
                                                <p class="mt-1 text-xs text-red-600">
                                                    {{ $message }}
                                                </p>
                                            @enderror

                                        </div>

                                    </div>

                                        @php
                                            $jumlahLayakSaatIni = (int) ($jumlahLayak[$detail->id] ?? 0);
                                            $jumlahTidakLayakSaatIni = max(0, $detail->jumlah - $jumlahLayakSaatIni);
                                        @endphp

                                    <div class="mt-3 rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">

                                        <div class="flex justify-between text-sm">

                                            <span class="text-zinc-500 dark:text-zinc-400">
                                                Jumlah Tidak Layak
                                            </span>

                                            <span class="font-semibold text-red-600">
                                                {{ max(0, $detail->jumlah - (int) ($jumlahLayak[$detail->id] ?? 0)) }}
                                                {{ $detail->bahan?->satuan }}
                                            </span>

                                        </div>

                                        <p class="mt-1 text-xs text-zinc-500">
                                            Jumlah tidak layak tidak akan ditambahkan kembali ke stok.
                                        </p>

                                    </div>

                                    <div class="mt-3">

                                        <label class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">
                                            Catatan Kondisi Bahan (Opsional)
                                        </label>

                                        <textarea
                                            wire:model="catatanBahan.{{ $detail->id }}"
                                            rows="2"
                                            maxlength="1000"
                                            placeholder="Contoh: 5 unit rusak atau tidak dapat digunakan..."
                                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                                        ></textarea>

                                        @error('catatanBahan.' . $detail->id)
                                            <p class="mt-1 text-xs text-red-600">
                                                {{ $message }}
                                            </p>
                                        @enderror

                                    </div>

                                </div>

                            @endforeach

                        </div>

                    </div>

                    <div>

                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Catatan Pengembalian (Opsional)
                        </label>

                        <textarea
                            wire:model="catatanPengembalian"
                            rows="3"
                            maxlength="2000"
                            placeholder="Tambahkan catatan umum jika diperlukan..."
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                        ></textarea>

                        @error('catatanPengembalian')
                            <p class="mt-1 text-xs text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>

                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300">

                        <p class="font-medium">
                            Perhatian
                        </p>

                        <p class="mt-1 text-xs leading-relaxed">
                            Pastikan seluruh bahan telah diterima. Setelah diproses, transaksi akan menjadi selesai dan stok hanya bertambah berdasarkan jumlah bahan yang layak digunakan. Proses ini tidak dapat diulang.
                        </p>

                    </div>

                    <div class="flex flex-wrap justify-end gap-3">

                        <button
                            type="button"
                            wire:click="tutupPengembalian"
                            wire:loading.attr="disabled"
                            class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 disabled:opacity-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800"
                        >
                            Batal
                        </button>

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="prosesPengembalian"
                            wire:confirm="Pastikan seluruh bahan telah diterima dan jumlah layak sudah benar. Proses pengembalian sekarang?"
                            class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="prosesPengembalian">
                                Simpan Pengembalian
                            </span>

                            <span wire:loading wire:target="prosesPengembalian">
                                Memproses...
                            </span>
                        </button>

                    </div>

                </form>

            </div>

        </div>

    @endif

</div>
