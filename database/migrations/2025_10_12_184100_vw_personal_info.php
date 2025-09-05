<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * --, CONCAT(street, ' #',num_ext, ' ') as address
     */
    public function up(): void
    {
        DB::statement(
            "CREATE OR REPLACE VIEW vw_personal_info AS 
            SELECT pi.*, CONCAT(name,' ',plast_name,' ',mlast_name) as full_name, CONCAT(plast_name,' ',mlast_name,' ',name) as full_name_reverse, IF(gender='H', 'HOMBRE','MUJER') as full_gender 
            FROM personal_info pi;"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS vw_personal_info');
    }
};
