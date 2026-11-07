<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UnitPeminjamanAlat extends Model
{
    protected $table = 'unit_peminjaman_alat';

    protected $fillable = [
        'detail_peminjaman_alat_id',
        'unit_alat_id',
    ];

    public function detailPeminjaman(): BelongsTo
    {
        return $this->belongsTo(
            DetailPeminjamanAlat::class,
            'detail_peminjaman_alat_id'
        );
    }

    public function unitAlat(): BelongsTo
    {
        return $this->belongsTo(UnitAlat::class, 'unit_alat_id');
    }

    public function detailPengembalian(): HasOne
    {
        return $this->hasOne(
            DetailPengembalianAlat::class,
            'unit_peminjaman_alat_id'
        );
    }
}