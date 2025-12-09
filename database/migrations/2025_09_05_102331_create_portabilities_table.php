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
        Schema::create('portabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained('products', 'id');
            // $table->foreignId('user_id')->nullable()->constrained('users', 'id'); //vendedor
            $table->string('phone_number', 20)->nullable();
            $table->timestamp('activation_date')->nullable();
            $table->timestamp('portability_date')->nullable();
            // $table->string('status', 50)->default('completada');
            $table->foreignId('import_id')->nullable()->constrained('imports', 'id');

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
        Schema::dropIfExists('portabilities');
    }
};
