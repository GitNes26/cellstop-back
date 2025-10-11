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
        // Schema::create('chip_distribucion_details', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('chip_id')->constrained('chips', 'id')->onDelete('cascade');
        //     $table->foreignId('seller_id')->constrained('users', 'id')->onDelete('cascade');
        //     $table->unsignedBigInteger('lote_id')->nullable();
        //     $table->unsignedBigInteger('assigned_by');
        //     $table->dateTime('assigned_at')->default(DB::raw('CURRENT_TIMESTAMP'));
        //     $table->enum('status', ['asignado', 'devuelto', 'vendido'])->default('asignado');
        //     $table->boolean('active')->default(true);
        //     $table->timestamps();
        //     $table->softDeletes();

        //     // 🔗 Relaciones
        //     $table->foreign('chip_id')->references('id')->on('chips')->onDelete('cascade');
        //     $table->foreign('seller_id')->references('id')->on('users')->onDelete('cascade');
        //     $table->foreign('assigned_by')->references('id')->on('users')->onDelete('cascade');
        //     $table->foreign('package_id')->references('id')->on('chip_packages')->onDelete('set null');
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chip_distribucion_details');
    }
};