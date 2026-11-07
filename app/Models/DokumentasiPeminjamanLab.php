<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumentasiPeminjamanLab extends Model
{
    protected $table = 'dokumentasi_peminjaman_lab';

    protected $fillable = [
        'peminjaman_lab_id',
        'uploaded_by',
        'file_path',
        'jenis',
    ];

    public function peminjamanLab(): BelongsTo
    {
        return $this->belongsTo(
            PeminjamanLab::class,
            'peminjaman_lab_id'
        );
    }

    public function pengunggah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}