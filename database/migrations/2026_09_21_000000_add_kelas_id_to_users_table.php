<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * MPK perkelas: 1 akun MPK pegang 1 kelas.
     * Nullable agar akun lama / non-MPK tetap valid;
     * MPK tanpa kelas dianggap tidak punya akses (ditangani di controller).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('kelas_id')->nullable()->after('role')->constrained('kelas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kelas_id');
        });
    }
};
