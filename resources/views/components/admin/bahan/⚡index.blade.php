<?php

use App\Models\Bahan;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showForm = false;

    public ?int $bahanId = null;

    public string $kode_bahan = '';
    public string $nama = '';
    public string $kategori = '';
    public string $satuan = '';
    public int $stok = 0;
    public int $stok_minimum = 0;
    public string $deskripsi = '';

    #[Computed]
    public function bahans()
    {
        return Bahan::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('kode_bahan', 'like', '%' . $this->search . '%')
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
            'kode_bahan' => [
                'required',
                'string',
                'max:50',
                Rule::unique('bahan', 'kode_bahan')->ignore($this->bahanId),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'kategori' => ['nullable', 'string', 'max:100'],
            'satuan' => ['required', 'string', 'max:50'],
            'stok' => ['required', 'integer', 'min:0'],
            'stok_minimum' => ['required', 'integer', 'min:0'],
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
        $bahan = Bahan::findOrFail($id);

        $this->bahanId = $bahan->id;
        $this->kode_bahan = $bahan->kode_bahan;
        $this->nama = $bahan->nama;
        $this->kategori = $bahan->kategori ?? '';
        $this->satuan = $bahan->satuan;
        $this->stok = $bahan->stok;
        $this->stok_minimum = $bahan->stok_minimum;
        $this->deskripsi = $bahan->deskripsi ?? '';

        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->bahanId) {
            $bahan = Bahan::findOrFail($this->bahanId);
            $bahan->update($validated);

            session()->flash('success', 'Data bahan berhasil diperbarui.');
        } else {
            Bahan::create($validated);

            session()->flash('success', 'Bahan baru berhasil ditambahkan.');
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function toggleStatus(int $id): void
    {
        $bahan = Bahan::findOrFail($id);

        $bahan->update([
            'is_active' => ! $bahan->is_active,
        ]);

        session()->flash(
            'success',
            $bahan->is_active
                ? 'Bahan berhasil diaktifkan.'
                : 'Bahan berhasil dinonaktifkan.'
        );
    }

    public function cancelForm(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->reset([
            'bahanId',
            'kode_bahan',
            'nama',
            'kategori',
            'satuan',
            'stok',
            'stok_minimum',
            'deskripsi',
        ]);

        $this->resetValidation();
    }
};
?>

<div class="space-y-6">

    {{-- JUDUL HALAMAN --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">
                Data Bahan
            </h2>

            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                Kelola persediaan bahan laboratorium.
            </p>
        </div>

        <button
            wire:click="create"
            class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700"
        >
            + Tambah Bahan
        </button>
    </div>

    {{-- NOTIFIKASI --}}
    @if (session()->has('success'))
        <div class="rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-950 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    {{-- FORM TAMBAH / EDIT --}}
    @if ($showForm)
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

            <div class="mb-6 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">
                    {{ $bahanId ? 'Edit Data Bahan' : 'Tambah Bahan Baru' }}
                </h3>

                <button
                    wire:click="cancelForm"
                    class="text-sm text-zinc-500 hover:text-zinc-800 dark:hover:text-white"
                >
                    Batal
                </button>
            </div>

            <form wire:submit="save" class="space-y-5">

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                    {{-- KODE BAHAN --}}
                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Kode Bahan *
                        </label>

                        <input
                            type="text"
                            wire:model="kode_bahan"
                            placeholder="Contoh: BHN-001"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                        >

                        @error('kode_bahan')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- NAMA BAHAN --}}
                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Nama Bahan *
                        </label>

                        <input
                            type="text"
                            wire:model="nama"
                            placeholder="Masukkan nama bahan"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                        >

                        @error('nama')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- KATEGORI --}}
                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Kategori
                        </label>

                        <input
                            type="text"
                            wire:model="kategori"
                            placeholder="Contoh: Jaringan"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                        >

                        @error('kategori')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- SATUAN --}}
                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Satuan *
                        </label>

                        <input
                            type="text"
                            wire:model="satuan"
                            placeholder="Contoh: Meter, Buah, Tube"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                        >

                        @error('satuan')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- STOK --}}
                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Stok *
                        </label>

                        <input
                            type="number"
                            min="0"
                            wire:model="stok"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                        >

                        @error('stok')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- STOK MINIMUM --}}
                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Stok Minimum *
                        </label>

                        <input
                            type="number"
                            min="0"
                            wire:model="stok_minimum"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                        >

                        @error('stok_minimum')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                </div>

                {{-- DESKRIPSI --}}
                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Deskripsi
                    </label>

                    <textarea
                        wire:model="deskripsi"
                        rows="3"
                        placeholder="Keterangan tambahan tentang bahan..."
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                    ></textarea>

                    @error('deskripsi')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        wire:click="cancelForm"
                        class="rounded-lg border border-zinc-300 px-4 py-2 text-sm text-zinc-700 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800"
                    >
                        Batal
                    </button>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="save">
                            {{ $bahanId ? 'Simpan Perubahan' : 'Simpan Bahan' }}
                        </span>

                        <span wire:loading wire:target="save">
                            Menyimpan...
                        </span>
                    </button>
                </div>

            </form>
        </div>
    @endif

    {{-- TABEL DATA BAHAN --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

        <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari kode, nama, atau kategori bahan..."
                class="w-full max-w-md rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm text-zinc-900 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
            >
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">

                <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="px-5 py-4">No.</th>
                        <th class="px-5 py-4">Kode Bahan</th>
                        <th class="px-5 py-4">Nama Bahan</th>
                        <th class="px-5 py-4">Kategori</th>
                        <th class="px-5 py-4">Stok</th>
                        <th class="px-5 py-4">Status Stok</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">

                    @forelse ($this->bahans as $index => $bahan)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/60">

                            <td class="px-5 py-4 text-zinc-500">
                                {{ $this->bahans->firstItem() + $index }}
                            </td>

                            <td class="px-5 py-4 font-medium text-zinc-900 dark:text-white">
                                {{ $bahan->kode_bahan }}
                            </td>

                            <td class="px-5 py-4 text-zinc-700 dark:text-zinc-300">
                                {{ $bahan->nama }}
                                <div class="text-xs text-zinc-400">
                                    {{ $bahan->satuan }}
                                </div>
                            </td>

                            <td class="px-5 py-4 text-zinc-600 dark:text-zinc-400">
                                {{ $bahan->kategori ?: '-' }}
                            </td>

                            <td class="px-5 py-4 font-semibold text-zinc-900 dark:text-white">
                                {{ number_format($bahan->stok) }}
                                <span class="text-xs font-normal text-zinc-500">
                                    {{ $bahan->satuan }}
                                </span>
                            </td>

                            <td class="px-5 py-4">
                                @if ($bahan->stok <= $bahan->stok_minimum)
                                    <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-700 dark:bg-red-950 dark:text-red-300">
                                        Stok Menipis
                                    </span>
                                @else
                                    <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700 dark:bg-green-950 dark:text-green-300">
                                        Aman
                                    </span>
                                @endif
                            </td>

                            <td class="px-5 py-4">
                                @if ($bahan->is_active)
                                    <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700 dark:bg-green-950 dark:text-green-300">
                                        Aktif
                                    </span>
                                @else
                                    <span class="rounded-full bg-zinc-200 px-2.5 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>

                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">

                                    <button
                                        wire:click="edit({{ $bahan->id }})"
                                        class="rounded-md border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700"
                                    >
                                        Edit
                                    </button>

                                    <button
                                        wire:click="toggleStatus({{ $bahan->id }})"
                                        wire:confirm="Yakin ingin {{ $bahan->is_active ? 'menonaktifkan' : 'mengaktifkan' }} bahan ini?"
                                        class="rounded-md border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700"
                                    >
                                        {{ $bahan->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>

                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center text-zinc-500">
                                Tidak ada data bahan yang ditemukan.
                            </td>
                        </tr>
                    @endforelse

                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">
            {{ $this->bahans->links() }}
        </div>

    </div>

</div>