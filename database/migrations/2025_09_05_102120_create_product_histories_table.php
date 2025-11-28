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
        Schema::create('product_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products', 'id');

            $table->string('filtro')->nullable();
            $table->string('telefono')->nullable();
            $table->string('imei')->nullable();
            $table->string('iccid')->unique()->nullable();
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

            $table->foreignId('import_id')->constrained('imports', 'id');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Índices para mejor performance
            // $table->index(['iccid', 'created_at']);
            $table->index('telefono');
            $table->index('iccid');
            $table->index('imei');
            $table->index(['iccid', 'estatus_pago']);
            $table->index('estatus_pago');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_histories');
    }
};
