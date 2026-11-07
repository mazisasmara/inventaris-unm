<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('unit_peminjaman_alat')) {
            return;
        }

        Schema::create('unit_peminjaman_alat', function (Blueprint $table) {
            $table->id();

            $table->foreignId('detail_peminjaman_alat_id')
                ->constrained('detail_peminjaman_alat')
                ->restrictOnDelete();

            $table->foreignId('unit_alat_id')
                ->constrained('unit_alat')
                ->restrictOnDelete();

            $table->timestamps();

            $table->unique(
                ['detail_peminjaman_alat_id', 'unit_alat_id'],
                'unit_pinjam_detail_unit_unique'
            );

            $table->unique('unit_alat_id', 'unit_pinjam_unit_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_peminjaman_alat');
    }
};
