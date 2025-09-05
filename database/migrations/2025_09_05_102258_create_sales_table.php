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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('assignments', 'id');
            $table->foreignId('user_id')->constrained('users', 'id'); // vendedor
            $table->foreignId('pos_id')->nullable()->constrained('points_of_sale', 'id');
            $table->string('buyer_name', 150)->nullable();
            $table->string('buyer_phone', 20)->nullable();
            $table->timestamp('sale_date')->useCurrent();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('evidence_photo')->nullable();
            $table->enum('status', ['completada', 'cancelada'])->default('completada');
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
        Schema::dropIfExists('sales');
    }
};
