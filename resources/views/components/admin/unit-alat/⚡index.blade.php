<?php

use App\Models\Alat;
use App\Models\UnitAlat;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterKondisi = '';
    public string $filterStatus = '';

    public bool $showForm = false;
    public ?int $unitId = null;

    public string $alat_id = '';
    public string $kode_inventaris = '';
    public string $kondisi = 'baik';
    public string $status = 'tersedia';
    public string $keterangan = '';

    #[Computed]
    public function units()
    {
        return UnitAlat::query()
            ->with('alat')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('kode_inventaris', 'like', '%' . $this->search . '%')
                        ->orWhereHas('alat', function ($alatQuery) {
                            $alatQuery->where('nama', 'like', '%' . $this->search . '%')
                                ->orWhere('kode_alat', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->filterKondisi, fn ($query) =>
                $query->where('kondisi', $this->filterKondisi)
            )
            ->when($this->filterStatus, fn ($query) =>
                $query->where('status', $this->filterStatus)
            )
            ->latest()
            ->paginate(10);
    }

    #[Computed]
    public function alats()
    {
        return Alat::where('is_active', true)
            ->orderBy('nama')
            ->get();
    }

    #[Computed]
    public function statistik()
    {
        return [
            'total' => UnitAlat::count(),
            'baik' => UnitAlat::where('kondisi', 'baik')->count(),
            'rusak' => UnitAlat::where('kondisi', 'rusak')->count(),
            'perbaikan' => UnitAlat::where('kondisi', 'dalam_perbaikan')->count(),
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterKondisi(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    protected function rules(): array
    {
        return [
            'alat_id' => [
                'required',
                'integer',
                Rule::exists('alat', 'id')->where('is_active', true),
            ],
            'kode_inventaris' => [
                'required',
                'string',
                'max:255',
                Rule::unique('unit_alat', 'kode_inventaris')->ignore($this->unitId),
            ],
            'kondisi' => [
                'required',
                Rule::in(['baik', 'rusak', 'dalam_perbaikan']),
            ],
            'status' => [
                'required',
                Rule::in(['tersedia', 'dipinjam', 'tidak_tersedia']),
            ],
            'keterangan' => ['nullable', 'string'],
        ];
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $unit = UnitAlat::findOrFail($id);

        $this->unitId = $unit->id;
        $this->alat_id = (string) $unit->alat_id;
        $this->kode_inventaris = $unit->kode_inventaris;
        $this->kondisi = $unit->kondisi;
        $this->status = $unit->status;
        $this->keterangan = $unit->keterangan ?? '';

        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->unitId) {
            $unit = UnitAlat::findOrFail($this->unitId);

            if ($unit->status === 'dipinjam' &&
                ($validated['status'] !== 'dipinjam' ||
                 $validated['kondisi'] !== $unit->kondisi)) {
                $this->addError(
                    'status',
                    'Unit sedang dipinjam. Kondisi dan statusnya tidak boleh diubah melalui formulir ini.'
                );

                return;
            }

            if ($validated['status'] === 'dipinjam' &&
                $validated['kondisi'] !== 'baik') {
                $this->addError(
                    'status',
                    'Unit dengan kondisi rusak atau dalam perbaikan tidak dapat berstatus dipinjam.'
                );

                return;
            }

            $unit->update($validated);

            session()->flash('success', 'Unit inventaris berhasil diperbarui.');
        } else {
            if ($validated['status'] === 'dipinjam') {
                $this->addError(
                    'status',
                    'Unit baru tidak boleh langsung berstatus dipinjam.'
                );

                return;
            }

            if ($validated['kondisi'] !== 'baik' &&
                $validated['status'] === 'tersedia') {
                $this->addError(
                    'status',
                    'Unit rusak atau dalam perbaikan tidak boleh berstatus tersedia.'
                );

                return;
            }

            UnitAlat::create($validated);

            session()->flash('success', 'Unit inventaris berhasil ditambahkan.');
        }

        $this->cancelForm();
    }

    public function cancelForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset([
            'unitId',
            'alat_id',
            'kode_inventaris',
            'keterangan',
        ]);

        $this->kondisi = 'baik';
        $this->status = 'tersedia';

        $this->resetValidation();
    }
};
?>

<div>
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">
                Unit Inventaris
            </h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Kelola setiap unit fisik alat laboratorium.
            </p>
        </div>

        <flux:button variant="primary" wire:click="create">
            Tambah Unit
        </flux:button>
    </div>

    @if (session()->has('success'))
        <div class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500">Total Unit</p>
            <p class="mt-1 text-2xl font-semibold">{{ $this->statistik['total'] }}</p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500">Kondisi Baik</p>
            <p class="mt-1 text-2xl font-semibold text-green-600">{{ $this->statistik['baik'] }}</p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500">Rusak</p>
            <p class="mt-1 text-2xl font-semibold text-red-600">{{ $this->statistik['rusak'] }}</p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500">Dalam Perbaikan</p>
            <p class="mt-1 text-2xl font-semibold text-amber-600">{{ $this->statistik['perbaikan'] }}</p>
        </div>
    </div>

    @if ($showForm)
        <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <h3 class="mb-4 font-semibold">
                {{ $unitId ? 'Edit Unit Inventaris' : 'Tambah Unit Inventaris' }}
            </h3>

            <form wire:submit="save" class="space-y-4">
                <flux:field>
                    <flux:label>Jenis Alat</flux:label>
                    <flux:select wire:model="alat_id" placeholder="Pilih jenis alat">
                        @foreach ($this->alats as $alat)
                            <flux:select.option value="{{ $alat->id }}">
                                {{ $alat->kode_alat }} - {{ $alat->nama }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="alat_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Kode Inventaris</flux:label>
                    <flux:input wire:model="kode_inventaris" placeholder="Contoh: INV-001" />
                    <flux:error name="kode_inventaris" />
                </flux:field>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Kondisi</flux:label>
                        <flux:select wire:model="kondisi">
                            <flux:select.option value="baik">Baik</flux:select.option>
                            <flux:select.option value="rusak">Rusak</flux:select.option>
                            <flux:select.option value="dalam_perbaikan">Dalam Perbaikan</flux:select.option>
                        </flux:select>
                        <flux:error name="kondisi" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Status</flux:label>
                        <flux:select wire:model="status">
                            <flux:select.option value="tersedia">Tersedia</flux:select.option>
                            <flux:select.option value="dipinjam">Dipinjam</flux:select.option>
                            <flux:select.option value="tidak_tersedia">Tidak Tersedia</flux:select.option>
                        </flux:select>
                        <flux:error name="status" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Keterangan</flux:label>
                    <flux:textarea wire:model="keterangan" rows="3" placeholder="Keterangan kondisi atau unit..." />
                    <flux:error name="keterangan" />
                </flux:field>

                <div class="flex justify-end gap-2">
                    <flux:button type="button" variant="ghost" wire:click="cancelForm">
                        Batal
                    </flux:button>

                    <flux:button type="submit" variant="primary">
                        Simpan
                    </flux:button>
                </div>
            </form>
        </div>
    @endif

    <div class="mb-4 grid gap-3 sm:grid-cols-3">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Cari kode inventaris atau nama alat..."
        />

        <flux:select wire:model.live="filterKondisi" placeholder="Semua kondisi" clearable>
            <flux:select.option value="baik">Baik</flux:select.option>
            <flux:select.option value="rusak">Rusak</flux:select.option>
            <flux:select.option value="dalam_perbaikan">Dalam Perbaikan</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="filterStatus" placeholder="Semua status" clearable>
            <flux:select.option value="tersedia">Tersedia</flux:select.option>
            <flux:select.option value="dipinjam">Dipinjam</flux:select.option>
            <flux:select.option value="tidak_tersedia">Tidak Tersedia</flux:select.option>
        </flux:select>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 text-left dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 font-medium">Kode Inventaris</th>
                        <th class="px-4 py-3 font-medium">Nama Alat</th>
                        <th class="px-4 py-3 font-medium">Kondisi</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($this->units as $unit)
                        <tr wire:key="unit-{{ $unit->id }}">
                            <td class="px-4 py-3 font-medium">{{ $unit->kode_inventaris }}</td>
                            <td class="px-4 py-3">{{ $unit->alat->nama }}</td>
                            <td class="px-4 py-3">
                                @if ($unit->kondisi === 'baik')
                                    <flux:badge color="green">Baik</flux:badge>
                                @elseif ($unit->kondisi === 'rusak')
                                    <flux:badge color="red">Rusak</flux:badge>
                                @else
                                    <flux:badge color="amber">Dalam Perbaikan</flux:badge>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($unit->status === 'tersedia')
                                    <flux:badge color="green">Tersedia</flux:badge>
                                @elseif ($unit->status === 'dipinjam')
                                    <flux:badge color="blue">Dipinjam</flux:badge>
                                @else
                                    <flux:badge color="zinc">Tidak Tersedia</flux:badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    wire:click="edit({{ $unit->id }})"
                                >
                                    Edit
                                </flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-zinc-500">
                                Unit inventaris belum tersedia.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">
            {{ $this->units->links() }}
        </div>
    </div>
</div>
