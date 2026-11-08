<?php

use App\Models\Laboratorium;
use App\Models\PeminjamanLab;
use App\Models\JadwalLaboratorium;
use App\Models\DokumentasiPeminjamanLab;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

new class extends Component
{
    use WithFileUploads;

    public $laboratorium_id = '';
    public string $tujuan = '';
    public string $waktu_mulai = '';
    public string $waktu_selesai = '';
    public array $dokumentasi = [];

    public function updatedLaboratoriumId(): void
    {
        unset($this->jadwalLab);
    }

    #[Computed]
    public function laboratorium()
    {
        return Laboratorium::where('is_active', true)
            ->orderBy('nama')
            ->get();
    }

    #[Computed]
    public function jadwalLab()
    {
        if (! $this->laboratorium_id) {
            return collect();
        }

        return JadwalLaboratorium::where(
            'laboratorium_id',
            $this->laboratorium_id
        )
            ->where('is_active', true)
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->get();
    }

    protected function rules(): array
    {
        return [
            'laboratorium_id' => [
                'required',
                'integer',
                'exists:laboratorium,id',
            ],
            'tujuan' => [
                'required',
                'string',
                'min:5',
                'max:2000',
            ],
            'waktu_mulai' => [
                'required',
                'date',
            ],
            'waktu_selesai' => [
                'required',
                'date',
                'after:waktu_mulai',
            ],
            'dokumentasi' => [
                'nullable',
                'array',
                'max:5',
            ],
            'dokumentasi.*' => [
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ];
    }

    public function submit(): void
    {
        $validated = $this->validate();

        $start = \Carbon\Carbon::parse($validated['waktu_mulai']);
        $end = \Carbon\Carbon::parse($validated['waktu_selesai']);

        if ($start->lt(now())) {
            throw ValidationException::withMessages([
                'waktu_mulai' => 'Waktu mulai tidak boleh berada di masa lalu.',
            ]);
        }

        $lab = Laboratorium::where('is_active', true)
            ->findOrFail($validated['laboratorium_id']);

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

        $jadwal = JadwalLaboratorium::where(
            'laboratorium_id',
            $lab->id
        )
            ->where('hari', $hari)
            ->where('is_active', true)
            ->where('jam_mulai', '<=', $start->format('H:i:s'))
            ->where('jam_selesai', '>=', $end->format('H:i:s'))
            ->exists();

        if (! $jadwal) {
            throw ValidationException::withMessages([
                'waktu_mulai' => 'Waktu peminjaman berada di luar jadwal operasional laboratorium.',
            ]);
        }

        $conflict = PeminjamanLab::where(
            'laboratorium_id',
            $lab->id
        )
            ->whereIn('status', ['disetujui', 'berlangsung'])
            ->where('waktu_mulai', '<', $end)
            ->where('waktu_selesai', '>', $start)
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'waktu_mulai' => 'Laboratorium sudah memiliki peminjaman pada rentang waktu tersebut.',
            ]);
        }

        DB::transaction(function () use ($validated, $start, $end, $lab) {
            $request = PeminjamanLab::create([
                'user_id' => Auth::id(),
                'laboratorium_id' => $lab->id,
                'tujuan' => $validated['tujuan'],
                'status' => 'menunggu',
                'waktu_mulai' => $start,
                'waktu_selesai' => $end,
            ]);

            foreach ($this->dokumentasi as $file) {
                $path = $file->store(
                    'dokumentasi-peminjaman-lab',
                    'public'
                );

                DokumentasiPeminjamanLab::create([
                    'peminjaman_lab_id' => $request->id,
                    'uploaded_by' => Auth::id(),
                    'file_path' => $path,
                    'jenis' => 'foto',
                ]);
            }
        });

        $this->reset([
            'laboratorium_id',
            'tujuan',
            'waktu_mulai',
            'waktu_selesai',
            'dokumentasi',
        ]);

        session()->flash(
            'success',
            'Pengajuan peminjaman laboratorium berhasil dikirim dan menunggu persetujuan admin.'
        );

        unset($this->jadwalLab);
    }

    #[Url(as: 'laboratorium')]
    public ?int $laboratoriumId = null;

    public function mount(): void
    {
        if ($this->laboratoriumId) {
            $this->laboratorium_id = (string) $this->laboratoriumId;
        }
    }

};
?>

