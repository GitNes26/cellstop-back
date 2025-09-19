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
        Schema::create('chips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products', 'id');

            $table->string('filtro')->nullable();
            $table->string('telefono')->nullable();
            $table->string('imei')->nullable();
            $table->string('iccid')->nullable();
            $table->string('estatus_lin')->nullable();
            $table->string('movimiento')->nullable();
            $table->date('fecha_activ')->nullable();
            $table->date('fecha_prim_llam')->nullable();
            $table->date('fecha_dol')->nullable();
            $table->string('estatus_pago')->nullable();
            $table->string('motivo_estatus')->nullable();
            $table->decimal('monto_com', 10, 2)->nullable();
            $table->string('tipo_comision')->nullable();
            $table->string('evaluacion')->nullable();
            $table->string('fza_vta_pago')->nullable();
            $table->date('fecha_evaluacion')->nullable();
            $table->string('folio_factura')->nullable();
            $table->date('fecha_publicacion')->nullable();

            $table->enum('location_status', ['stock', 'con_vendedor', 'distribuido'])->default('stock');
            $table->enum('activation_status', ['virgen', 'pre_activado', 'activado', 'caducado'])->default('virgen');

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
        Schema::dropIfExists('chips');
    }
};