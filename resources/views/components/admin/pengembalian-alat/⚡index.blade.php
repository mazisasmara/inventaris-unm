<?php

use App\Models\PeminjamanAlat;
use App\Models\PengembalianAlat;
use App\Models\DetailPengembalianAlat;
use App\Models\UnitAlat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $detailId = null;
    public ?int $returnId = null;

    public array $kondisiKembali = [];
    public array $catatanUnit = [];

    public string $catatanPengembalian = '';
    public string $returnError = '';

    public bool $showDetailModal = false;
    public bool $showReturnModal = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function loans()
    {
        return PeminjamanAlat::with([
            'user',
            'detail.alat',
            'detail.unitPeminjaman.unitAlat',
        ])
            ->where('status', 'dipinjam')
            ->when($this->search !== '', function ($query) {
                $search = $this->search;

                $query->where(function ($q) use ($search) {
                    $q->whereHas('user', function ($user) use ($search) {
                        $user->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('identity_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('detail.alat', function ($alat) use ($search) {
                        $alat->where('nama', 'like', "%{$search}%")
                            ->orWhere('kode_alat', 'like', "%{$search}%");
                    })
                    ->orWhereHas('detail.unitPeminjaman.unitAlat', function ($unit) use ($search) {
                        $unit->where('kode_inventaris', 'like', "%{$search}%");
                    });
                });
            })
            ->latest()
            ->paginate(10);
    }

    #[Computed]
    public function detailRequest(): ?PeminjamanAlat
    {
        if (! $this->detailId) {
            return null;
        }

        return PeminjamanAlat::with([
            'user',
            'detail.alat',
            'detail.unitPeminjaman.unitAlat',
            'pemroses',
        ])->find($this->detailId);
    }

    #[Computed]
    public function returnRequest(): ?PeminjamanAlat
    {
        if (! $this->returnId) {
            return null;
        }

        return PeminjamanAlat::with([
            'user',
            'detail.alat',
            'detail.unitPeminjaman.unitAlat',
        ])->find($this->returnId);
    }

    public function openDetail(int $id): void
    {
        $this->detailId = $id;
        $this->showDetailModal = true;
    }

    public function closeDetail(): void
    {
        $this->showDetailModal = false;
        $this->detailId = null;
    }

    public function openReturn(int $id): void
    {
        $request = PeminjamanAlat::with([
            'detail.unitPeminjaman',
        ])->findOrFail($id);

        if ($request->status !== 'dipinjam') {
            return;
        }

        $this->returnId = $id;
        $this->kondisiKembali = [];
        $this->catatanUnit = [];
        $this->catatanPengembalian = '';
        $this->returnError = '';

        foreach ($request->detail as $detail) {
            foreach ($detail->unitPeminjaman as $unitLoan) {
                $this->kondisiKembali[$unitLoan->id] = '';
                $this->catatanUnit[$unitLoan->id] = '';
            }
        }

        $this->showReturnModal = true;
    }

    public function closeReturn(): void
    {
        $this->showReturnModal = false;
        $this->returnId = null;
        $this->kondisiKembali = [];
        $this->catatanUnit = [];
        $this->catatanPengembalian = '';
        $this->returnError = '';
    }

    public function processReturn(): void
    {
        if (! $this->returnId) {
            return;
        }

        $requestId = $this->returnId;

        try {
            DB::transaction(function () use ($requestId) {
                $request = PeminjamanAlat::with([
                    'detail.unitPeminjaman',
                ])
                    ->lockForUpdate()
                    ->findOrFail($requestId);

                if ($request->status !== 'dipinjam') {
                    throw ValidationException::withMessages([
                        'returnError' => 'Transaksi ini tidak sedang berstatus dipinjam.',
                    ]);
                }

                if ($request->pengembalian()->exists()) {
                    throw ValidationException::withMessages([
                        'returnError' => 'Pengembalian untuk transaksi ini sudah tercatat.',
                    ]);
                }

                $unitLoans = $request->detail
                    ->flatMap(fn ($detail) => $detail->unitPeminjaman);

                if ($unitLoans->isEmpty()) {
                    throw ValidationException::withMessages([
                        'returnError' => 'Tidak ditemukan unit inventaris dalam transaksi ini.',
                    ]);
                }

                if (count($this->kondisiKembali) !== $unitLoans->count()) {
                    throw ValidationException::withMessages([
                        'returnError' => 'Data kondisi pengembalian belum lengkap.',
                    ]);
                }

                $allowedConditions = [
                    'baik',
                    'rusak',
                    'dalam_perbaikan',
                ];

                foreach ($unitLoans as $unitLoan) {
                    $condition = $this->kondisiKembali[$unitLoan->id] ?? '';

                    if (! in_array($condition, $allowedConditions, true)) {
                        throw ValidationException::withMessages([
                            'returnError' => 'Pilih kondisi untuk seluruh unit inventaris.',
                        ]);
                    }
                }

                $unitIds = $unitLoans->pluck('unit_alat_id');

                $units = UnitAlat::whereIn('id', $unitIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                if ($units->count() !== $unitLoans->count()) {
                    throw ValidationException::withMessages([
                        'returnError' => 'Ada unit inventaris yang tidak ditemukan.',
                    ]);
                }

                foreach ($unitLoans as $unitLoan) {
                    $unit = $units->get($unitLoan->unit_alat_id);

                    if (! $unit || $unit->status !== 'dipinjam') {
                        throw ValidationException::withMessages([
                            'returnError' => 'Status salah satu unit tidak sesuai. Periksa inventaris terlebih dahulu.',
                        ]);
                    }
                }

                $return = PengembalianAlat::create([
                    'peminjaman_alat_id' => $request->id,
                    'diproses_oleh' => Auth::id(),
                    'tanggal_pengembalian' => now(),
                    'catatan' => $this->catatanPengembalian ?: null,
                ]);

                foreach ($unitLoans as $unitLoan) {
                    $condition = $this->kondisiKembali[$unitLoan->id];

                    DetailPengembalianAlat::create([
                        'pengembalian_alat_id' => $return->id,
                        'unit_peminjaman_alat_id' => $unitLoan->id,
                        'kondisi_saat_kembali' => $condition,
                        'catatan' => $this->catatanUnit[$unitLoan->id] ?? null,
                    ]);

                    $units->get($unitLoan->unit_alat_id)->update([
                        'kondisi' => $condition,
                        'status' => $condition === 'baik'
                            ? 'tersedia'
                            : 'tidak_tersedia',
                    ]);
                }

                $request->update([
                    'status' => 'selesai',
                ]);
            });

            $this->closeReturn();

            unset($this->loans);

            session()->flash(
                'success',
                'Pengembalian berhasil dicatat. Kondisi dan status seluruh unit inventaris telah diperbarui.'
            );

        } catch (ValidationException $e) {
            $this->returnError = collect($e->errors())->flatten()->first();

        } catch (\Throwable $e) {
            report($e);

            $this->returnError = 'Terjadi kesalahan saat memproses pengembalian. Silakan coba kembali.';
        }
    }
};
?>

<div class="space-y-6">

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
            Pengembalian Alat
        </h1>
        <p class="mt-1 text-sm text-zinc-500">
            Kelola pengembalian alat dan pemeriksaan kondisi inventaris.
        </p>
    </div>

    {{-- Notifikasi --}}
    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- Pencarian --}}
    <div>
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Cari nama, NIM/NIP, nama alat, atau kode inventaris..."
            class="w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900"
        >
    </div>

    {{-- Tabel --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                    <tr>
                        <th class="px-4 py-3">Peminjam</th>
                        <th class="px-4 py-3">Tanggal Peminjaman</th>
                        <th class="px-4 py-3">Rencana Pengembalian</th>
                        <th class="px-4 py-3">Jumlah Unit</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($this->loans as $loan)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium text-zinc-900 dark:text-white">
                                    {{ $loan->user->name }}
                                </div>
                                <div class="text-xs text-zinc-500">
                                    {{ $loan->user->identity_number ?? $loan->user->email }}
                                </div>
                            </td>

                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">
                                {{ $loan->tanggal_peminjaman?->format('d/m/Y H:i') }}
                            </td>

                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">
                                {{ $loan->tanggal_pengembalian_rencana?->format('d/m/Y H:i') }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $loan->detail->sum('jumlah') }} unit
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <button
                                        wire:click="openDetail({{ $loan->id }})"
                                        class="rounded-lg border border-zinc-300 px-3 py-1.5 text-xs hover:bg-zinc-50 dark:border-zinc-600 dark:hover:bg-zinc-800"
                                    >
                                        Detail
                                    </button>

                                    <button
                                        wire:click="openReturn({{ $loan->id }})"
                                        class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs text-white hover:bg-blue-700"
                                    >
                                        Proses Pengembalian
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-zinc-500">
                                Tidak ada alat yang sedang dipinjam.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">
            {{ $this->loans->links() }}
        </div>
    </div>

    {{-- Modal Detail --}}
    @if ($showDetailModal && $this->detailRequest)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/50 p-4">
            <div class="mx-auto my-4 w-full max-w-xl rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-900">

                <h2 class="mb-4 text-lg font-bold text-zinc-900 dark:text-white">
                    Detail Peminjaman
                </h2>

                <div class="mb-5 space-y-2 text-sm">
                    <p>
                        <strong>Peminjam:</strong>
                        {{ $this->detailRequest->user->name }}
                    </p>

                    <p>
                        <strong>Identitas:</strong>
                        {{ $this->detailRequest->user->identity_number ?? '-' }}
                    </p>

                    <p>
                        <strong>Tujuan:</strong>
                        {{ $this->detailRequest->tujuan }}
                    </p>

                    <p>
                        <strong>Tanggal peminjaman:</strong>
                        {{ $this->detailRequest->tanggal_peminjaman?->format('d/m/Y H:i') }}
                    </p>

                    <p>
                        <strong>Rencana pengembalian:</strong>
                        {{ $this->detailRequest->tanggal_pengembalian_rencana?->format('d/m/Y H:i') }}
                    </p>
                </div>

                <h3 class="mb-2 font-semibold">Unit yang dipinjam</h3>

                <div class="mb-5 space-y-3">
                    @foreach ($this->detailRequest->detail as $detail)
                        <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                            <div class="font-medium text-zinc-900 dark:text-white">
                                {{ $detail->alat->nama }}
                            </div>

                            <div class="mt-2 space-y-1 text-sm text-zinc-500">
                                @foreach ($detail->unitPeminjaman as $unitLoan)
                                    <div>
                                        {{ $unitLoan->unitAlat->kode_inventaris }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end">
                    <button
                        wire:click="closeDetail"
                        class="rounded-lg bg-zinc-800 px-4 py-2 text-sm text-white hover:bg-zinc-700"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Pengembalian --}}
    @if ($showReturnModal && $this->returnRequest)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/50 p-4">
            <div class="mx-auto my-4 max-h-[calc(100vh-2rem)] w-full max-w-2xl overflow-y-auto rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-900">

                <div class="mb-5">
                    <h2 class="text-lg font-bold text-zinc-900 dark:text-white">
                        Proses Pengembalian Alat
                    </h2>

                    <p class="mt-1 text-sm text-zinc-500">
                        Periksa kondisi setiap unit inventaris sebelum mengonfirmasi pengembalian.
                    </p>
                </div>

                <div class="mb-5 rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-800">
                    <div>
                        <strong>Peminjam:</strong>
                        {{ $this->returnRequest->user->name }}
                    </div>

                    <div class="mt-1">
                        <strong>Jumlah unit:</strong>
                        {{ $this->returnRequest->detail->sum('jumlah') }} unit
                    </div>
                </div>

                @if ($returnError)
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                        {{ $returnError }}
                    </div>
                @endif

                <div class="space-y-4">
                    @foreach ($this->returnRequest->detail as $detail)
                        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">

                            <div class="mb-3 font-semibold text-zinc-900 dark:text-white">
                                {{ $detail->alat->nama }}
                            </div>

                            <div class="space-y-4">
                                @foreach ($detail->unitPeminjaman as $unitLoan)
                                    <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">

                                        <div class="mb-3 font-medium text-sm text-zinc-900 dark:text-white">
                                            {{ $unitLoan->unitAlat->kode_inventaris }}
                                        </div>

                                        <label class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-300">
                                            Kondisi saat dikembalikan
                                        </label>

                                        <select
                                            wire:model="kondisiKembali.{{ $unitLoan->id }}"
                                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900"
                                        >
                                            <option value="">Pilih kondisi</option>
                                            <option value="baik">Baik</option>
                                            <option value="rusak">Rusak</option>
                                            <option value="dalam_perbaikan">Dalam Perbaikan</option>
                                        </select>

                                        <label class="mb-1 mt-3 block text-xs font-medium text-zinc-600 dark:text-zinc-300">
                                            Catatan kondisi (opsional)
                                        </label>

                                        <textarea
                                            wire:model="catatanUnit.{{ $unitLoan->id }}"
                                            rows="2"
                                            placeholder="Contoh: Terdapat kerusakan pada bagian kabel..."
                                            class="w-full rounded-lg border border-zinc-300 bg-white p-3 text-sm dark:border-zinc-700 dark:bg-zinc-900"
                                        ></textarea>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5">
                    <label class="mb-2 block text-sm font-medium text-zinc-900 dark:text-white">
                        Catatan umum pengembalian (opsional)
                    </label>

                    <textarea
                        wire:model="catatanPengembalian"
                        rows="3"
                        placeholder="Catatan tambahan mengenai pengembalian..."
                        class="w-full rounded-lg border border-zinc-300 bg-white p-3 text-sm dark:border-zinc-700 dark:bg-zinc-900"
                    ></textarea>
                </div>

                <div class="mt-6 flex flex-wrap justify-end gap-2">
                    <button
                        wire:click="closeReturn"
                        class="rounded-lg border border-zinc-300 px-4 py-2 text-sm dark:border-zinc-600"
                    >
                        Batal
                    </button>

                    <button
                        wire:click="processReturn"
                        wire:confirm="Pastikan seluruh unit sudah diperiksa. Lanjutkan pencatatan pengembalian?"
                        wire:loading.attr="disabled"
                        wire:target="processReturn"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="processReturn">
                            Konfirmasi Pengembalian
                        </span>

                        <span wire:loading wire:target="processReturn">
                            Memproses...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
