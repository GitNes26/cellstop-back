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
            "CREATE OR REPLACE VIEW vw_users AS 
            SELECT u.*, r.role, r.read, r.create, r.update, r.delete, r.more_permissions, r.page_index, e.payroll_number,
            e.avatar,e.name, e.plast_name, e.mlast_name, e.cellphone, e.office_phone, e.ext, e.img_firm, e.position_id, e.department_id, e.full_name, e.full_name_reverse, e.position, e.description, e.letters, e.department, e.department_description
            FROM users u 
            INNER JOIN roles r ON u.role_id=r.id
            LEFT JOIN vw_employees e ON u.employee_id=e.id
            ;"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS vw_users');
    }
};
