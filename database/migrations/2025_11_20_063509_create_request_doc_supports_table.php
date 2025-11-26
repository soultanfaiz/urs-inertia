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
        Schema::create('request_doc_supports', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('request_history_id')->constrained('request_histories')->onDelete('cascade');
            $table->string('request_status');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('verification_status');
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_doc_supports');
    }
};
