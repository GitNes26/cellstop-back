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
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            // vendedor que realiza la visita
            $table->foreignId('seller_id')->constrained('users', 'id');

            // punto de venta visitado
            $table->foreignId('pos_id')->nullable()->constrained('points_of_sale', 'id');

            // producto vinculado (chip u otro)
            $table->longText('product_ids')->nullable();

            // información opcional de contacto o responsable
            $table->string('contact_name', 150)->nullable();  // antes buyer_name
            $table->string('contact_phone', 20)->nullable();  // antes buyer_phone

            // tipo de visita (puede ser entrega o revisión)
            $table->enum('visit_type', ['Distribución', 'Monitoreo'])->default('Monitoreo');

            // coordenadas de la visita
            $table->decimal('lat', 25, 18)->nullable();
            $table->decimal('lon', 25, 18)->nullable();

            // dirección o referencia textual
            $table->text('ubication')->nullable();

            // evidencia fotográfica (URL o path)
            $table->text('evidence_photo')->nullable();

            // información de seguimiento
            $table->integer('chips_delivered')->nullable(); // Cantidad de chips entregados (si aplica)
            $table->integer('chips_sold')->nullable(); // Cuántos se vendieron desde la última visita
            $table->integer('chips_remaining')->nullable(); // Inventario en tienda (seguimiento)

            // estado general de la visita
            // $table->enum('status', ['realizada', 'cancelada'])->default('realizada');

            // observaciones del vendedor
            $table->text('observations')->nullable();

            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Schema::create('sales', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('product_id')->constrained('products', 'id');
        //     $table->foreignId('seller_id')->constrained('users', 'id'); // vendedor
        //     $table->foreignId('pos_id')->nullable()->constrained('points_of_sale', 'id');
        //     $table->string('buyer_name', 150)->nullable();
        //     $table->string('buyer_phone', 20)->nullable();
        //     $table->decimal('lat', 10, 8)->nullable();
        //     $table->decimal('lon', 11, 8)->nullable();
        //     $table->text('ubication')->nullable();
        //     $table->text('evidence_photo')->nullable();
        //     $table->enum('status', ['completada', 'cancelada'])->default('completada');
        //     $table->boolean('active')->default(true);
        //     $table->timestamps();
        //     $table->softDeletes();
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
