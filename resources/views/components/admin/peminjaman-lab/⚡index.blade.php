<?php

use App\Models\PeminjamanLab;
use App\Models\JadwalLaboratorium;
use App\Models\Laboratorium;
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
    public string $statusFilter = 'semua';

    public ?int $selectedRequestId = null;
    public string $catatanAdmin = '';

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
        return PeminjamanLab::with([
            'user',
            'laboratorium',
            'pemroses',
        ])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('tujuan', 'like', '%' . $this->search . '%')
                        ->orWhereHas('user', function ($user) {
                            $user->where('name', 'like', '%' . $this->search . '%')
                                ->orWhere('identity_number', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('laboratorium', function ($lab) {
                            $lab->where('nama', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->statusFilter !== 'semua', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->latest()
            ->paginate(10);
    }

    #[Computed]
    public function stats()
    {
        return [
            'total' => PeminjamanLab::count(),
            'menunggu' => PeminjamanLab::where('status', 'menunggu')->count(),
            'disetujui' => PeminjamanLab::where('status', 'disetujui')->count(),
            'berlangsung' => PeminjamanLab::where('status', 'berlangsung')->count(),
            'selesai' => PeminjamanLab::where('status', 'selesai')->count(),
            'ditolak' => PeminjamanLab::where('status', 'ditolak')->count(),
        ];
    }

    #[Computed]
    public function detailRequest()
    {
        if (! $this->selectedRequestId) {
            return null;
        }

        return PeminjamanLab::with([
            'user',
            'laboratorium',
            'pemroses',
            'dokumentasi',
        ])->find($this->selectedRequestId);
    }

    public function showDetail(int $id): void
    {
        $this->selectedRequestId = $id;
        $this->catatanAdmin = '';

        unset($this->detailRequest);
    }

    public function closeDetail(): void
    {
        $this->selectedRequestId = null;
        $this->catatanAdmin = '';

        unset($this->detailRequest);
    }

    public function approve(int $id): void
    {
        DB::transaction(function () use ($id) {
            $request = PeminjamanLab::whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($request->status !== 'menunggu') {
                throw ValidationException::withMessages([
                    'approval' => 'Pengajuan ini sudah diproses sebelumnya.',
                ]);
            }

            // Kunci data laboratorium agar persetujuan untuk
            // laboratorium yang sama berjalan secara berurutan.
            $lab = Laboratorium::whereKey($request->laboratorium_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lab->is_active) {
                throw ValidationException::withMessages([
                    'approval' => 'Laboratorium sudah tidak aktif.',
                ]);
            }

            $start = $request->waktu_mulai;
            $end = $request->waktu_selesai;

            if ($start->toDateString() !== $end->toDateString()) {
                throw ValidationException::withMessages([
                    'approval' => 'Peminjaman lintas hari belum didukung.',
                ]);
            }

            $hariIndonesia = [
                'Sunday' => 'minggu',
                'Monday' => 'senin',
                'Tuesday' => 'selasa',
                'Wednesday' => 'rabu',
                'Thursday' => 'kamis',
                'Friday' => 'jumat',
                'Saturday' => 'sabtu',
            ];

            $hari = $hariIndonesia[$start->format('l')];

            $jadwalValid = JadwalLaboratorium::where(
                'laboratorium_id',
                $lab->id
            )
                ->where('hari', $hari)
                ->where('is_active', true)
                ->where('jam_mulai', '<=', $start->format('H:i:s'))
                ->where('jam_selesai', '>=', $end->format('H:i:s'))
                ->exists();

            if (! $jadwalValid) {
                throw ValidationException::withMessages([
                    'approval' => 'Waktu pengajuan berada di luar jadwal operasional laboratorium.',
                ]);
            }

            $bentrok = PeminjamanLab::where(
                'laboratorium_id',
                $lab->id
            )
                ->whereIn('status', ['disetujui', 'berlangsung'])
                ->where('id', '!=', $request->id)
                ->where('waktu_mulai', '<', $end)
                ->where('waktu_selesai', '>', $start)
                ->exists();

            if ($bentrok) {
                throw ValidationException::withMessages([
                    'approval' => 'Pengajuan tidak dapat disetujui karena jadwal laboratorium sudah digunakan.',
                ]);
            }

            $request->update([
                'status' => 'disetujui',
                'diproses_oleh' => Auth::id(),
                'diproses_at' => now(),
                'catatan_admin' => $this->catatanAdmin ?: null,
            ]);
        });

        $this->closeDetail();

        session()->flash(
            'success',
            'Pengajuan peminjaman laboratorium berhasil disetujui.'
        );

        unset($this->requests, $this->stats);
    }

    public function reject(int $id): void
    {
        $request = PeminjamanLab::findOrFail($id);

        if ($request->status !== 'menunggu') {
            throw ValidationException::withMessages([
                'approval' => 'Hanya pengajuan yang menunggu yang dapat ditolak.',
            ]);
        }

        $request->update([
            'status' => 'ditolak',
            'diproses_oleh' => Auth::id(),
            'diproses_at' => now(),
            'catatan_admin' => $this->catatanAdmin ?: null,
        ]);

        $this->closeDetail();

        session()->flash(
            'success',
            'Pengajuan peminjaman laboratorium berhasil ditolak.'
        );

        unset($this->requests, $this->stats);
    }

    public function startUsage(int $id): void
    {
        $request = PeminjamanLab::findOrFail($id);

        if ($request->status !== 'disetujui') {
            throw ValidationException::withMessages([
                'approval' => 'Hanya peminjaman yang disetujui yang dapat dimulai.',
            ]);
        }

        if (now()->lt($request->waktu_mulai)) {
            throw ValidationException::withMessages([
                'approval' => 'Waktu penggunaan laboratorium belum dimulai.',
            ]);
        }

        $request->update([
            'status' => 'berlangsung',
        ]);

        session()->flash('success', 'Penggunaan laboratorium ditandai berlangsung.');

        unset($this->requests, $this->stats, $this->detailRequest);
    }

    public function finishUsage(int $id): void
    {
        $request = PeminjamanLab::findOrFail($id);

        if ($request->status !== 'berlangsung') {
            throw ValidationException::withMessages([
                'approval' => 'Peminjaman ini tidak sedang berlangsung.',
            ]);
        }

        $request->update([
            'status' => 'selesai',
        ]);

        session()->flash('success', 'Penggunaan laboratorium berhasil diselesaikan.');

        $this->closeDetail();

        unset($this->requests, $this->stats);
    }
};
?>


<div class="space-y-6">

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
            Peminjaman Laboratorium
        </h1>
        <p class="mt-1 text-sm text-zinc-500">
            Kelola pengajuan dan penggunaan laboratorium.
        </p>
    </div>

    {{-- Flash Message --}}
    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700 dark:border-green-900 dark:bg-green-950 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->has('approval'))
        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300">
            {{ $errors->first('approval') }}
        </div>
    @endif

    {{-- Statistik --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-3">
        @foreach ([
            ['label' => 'Total Pengajuan', 'value' => $this->stats['total']],
            ['label' => 'Menunggu', 'value' => $this->stats['menunggu']],
            ['label' => 'Disetujui', 'value' => $this->stats['disetujui']],
            ['label' => 'Berlangsung', 'value' => $this->stats['berlangsung']],
            ['label' => 'Selesai', 'value' => $this->stats['selesai']],
            ['label' => 'Ditolak', 'value' => $this->stats['ditolak']],
        ] as $stat)
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-sm text-zinc-500">{{ $stat['label'] }}</p>
                <p class="mt-2 text-2xl font-bold text-zinc-900 dark:text-white">
                    {{ $stat['value'] }}
                </p>
            </div>
        @endforeach
    </div>

    {{-- Filter --}}
    <div class="flex flex-col gap-3 sm:flex-row">
        <div class="flex-1">
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari peminjam, NIM/NIP, laboratorium, atau tujuan..."
                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900"
            >
        </div>

        <select
            wire:model.live="statusFilter"
            class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900"
        >
            <option value="semua">Semua Status</option>
            <option value="menunggu">Menunggu</option>
            <option value="disetujui">Disetujui</option>
            <option value="ditolak">Ditolak</option>
            <option value="berlangsung">Berlangsung</option>
            <option value="selesai">Selesai</option>
        </select>
    </div>

    {{-- Tabel --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Peminjam</th>
                        <th class="px-4 py-3 font-semibold">Laboratorium</th>
                        <th class="px-4 py-3 font-semibold">Waktu Penggunaan</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($this->requests as $request)
                        <tr wire:key="lab-request-{{ $request->id }}">
                            <td class="px-4 py-4">
                                <p class="font-medium text-zinc-900 dark:text-white">
                                    {{ $request->user->name }}
                                </p>
                                <p class="text-xs text-zinc-500">
                                    {{ $request->user->identity_number ?? '-' }}
                                </p>
                            </td>

                            <td class="px-4 py-4">
                                {{ $request->laboratorium->nama }}
                            </td>

                            <td class="px-4 py-4">
                                <p>{{ $request->waktu_mulai->format('d/m/Y') }}</p>
                                <p class="text-xs text-zinc-500">
                                    {{ $request->waktu_mulai->format('H:i') }}
                                    –
                                    {{ $request->waktu_selesai->format('H:i') }}
                                </p>
                            </td>

                            <td class="px-4 py-4">
                                @php
                                    $statusClass = match ($request->status) {
                                        'menunggu' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',
                                        'disetujui' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
                                        'ditolak' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
                                        'berlangsung' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                                        'selesai' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
                                        default => 'bg-zinc-100 text-zinc-700',
                                    };
                                @endphp

                                <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass }}">
                                    {{ ucfirst($request->status) }}
                                </span>
                            </td>

                            <td class="px-4 py-4 text-right">
                                <button
                                    wire:click="showDetail({{ $request->id }})"
                                    class="rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium hover:bg-zinc-50 dark:border-zinc-600 dark:hover:bg-zinc-800"
                                >
                                    Detail
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-zinc-500">
                                Belum ada pengajuan peminjaman laboratorium.
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

    {{-- Modal Detail --}}
    @if ($this->detailRequest)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/50 p-4">
            <div class="mx-auto my-4 max-h-[calc(100vh-2rem)] w-full max-w-2xl overflow-y-auto rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-900">

                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-zinc-900 dark:text-white">
                            Detail Pengajuan #{{ $this->detailRequest->id }}
                        </h2>
                        <p class="mt-1 text-sm text-zinc-500">
                            Informasi lengkap peminjaman laboratorium.
                        </p>
                    </div>

                    <button
                        wire:click="closeDetail"
                        class="text-zinc-500 hover:text-zinc-900 dark:hover:text-white"
                    >
                        ✕
                    </button>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs text-zinc-500">Peminjam</p>
                        <p class="mt-1 font-medium">
                            {{ $this->detailRequest->user->name }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-zinc-500">NIM / NIP</p>
                        <p class="mt-1 font-medium">
                            {{ $this->detailRequest->user->identity_number ?? '-' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-zinc-500">Laboratorium</p>
                        <p class="mt-1 font-medium">
                            {{ $this->detailRequest->laboratorium->nama }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-zinc-500">Status</p>
                        <p class="mt-1 font-medium">
                            {{ ucfirst($this->detailRequest->status) }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-zinc-500">Waktu Mulai</p>
                        <p class="mt-1 font-medium">
                            {{ $this->detailRequest->waktu_mulai->format('d/m/Y H:i') }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-zinc-500">Waktu Selesai</p>
                        <p class="mt-1 font-medium">
                            {{ $this->detailRequest->waktu_selesai->format('d/m/Y H:i') }}
                        </p>
                    </div>
                </div>

                <div class="mt-5">
                    <p class="text-xs text-zinc-500">Tujuan Penggunaan</p>
                    <div class="mt-2 rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-800">
                        {{ $this->detailRequest->tujuan }}
                    </div>
                </div>

                {{-- Dokumentasi --}}
                <div class="mt-5">
                    <h3 class="mb-3 text-sm font-semibold">
                        Dokumentasi Pendukung
                    </h3>

                    @if ($this->detailRequest->dokumentasi->isNotEmpty())
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                            @foreach ($this->detailRequest->dokumentasi as $file)
                                <a
                                    href="{{ Storage::disk('public')->url($file->file_path) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="block overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700"
                                >
                                    <img
                                        src="{{ Storage::disk('public')->url($file->file_path) }}"
                                        alt="Dokumentasi peminjaman"
                                        class="h-32 w-full object-cover"
                                    >
                                    <p class="p-2 text-xs text-zinc-500">
                                        Lihat dokumentasi
                                    </p>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-zinc-500">
                            Tidak ada dokumentasi yang diunggah.
                        </p>
                    @endif
                </div>

                @if ($this->detailRequest->status === 'menunggu')
                    <div class="mt-6 space-y-4 border-t border-zinc-200 pt-5 dark:border-zinc-700">
                        <div>
                            <label class="mb-2 block text-sm font-medium">
                                Catatan Admin (Opsional)
                            </label>

                            <textarea
                                wire:model="catatanAdmin"
                                rows="3"
                                placeholder="Tambahkan catatan untuk pemohon..."
                                class="w-full rounded-lg border border-zinc-300 bg-white p-3 text-sm dark:border-zinc-700 dark:bg-zinc-800"
                            ></textarea>
                        </div>

                        <div class="flex flex-col-reverse justify-end gap-2 sm:flex-row">
                            <button
                                wire:click="reject({{ $this->detailRequest->id }})"
                                wire:confirm="Yakin ingin menolak pengajuan ini?"
                                wire:loading.attr="disabled"
                                class="rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 disabled:opacity-50"
                            >
                                Tolak Pengajuan
                            </button>

                            <button
                                wire:click="approve({{ $this->detailRequest->id }})"
                                wire:confirm="Yakin ingin menyetujui pengajuan ini?"
                                wire:loading.attr="disabled"
                                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                            >
                                Setujui Pengajuan
                            </button>
                        </div>
                    </div>
                @elseif ($this->detailRequest->status === 'disetujui')
                    <div class="mt-6 border-t border-zinc-200 pt-5 dark:border-zinc-700">
                        <button
                            wire:click="startUsage({{ $this->detailRequest->id }})"
                            wire:confirm="Tandai penggunaan laboratorium ini sebagai berlangsung?"
                            class="w-full rounded-lg bg-green-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-green-700"
                        >
                            Mulai Penggunaan
                        </button>
                    </div>
                @elseif ($this->detailRequest->status === 'berlangsung')
                    <div class="mt-6 border-t border-zinc-200 pt-5 dark:border-zinc-700">
                        <button
                            wire:click="finishUsage({{ $this->detailRequest->id }})"
                            wire:confirm="Yakin penggunaan laboratorium ini sudah selesai?"
                            class="w-full rounded-lg bg-zinc-800 px-4 py-2.5 text-sm font-medium text-white hover:bg-zinc-700"
                        >
                            Selesaikan Penggunaan
                        </button>
                    </div>
                @endif

                @if ($this->detailRequest->catatan_admin)
                    <div class="mt-5 rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                        <p class="text-xs font-semibold text-zinc-500">
                            Catatan Admin
                        </p>
                        <p class="mt-1 text-sm">
                            {{ $this->detailRequest->catatan_admin }}
                        </p>
                    </div>
                @endif

                <div class="mt-6 flex justify-end">
                    <button
                        wire:click="closeDetail"
                        class="rounded-lg border border-zinc-300 px-4 py-2 text-sm hover:bg-zinc-50 dark:border-zinc-600 dark:hover:bg-zinc-800"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
