<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeminjamanLab extends Model
{
    protected $table = 'peminjaman_lab';

    protected $fillable = [
        'user_id',
        'laboratorium_id',
        'diproses_oleh',
        'tujuan',
        'status',
        'tanggal_pengajuan',
        'waktu_mulai',
        'waktu_selesai',
        'diproses_at',
        'catatan_admin',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pengajuan' => 'datetime',
            'waktu_mulai' => 'datetime',
            'waktu_selesai' => 'datetime',
            'diproses_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function laboratorium(): BelongsTo
    {
        return $this->belongsTo(Laboratorium::class, 'laboratorium_id');
    }

    public function pemroses(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    public function dokumentasi(): HasMany
    {
        return $this->hasMany(
            DokumentasiPeminjamanLab::class,
            'peminjaman_lab_id'
        );
    }
}