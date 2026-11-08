<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Bahan;
use App\Models\Laboratorium;
use App\Models\PeminjamanLab;
use Illuminate\Http\Request;

class InventarisController extends Controller
{
    public function index(Request $request)
    {
        $kataKunci = trim($request->query('q', ''));
        $kategori = $request->query('kategori', 'semua');

        $cari = function ($query, bool $cariKategori = true) use ($kataKunci) {
            if ($kataKunci !== '') {
                $query->where(function ($subQuery) use ($kataKunci, $cariKategori) {
                    $subQuery->where('nama', 'like', "%{$kataKunci}%");

                    if ($cariKategori) {
                        $subQuery->orWhere('kategori', 'like', "%{$kataKunci}%");
                    }
                });
            }

            return $query;
        };

        $alats = collect();
        $bahans = collect();
        $laboratoriums = collect();

        if (in_array($kategori, ['semua', 'alat'], true)) {
            $alats = $cari(
                Alat::query()->where('is_active', true)
            )
                ->withCount([
                    'unitAlat as unit_total_count',
                    'unitAlat as unit_tersedia_count' => function ($query) {
                        $query->where('status', 'tersedia')
                            ->where('kondisi', 'baik');
                    },
                ])
                ->orderBy('nama')
                ->get();
        }

        if (in_array($kategori, ['semua', 'bahan'], true)) {
            $bahans = $cari(
                Bahan::query()->where('is_active', true)
            )
                ->orderBy('nama')
                ->get();
        }

        if (in_array($kategori, ['semua', 'laboratorium'], true)) {
            $laboratoriums = $cari(
                Laboratorium::query()->where('is_active', true),
                false
            )            
                ->with([
                    'jadwal' => function ($query) {
                        $query->where('is_active', true)
                            ->orderBy('hari')
                            ->orderBy('jam_mulai');
                    },
                ])
                ->orderBy('nama')
                ->get();
        }

        return view('inventaris.index', compact(
            'alats',
            'bahans',
            'laboratoriums',
            'kataKunci',
            'kategori'
        ));
    }

    public function showAlat(Alat $alat)
    {
        abort_unless($alat->is_active, 404);

        $alat->loadCount([
            'unitAlat as unit_total_count',
            'unitAlat as unit_tersedia_count' => function ($query) {
                $query->where('status', 'tersedia')
                    ->where('kondisi', 'baik');
            },
        ]);

        return view('inventaris.show', [
            'jenis' => 'alat',
            'item' => $alat,
            'jadwal' => collect(),
        ]);
    }

    public function showBahan(Bahan $bahan)
    {
        abort_unless($bahan->is_active, 404);

        return view('inventaris.show', [
            'jenis' => 'bahan',
            'item' => $bahan,
            'jadwal' => collect(),
        ]);
    }

    public function showLaboratorium(Laboratorium $laboratorium)
    {
        abort_unless($laboratorium->is_active, 404);

        $laboratorium->load([
            'jadwal' => function ($query) {
                $query->where('is_active', true)
                    ->orderBy('hari')
                    ->orderBy('jam_mulai');
            },
        ]);

        return view('inventaris.show', [
            'jenis' => 'laboratorium',
            'item' => $laboratorium,
            'jadwal' => $laboratorium->jadwal,
        ]);
    }

    public function ajukanAlat(Alat $alat)
    {
        abort_unless($alat->is_active, 404);

        return $this->arahkanKeForm(
            'peminjaman-alat',
            $alat->id,
            'inventaris.alat.show'
        );
    }

    public function ajukanBahan(Bahan $bahan)
    {
        abort_unless($bahan->is_active, 404);

        return $this->arahkanKeForm(
            'pengambilan-bahan',
            $bahan->id,
            'inventaris.bahan.show'
        );
    }

    public function ajukanLaboratorium(Laboratorium $laboratorium)
    {
        abort_unless($laboratorium->is_active, 404);

        return $this->arahkanKeForm(
            'peminjaman-lab',
            $laboratorium->id,
            'inventaris.laboratorium.show'
        );
    }

    private function arahkanKeForm(
        string $jenis,
        int $id,
        string $routeDetail
    ) {
        $user = auth()->user();

        if (! in_array($user->role, ['mahasiswa', 'dosen'], true)) {
            return redirect()
                ->route($routeDetail, $id)
                ->with('info', 'Akun admin tidak dapat mengajukan peminjaman melalui katalog publik.');
        }

        $routeName = $user->role . '.' . $jenis . '.create';

        $parameter = match ($jenis) {
            'peminjaman-alat' => ['alat' => $id],
            'pengambilan-bahan' => ['bahan' => $id],
            'peminjaman-lab' => ['laboratorium' => $id],
        };

        return redirect()->route($routeName, $parameter);
    }
}