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
            $table->string('iccid', 30)->unique();
            $table->string('imei', 30)->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->string('operator', 50)->nullable();
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
