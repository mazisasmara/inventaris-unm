<?php

use App\Models\PeminjamanAlat;
use App\Models\DetailPeminjamanAlat;
use App\Models\UnitAlat;
use App\Models\UnitPeminjamanAlat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'menunggu';

    public ?int $detailId = null;
    public ?int $approvalId = null;

    public array $selectedUnits = [];

    public string $adminNote = '';
    public string $approvalError = '';

    public bool $showDetailModal = false;
    public bool $showApprovalModal = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function requests()
    {
        return PeminjamanAlat::with([
            'user',
            'detail.alat',
            'detail.unitPeminjaman.unitAlat',
            'pemroses',
        ])
            ->when($this->statusFilter !== 'semua', function ($query) {
                $query->where('status', $this->statusFilter);
            })
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
                    });
                });
            })
            ->latest()
            ->paginate(10);
    }

    #[Computed]
    public function stats()
    {
        return [
            'menunggu' => PeminjamanAlat::where('status', 'menunggu')->count(),
            'disetujui' => PeminjamanAlat::where('status', 'disetujui')->count(),
            'dipinjam' => PeminjamanAlat::where('status', 'dipinjam')->count(),
            'selesai' => PeminjamanAlat::where('status', 'selesai')->count(),
            'ditolak' => PeminjamanAlat::where('status', 'ditolak')->count(),
        ];
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

    public function openApproval(int $id): void
    {
        $request = PeminjamanAlat::with('detail')->findOrFail($id);

        if ($request->status !== 'menunggu') {
            return;
        }

        $this->approvalId = $id;
        $this->selectedUnits = [];

        foreach ($request->detail as $detail) {
            $this->selectedUnits[$detail->id] = [];
        }

        $this->adminNote = '';
        $this->approvalError = '';
        $this->showApprovalModal = true;
    }

    public function closeApproval(): void
    {
        $this->showApprovalModal = false;
        $this->approvalId = null;
        $this->selectedUnits = [];
        $this->adminNote = '';
        $this->approvalError = '';
    }

    #[Computed]
    public function approvalRequest(): ?PeminjamanAlat
    {
        if (! $this->approvalId) {
            return null;
        }

        return PeminjamanAlat::with('detail.alat')
            ->find($this->approvalId);
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

    public function availableUnits(int $detailId)
    {
        $detail = DetailPeminjamanAlat::find($detailId);

        if (! $detail) {
            return collect();
        }

        return UnitAlat::where('alat_id', $detail->alat_id)
            ->where('status', 'tersedia')
            ->where('kondisi', 'baik')
            ->whereDoesntHave('peminjaman.detailPeminjaman.peminjamanAlat', function ($query) {
                $query->whereIn('status', ['disetujui', 'dipinjam']);
            })
            ->orderBy('kode_inventaris')
            ->get();
    }

    public function approve(): void
    {
        $requestId = $this->approvalId;

        if (! $requestId) {
            return;
        }

        try {
            DB::transaction(function () use ($requestId) {
                $request = PeminjamanAlat::with('detail')
                    ->lockForUpdate()
                    ->findOrFail($requestId);

                if ($request->status !== 'menunggu') {
                    throw ValidationException::withMessages([
                        'approvalError' => 'Pengajuan ini sudah diproses.',
                    ]);
                }

                $allUnitIds = collect();

                foreach ($request->detail as $detail) {
                    $unitIds = $this->selectedUnits[$detail->id] ?? [];

                    if (! is_array($unitIds) || count($unitIds) !== $detail->jumlah) {
                        throw ValidationException::withMessages([
                            'approvalError' => "Pilih tepat {$detail->jumlah} unit untuk {$detail->alat->nama}.",
                        ]);
                    }

                    $allUnitIds = $allUnitIds->merge($unitIds);
                }

                $allUnitIds = $allUnitIds->map(fn ($id) => (int) $id);

                if ($allUnitIds->count() !== $allUnitIds->unique()->count()) {
                    throw ValidationException::withMessages([
                        'approvalError' => 'Satu unit inventaris tidak boleh dipilih lebih dari satu kali.',
                    ]);
                }

                $units = UnitAlat::whereIn('id', $allUnitIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                if ($units->count() !== $allUnitIds->count()) {
                    throw ValidationException::withMessages([
                        'approvalError' => 'Ada unit inventaris yang tidak ditemukan.',
                    ]);
                }

                foreach ($request->detail as $detail) {
                    $unitIds = collect($this->selectedUnits[$detail->id] ?? [])
                        ->map(fn ($id) => (int) $id);

                    $validUnits = $units->where('alat_id', $detail->alat_id)
                        ->where('status', 'tersedia')
                        ->where('kondisi', 'baik');

                    if ($validUnits->whereIn('id', $unitIds)->count() !== $detail->jumlah) {
                        throw ValidationException::withMessages([
                            'approvalError' => "Unit untuk {$detail->alat->nama} sudah tidak tersedia atau tidak sesuai.",
                        ]);
                    }
                }

                $reserved = DB::table('unit_peminjaman_alat as upa')
                    ->join('detail_peminjaman_alat as dpa', 'dpa.id', '=', 'upa.detail_peminjaman_alat_id')
                    ->join('peminjaman_alat as pa', 'pa.id', '=', 'dpa.peminjaman_alat_id')
                    ->whereIn('upa.unit_alat_id', $allUnitIds)
                    ->whereIn('pa.status', ['disetujui', 'dipinjam'])
                    ->lockForUpdate()
                    ->exists();

                if ($reserved) {
                    throw ValidationException::withMessages([
                        'approvalError' => 'Salah satu unit sudah direservasi oleh pengajuan lain.',
                    ]);
                }

                foreach ($request->detail as $detail) {
                    foreach ($this->selectedUnits[$detail->id] as $unitId) {
                        UnitPeminjamanAlat::create([
                            'detail_peminjaman_alat_id' => $detail->id,
                            'unit_alat_id' => $unitId,
                        ]);
                    }
                }

                $request->update([
                    'status' => 'disetujui',
                    'diproses_oleh' => Auth::id(),
                    'diproses_at' => now(),
                    'catatan_admin' => $this->adminNote ?: null,
                ]);
            });

            $this->closeApproval();

            unset($this->requests, $this->stats);

            session()->flash('success', 'Pengajuan berhasil disetujui dan unit inventaris berhasil direservasi.');

        } catch (ValidationException $e) {
            $this->approvalError = collect($e->errors())->flatten()->first();
        } catch (\Throwable $e) {
            report($e);
            $this->approvalError = 'Terjadi kesalahan saat memproses pengajuan. Silakan coba kembali.';
        }
    }

    public function reject(int $id): void
    {
        $request = PeminjamanAlat::findOrFail($id);

        if ($request->status !== 'menunggu') {
            return;
        }

        $request->update([
            'status' => 'ditolak',
            'diproses_oleh' => Auth::id(),
            'diproses_at' => now(),
            'catatan_admin' => $this->adminNote ?: null,
        ]);

        $this->adminNote = '';

        unset($this->requests, $this->stats);

        session()->flash('success', 'Pengajuan peminjaman berhasil ditolak.');
    }

    public function handover(int $id): void
    {
        try {
            DB::transaction(function () use ($id) {
                $request = PeminjamanAlat::with('detail.unitPeminjaman')
                    ->lockForUpdate()
                    ->findOrFail($id);

                if ($request->status !== 'disetujui') {
                    throw ValidationException::withMessages([
                        'handover' => 'Pengajuan ini tidak dapat diserahkan.',
                    ]);
                }

                $unitIds = $request->detail
                    ->flatMap(fn ($detail) => $detail->unitPeminjaman->pluck('unit_alat_id'));

                $expectedCount = $request->detail->sum('jumlah');

                if ($unitIds->count() !== $expectedCount) {
                    throw ValidationException::withMessages([
                        'handover' => 'Alokasi unit inventaris belum lengkap.',
                    ]);
                }

                $units = UnitAlat::whereIn('id', $unitIds)
                    ->lockForUpdate()
                    ->get();

                if ($units->count() !== $expectedCount || $units->contains(fn ($unit) => $unit->status !== 'tersedia')) {
                    throw ValidationException::withMessages([
                        'handover' => 'Ada unit yang tidak lagi tersedia.',
                    ]);
                }

                UnitAlat::whereIn('id', $unitIds)->update([
                    'status' => 'dipinjam',
                ]);

                $request->update([
                    'status' => 'dipinjam',
                ]);
            });

            unset($this->requests, $this->stats);

            session()->flash('success', 'Alat berhasil ditandai telah diserahkan kepada peminjam.');

        } catch (ValidationException $e) {
            session()->flash('error', collect($e->errors())->flatten()->first());
        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', 'Terjadi kesalahan saat menyerahkan alat.');
        }
    }
};
?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
            Peminjaman Alat
        </h1>
        <p class="mt-1 text-sm text-zinc-500">
            Kelola pengajuan, alokasi unit inventaris, dan penyerahan alat.
        </p>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-2 gap-3 md:grid-cols-5">
        @foreach ([
            'menunggu' => 'Menunggu',
            'disetujui' => 'Disetujui',
            'dipinjam' => 'Dipinjam',
            'selesai' => 'Selesai',
            'ditolak' => 'Ditolak',
        ] as $key => $label)
            <button
                type="button"
                wire:click="$set('statusFilter', '{{ $key }}')"
                class="rounded-xl border p-4 text-left transition
                    {{ $statusFilter === $key
                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-950'
                        : 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900' }}"
            >
                <div class="text-sm text-zinc-500">{{ $label }}</div>
                <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">
                    {{ $this->stats[$key] }}
                </div>
            </button>
        @endforeach
    </div>

    <div class="flex flex-col gap-3 sm:flex-row">
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Cari nama, NIM/NIP, atau nama alat..."
            class="w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900"
        >

        <select
            wire:model.live="statusFilter"
            class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900"
        >
            <option value="semua">Semua Status</option>
            <option value="menunggu">Menunggu</option>
            <option value="disetujui">Disetujui</option>
            <option value="dipinjam">Dipinjam</option>
            <option value="selesai">Selesai</option>
            <option value="ditolak">Ditolak</option>
        </select>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                    <tr>
                        <th class="px-4 py-3">Peminjam</th>
                        <th class="px-4 py-3">Tanggal Pengajuan</th>
                        <th class="px-4 py-3">Rencana Peminjaman</th>
                        <th class="px-4 py-3">Jumlah Jenis Alat</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($this->requests as $request)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium text-zinc-900 dark:text-white">
                                    {{ $request->user->name }}
                                </div>
                                <div class="text-xs text-zinc-500">
                                    {{ $request->user->identity_number ?? $request->user->email }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">
                                {{ $request->tanggal_pengajuan?->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">
                                {{ $request->tanggal_peminjaman?->format('d/m/Y H:i') }}
                                <div class="text-xs text-zinc-500">
                                    s.d. {{ $request->tanggal_pengembalian_rencana?->format('d/m/Y H:i') }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                {{ $request->detail->count() }} jenis
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium
                                    {{ match($request->status) {
                                        'menunggu' => 'bg-yellow-100 text-yellow-700',
                                        'disetujui' => 'bg-blue-100 text-blue-700',
                                        'dipinjam' => 'bg-purple-100 text-purple-700',
                                        'selesai' => 'bg-green-100 text-green-700',
                                        'ditolak' => 'bg-red-100 text-red-700',
                                        default => 'bg-zinc-100 text-zinc-700',
                                    } }}">
                                    {{ ucfirst($request->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <button
                                        wire:click="openDetail({{ $request->id }})"
                                        class="rounded-lg border border-zinc-300 px-3 py-1.5 text-xs hover:bg-zinc-50 dark:border-zinc-600 dark:hover:bg-zinc-800"
                                    >
                                        Detail
                                    </button>

                                    @if ($request->status === 'menunggu')
                                        <button
                                            wire:click="openApproval({{ $request->id }})"
                                            class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs text-white hover:bg-blue-700"
                                        >
                                            Proses
                                        </button>
                                    @elseif ($request->status === 'disetujui')
                                        <button
                                            wire:click="handover({{ $request->id }})"
                                            wire:confirm="Tandai bahwa semua alat sudah diserahkan kepada peminjam?"
                                            class="rounded-lg bg-green-600 px-3 py-1.5 text-xs text-white hover:bg-green-700"
                                        >
                                            Serahkan Alat
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-zinc-500">
                                Belum ada pengajuan peminjaman alat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">
            {{ $this->requests->links() }}
        </div>
    </div>

    {{-- Modal Persetujuan --}}
    @if ($showApprovalModal && $this->approvalRequest)
        
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/50 p-4">
            <div class="mx-auto my-4 max-h-[calc(100vh-2rem)] w-full max-w-2xl overflow-y-auto rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-900">
                <div class="mb-5">
                    <h2 class="text-lg font-bold text-zinc-900 dark:text-white">
                        Persetujuan dan Alokasi Unit
                    </h2>
                    <p class="mt-1 text-sm text-zinc-500">
                        Pilih unit inventaris yang akan diberikan kepada peminjam.
                    </p>
                </div>

                @if ($approvalError)
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                        {{ $approvalError }}
                    </div>
                @endif

                <div class="mb-5 space-y-4">
                    @foreach ($this->approvalRequest->detail as $detail)
                        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            <div class="font-semibold text-zinc-900 dark:text-white">
                                {{ $detail->alat->nama }}
                            </div>
                            <div class="mb-3 text-sm text-zinc-500">
                                Kebutuhan: {{ $detail->jumlah }} unit
                            </div>

                            <label class="mb-2 block text-sm font-medium">
                                Pilih kode inventaris
                            </label>


                            <div class="max-h-52 space-y-2 overflow-y-auto rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                                @forelse ($this->availableUnits($detail->id) as $unit)
                                    <label class="flex cursor-pointer items-center gap-3 rounded-md p-2 hover:bg-zinc-50 dark:hover:bg-zinc-800">
                                        <input
                                            type="checkbox"
                                            wire:model="selectedUnits.{{ $detail->id }}"
                                            value="{{ $unit->id }}"
                                            class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500"
                                        >

                                        <span class="text-sm text-zinc-800 dark:text-zinc-200">
                                            {{ $unit->kode_inventaris }}
                                        </span>

                                        <span class="ml-auto text-xs text-green-600">
                                            {{ ucfirst($unit->kondisi) }}
                                        </span>
                                    </label>
                                @empty
                                    <p class="text-sm text-red-500">
                                        Tidak ada unit inventaris yang tersedia.
                                    </p>
                                @endforelse
                            </div>

                            <p class="mt-2 text-xs text-zinc-500">
                                Pilih tepat {{ $detail->jumlah }} unit inventaris.
                            </p>

                        </div>
                    @endforeach
                </div>

                <div class="mb-5">
                    <label class="mb-2 block text-sm font-medium">
                        Catatan admin (opsional)
                    </label>
                    <textarea
                        wire:model="adminNote"
                        rows="2"
                        class="w-full rounded-lg border border-zinc-300 bg-white p-3 text-sm dark:border-zinc-700 dark:bg-zinc-800"
                        placeholder="Catatan untuk pengajuan ini..."
                    ></textarea>
                </div>

                <div class="flex justify-end gap-2">
                    <button
                        wire:click="closeApproval"
                        class="rounded-lg border border-zinc-300 px-4 py-2 text-sm dark:border-zinc-600"
                    >
                        Batal
                    </button>
                    <button
                        wire:click="approve"
                        wire:loading.attr="disabled"
                        wire:target="approve"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="approve">Setujui Pengajuan</span>
                        <span wire:loading wire:target="approve">Memproses...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Detail --}}
    @if ($showDetailModal && $this->detailRequest)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 p-4">
            <div class="my-8 w-full max-w-xl rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-900">
                <h2 class="mb-4 text-lg font-bold text-zinc-900 dark:text-white">
                    Detail Peminjaman
                </h2>

                <div class="mb-4 space-y-2 text-sm">
                    <p><strong>Peminjam:</strong> {{ $this->detailRequest->user->name }}</p>
                    <p><strong>Identitas:</strong> {{ $this->detailRequest->user->identity_number ?? '-' }}</p>
                    <p><strong>Tujuan:</strong> {{ $this->detailRequest->tujuan }}</p>
                    <p><strong>Tanggal pengajuan:</strong> {{ $this->detailRequest->tanggal_pengajuan?->format('d/m/Y H:i') }}</p>
                    <p><strong>Rencana peminjaman:</strong> {{ $this->detailRequest->tanggal_peminjaman?->format('d/m/Y H:i') }}</p>
                    <p><strong>Rencana pengembalian:</strong> {{ $this->detailRequest->tanggal_pengembalian_rencana?->format('d/m/Y H:i') }}</p>
                    <p><strong>Status:</strong> {{ ucfirst($this->detailRequest->status) }}</p>
                    <p><strong>Catatan admin:</strong> {{ $this->detailRequest->catatan_admin ?: '-' }}</p>
                </div>

                <div class="mb-5">
                    <h3 class="mb-2 font-semibold">Daftar alat</h3>
                    <div class="space-y-3">
                        @foreach ($this->detailRequest->detail as $detail)
                            <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                                <div class="font-medium">{{ $detail->alat->nama }}</div>
                                <div class="text-sm text-zinc-500">
                                    Jumlah: {{ $detail->jumlah }} unit
                                </div>

                                @if ($detail->unitPeminjaman->isNotEmpty())
                                    <div class="mt-2 text-sm">
                                        <strong>Unit dialokasikan:</strong>
                                        <ul class="list-inside list-disc text-zinc-500">
                                            @foreach ($detail->unitPeminjaman as $allocation)
                                                <li>{{ $allocation->unitAlat->kode_inventaris }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
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
</div>
