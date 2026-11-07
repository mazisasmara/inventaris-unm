<?php

use App\Models\Bahan;
use App\Models\PengambilanBahan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $tujuan = '';

    public array $items = [
        ['bahan_id' => '', 'jumlah' => 1],
    ];

    #[Computed]
    public function bahans()
    {
        return Bahan::query()
            ->where('is_active', true)
            ->where('stok', '>', 0)
            ->orderBy('nama')
            ->get();
    }

    public function tambahBahan(): void
    {
        $this->items[] = [
            'bahan_id' => '',
            'jumlah' => 1,
        ];
    }

    public function hapusBahan(int $index): void
    {
        if (count($this->items) <= 1) {
            return;
        }

        unset($this->items[$index]);

        $this->items = array_values($this->items);

        $this->resetValidation();
    }

    public function ajukan(): void
    {
        $validated = $this->validate([
            'tujuan' => ['required', 'string', 'min:5'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.bahan_id' => [
                'required',
                'distinct',
                Rule::exists('bahan', 'id')
                    ->where('is_active', true),
            ],
            'items.*.jumlah' => [
                'required',
                'integer',
                'min:1',
            ],
        ], [
            'tujuan.required' => 'Tujuan pengambilan wajib diisi.',
            'tujuan.min' => 'Tujuan minimal 5 karakter.',
            'items.*.bahan_id.required' => 'Pilih bahan terlebih dahulu.',
            'items.*.bahan_id.distinct' => 'Bahan yang sama tidak boleh dipilih dua kali.',
            'items.*.bahan_id.exists' => 'Bahan tidak tersedia atau sudah tidak aktif.',
            'items.*.jumlah.required' => 'Jumlah wajib diisi.',
            'items.*.jumlah.integer' => 'Jumlah harus berupa angka bulat.',
            'items.*.jumlah.min' => 'Jumlah minimal 1.',
        ]);

        // Periksa stok terbaru sebelum menyimpan pengajuan.
        foreach ($validated['items'] as $index => $item) {
            $bahan = Bahan::find($item['bahan_id']);

            if (! $bahan || $item['jumlah'] > $bahan->stok) {
                $this->addError(
                    "items.$index.jumlah",
                    'Stok tersedia hanya ' . ($bahan?->stok ?? 0) . ' ' . ($bahan?->satuan ?? '') . '.'
                );

                return;
            }
        }

        DB::transaction(function () use ($validated) {
            $pengambilan = PengambilanBahan::create([
                'user_id' => auth()->id(),
                'tujuan' => $validated['tujuan'],
                'status' => 'menunggu',
            ]);

            foreach ($validated['items'] as $item) {
                $pengambilan->detail()->create([
                    'bahan_id' => $item['bahan_id'],
                    'jumlah' => $item['jumlah'],
                ]);
            }
        });

        $this->reset([
            'tujuan',
            'items',
        ]);

        $this->items = [
            ['bahan_id' => '', 'jumlah' => 1],
        ];

        session()->flash(
            'success',
            'Pengajuan pengambilan bahan berhasil dikirim dan sedang menunggu persetujuan admin.'
        );
    }
};
?>

<div class="mx-auto max-w-5xl space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">
            Pengambilan Bahan
        </h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            Ajukan pengambilan bahan yang tersedia di laboratorium.
        </p>
    </div>

    @if (session()->has('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700 dark:border-green-900 dark:bg-green-950 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    @if ($this->bahans->isEmpty())
        <div class="rounded-xl border border-zinc-200 p-6 text-center dark:border-zinc-700">
            <p class="font-medium text-zinc-800 dark:text-white">
                Belum ada bahan yang tersedia.
            </p>
            <p class="mt-1 text-sm text-zinc-500">
                Silakan hubungi admin laboratorium.
            </p>
        </div>
    @else
        <form wire:submit="ajukan" class="space-y-6">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="font-semibold text-zinc-900 dark:text-white">
                    Informasi Pengajuan
                </h2>

                <div class="mt-4">
                    <label for="tujuan" class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Tujuan Pengambilan
                    </label>

                    <textarea
                        id="tujuan"
                        wire:model="tujuan"
                        rows="3"
                        placeholder="Contoh: Praktikum jaringan komputer"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                    ></textarea>

                    @error('tujuan')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="font-semibold text-zinc-900 dark:text-white">
                            Daftar Bahan
                        </h2>
                        <p class="mt-1 text-sm text-zinc-500">
                            Pilih bahan dan masukkan jumlah yang dibutuhkan.
                        </p>
                    </div>

                    <button
                        type="button"
                        wire:click="tambahBahan"
                        class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                    >
                        + Tambah Bahan
                    </button>
                </div>

                <div class="mt-5 space-y-4">
                    @foreach ($items as $index => $item)
                        <div wire:key="bahan-item-{{ $index }}" class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    Bahan {{ $index + 1 }}
                                </p>

                                @if (count($items) > 1)
                                    <button
                                        type="button"
                                        wire:click="hapusBahan({{ $index }})"
                                        class="text-sm text-red-600 hover:text-red-700"
                                    >
                                        Hapus
                                    </button>
                                @endif
                            </div>

                            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm text-zinc-600 dark:text-zinc-400">
                                        Pilih Bahan
                                    </label>

                                    <select
                                        wire:model="items.{{ $index }}.bahan_id"
                                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-blue-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                                    >
                                        <option value="">Pilih bahan</option>

                                        @foreach ($this->bahans as $bahan)
                                            <option value="{{ $bahan->id }}">
                                                {{ $bahan->nama }} — Stok: {{ $bahan->stok }} {{ $bahan->satuan }}
                                            </option>
                                        @endforeach
                                    </select>

                                    @error("items.$index.bahan_id")
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm text-zinc-600 dark:text-zinc-400">
                                        Jumlah
                                    </label>

                                    <input
                                        type="number"
                                        min="1"
                                        wire:model="items.{{ $index }}.jumlah"
                                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-blue-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                                    >

                                    @error("items.$index.jumlah")
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="ajukan">
                        Kirim Pengajuan
                    </span>
                    <span wire:loading wire:target="ajukan">
                        Mengirim...
                    </span>
                </button>
            </div>
        </form>
    @endif
</div>
