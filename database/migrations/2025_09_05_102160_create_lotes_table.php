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
        Schema::create('lotes', function (Blueprint $table) {
            $table->id();
            $table->string("lote");
            $table->foreignId('seller_id')->constrained('users', 'id')->onDelete('cascade')->comment("usuario al que se le asigno el lote"); // vendedor
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users', 'id')->onDelete('cascade')->comment("usuario que crea el lote"); // admin o supervisor

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
        Schema::dropIfExists('lotes');
    }
};
