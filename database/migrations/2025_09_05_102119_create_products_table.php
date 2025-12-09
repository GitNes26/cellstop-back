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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // 🧾 Nuevos campos
            $table->string('region')->nullable();                        // Región
            $table->string('celular')->nullable();                       // Celular
            $table->string('iccid')->unique()->nullable();               // ICCID
            $table->string('imei')->nullable();                          // IMEI
            $table->date('fecha')->nullable();                           // Fecha
            $table->string('tramite')->nullable();                       // Trámite
            $table->string('estatus')->nullable();                       // Estatus
            $table->text('comentario')->nullable();                      // Comentario
            $table->string('fza_vta_prepago')->nullable();               // Fuerza de Venta Prepago
            $table->string('fza_vta_padre')->nullable();                 // Fuerza de Venta Padre
            $table->string('usuario')->nullable();                       // Usuario (externo)
            $table->string('folio')->nullable();                         // Folio
            $table->string('producto')->nullable();                      // Producto
            $table->string('num_orden')->nullable();                     // Número de orden
            $table->string('estatus_orden')->nullable();                 // Estatus de orden
            $table->string('motivo_error')->nullable();                  // Motivo de error
            $table->string('tipo_sim')->nullable();                      // Tipo SIM
            $table->string('modelo', 100)->nullable();                    // Modelo del dispositivo
            $table->string('marca', 100)->nullable();                    // Marca del dispositivo
            $table->string('color', 100)->nullable();                     // Color del dispositivo

            // 🔧 Control interno
            $table->enum('location_status', ['Stock', 'Asignado', 'Distribuido'])->default('stock');
            $table->enum('activation_status', ['Virgen', 'Pre-activado', 'Activado', 'Portado', 'Caducado'])->default('Pre-activado');

            // 🔗 Relaciones
            $table->foreignId('product_type_id')->nullable()->constrained('product_types', 'id');
            $table->foreignId('import_id')->nullable()->constrained('imports', 'id');
            // $table->foreignId('created_by')->nullable()->constrained('users', 'id');
            $table->string('evaluations_rejected', 100)->nullable()->comment('advertencia, peligro, null = ok');                     // Color del dispositivo

            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Índices para mejor performance
            $table->index(['folio', 'created_at']);
            $table->index('location_status');
            $table->index('activation_status');
            $table->index('product_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
