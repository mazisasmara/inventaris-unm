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
        Schema::create('dokumentasi_peminjaman_lab', function (Blueprint $table) {
            $table->id();

            $table->foreignId('peminjaman_lab_id')
                ->constrained('peminjaman_lab')
                ->restrictOnDelete();

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('file_path');

            $table->string('jenis')->default('foto');

            $table->timestamps();

            $table->index('jenis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dokumentasi_peminjaman_lab');
    }
};
