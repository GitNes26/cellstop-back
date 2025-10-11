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
        Schema::create('chip_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chip_id')->constrained('chips', 'id')->onDelete('cascade');
            $table->string('action'); // Ej: "Asignación", "Venta", "Devolución", "Importación" "Importación inicial" | "Asignación a vendedor" | "Venta final" | "Devolución" | "Reasignación"
            $table->text('description')->nullable();
            $table->string('origin')->nullable(); // desde dónde vino el chip (ej: "stock", "vendedor 3")
            $table->string('destination')->nullable(); // hacia dónde fue (ej: "vendedor 5", "cliente final")
            $table->timestamp('executed_at')->useCurrent();
            $table->foreignId('executed_by')->constrained('users', 'id')->onDelete('cascade')->comment("usuario que asigno el chip"); // admin o supervisor

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
        Schema::dropIfExists('chip_movements');
    }
};
