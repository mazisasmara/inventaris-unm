<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_alat', function (Blueprint $table) {
            $table->id();

            $table->foreignId('alat_id')
                ->constrained('alat')
                ->restrictOnDelete();

            $table->string('kode_inventaris')->unique();
            $table->string('kondisi')->default('baik');
            $table->string('status')->default('tersedia');
            $table->text('keterangan')->nullable();

            $table->timestamps();

            $table->index(['kondisi', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_alat');
    }
};
