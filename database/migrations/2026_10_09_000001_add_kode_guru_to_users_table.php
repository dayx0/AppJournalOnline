<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kode guru dari inisial nama (mis. "Ana Himmatun Ulya" -> "AHU").
     * Nullable: hanya role guru yang diisi, dibuat otomatis oleh model
     * (lihat User::generateKodeGuru) setiap nama/role berubah.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('kode_guru', 20)->nullable()->after('name');
        });

        // Backfill guru yang sudah ada (seeder lama / data manual).
        foreach (DB::table('users')->where('role', 'guru')->select('id', 'name')->get() as $row) {
            DB::table('users')->where('id', $row->id)->update([
                'kode_guru' => User::generateKodeGuru((string) $row->name),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('kode_guru');
        });
    }
};
