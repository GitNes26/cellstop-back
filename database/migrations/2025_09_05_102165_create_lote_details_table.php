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
        Schema::create('lote_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lote_id')->constrained('lotes', 'id')->onDelete('cascade');
            $table->foreignId('chip_id')->constrained('chips', 'id')->onDelete('cascade');
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
        Schema::dropIfExists('lote_details');
    }
};
