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
        Schema::create('peminjaman_alat', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('diproses_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('status')->default('menunggu');

            $table->timestamp('tanggal_pengajuan')->useCurrent();
            $table->dateTime('tanggal_peminjaman');
            $table->dateTime('tanggal_pengembalian_rencana');

            $table->timestamp('diproses_at')->nullable();

            $table->text('catatan_admin')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peminjaman_alat');
    }
};
