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
        Schema::table('notifications', function (Blueprint $table) {
            // Tambahkan kolom 'link' setelah kolom 'message'
            // Kolom ini bisa null karena mungkin ada notifikasi tanpa tautan
            $table->string('link')->nullable()->after('message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Hapus kolom 'link' jika migrasi di-rollback
            $table->dropColumn('link');
        });
    }
};
