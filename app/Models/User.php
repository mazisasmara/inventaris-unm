<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'identity_number',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isMahasiswa(): bool
    {
        return $this->role === 'mahasiswa';
    }

    public function isDosen(): bool
    {
        return $this->role === 'dosen';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSuperadmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function pengambilanBahan(): HasMany
    {
        return $this->hasMany(PengambilanBahan::class, 'user_id');
    }

    public function pengambilanBahanDiproses(): HasMany
    {
        return $this->hasMany(PengambilanBahan::class, 'diproses_oleh');
    }

    public function peminjamanAlat(): HasMany
    {
        return $this->hasMany(PeminjamanAlat::class, 'user_id');
    }

    public function peminjamanAlatDiproses(): HasMany
    {
        return $this->hasMany(PeminjamanAlat::class, 'diproses_oleh');
    }

    public function pengembalianAlatDiproses(): HasMany
    {
        return $this->hasMany(PengembalianAlat::class, 'diproses_oleh');
    }

    public function peminjamanLab(): HasMany
    {
        return $this->hasMany(PeminjamanLab::class, 'user_id');
    }

    public function peminjamanLabDiproses(): HasMany
    {
        return $this->hasMany(PeminjamanLab::class, 'diproses_oleh');
    }

    public function dokumentasiPeminjamanLabDiunggah(): HasMany
    {
        return $this->hasMany(DokumentasiPeminjamanLab::class, 'uploaded_by');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'user_id');
    }
    
    public function initials(): string
    {
        return collect(explode(' ', $this->name))
            ->filter()
            ->take(2)
            ->map(fn ($word) => strtoupper(substr($word, 0, 1)))
            ->implode('');
    }
}
