<?php

namespace Database\Seeders;

use App\Models\Laboratorium;
use Illuminate\Database\Seeder;

class LaboratoriumSeeder extends Seeder
{
    public function run(): void
    {
        $daftarLab = [
            [
                'kode_lab' => 'LAB-001',
                'nama' => 'Lab 1',
            ],
            [
                'kode_lab' => 'LAB-002',
                'nama' => 'Lab 2',
            ],
            [
                'kode_lab' => 'LAB-003',
                'nama' => 'Lab 3',
            ],
            [
                'kode_lab' => 'LAB-004',
                'nama' => 'Lab 4',
            ],
            [
                'kode_lab' => 'LAB-005',
                'nama' => 'Lab 5',
            ],
        ];

        foreach ($daftarLab as $data) {
            Laboratorium::updateOrCreate(
                ['kode_lab' => $data['kode_lab']],
                [
                    'nama' => $data['nama'],
                    'is_active' => true,
                ]
            );
        }
    }
}
