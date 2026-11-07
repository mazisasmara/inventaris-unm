
<?php

use App\Models\JadwalLaboratorium;
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
    public ?int $jadwalId = null;

    public string $laboratorium_id = '';
    public string $hari = '';
    public string $jam_mulai = '';
    public string $jam_selesai = '';

    #[Computed]
    public function jadwals()
    {
        return JadwalLaboratorium::query()
            ->with('laboratorium')
            ->when($this->search, function ($query) {
                $query->whereHas('laboratorium', function ($q) {
                    $q->where('nama', 'like', '%' . $this->search . '%')
                        ->orWhere('kode_lab', 'like', '%' . $this->search . '%');
                })->orWhere('hari', 'like', '%' . $this->search . '%');
            })
            ->orderByRaw("
                CASE hari
                    WHEN 'Senin' THEN 1
                    WHEN 'Selasa' THEN 2
                    WHEN 'Rabu' THEN 3
                    WHEN 'Kamis' THEN 4
                    WHEN 'Jumat' THEN 5
                    WHEN 'Sabtu' THEN 6
                    WHEN 'Minggu' THEN 7
                    ELSE 8
                END
            ")
            ->orderBy('jam_mulai')
            ->paginate(10);
    }

    #[Computed]
    public function laboratoriumList()
    {
        return Laboratorium::query()
            ->where('is_active', true)
            ->orderBy('nama')
            ->get();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    protected function rules(): array
    {
        return [
            'laboratorium_id' => [
                'required',
                'integer',
                Rule::exists('laboratorium', 'id')->where('is_active', true),
            ],
            'hari' => [
                'required',
                Rule::in([
                    'Senin', 'Selasa', 'Rabu', 'Kamis',
                    'Jumat', 'Sabtu', 'Minggu',
                ]),
            ],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => [
                'required',
                'date_format:H:i',
                'after:jam_mulai',
            ],
        ];
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $jadwal = JadwalLaboratorium::findOrFail($id);

        $this->jadwalId = $jadwal->id;
        $this->laboratorium_id = (string) $jadwal->laboratorium_id;
        $this->hari = $jadwal->hari;
        $this->jam_mulai = substr($jadwal->jam_mulai, 0, 5);
        $this->jam_selesai = substr($jadwal->jam_selesai, 0, 5);

        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        $conflict = JadwalLaboratorium::query()
            ->where('laboratorium_id', $validated['laboratorium_id'])
            ->where('hari', $validated['hari'])
            ->where('is_active', true)
            ->where('jam_mulai', '<', $validated['jam_selesai'])
            ->where('jam_selesai', '>', $validated['jam_mulai'])
            ->when($this->jadwalId, function ($query) {
                $query->where('id', '!=', $this->jadwalId);
            })
            ->exists();

        if ($conflict) {
            $this->addError(
                'jam_mulai',
                'Jadwal bertabrakan dengan jadwal aktif lain pada laboratorium dan hari yang sama.'
            );

            return;
        }

        if ($this->jadwalId) {
            $jadwal = JadwalLaboratorium::findOrFail($this->jadwalId);
            $jadwal->update($validated);

            session()->flash('success', 'Jadwal laboratorium berhasil diperbarui.');
        } else {
            JadwalLaboratorium::create($validated);

            session()->flash('success', 'Jadwal laboratorium berhasil ditambahkan.');
        }

        $this->cancelForm();
    }

    public function toggleStatus(int $id): void
    {
        $jadwal = JadwalLaboratorium::findOrFail($id);

        if (! $jadwal->is_active) {
            $conflict = JadwalLaboratorium::query()
                ->where('laboratorium_id', $jadwal->laboratorium_id)
                ->where('hari', $jadwal->hari)
                ->where('is_active', true)
                ->where('id', '!=', $jadwal->id)
                ->where('jam_mulai', '<', $jadwal->jam_selesai)
                ->where('jam_selesai', '>', $jadwal->jam_mulai)
                ->exists();

            if ($conflict) {
                session()->flash(
                    'error',
                    'Jadwal tidak dapat diaktifkan karena bertabrakan dengan jadwal aktif lainnya.'
                );

                return;
            }
        }

        $jadwal->update([
            'is_active' => ! $jadwal->is_active,
        ]);

        session()->flash(
            'success',
            $jadwal->is_active
                ? 'Jadwal berhasil diaktifkan.'
                : 'Jadwal berhasil dinonaktifkan.'
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
            'jadwalId',
            'laboratorium_id',
            'hari',
            'jam_mulai',
            'jam_selesai',
        ]);

        $this->resetValidation();
    }
};
?>

<div>
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">
                Jadwal Laboratorium
            </h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Atur jadwal operasional setiap laboratorium.
            </p>
        </div>

        <flux:button variant="primary" wire:click="create">
            Tambah Jadwal
        </flux:button>
    </div>

    @if (session()->has('success'))
        <div class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-300">
            {{ session('error') }}
        </div>
    @endif

    @if ($showForm)
        <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <h3 class="mb-4 font-semibold text-zinc-900 dark:text-white">
                {{ $jadwalId ? 'Edit Jadwal' : 'Tambah Jadwal' }}
            </h3>

            <form wire:submit="save" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Laboratorium</flux:label>
                        <flux:select wire:model="laboratorium_id" placeholder="Pilih laboratorium">
                            @foreach ($this->laboratoriumList as $lab)
                                <flux:select.option value="{{ $lab->id }}">
                                    {{ $lab->kode_lab }} - {{ $lab->nama }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="laboratorium_id" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Hari</flux:label>
                        <flux:select wire:model="hari" placeholder="Pilih hari">
                            @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $day)
                                <flux:select.option value="{{ $day }}">
                                    {{ $day }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="hari" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Jam Mulai</flux:label>
                        <flux:input type="time" wire:model="jam_mulai" />
                        <flux:error name="jam_mulai" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Jam Selesai</flux:label>
                        <flux:input type="time" wire:model="jam_selesai" />
                        <flux:error name="jam_selesai" />
                    </flux:field>
                </div>

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
            placeholder="Cari nama lab, kode, atau hari..."
        />
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 text-left dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 font-medium">Laboratorium</th>
                        <th class="px-4 py-3 font-medium">Hari</th>
                        <th class="px-4 py-3 font-medium">Jam Mulai</th>
                        <th class="px-4 py-3 font-medium">Jam Selesai</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($this->jadwals as $jadwal)
                        <tr wire:key="jadwal-{{ $jadwal->id }}">
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $jadwal->laboratorium->nama }}</div>
                                <div class="text-xs text-zinc-500">{{ $jadwal->laboratorium->kode_lab }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $jadwal->hari }}</td>
                            <td class="px-4 py-3">{{ substr($jadwal->jam_mulai, 0, 5) }}</td>
                            <td class="px-4 py-3">{{ substr($jadwal->jam_selesai, 0, 5) }}</td>
                            <td class="px-4 py-3">
                                @if ($jadwal->is_active)
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
                                        wire:click="edit({{ $jadwal->id }})"
                                    >
                                        Edit
                                    </flux:button>

                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        wire:click="toggleStatus({{ $jadwal->id }})"
                                        wire:confirm="Yakin ingin mengubah status jadwal ini?"
                                    >
                                        {{ $jadwal->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-zinc-500">
                                Jadwal laboratorium tidak ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">
            {{ $this->jadwals->links() }}
        </div>
    </div>
</div>
