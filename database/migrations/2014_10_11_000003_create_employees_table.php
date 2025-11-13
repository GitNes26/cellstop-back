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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->integer('payroll_number')->nullable();
            $table->string('avatar', 255)->nullable();
            $table->string('name');
            $table->string('plast_name');
            $table->string('mlast_name')->nullable();
            $table->string('cellphone')->nullable();
            $table->string('office_phone')->nullable();
            $table->string('ext')->nullable()->comment('extension telefonica de su lugar en caso de tener');
            $table->string('img_firm', 255)->nullable();
            $table->string('ine_front', 255)->nullable();
            $table->string('ine_back', 255)->nullable();
            $table->foreignId('position_id')->constrained('positions')->comment('Puesto de trabajo');
            $table->foreignId('department_id')->nullable()->constrained('departments');
            // $table->foreignId('workstation_id')->constrained('workstations');
            // $table->foreignId('user_id')->constrained('users');
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
        Schema::dropIfExists('employees');
    }
};
