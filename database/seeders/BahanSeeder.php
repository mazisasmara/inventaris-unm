<?php

namespace Database\Seeders;

use App\Models\Bahan;
use Illuminate\Database\Seeder;

class BahanSeeder extends Seeder
{
    public function run(): void
    {
        $daftarBahan = [
            [
                'kode_bahan' => 'BHN-001',
                'nama' => 'Kabel UTP',
                'kategori' => 'Jaringan',
                'satuan' => 'Meter',
                'stok' => 500,
                'stok_minimum' => 50,
                'deskripsi' => 'Kabel UTP untuk kebutuhan jaringan.',
            ],
            [
                'kode_bahan' => 'BHN-002',
                'nama' => 'Konektor RJ45',
                'kategori' => 'Jaringan',
                'satuan' => 'Buah',
                'stok' => 200,
                'stok_minimum' => 20,
                'deskripsi' => 'Konektor RJ45 untuk kabel jaringan.',
            ],
            [
                'kode_bahan' => 'BHN-003',
                'nama' => 'Thermal Paste',
                'kategori' => 'Komputer',
                'satuan' => 'Tube',
                'stok' => 10,
                'stok_minimum' => 2,
                'deskripsi' => 'Pasta termal untuk kebutuhan perawatan PC.',
            ],
        ];

        foreach ($daftarBahan as $data) {
            Bahan::updateOrCreate(
                ['kode_bahan' => $data['kode_bahan']],
                [
                    'nama' => $data['nama'],
                    'kategori' => $data['kategori'],
                    'satuan' => $data['satuan'],
                    'stok' => $data['stok'],
                    'stok_minimum' => $data['stok_minimum'],
                    'deskripsi' => $data['deskripsi'],
                    'is_active' => true,
                ]
            );
        }
    }
}
