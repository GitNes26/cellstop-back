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
        Schema::create('chip_distribucion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chip_id')->constrained('chips', 'id')->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('users', 'id')->onDelete('cascade');
            $table->unsignedBigInteger('lote_id')->nullable(); // Si se asignan por lote
            $table->timestamp('assigned_at')->useCurrent();
            $table->foreignId('assigned_by')->constrained('users', 'id')->onDelete('cascade')->comment("usuario que asigno el chip"); // admin o supervisor

            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chip_distribucion');
    }
};