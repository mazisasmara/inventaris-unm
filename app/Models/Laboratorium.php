<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Laboratorium extends Model
{
    protected $table = 'laboratorium';

    protected $fillable = [
        'kode_lab',
        'nama',
        'lokasi',
        'kapasitas',
        'deskripsi',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'kapasitas' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function jadwal(): HasMany
    {
        return $this->hasMany(JadwalLaboratorium::class, 'laboratorium_id');
    }

    public function peminjaman(): HasMany
    {
        return $this->hasMany(PeminjamanLab::class, 'laboratorium_id');
    }
}