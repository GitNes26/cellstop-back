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
        Schema::create('roles', function (Blueprint $table) {
            // $table->bigInteger('id', true)->primary();
            $table->id();
            $table->string('role');
            $table->string('description')->nullable();
            $table->string('read')->nullable();
            $table->string('create')->nullable();
            $table->string('update')->nullable();
            $table->string('delete')->nullable();
            $table->longText('more_permissions')->nullable();
            $table->string('page_index')->default('/admin')->nullable();
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
        Schema::dropIfExists('roles');
    }
};