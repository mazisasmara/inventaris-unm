<?php

namespace Database\Seeders;

use App\Models\Alat;
use App\Models\UnitAlat;
use Illuminate\Database\Seeder;

class AlatSeeder extends Seeder
{
    public function run(): void
    {
        $daftarAlat = [
            [
                'kode_alat' => 'ALT-001',
                'nama' => 'Proyektor',
                'jumlah' => 3,
                'rusak' => 0,
                'prefix' => 'PRO',
            ],
            [
                'kode_alat' => 'ALT-002',
                'nama' => 'Kabel HDMI',
                'jumlah' => 5,
                'rusak' => 0,
                'prefix' => 'HDMI',
            ],
            [
                'kode_alat' => 'ALT-003',
                'nama' => 'PC',
                'jumlah' => 10,
                'rusak' => 3,
                'prefix' => 'PC',
            ],
            [
                'kode_alat' => 'ALT-004',
                'nama' => 'Monitor',
                'jumlah' => 10,
                'rusak' => 0,
                'prefix' => 'MON',
            ],
            [
                'kode_alat' => 'ALT-005',
                'nama' => 'Keyboard',
                'jumlah' => 10,
                'rusak' => 0,
                'prefix' => 'KEY',
            ],
            [
                'kode_alat' => 'ALT-006',
                'nama' => 'Mouse',
                'jumlah' => 10,
                'rusak' => 0,
                'prefix' => 'MOU',
            ],
        ];

        foreach ($daftarAlat as $data) {
            $alat = Alat::updateOrCreate(
                ['kode_alat' => $data['kode_alat']],
                [
                    'nama' => $data['nama'],
                    'is_active' => true,
                ]
            );

            for ($i = 1; $i <= $data['jumlah']; $i++) {
                $nomor = str_pad($i, 3, '0', STR_PAD_LEFT);

                $isRusak = $i <= $data['rusak'];

                UnitAlat::firstOrCreate(
                    [
                        'kode_inventaris' => 'INV-' . $data['prefix'] . '-' . $nomor,
                    ],
                    [
                        'alat_id' => $alat->id,
                        'kondisi' => $isRusak ? 'rusak' : 'baik',
                        'status' => $isRusak ? 'tidak_tersedia' : 'tersedia',
                    ]
                );
            }
        }
    }
}
