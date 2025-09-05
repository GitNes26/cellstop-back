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
        Schema::create('menus', function (Blueprint $table) {
            // $table->bigInteger('id', true)->primary();
            $table->id();
            $table->string('menu');
            $table->string('caption')->nullable()->comment("disponible solo para los menus padres");
            $table->enum('type', ['group', 'item']);
            $table->integer('belongs_to');
            $table->string('url')->nullable()->comment("disponible solo para los menus hijos");
            $table->string('icon')->nullable()->comment("disponible solo para los menus hijos");
            // $table->string('file_name')->default('#');
            $table->integer('order')->nullable();
            $table->boolean('show_counter')->default(false);
            $table->string('counter_name', 100)->nullable();
            $table->longText('others_permissions')->nullable();
            $table->boolean('read_only')->default(false)->comment("solo tendra la casilla de lectura, porque sus permisos se concentran en una 'pagina maestra'");
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
        Schema::dropIfExists('menus');
    }
};
