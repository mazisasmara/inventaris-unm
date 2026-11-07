<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('identity_number')
                ->nullable()
                ->unique()
                ->after('email');

            $table->string('role')
                ->default('mahasiswa')
                ->after('password');

            $table->boolean('is_active')
                ->default(true)
                ->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['identity_number']);
            $table->dropColumn([
                'identity_number',
                'role',
                'is_active',
            ]);
        });
    }
};
