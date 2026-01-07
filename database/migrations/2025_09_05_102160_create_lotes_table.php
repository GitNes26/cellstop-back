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

            // Información del lote
            $table->unsignedBigInteger('folio')->nullable()->comment('Folio o número identificador del lote');
            $table->string('lada', 10)->nullable()->comment('Código de área o LADA asociado al lote');
            $table->date('preactivation_date')->nullable()->comment('Fecha de preactivación del lote');
            $table->integer('quantity')->nullable()->comment('Cantidad de productos o chips en el lote');

            // Relaciones
            $table->foreignId('seller_id')
                ->constrained('users', 'id')
                ->onDelete('cascade')
                ->comment("Usuario al que se le asignó el lote"); // vendedor

            $table->text('description')->nullable();

            $table->foreignId('created_by')
                ->constrained('users', 'id')
                ->onDelete('cascade')
                ->comment("Usuario que crea el lote"); // admin o supervisor

            // Estado
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('seller_id');
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
