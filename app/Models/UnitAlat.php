<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitAlat extends Model
{
    protected $table = 'unit_alat';

    protected $fillable = [
        'alat_id',
        'kode_inventaris',
        'kondisi',
        'status',
        'keterangan',
    ];

    public function alat(): BelongsTo
    {
        return $this->belongsTo(Alat::class, 'alat_id');
    }

    public function peminjaman(): HasMany
    {
        return $this->hasMany(UnitPeminjamanAlat::class, 'unit_alat_id');
    }
}