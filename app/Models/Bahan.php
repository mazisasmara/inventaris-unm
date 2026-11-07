<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bahan extends Model
{
    protected $table = 'bahan';

    protected $fillable = [
        'kode_bahan',
        'nama',
        'kategori',
        'satuan',
        'stok',
        'stok_minimum',
        'deskripsi',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'stok' => 'integer',
            'stok_minimum' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function detailPengambilan(): HasMany
    {
        return $this->hasMany(
            DetailPengambilanBahan::class,
            'bahan_id'
        );
    }
}