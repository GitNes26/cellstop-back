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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products', 'id');
            $table->string('imei', 30)->unique();
            $table->string('model', 100)->nullable();
            $table->string('brand', 100)->nullable();
            $table->string('color', 50)->nullable();
            $table->foreignId('chip_id')->nullable()->constrained('chips', 'id');
            $table->enum('location_status', ['stock', 'con_vendedor', 'distribuido'])->default('stock');
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
        Schema::dropIfExists('devices');
    }
};
