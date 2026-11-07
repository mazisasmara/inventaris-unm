<?php

use App\Models\PengambilanBahan;
use App\Models\Bahan;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterStatus = 'menunggu';

    public ?int $selectedId = null;
    public bool $showDetail = false;
    public ?string $approvalError = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function pengajuan()
    {
        return PengambilanBahan::query()
            ->with(['user', 'pemroses', 'detail.bahan'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->whereHas('user', function ($userQuery) {
                        $userQuery->where('name', 'like', '%' . $this->search . '%')
                            ->orWhere('identity_number', 'like', '%' . $this->search . '%');
                    })
                    ->orWhere('tujuan', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterStatus !== 'semua', function ($query) {
                $query->where('status', $this->filterStatus);
            })
            ->latest('tanggal_pengajuan')
            ->paginate(10);
    }

    #[Computed]
    public function statistik()
    {
        return [
            'menunggu' => PengambilanBahan::where('status', 'menunggu')->count(),
            'disetujui' => PengambilanBahan::where('status', 'disetujui')->count(),
            'ditolak' => PengambilanBahan::where('status', 'ditolak')->count(),
            'selesai' => PengambilanBahan::where('status', 'selesai')->count(),
        ];
    }

    public function lihatDetail(int $id): void
    {
        $pengajuan = PengambilanBahan::with('detail.bahan')->findOrFail($id);

        $this->selectedId = $pengajuan->id;
        $this->showDetail = true;
        $this->approvalError = null;
    }

    public function tutupDetail(): void
    {
        $this->showDetail = false;
        $this->selectedId = null;
    }

    
    public function setujui(int $id): void
    {
        $this->approvalError = null;

        $error = DB::transaction(function () use ($id) {
            $pengajuan = PengambilanBahan::with('detail.bahan')
                ->lockForUpdate()
                ->findOrFail($id);

            if ($pengajuan->status !== 'menunggu') {
                return 'Pengajuan ini sudah diproses.';
            }

            foreach ($pengajuan->detail as $detail) {
                $bahan = Bahan::whereKey($detail->bahan_id)
                    ->lockForUpdate()
                    ->first();

                if (! $bahan || ! $bahan->is_active || $bahan->stok < $detail->jumlah) {
                    return 'Stok bahan ' . ($bahan?->nama ?? 'yang diminta')
                        . ' tidak mencukupi. Pengajuan belum disetujui.';
                }
            }

            foreach ($pengajuan->detail as $detail) {
                Bahan::whereKey($detail->bahan_id)
                    ->decrement('stok', $detail->jumlah);
            }

            $pengajuan->update([
                'status' => 'disetujui',
                'diproses_oleh' => auth()->id(),
                'diproses_at' => now(),
            ]);

            return null;
        });

        if ($error !== null) {
            $this->approvalError = $error;
            unset($this->pengajuan, $this->statistik);
            return;
        }

        unset($this->pengajuan, $this->statistik);

        $this->tutupDetail();

        $this->dispatch('toast', message: 'Pengajuan berhasil disetujui.');
    }


    public function tandaiSelesai(int $id): void
    {
        DB::transaction(function () use ($id) {
            $pengajuan = PengambilanBahan::lockForUpdate()
                ->findOrFail($id);

            if ($pengajuan->status !== 'disetujui') {
                $this->approvalError = 'Hanya pengajuan yang sudah disetujui yang dapat diselesaikan.';
                return;
            }

            $pengajuan->update([
                'status' => 'selesai',
                'selesai_at' => now(),
            ]);
        });

        unset($this->pengajuan, $this->statistik);

        if ($this->approvalError) {
            return;
        }

        $this->tutupDetail();

        $this->dispatch('toast', message: 'Pengambilan bahan berhasil diselesaikan.');
    }

    public function tolak(int $id): void
    {
        $pengajuan = PengambilanBahan::findOrFail($id);

        if ($pengajuan->status !== 'menunggu') {
            $this->addError('approval', 'Pengajuan ini sudah diproses.');
            return;
        }

        $pengajuan->update([
            'status' => 'ditolak',
            'diproses_oleh' => auth()->id(),
            'diproses_at' => now(),
        ]);

        unset($this->pengajuan, $this->statistik);

        $this->tutupDetail();

        $this->dispatch('toast', message: 'Pengajuan berhasil ditolak.');
    }
};
?>

<div class="mx-auto max-w-7xl space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">
            Pengambilan Bahan
        </h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            Kelola dan proses pengajuan pengambilan bahan dari mahasiswa dan dosen.
        </p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($this->statistik as $status => $jumlah)
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-sm capitalize text-zinc-500">{{ $status }}</p>
                <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-white">
                    {{ $jumlah }}
                </p>
            </div>
        @endforeach
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari nama, NIM/NIP, atau tujuan..."
                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white sm:max-w-sm"
            >

            <select
                wire:model.live="filterStatus"
                class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
            >
                <option value="semua">Semua Status</option>
                <option value="menunggu">Menunggu</option>
                <option value="disetujui">Disetujui</option>
                <option value="ditolak">Ditolak</option>
                <option value="selesai">Selesai</option>
            </select>
        </div>

        @error('approval')
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300">
                {{ $message }}
            </div>
        @enderror

        <div class="mt-5 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 text-zinc-500 dark:border-zinc-700">
                    <tr>
                        <th class="px-3 py-3">Pemohon</th>
                        <th class="px-3 py-3">Tujuan</th>
                        <th class="px-3 py-3">Tanggal</th>
                        <th class="px-3 py-3">Jumlah Jenis</th>
                        <th class="px-3 py-3">Status</th>
                        <th class="px-3 py-3 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($this->pengajuan as $item)
                        <tr>
                            <td class="px-3 py-3">
                                <p class="font-medium text-zinc-900 dark:text-white">
                                    {{ $item->user->name }}
                                </p>
                                <p class="text-xs text-zinc-500">
                                    {{ $item->user->identity_number ?? $item->user->email }}
                                </p>
                            </td>

                            <td class="px-3 py-3">
                                {{ $item->tujuan }}
                            </td>

                            <td class="px-3 py-3 whitespace-nowrap">
                                {{ $item->tanggal_pengajuan?->format('d/m/Y H:i') }}
                            </td>

                            <td class="px-3 py-3">
                                {{ $item->detail->count() }} jenis
                            </td>

                            <td class="px-3 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium
                                    {{ $item->status === 'menunggu' ? 'bg-amber-100 text-amber-700' : '' }}
                                    {{ $item->status === 'disetujui' ? 'bg-green-100 text-green-700' : '' }}
                                    {{ $item->status === 'ditolak' ? 'bg-red-100 text-red-700' : '' }}
                                    {{ $item->status === 'selesai' ? 'bg-blue-100 text-blue-700' : '' }}">
                                    {{ ucfirst($item->status) }}
                                </span>
                            </td>

                            <td class="px-3 py-3 text-right whitespace-nowrap">
                                <button
                                    type="button"
                                    wire:click="lihatDetail({{ $item->id }})"
                                    class="text-sm font-medium text-blue-600 hover:text-blue-700"
                                >
                                    Detail
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-10 text-center text-zinc-500">
                                Belum ada pengajuan pengambilan bahan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $this->pengajuan->links() }}
        </div>
    </div>

    @if ($showDetail && $selectedId)
        @php
            $selected = PengambilanBahan::with(['user', 'detail.bahan'])->find($selectedId);
        @endphp

        @if ($selected)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <div class="w-full max-w-2xl rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                            Detail Pengajuan
                        </h2>

                        <button
                            type="button"
                            wire:click="tutupDetail"
                            class="text-zinc-500 hover:text-zinc-800 dark:hover:text-white"
                        >
                            Tutup
                        </button>
                    </div>

                    <div class="mt-5 space-y-3 text-sm">
                        @if ($approvalError)
                            <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300">
                                {{ $approvalError }}
                            </div>
                        @endif

                        <div>
                            <span class="text-zinc-500">Pemohon:</span>
                            <span class="font-medium text-zinc-900 dark:text-white">
                                {{ $selected->user->name }}
                            </span>
                        </div>

                        <div>
                            <span class="text-zinc-500">Tujuan:</span>
                            <p class="mt-1 text-zinc-900 dark:text-white">
                                {{ $selected->tujuan }}
                            </p>
                        </div>

                        <div>
                            <p class="mb-2 font-medium text-zinc-900 dark:text-white">
                                Bahan yang diminta
                            </p>

                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead class="border-b dark:border-zinc-700">
                                        <tr>
                                            <th class="py-2">Bahan</th>
                                            <th class="py-2">Jumlah</th>
                                            <th class="py-2">Stok Saat Ini</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($selected->detail as $detail)
                                            <tr class="border-b dark:border-zinc-800">
                                                <td class="py-2">
                                                    {{ $detail->bahan->nama }}
                                                </td>
                                                <td class="py-2">
                                                    {{ $detail->jumlah }} {{ $detail->bahan->satuan }}
                                                </td>
                                                <td class="py-2">
                                                    {{ $detail->bahan->stok }} {{ $detail->bahan->satuan }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    @if ($selected->status === 'menunggu')
                        <div class="mt-6 flex flex-wrap justify-end gap-3">
                            <button
                                type="button"
                                wire:click="tolak({{ $selected->id }})"
                                wire:confirm="Yakin ingin menolak pengajuan ini?"
                                class="rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50"
                            >
                                Tolak
                            </button>

                            <button
                                type="button"
                                wire:click="setujui({{ $selected->id }})"
                                wire:confirm="Setujui pengajuan ini dan kurangi stok bahan?"
                                class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
                            >
                                Setujui Pengajuan
                            </button>
                        </div>
                    @elseif ($selected->status === 'disetujui')
                        <div class="mt-6 flex justify-end">
                            <button
                                type="button"
                                wire:click="tandaiSelesai({{ $selected->id }})"
                                wire:confirm="Pastikan bahan sudah diserahkan kepada pemohon. Tandai selesai?"
                                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                            >
                                Tandai Selesai
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    @endif
</div>
