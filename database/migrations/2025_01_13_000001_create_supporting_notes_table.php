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
        Schema::create('supporting_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('app_request_id')->constrained('app_requests')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users'); // Pembuat catatan
            $table->string('title'); // Judul catatan
            $table->text('note'); // Isi catatan (HTML dari Tiptap)
            $table->string('image_path')->nullable(); // Path gambar (opsional)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supporting_notes');
    }
};
