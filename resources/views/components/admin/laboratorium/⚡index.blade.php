<?php

use App\Models\Laboratorium;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showForm = false;
    public ?int $laboratoriumId = null;

    public string $kode_lab = '';
    public string $nama = '';
    public string $lokasi = '';
    public string $kapasitas = '';
    public string $deskripsi = '';

    #[Computed]
    public function laboratorium()
    {
        return Laboratorium::query()
            ->withCount('jadwal')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('kode_lab', 'like', '%' . $this->search . '%')
                        ->orWhere('nama', 'like', '%' . $this->search . '%')
                        ->orWhere('lokasi', 'like', '%' . $this->search . '%');
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
            'kode_lab' => [
                'required',
                'string',
                'max:255',
                Rule::unique('laboratorium', 'kode_lab')->ignore($this->laboratoriumId),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'kapasitas' => ['nullable', 'integer', 'min:1'],
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
        $laboratorium = Laboratorium::findOrFail($id);

        $this->laboratoriumId = $laboratorium->id;
        $this->kode_lab = $laboratorium->kode_lab;
        $this->nama = $laboratorium->nama;
        $this->lokasi = $laboratorium->lokasi ?? '';
        $this->kapasitas = $laboratorium->kapasitas !== null
            ? (string) $laboratorium->kapasitas
            : '';
        $this->deskripsi = $laboratorium->deskripsi ?? '';

        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->laboratoriumId) {
            $laboratorium = Laboratorium::findOrFail($this->laboratoriumId);
            $laboratorium->update($validated);

            session()->flash('success', 'Data laboratorium berhasil diperbarui.');
        } else {
            Laboratorium::create($validated);

            session()->flash('success', 'Data laboratorium berhasil ditambahkan.');
        }

        $this->cancelForm();
    }

    public function toggleStatus(int $id): void
    {
        $laboratorium = Laboratorium::findOrFail($id);

        $laboratorium->update([
            'is_active' => ! $laboratorium->is_active,
        ]);

        session()->flash(
            'success',
            $laboratorium->is_active
                ? 'Laboratorium berhasil diaktifkan.'
                : 'Laboratorium berhasil dinonaktifkan.'
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
            'laboratoriumId',
            'kode_lab',
            'nama',
            'lokasi',
            'kapasitas',
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
                Data Laboratorium
            </h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Kelola data ruangan laboratorium.
            </p>
        </div>

        <flux:button variant="primary" wire:click="create">
            Tambah Laboratorium
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
                {{ $laboratoriumId ? 'Edit Laboratorium' : 'Tambah Laboratorium' }}
            </h3>

            <form wire:submit="save" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Kode Laboratorium</flux:label>
                        <flux:input wire:model="kode_lab" placeholder="Contoh: LAB-001" />
                        <flux:error name="kode_lab" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Nama Laboratorium</flux:label>
                        <flux:input wire:model="nama" placeholder="Contoh: Lab Komputer 1" />
                        <flux:error name="nama" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Lokasi</flux:label>
                        <flux:input wire:model="lokasi" placeholder="Contoh: Gedung ICT Lantai 2" />
                        <flux:error name="lokasi" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Kapasitas (orang)</flux:label>
                        <flux:input
                            type="number"
                            min="1"
                            wire:model="kapasitas"
                            placeholder="Contoh: 30"
                        />
                        <flux:error name="kapasitas" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Deskripsi</flux:label>
                    <flux:textarea
                        wire:model="deskripsi"
                        rows="3"
                        placeholder="Keterangan tambahan laboratorium..."
                    />
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
            placeholder="Cari kode, nama, atau lokasi laboratorium..."
        />
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 text-left dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 font-medium">Kode</th>
                        <th class="px-4 py-3 font-medium">Nama Laboratorium</th>
                        <th class="px-4 py-3 font-medium">Lokasi</th>
                        <th class="px-4 py-3 text-center font-medium">Kapasitas</th>
                        <th class="px-4 py-3 text-center font-medium">Jumlah Jadwal</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($this->laboratorium as $lab)
                        <tr wire:key="laboratorium-{{ $lab->id }}">
                            <td class="px-4 py-3 font-medium">{{ $lab->kode_lab }}</td>
                            <td class="px-4 py-3">{{ $lab->nama }}</td>
                            <td class="px-4 py-3">{{ $lab->lokasi ?: '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                {{ $lab->kapasitas !== null ? $lab->kapasitas . ' orang' : '-' }}
                            </td>
                            <td class="px-4 py-3 text-center">{{ $lab->jadwal_count }}</td>
                            <td class="px-4 py-3">
                                @if ($lab->is_active)
                                    <flux:badge color="green">Aktif</flux:badge>
                                @else
                                    <flux:badge color="zinc">Nonaktif</flux:badge>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        wire:click="edit({{ $lab->id }})"
                                    >
                                        Edit
                                    </flux:button>

                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        wire:click="toggleStatus({{ $lab->id }})"
                                        wire:confirm="Yakin ingin mengubah status laboratorium ini?"
                                    >
                                        {{ $lab->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-zinc-500">
                                Data laboratorium tidak ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">
            {{ $this->laboratorium->links() }}
        </div>
    </div>
</div>
