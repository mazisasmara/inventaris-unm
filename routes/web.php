
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
    });

// Area dosen
Route::middleware(['auth', 'role:dosen'])
    ->prefix('dosen')
    ->name('dosen.')
    ->group(function () {
        Route::view('/dashboard', 'dashboard')->name('dashboard');
    });

// Area admin dan superadmin
Route::middleware(['auth', 'role:admin,superadmin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::view('/dashboard', 'dashboard')->name('dashboard');
    });

// Area khusus superadmin
Route::middleware(['auth', 'role:superadmin'])
    ->prefix('superadmin')
    ->name('superadmin.')
    ->group(function () {
        Route::view('/dashboard', 'dashboard')->name('dashboard');
    });

require __DIR__.'/settings.php';
