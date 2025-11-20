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
        Schema::create('development_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_request_id')->constrained('app_requests')->onDelete('cascade');
            $table->unsignedInteger('iteration_count');
            $table->text('description');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('development_activities');
    }
};
