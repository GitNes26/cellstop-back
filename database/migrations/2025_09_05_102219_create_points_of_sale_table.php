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
        Schema::create('points_of_sale', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('contact_name', 100)->nullable();
            $table->string('contact_phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->decimal('lat', 25, 18)->nullable();
            $table->decimal('lon', 25, 18)->nullable();
            $table->text('ubication')->nullable();
            $table->foreignId('seller_id')->constrained('users')->onDelete('restrict')->onUpdate('cascade');

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
        Schema::dropIfExists('points_of_sale');
    }
};
