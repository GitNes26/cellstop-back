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
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->nullable();
            // $table->enum('file_type', ['imp', 'int']);
            $table->bigInteger('size')->nullable();
            $table->bigInteger("last_modified");
            $table->string('path')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('uploaded_by')->constrained('users', 'id'); // quién subió
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
        Schema::dropIfExists('imports');
    }
};
