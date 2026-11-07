<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPengembalianAlat extends Model
{
    protected $table = 'detail_pengembalian_alat';

    protected $fillable = [
        'pengembalian_alat_id',
        'unit_peminjaman_alat_id',
        'kondisi_saat_kembali',
        'catatan',
    ];

    public function pengembalianAlat(): BelongsTo
    {
        return $this->belongsTo(
            PengembalianAlat::class,
            'pengembalian_alat_id'
        );
    }

    public function unitPeminjaman(): BelongsTo
    {
        return $this->belongsTo(
            UnitPeminjamanAlat::class,
            'unit_peminjaman_alat_id'
        );
    }
}