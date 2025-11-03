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
        Schema::create('activations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products', 'id')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users', 'id')->onDelete('cascade'); //vendedor
            $table->string('activation_type', 100)->nullable();
            $table->timestamp('activation_date')->nullable();
            $table->string('status', 50)->nullable();
            // $table->enum('status', ['completada', 'pendiente', 'cancelada'])->default('completada');
            $table->enum('source', ['imp', 'int']);
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
        Schema::dropIfExists('activations');
    }
};
