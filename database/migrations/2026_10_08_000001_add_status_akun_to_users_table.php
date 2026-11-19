<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Status akun untuk Model B (akun MPK milik rombel, diwariskan tiap
     * angkatan): is_active = pengaman umum (kick paksa), sedangkan
     * must_change_password = flag serah terima tahunan (wajib ganti
     * password saat login pertama pengurus baru).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('remember_token');
            $table->boolean('must_change_password')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'must_change_password']);
        });
    }
};
