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
        Schema::create('personal_info', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('plast_name');
            $table->string('mlast_name');
            $table->enum('gender', ["H", "M"]);
            $table->enum('civil_status_id', ["Soltero", "Casado", "Divorciado", "Viudo", "Union Libre"])->default("Soltero");
            // $table->foreignId('civil_status_id')->constrained('civil_statuses')->nullable();
            $table->boolean('is_working')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('curp');
            $table->date('birthdate');
            $table->integer('community_id');
            $table->string('street');
            $table->string('num_ext')->default("S/N");
            $table->string('num_int')->nullable();
            $table->string('img_ine')->nullable();
            $table->string('img_photo')->nullable();
            // $table->string('voter_key');
            // $table->string('year_registration');
            $table->string('section');
            $table->string('validity');
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
        Schema::dropIfExists('users');
    }
};
