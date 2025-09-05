<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement(
            "CREATE OR REPLACE VIEW vw_employees AS 
            SELECT e.*, 
            CONCAT(e.name,' ',e.plast_name,' ',e.mlast_name) as full_name, 
            CONCAT(e.plast_name,' ',e.mlast_name,' ',e.name) as full_name_reverse, 
            p.position, p.description, d.letters, d.department, d.department_description,
            u.username
            FROM employees e
            INNER JOIN positions p ON e.position_id=p.id
            INNER JOIN departments d ON e.department_id=d.id
            RIGHT JOIN users u ON e.id=u.employee_id;"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS vw_employee');
    }
};
