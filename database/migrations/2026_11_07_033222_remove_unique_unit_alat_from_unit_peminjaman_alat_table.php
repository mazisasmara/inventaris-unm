<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('unit_peminjaman_alat', function (Blueprint $table) {
                $table->dropUnique('unit_pinjam_unit_unique');
            });

            return;
        }

        Schema::table('unit_peminjaman_alat', function (Blueprint $table) {
            $table->dropForeign(
                'unit_peminjaman_alat_unit_alat_id_foreign'
            );

            $table->dropUnique('unit_pinjam_unit_unique');

            $table->index('unit_alat_id');

            $table->foreign('unit_alat_id')
                ->references('id')
                ->on('unit_alat')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('unit_peminjaman_alat', function (Blueprint $table) {
                $table->unique(
                    'unit_alat_id',
                    'unit_pinjam_unit_unique'
                );
            });

            return;
        }

        Schema::table('unit_peminjaman_alat', function (Blueprint $table) {
            $table->dropForeign(
                'unit_peminjaman_alat_unit_alat_id_foreign'
            );

            $table->dropIndex(['unit_alat_id']);

            $table->unique(
                'unit_alat_id',
                'unit_pinjam_unit_unique'
            );

            $table->foreign('unit_alat_id')
                ->references('id')
                ->on('unit_alat')
                ->restrictOnDelete();
        });
    }
};
