
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengembalian_bahan', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pengambilan_bahan_id')
                ->constrained('pengambilan_bahan')
                ->restrictOnDelete();

            $table->foreignId('diproses_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('tanggal_pengembalian')->useCurrent();

            $table->text('catatan')->nullable();

            $table->timestamps();

            $table->index('tanggal_pengembalian');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengembalian_bahan');
    }
};
