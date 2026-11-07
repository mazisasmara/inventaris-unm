
<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// Dashboard bawaan Laravel
Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
});

// Area mahasiswa
Route::middleware(['auth', 'role:mahasiswa'])
    ->prefix('mahasiswa')
    ->name('mahasiswa.')
    ->group(function () {
        Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::livewire('/pengambilan-bahan', 'pengambilan-bahan.create')
        ->name('pengambilan-bahan.create');
    });

// Area dosen
Route::middleware(['auth', 'role:dosen'])
    ->prefix('dosen')
    ->name('dosen.')
    ->group(function () {
        Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::livewire('/pengambilan-bahan', 'pengambilan-bahan.create')
        ->name('pengambilan-bahan.create');
    });

// Area admin dan superadmin
Route::middleware(['auth', 'role:admin,superadmin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::view('/dashboard', 'dashboard')->name('dashboard');

        Route::livewire('/bahan', 'admin.bahan.index')
            ->name('bahan.index');

        Route::livewire('/alat', 'admin.alat.index')
            ->name('alat.index');

        Route::livewire('/unit-alat', 'admin.unit-alat.index')
            ->name('unit-alat.index');

        Route::livewire('/laboratorium', 'admin.laboratorium.index')
            ->name('laboratorium.index');

        Route::livewire('/jadwal-laboratorium', 'admin.jadwal-laboratorium.index')
            ->name('jadwal-laboratorium.index');

        Route::livewire('/pengambilan-bahan', 'admin.pengambilan-bahan.index')
            ->name('pengambilan-bahan.index');
    });

// Area khusus superadmin
Route::middleware(['auth', 'role:superadmin'])
    ->prefix('superadmin')
    ->name('superadmin.')
    ->group(function () {
        Route::view('/dashboard', 'dashboard')->name('dashboard');
    });

require __DIR__.'/settings.php';
