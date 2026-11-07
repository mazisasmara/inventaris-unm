<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alat extends Model
{
    protected $table = 'alat';

    protected $fillable = [
        'kode_alat',
        'nama',
        'kategori',
        'deskripsi',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function unitAlat(): HasMany
    {
        return $this->hasMany(UnitAlat::class, 'alat_id');
    }

    public function detailPeminjaman(): HasMany
    {
        return $this->hasMany(
            DetailPeminjamanAlat::class,
            'alat_id'
        );
    }
}