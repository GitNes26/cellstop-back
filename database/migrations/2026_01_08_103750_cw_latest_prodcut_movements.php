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
        DB::statement("CREATE or REPLACE VIEW vw_latest_product_movements AS
        SELECT pm.*,
            p.iccid, p.imei, p.fecha, p.celular, p.folio, p.num_orden,
            p.tipo_sim, p.modelo, p.marca, p.color, p.location_status,
            p.activation_status, p.product_type_id, pt.product_type,
            p.evaluations_rejected,
            i.name import_name, ui.username uploader_username, ui.full_name uploader_full_name,
            l.lote, l.lada, l.preactivation_date, l.quantity, l.description description_lote, l.seller_id,
            s.username, s.full_name, s.pin_color,
            v.id AS visit_id, v.visit_type, v.evidence_photo, v.observations,
            pos.id AS pos_id, pos.name AS pos_name, pos.address as pos_address, pos.lat, pos.lon, pos.ubication, pos.img as pos_img
        FROM (
            SELECT *,
                ROW_NUMBER() OVER (PARTITION BY product_id ORDER BY executed_at DESC) AS rn
            FROM product_movements
        ) pm
        JOIN products p ON p.id = pm.product_id
        JOIN imports i ON i.id = p.import_id
        JOIN vw_users ui ON ui.id = i.uploaded_by
        JOIN product_types pt ON pt.id = p.product_type_id
        LEFT JOIN lote_details ld ON ld.product_id = p.id
        LEFT JOIN lotes l ON l.id = ld.lote_id
        LEFT JOIN vw_users s ON s.id = l.seller_id
        LEFT JOIN visits v ON JSON_SEARCH(v.product_ids, 'one', pm.product_id)
        LEFT JOIN points_of_sale pos ON pos.id = v.pos_id
        WHERE pm.rn = 1
        ORDER BY pm.executed_at DESC, pm.product_id;
        ");
        // DB::statement(
        //     "CREATE VIEW vw_latest_product_movements AS
        //     SELECT product_id, MAX(executed_at) AS max_executed_at
        //     FROM product_movements
        //     GROUP BY product_id;
        //     "
        // );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS vw_latest_product_movements');
    }
};
