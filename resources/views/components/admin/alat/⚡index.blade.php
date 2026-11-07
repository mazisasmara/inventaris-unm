<?php

use App\Models\Alat;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showForm = false;
    public ?int $alatId = null;

    public string $kode_alat = '';
    public string $nama = '';
    public string $kategori = '';
    public string $deskripsi = '';

    #[Computed]
    public function alats()
    {
        return Alat::query()
            ->withCount('unitAlat')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('kode_alat', 'like', '%' . $this->search . '%')
                        ->orWhere('nama', 'like', '%' . $this->search . '%')
                        ->orWhere('kategori', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('nama')
            ->paginate(10);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    protected function rules(): array
    {
        return [
            'kode_alat' => [
                'required',
                'string',
                'max:255',
                Rule::unique('alat', 'kode_alat')->ignore($this->alatId),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'kategori' => ['nullable', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
        ];
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $alat = Alat::findOrFail($id);

        $this->alatId = $alat->id;
        $this->kode_alat = $alat->kode_alat;
        $this->nama = $alat->nama;
        $this->kategori = $alat->kategori ?? '';
        $this->deskripsi = $alat->deskripsi ?? '';

        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->alatId) {
            $alat = Alat::findOrFail($this->alatId);
            $alat->update($validated);

            session()->flash('success', 'Data alat berhasil diperbarui.');
        } else {
            Alat::create($validated);

            session()->flash('success', 'Data alat berhasil ditambahkan.');
        }

        $this->cancelForm();
    }

    public function toggleStatus(int $id): void
    {
        $alat = Alat::findOrFail($id);
        $alat->update([
            'is_active' => ! $alat->is_active,
        ]);

        session()->flash(
            'success',
            $alat->is_active
                ? 'Alat berhasil diaktifkan.'
                : 'Alat berhasil dinonaktifkan.'
        );
    }

    public function cancelForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset([
            'alatId',
            'kode_alat',
            'nama',
            'kategori',
            'deskripsi',
        ]);

        $this->resetValidation();
    }
};
?>

<div>
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">
                Data Alat
            </h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Kelola jenis alat inventaris laboratorium.
            </p>
        </div>

        <flux:button variant="primary" wire:click="create">
            Tambah Alat
        </flux:button>
    </div>

    @if (session()->has('success'))
        <div class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    @if ($showForm)
        <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <h3 class="mb-4 font-semibold text-zinc-900 dark:text-white">
                {{ $alatId ? 'Edit Data Alat' : 'Tambah Data Alat' }}
            </h3>

            <form wire:submit="save" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Kode Alat</flux:label>
                        <flux:input wire:model="kode_alat" placeholder="Contoh: ALT-001" />
                        <flux:error name="kode_alat" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Nama Alat</flux:label>
                        <flux:input wire:model="nama" placeholder="Contoh: Mikroskop" />
                        <flux:error name="nama" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Kategori</flux:label>
                        <flux:input wire:model="kategori" placeholder="Contoh: Elektronik" />
                        <flux:error name="kategori" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Deskripsi</flux:label>
                    <flux:textarea wire:model="deskripsi" rows="3" placeholder="Keterangan tambahan alat..." />
                    <flux:error name="deskripsi" />
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

    <div class="mb-4 max-w-md">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Cari kode, nama, atau kategori alat..."
        />
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 text-left dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 font-medium">Kode</th>
                        <th class="px-4 py-3 font-medium">Nama Alat</th>
                        <th class="px-4 py-3 font-medium">Kategori</th>
                        <th class="px-4 py-3 text-center font-medium">Jumlah Unit</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($this->alats as $alat)
                        <tr wire:key="alat-{{ $alat->id }}">
                            <td class="px-4 py-3 font-medium">{{ $alat->kode_alat }}</td>
                            <td class="px-4 py-3">{{ $alat->nama }}</td>
                            <td class="px-4 py-3">{{ $alat->kategori ?: '-' }}</td>
                            <td class="px-4 py-3 text-center">{{ $alat->unit_alat_count }}</td>
                            <td class="px-4 py-3">
                                @if ($alat->is_active)
                                    <flux:badge color="green">Aktif</flux:badge>
                                @else
                                    <flux:badge color="zinc">Nonaktif</flux:badge>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <flux:button size="sm" variant="ghost" wire:click="edit({{ $alat->id }})">
                                        Edit
                                    </flux:button>

                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        wire:click="toggleStatus({{ $alat->id }})"
                                        wire:confirm="Yakin ingin mengubah status alat ini?"
                                    >
                                        {{ $alat->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-zinc-500">
                                Data alat tidak ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">
            {{ $this->alats->links() }}
        </div>
    </div>
</div>
