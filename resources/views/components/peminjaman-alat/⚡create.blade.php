<?php

use App\Models\Alat;
use App\Models\PeminjamanAlat;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

new class extends Component
{
    public array $items = [
        ['alat_id' => '', 'jumlah' => 1],
    ];

    public string $tujuan = '';
    public string $tanggal_peminjaman = '';
    public string $tanggal_pengembalian_rencana = '';

    public function getAlatsProperty()
    {
        return Alat::query()
            ->where('is_active', true)
            ->whereHas('unitAlat', function ($query) {
                $query->where('status', 'tersedia')
                    ->where('kondisi', 'baik');
            })
            ->withCount([
                'unitAlat as unit_tersedia_count' => function ($query) {
                    $query->where('status', 'tersedia')
                        ->where('kondisi', 'baik');
                },
            ])
            ->orderBy('nama')
            ->get();
    }

    public function tambahItem(): void
    {
        $this->items[] = [
            'alat_id' => '',
            'jumlah' => 1,
        ];
    }

    public function hapusItem(int $index): void
    {
        if (count($this->items) <= 1) {
            return;
        }

        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function simpan(): void
    {
        $validated = $this->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.alat_id' => [
                'required',
                'integer',
                'distinct',
                'exists:alat,id',
            ],
            'items.*.jumlah' => [
                'required',
                'integer',
                'min:1',
            ],
            'tujuan' => ['required', 'string', 'min:5', 'max:2000'],
            'tanggal_peminjaman' => [
                'required',
                'date',
                'after_or_equal:now',
            ],
            'tanggal_pengembalian_rencana' => [
                'required',
                'date',
                'after:tanggal_peminjaman',
            ],
        ], [
            'items.*.alat_id.required' => 'Silakan pilih alat.',
            'items.*.alat_id.distinct' => 'Jenis alat tidak boleh dipilih lebih dari sekali.',
            'items.*.jumlah.required' => 'Jumlah wajib diisi.',
            'items.*.jumlah.min' => 'Jumlah minimal 1.',
            'tujuan.required' => 'Tujuan peminjaman wajib diisi.',
            'tujuan.min' => 'Tujuan peminjaman minimal 5 karakter.',
            'tanggal_peminjaman.after_or_equal' => 'Tanggal peminjaman tidak boleh di masa lalu.',
            'tanggal_pengembalian_rencana.after' => 'Waktu pengembalian harus setelah waktu peminjaman.',
        ]);

        DB::transaction(function () use ($validated) {
            $alatIds = collect($validated['items'])
                ->pluck('alat_id')
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values();

            $alatTersedia = Alat::query()
                ->whereIn('id', $alatIds)
                ->where('is_active', true)
                ->withCount([
                    'unitAlat as unit_tersedia_count' => function ($query) {
                        $query->where('status', 'tersedia')
                            ->where('kondisi', 'baik');
                    },
                ])
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($validated['items'] as $item) {
                $alat = $alatTersedia->get((int) $item['alat_id']);

                if (! $alat || $alat->unit_tersedia_count < (int) $item['jumlah']) {
                    throw ValidationException::withMessages([
                        'items' => 'Jumlah alat tersedia tidak mencukupi. Silakan periksa kembali permintaan.',
                    ]);
                }
            }

            $peminjaman = PeminjamanAlat::create([
                'user_id' => auth()->id(),
                'status' => 'menunggu',
                'tujuan' => $validated['tujuan'],
                'tanggal_peminjaman' => $validated['tanggal_peminjaman'],
                'tanggal_pengembalian_rencana' => $validated['tanggal_pengembalian_rencana'],
            ]);

            foreach ($validated['items'] as $item) {
                $peminjaman->detail()->create([
                    'alat_id' => $item['alat_id'],
                    'jumlah' => $item['jumlah'],
                ]);
            }
        });

        $this->reset([
            'tujuan',
            'tanggal_peminjaman',
            'tanggal_pengembalian_rencana',
        ]);

        $this->items = [
            ['alat_id' => '', 'jumlah' => 1],
        ];

        session()->flash('success', 'Pengajuan peminjaman alat berhasil dikirim dan menunggu persetujuan admin.');
    }
};
?>

<div>
    <div class="mx-auto max-w-4xl space-y-6">
        <div>
            <flux:heading size="xl">Pengajuan Peminjaman Alat</flux:heading>
            <flux:text class="mt-1">
                Isi formulir berikut untuk mengajukan peminjaman alat laboratorium.
            </flux:text>
        </div>

        @if (session('success'))
            <flux:callout variant="success">
                {{ session('success') }}
            </flux:callout>
        @endif

        <form wire:submit="simpan" class="space-y-6">
            <flux:card class="space-y-4">
                <flux:heading size="lg">Daftar Alat</flux:heading>

                @foreach ($items as $index => $item)
                    <div wire:key="item-alat-{{ $index }}" class="grid gap-4 rounded-xl border p-4 sm:grid-cols-12">
                        <div class="sm:col-span-7">
                            <flux:field>
                                <flux:label>Jenis Alat</flux:label>
                                <flux:select wire:model="items.{{ $index }}.alat_id">
                                    <flux:select.option value="">Pilih alat</flux:select.option>
                                    @foreach ($this->alats as $alat)
                                        <flux:select.option value="{{ $alat->id }}">
                                            {{ $alat->nama }} (Tersedia: {{ $alat->unit_tersedia_count }})
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="items.{{ $index }}.alat_id" />
                            </flux:field>
                        </div>

                        <div class="sm:col-span-3">
                            <flux:field>
                                <flux:label>Jumlah</flux:label>
                                <flux:input
                                    type="number"
                                    min="1"
                                    wire:model="items.{{ $index }}.jumlah"
                                />
                                <flux:error name="items.{{ $index }}.jumlah" />
                            </flux:field>
                        </div>

                        <div class="flex items-end sm:col-span-2">
                            <flux:button
                                type="button"
                                variant="danger"
                                wire:click="hapusItem({{ $index }})"
                                :disabled="count($items) <= 1"
                            >
                                Hapus
                            </flux:button>
                        </div>
                    </div>
                @endforeach

                <flux:error name="items" />

                <flux:button
                    type="button"
                    variant="outline"
                    wire:click="tambahItem"
                >
                    Tambah Alat
                </flux:button>
            </flux:card>

            <flux:card class="space-y-4">
                <flux:heading size="lg">Informasi Peminjaman</flux:heading>

                <flux:field>
                    <flux:label>Tujuan Peminjaman</flux:label>
                    <flux:textarea
                        wire:model="tujuan"
                        rows="3"
                        placeholder="Contoh: Praktikum jaringan komputer..."
                    />
                    <flux:error name="tujuan" />
                </flux:field>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Tanggal dan Waktu Peminjaman</flux:label>
                        <flux:input
                            type="datetime-local"
                            wire:model="tanggal_peminjaman"
                        />
                        <flux:error name="tanggal_peminjaman" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Rencana Pengembalian</flux:label>
                        <flux:input
                            type="datetime-local"
                            wire:model="tanggal_pengembalian_rencana"
                        />
                        <flux:error name="tanggal_pengembalian_rencana" />
                    </flux:field>
                </div>
            </flux:card>

            <div class="flex justify-end">
                <flux:button
                    type="submit"
                    variant="primary"
                    wire:loading.attr="disabled"
                >
                    Kirim Pengajuan
                </flux:button>
            </div>
        </form>
    </div>
</div>
