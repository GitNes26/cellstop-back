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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action'); // created, updated, deleted, restored, etc.
            $table->text('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('extra_data')->nullable(); // Para datos adicionales
            $table->morphs('model'); // Crea model_id (unsignedBigInteger) y model_type (string)
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); //->nullOnDelete();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->string('method')->nullable();
            $table->timestamps();

            // Índices para mejor performance
            $table->index(['model_type', 'model_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