<div class="mx-auto max-w-4xl space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
            Peminjaman Laboratorium
        </h1>
        <p class="mt-1 text-sm text-zinc-500">
            Ajukan penggunaan laboratorium sesuai jadwal operasional.
        </p>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit="submit" class="space-y-6">

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">

            <div class="space-y-5">

                {{-- Laboratorium --}}
                <div>
                    <label class="mb-2 block text-sm font-medium">
                        Pilih Laboratorium
                    </label>

                    <select
                        wire:model.live="laboratorium_id"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800"
                    >
                        <option value="">Pilih laboratorium</option>

                        @foreach ($this->laboratorium as $lab)
                            <option value="{{ $lab->id }}">
                                {{ $lab->nama }}
                                @if ($lab->lokasi)
                                    — {{ $lab->lokasi }}
                                @endif
                            </option>
                        @endforeach
                    </select>

                    @error('laboratorium_id')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Jadwal Operasional --}}
                @if ($laboratorium_id)
                    <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800">
                        <h3 class="mb-3 text-sm font-semibold">
                            Jadwal Operasional Laboratorium
                        </h3>

                        @forelse ($this->jadwalLab as $jadwal)
                            <div class="flex justify-between py-1 text-sm text-zinc-600 dark:text-zinc-300">
                                <span>{{ ucfirst($jadwal->hari) }}</span>
                                <span>
                                    {{ substr($jadwal->jam_mulai, 0, 5) }}
                                    –
                                    {{ substr($jadwal->jam_selesai, 0, 5) }}
                                </span>
                            </div>
                        @empty
                            <p class="text-sm text-red-500">
                                Belum ada jadwal operasional aktif untuk laboratorium ini.
                            </p>
                        @endforelse
                    </div>
                @endif

                {{-- Tujuan --}}
                <div>
                    <label class="mb-2 block text-sm font-medium">
                        Tujuan Penggunaan
                    </label>

                    <textarea
                        wire:model="tujuan"
                        rows="4"
                        placeholder="Jelaskan tujuan penggunaan laboratorium..."
                        class="w-full rounded-lg border border-zinc-300 bg-white p-3 text-sm dark:border-zinc-700 dark:bg-zinc-800"
                    ></textarea>

                    @error('tujuan')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Waktu --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium">
                            Waktu Mulai
                        </label>

                        <input
                            type="datetime-local"
                            wire:model="waktu_mulai"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800"
                        >

                        @error('waktu_mulai')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">
                            Waktu Selesai
                        </label>

                        <input
                            type="datetime-local"
                            wire:model="waktu_selesai"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800"
                        >

                        @error('waktu_selesai')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Dokumentasi --}}
                <div>
                    <label class="mb-2 block text-sm font-medium">
                        Dokumentasi Pendukung (Opsional)
                    </label>

                    <input
                        type="file"
                        wire:model="dokumentasi"
                        multiple
                        accept=".jpg,.jpeg,.png,.webp"
                        class="w-full rounded-lg border border-zinc-300 bg-white p-3 text-sm dark:border-zinc-700 dark:bg-zinc-800"
                    >

                    <p class="mt-1 text-xs text-zinc-500">
                        Maksimal 5 foto, masing-masing maksimal 5 MB. Format JPG, PNG, atau WebP.
                    </p>

                    @error('dokumentasi')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror

                    @error('dokumentasi.*')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror

                    @if ($dokumentasi)
                        <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
                            @foreach ($dokumentasi as $file)
                                <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                                    <img
                                        src="{{ $file->temporaryUrl() }}"
                                        class="h-32 w-full object-cover"
                                        alt="Preview dokumentasi"
                                    >
                                    <p class="truncate p-2 text-xs text-zinc-500">
                                        {{ $file->getClientOriginalName() }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div wire:loading wire:target="dokumentasi" class="mt-2 text-xs text-blue-600">
                        Mengunggah foto...
                    </div>
                </div>

            </div>
        </div>

        <div class="flex justify-end">
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="submit,dokumentasi"
                class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="submit">
                    Kirim Pengajuan
                </span>

                <span wire:loading wire:target="submit">
                    Memproses...
                </span>
            </button>
        </div>

    </form>
</div>
