<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DetailPeminjamanAlat extends Model
{
    protected $table = 'detail_peminjaman_alat';

    protected $fillable = [
        'peminjaman_alat_id',
        'alat_id',
        'jumlah',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
        ];
    }

    public function peminjamanAlat(): BelongsTo
    {
        return $this->belongsTo(
            PeminjamanAlat::class,
            'peminjaman_alat_id'
        );
    }

    public function alat(): BelongsTo
    {
        return $this->belongsTo(Alat::class, 'alat_id');
    }

    public function unitPeminjaman(): HasMany
    {
        return $this->hasMany(
            UnitPeminjamanAlat::class,
            'detail_peminjaman_alat_id'
        );
    }
}