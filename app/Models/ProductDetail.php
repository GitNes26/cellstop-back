<?php

namespace App\Models;

use App\Services\NormalizerDateService;
use App\Services\ProductMovementService;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class ProductDetail extends Model
{
    use HasFactory, Auditable; // Audtiable(logs)
    use SoftDeletes;


    protected $fillable = [
        'product_id',

        'filtro',
        'telefono',
        'imei',
        'iccid',
        'estatus_lin',
        'movimiento',
        'fecha_activ',
        'fecha_prim_llam',
        'fecha_dol',
        'estatus_pago',
        'motivo_estatus',
        'monto_com',
        'tipo_comision',
        'evaluacion',
        'fza_vta_pago',
        'fecha_evaluacion',
        'folio_factura',
        'fecha_publicacion',

        'import_id',
        'active'
    ];

    protected $table = 'product_details';

    protected $primaryKey = 'id';

    protected $casts = [
        'fecha_activ' => 'date',
        'fecha_prim_llam' => 'date',
        'fecha_dol' => 'date',
        'fecha_evaluacion' => 'date',
        'fecha_publicacion' => 'date',
        'monto_com' => 'decimal:2',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];


    /* -------------------------------------------------------------
     | 🔗 RELACIONES
     |--------------------------------------------------------------*/

    /**
     * Relación con el producto
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Relación con la importación
     */
    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class, 'import_id');
    }

    /**
     * Usuario que subió la importación (a través de import)
     */
    public function uploadedByUser()
    {
        return $this->hasOneThrough(
            VW_User::class,
            Import::class,
            'id',           // FK en imports table
            'id',           // FK en users table
            'import_id',    // FK en product_details table
            'uploaded_by'   // FK en imports table
        );
    }

    /**
     * Scopes para filtros comunes
     */

    /**
     * Scope para detalles activos
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope por ICCID
     */
    public function scopeByIccid($query, $iccid)
    {
        return $query->where('iccid', $iccid);
    }

    /**
     * Scope por teléfono
     */
    public function scopeByTelefono($query, $telefono)
    {
        return $query->where('telefono', $telefono);
    }

    /**
     * Scope por IMEI
     */
    public function scopeByImei($query, $imei)
    {
        return $query->where('imei', $imei);
    }

    /**
     * Scope por estatus de pago
     */
    public function scopeByEstatusPago($query, $estatus)
    {
        return $query->where('estatus_pago', $estatus);
    }

    /**
     * Scope por importación
     */
    public function scopeByImport($query, $importId)
    {
        return $query->where('import_id', $importId);
    }

    /**
     * Scope para detalles recientes
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope para búsqueda en múltiples campos
     */
    public function scopeSearch($query, $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('iccid', 'LIKE', "%{$searchTerm}%")
                ->orWhere('telefono', 'LIKE', "%{$searchTerm}%")
                ->orWhere('imei', 'LIKE', "%{$searchTerm}%")
                ->orWhere('folio_factura', 'LIKE', "%{$searchTerm}%");
        });
    }

    /**
     * Accessors para datos formateados
     */

    /**
     * Monto de comisión formateado
     */
    public function getMontoComFormateadoAttribute()
    {
        return $this->monto_com ? '$' . number_format($this->monto_com, 2) : 'N/A';
    }

    /**
     * Fecha de activación formateada
     */
    public function getFechaActivFormateadaAttribute()
    {
        return $this->fecha_activ?->format('d/m/Y') ?? 'N/A';
    }

    /**
     * Estado del línea formateado
     */
    public function getEstatusLinFormateadoAttribute()
    {
        return match ($this->estatus_lin) {
            'active' => 'Activo',
            'inactive' => 'Inactivo',
            'suspended' => 'Suspendido',
            default => $this->estatus_lin ?? 'Desconocido'
        };
    }

    /**
     * Métodos para cargas masivas
     */

    /**
     * Insertar múltiples registros eficientemente
     */
    public static function bulkInsert(array $data)
    {
        return self::insert($data);
    }

    /**
     * Upsert para actualizar o insertar masivamente
     */
    public static function bulkUpsert(array $data, array $uniqueBy, array $updateColumns = null)
    {
        return self::upsert($data, $uniqueBy, $updateColumns);
    }

    /**
     * Cargar detalles desde un array con validación
     */
    public static function processBulkData(array $details, $importId)
    {
        $processed = [];
        $errors = [];
        $duplicates = [];
        $productsToUpdate = [];
        $productsToFlag = [];

        // Cache para productos ya encontrados
        $productCache = [];
        // Cache para evaluaciones recientes por ICCID
        $recentEvaluationsCache = [];

        foreach ($details as $index => $detail) {
            try {
                $iccid = trim($detail['ICCID'] ?? '');
                $estatusPago = trim($detail['ESTATUS_PAGO'] ?? '');
                $evaluacion = trim($detail['EVALUACION'] ?? '');
                $fechaEvaluacion = trim($detail['FECHA EVALUACION'] ?? '');

                // === VALIDACIÓN 1: Campos requeridos ===
                if (empty($iccid)) {
                    $errors[] = [
                        'index' => $index,
                        'iccid' => $iccid,
                        'telefono' => $detail['TELEFONO'] ?? null,
                        'message' => 'ICCID es requerido'
                    ];
                    continue;
                }

                // === VALIDACIÓN 2: Buscar duplicados con criterios específicos ===
                $isDuplicate = self::checkDuplicateCriteria($detail);
                if ($isDuplicate['is_duplicate']) {
                    $duplicates[] = [
                        'index' => $index,
                        'iccid' => $iccid,
                        'telefono' => $detail['TELEFONO'] ?? null,
                        'estatus_pago' => $estatusPago,
                        'evaluacion' => $evaluacion,
                        'fecha_evaluacion' => $fechaEvaluacion,
                        'reason' => $isDuplicate['reason'],
                        'existing_record' => $isDuplicate['existing_record'] ?? null
                    ];
                    continue; // Saltar este registro
                }

                // === VALIDACIÓN 3: Buscar producto ===
                $product = null;
                $productId = null;
                // Log::error("productCache: " . json_encode($productCache));

                // Buscar en cache primero
                if (isset($productCache[$iccid])) {
                    $product = $productCache[$iccid];
                    $productId = $product->id;
                } else {
                    // Buscar por product_id si se proporciona
                    if (!empty($detail['product_id'])) {
                        $product = Product::find($detail['product_id']);
                        if ($product) {
                            $productId = $product->id;
                            $productCache[$iccid] = $product;
                        }
                    }

                    // Si no se encontró por product_id, buscar por ICCID
                    // Log::error("product: " . json_encode($product));
                    // Log::error("iccid: " . json_encode($iccid));
                    if (!$product && !empty($iccid)) {
                        $product = Product::where('iccid', 'like', "$iccid%")->first();


                        if (!$product) {
                            $errors[] = [
                                'index' => $index,
                                'iccid' => $iccid,
                                'telefono' => $detail['TELEFONO'] ?? null,
                                'message' => 'Producto no encontrado en sistema'
                            ];
                            continue;
                        }
                        $productId = $product->id;
                        $productCache[$iccid] = $product;
                    }
                }

                if (!$product) {
                    $errors[] = [
                        'index' => $index,
                        'iccid' => $iccid,
                        'telefono' => $detail['TELEFONO'] ?? null,
                        'message' => 'No se pudo vincular a un producto'
                    ];
                    continue;
                }

                // === VALIDACIÓN 4: Checar evaluaciones RECHAZADA seguidas ===
                if ($estatusPago === 'RECHAZADA') {
                    if (!isset($recentEvaluationsCache[$iccid])) {
                        // Obtener las últimas 4 evaluaciones del producto
                        $recentEvaluations = self::where('iccid', 'like', "$iccid%")
                            ->whereNotNull('estatus_pago')
                            ->orderBy('fecha_evaluacion', 'desc')
                            ->orderBy('created_at', 'desc')
                            ->take(4)
                            ->pluck('estatus_pago')
                            ->toArray();

                        $recentEvaluationsCache[$iccid] = $recentEvaluations;
                    }

                    // Agregar la evaluación actual a las recientes
                    $currentEvaluations = array_merge([$estatusPago], $recentEvaluationsCache[$iccid]);
                    $currentEvaluations = array_slice($currentEvaluations, 0, 4); // Mantener solo 4

                    // Contar RECHAZADA seguidas
                    $rechazadasConsecutivas = 0;
                    foreach ($currentEvaluations as $eval) {
                        if ($eval === 'RECHAZADA') {
                            $rechazadasConsecutivas++;
                        } else {
                            break; // Si encuentra una no RECHAZADA, rompe la secuencia
                        }
                    }

                    // Actualizar cache para que siguientes registros procesados en esta misma importación
                    // tomen en cuenta las evaluaciones ya procesadas (incluida la actual)
                    $recentEvaluationsCache[$iccid] = $currentEvaluations;

                    // Marcar producto para actualización de advertencia
                    if ($rechazadasConsecutivas >= 2) {
                        $warningLevel = $rechazadasConsecutivas >= 3 ? 'peligro' : 'advertencia';

                        $productsToFlag[$productId] = [
                            'product' => $product,
                            'warning_level' => $warningLevel,
                            'rechazadas_consecutivas' => $rechazadasConsecutivas,
                            'evaluations' => $currentEvaluations
                        ];
                    }
                }

                // === VALIDACIÓN 5: Actualizar producto si corresponde ===
                if (
                    $estatusPago === 'PAGADA' &&
                    $product->activation_status === 'Pre-activado' &&
                    !isset($productsToUpdate[$productId])
                ) {

                    $productsToUpdate[$productId] = [
                        'product' => $product,
                        'detail_data' => $detail
                    ];
                }

                // === PREPARAR REGISTRO PARA INSERCIÓN ===
                $processed[] = [
                    'product_id' => $productId,
                    'filtro' => $detail['FILTRO'] ?? null,
                    'telefono' => $detail['TELEFONO'] ?? null,
                    'imei' => $detail['IMEI'] ?? null,
                    'iccid' => $iccid,
                    'estatus_lin' => $detail['ESTATUS LIN'] ?? null,
                    'movimiento' => $detail['MOVIMIENTO'] ?? null,
                    'fecha_activ' => !empty($detail['FECHA_ACTIV']) ?
                        $detail['FECHA_ACTIV'] : null,
                    'fecha_prim_llam' => !empty($detail['FECHA_PRIM_LLAM']) ?
                        $detail['FECHA_PRIM_LLAM'] : null,
                    'fecha_dol' => !empty($detail['FECHA DOL']) ?
                        $detail['FECHA DOL'] : null,
                    'estatus_pago' => $estatusPago,
                    'motivo_estatus' => $detail['MOTIVO_ESTATUS'] ?? null,
                    'monto_com' => !empty($detail['MONTO_COM']) ?
                        floatval($detail['MONTO_COM']) : null,
                    'tipo_comision' => $detail['TIPO_COMISION'] ?? null,
                    'evaluacion' => $evaluacion,
                    'fza_vta_pago' => $detail['FZA_VTA_PAGO'] ?? null,
                    'fecha_evaluacion' => !empty($fechaEvaluacion) ?
                        $fechaEvaluacion : null,
                    'folio_factura' => $detail['FOLIO FACTURA'] ?? null,
                    'fecha_publicacion' => !empty($detail['FECHA PUBLICACION']) ?
                        $detail['FECHA PUBLICACION'] : null,
                    'import_id' => $importId,
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            } catch (\Exception $e) {
                $errors[] = [
                    'index' => $index,
                    'iccid' => $iccid ?? null,
                    'telefono' => $detail['TELEFONO'] ?? null,
                    'message' => $e->getMessage()
                ];
            }
        }

        // === PROCESAR INSERCIONES ===
        $insertedCount = 0;
        if (empty($errors) || count($processed) > 0) {
            // Insertar en lotes para mejor performance
            $chunkSize = 500;
            $chunks = array_chunk($processed, $chunkSize);

            foreach ($chunks as $chunk) {
                try {
                    self::insert($chunk);
                    $insertedCount += count($chunk);
                } catch (\Exception $e) {
                    // Si falla un lote, agregar cada registro como error individual
                    foreach ($chunk as $record) {
                        $errors[] = [
                            'index' => 'batch_error',
                            'iccid' => $record['iccid'] ?? null,
                            'telefono' => $record['telefono'] ?? null,
                            'message' => 'Error en inserción por lote: ' . $e->getMessage()
                        ];
                    }
                }
            }

            // === ACTUALIZAR PRODUCTOS SI CORRESPONDE ===
            if (!empty($productsToUpdate)) {
                self::updateProductsFromDetalles(array_values($productsToUpdate));
            }

            // === ACTUALIZAR ADVERTENCIAS EN PRODUCTOS ===
            if (!empty($productsToFlag)) {
                self::updateProductWarnings(array_values($productsToFlag));
            }
        }

        return [
            'registros_procesados' => $insertedCount,
            'errores_encontrados' => $errors,
            'duplicados_encontrados' => $duplicates,
            'productos_actualizados' => count($productsToUpdate),
            'productos_marcados' => count($productsToFlag),
            'resumen_ejecucion' => [
                'total_registros' => count($details),
                'procesos_exitosos' => $insertedCount,
                'procesos_fallidos' => count($errors),
                'elementos_duplicados' => count($duplicates),
                'productos_afectados' => count($productsToUpdate),
                'productos_con_alertas' => count($productsToFlag),
                // 'listado_de_productos_con_alertas' => $productsToFlag,
            ]
        ];
    }

    /**
     * Checar si un registro es duplicado basado en criterios específicos
     */
    private static function checkDuplicateCriteria(array $detail): array
    {
        try {
            $iccid = trim($detail['ICCID'] ?? '');
            $estatusPago = trim($detail['ESTATUS_PAGO'] ?? '');
            $evaluacion = trim($detail['EVALUACION'] ?? '');
            $fechaEvaluacion = trim($detail['FECHA_EVALUACION'] ?? '');

            // Si falta algún campo clave, no se considera duplicado (será error de validación)
            if (empty($iccid) || empty($estatusPago)) {
                return ['is_duplicate' => false];
            }

            // Buscar registros existentes con el mismo ICCID
            $existingRecords = self::where('iccid', 'like', "$iccid%")
                ->orderBy('created_at', 'desc')
                ->take(10) // Revisar los últimos 10 registros
                ->get();

            foreach ($existingRecords as $existing) {
                // Criterio 1: Mismo ICCID + Mismo ESTATUS_PAGO + Misma EVALUACION + Misma FECHA_EVALUACION
                if (
                    $existing->estatus_pago === $estatusPago &&
                    $existing->evaluacion === $evaluacion &&
                    $existing->fecha_evaluacion == $fechaEvaluacion
                    // $existing->fecha_evaluacion == self::parseDate($fechaEvaluacion)
                ) {
                    return [
                        'is_duplicate' => true,
                        'reason' => 'Registro idéntico encontrado (ICCID, Estatus Pago, Evaluación, Fecha Evaluación)',
                        'existing_record' => [
                            'id' => $existing->id,
                            'created_at' => $existing->created_at,
                            'estatus_pago' => $existing->estatus_pago,
                            'evaluacion' => $existing->evaluacion,
                            'fecha_evaluacion' => $existing->fecha_evaluacion
                        ]
                    ];
                }

                // // Criterio 2: Mismo ICCID + Mismo ESTATUS_PAGO + Misma EVALUACION (sin fecha)
                // if (
                //     $existing->estatus_pago === $estatusPago &&
                //     $existing->evaluacion === $evaluacion &&
                //     empty($fechaEvaluacion)
                // ) {
                //     return [
                //         'is_duplicate' => true,
                //         'reason' => 'Registro similar encontrado (ICCID, Estatus Pago, Evaluación)',
                //         'existing_record' => [
                //             'id' => $existing->id,
                //             'created_at' => $existing->created_at,
                //             'estatus_pago' => $existing->estatus_pago,
                //             'evaluacion' => $existing->evaluacion
                //         ]
                //     ];
                // }

                // // Criterio 3: Mismo ICCID + Mismo ESTATUS_PAGO en las últimas 24 horas
                // if (
                //     $existing->estatus_pago === $estatusPago &&
                //     $existing->created_at->gt(now()->subHours(24))
                // ) {
                //     return [
                //         'is_duplicate' => true,
                //         'reason' => 'Mismo estatus de pago registrado en las últimas 24 horas',
                //         'existing_record' => [
                //             'id' => $existing->id,
                //             'created_at' => $existing->created_at,
                //             'estatus_pago' => $existing->estatus_pago
                //         ]
                //     ];
                // }
            }

            return ['is_duplicate' => false];
        } catch (\Exception $e) {
            Log::error("ProductDetail ~ checkDuplicateCriteria ~error" . $e->getMessage());
        }
    }

    /**
     * Actualizar advertencias en productos
     */
    private static function updateProductWarnings(array $productsToFlag): void
    {
        try {
            $executedAt = null;
            if (isset($request->executed_at)) $executedAt = $request->executed_at;

            foreach ($productsToFlag as $data) {
                $product = $data['product'];
                $warningLevel = $data['warning_level'];
                $rechazadasConsecutivas = $data['rechazadas_consecutivas'];

                // Actualizar el campo evaluations_rejected
                $product->update([
                    'evaluations_rejected' => $warningLevel,
                    // 'rejected_count' => $rechazadasConsecutivas,
                    // 'last_rejection_check' => now(),
                    'updated_at' => now()
                ]);

                $lastMovement = ProductMovement::where('product_id', $product->id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                // Registrar el movimiento
                ProductMovementService::log(
                    $product->id,
                    'Alerta de evaluaciones',
                    "El producto cuenta con $rechazadasConsecutivas evaluaciones RECHAZADAS seguidas - $warningLevel - en detalle de línea - ICCID: {$product->iccid}",
                    $lastMovement->origin,
                    $lastMovement->destination,
                    $executedAt
                );

                // // Opcional: Log del cambio
                // Log::info("Producto marcado con advertencia", [
                //     'product_id' => $product->id,
                //     'iccid' => $product->iccid,
                //     'warning_level' => $warningLevel,
                //     'rechazadas_consecutivas' => $rechazadasConsecutivas
                // ]);
            }
        } catch (\Exception $e) {
            Log::error("ProductDetail ~ updateProductWarnings ~error" . $e->getMessage());
        }
    }

    /**
     * Actualizar productos basado en los detalles con estatus PAGADA
     */
    protected static function updateProductsFromDetalles(array $productsToUpdate)
    {
        // $normalizerData = new NormalizerDateService();

        try {
            $executedAt = null;
            if (isset($request->executed_at)) $executedAt = $request->executed_at;

            foreach ($productsToUpdate as $item) {
                $product = $item['product'];
                $detail = $item['detail_data'];

                // Actualizar el producto
                $product->update([
                    'activation_status' => 'Activado',
                    // 'fecha' => !empty($detail['FECHA_ACTIV']) ? $normalizerData->normalizeDate($detail['FECHA_ACTIV']) : now(),
                    'updated_at' => now()
                ]);

                $lastMovement = ProductMovement::where('product_id', $product->id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                // Registrar el movimiento
                ProductMovementService::log(
                    $product->id,
                    'Activación automática',
                    "Producto activado automáticamente por pago confirmado en detalle de línea - ICCID: {$product->iccid}",
                    $lastMovement->origin,
                    'Activado',
                    $executedAt
                );

                // Opcional: Log adicional para debugging
                // Log::info("Producto activado automáticamente", [
                //     'product_id' => $product->id,
                //     'iccid' => $product->iccid,
                //     'estatus_pago' => $detail['ESTATUS_PAGO'],
                //     'fecha_activ' => $detail['FECHA_ACTIV'] ?? 'N/A'
                // ]);
            }
        } catch (\Exception $e) {
            Log::error("ProductDetail ~ updateProductsFromDetalles ~error" . $e->getMessage());
        }
    }

    /**
     * Estadísticas de importación
     */
    public static function getImportStats($importId)
    {
        return self::where('import_id', $importId)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('COUNT(DISTINCT iccid) as iccid_unicos')
            ->selectRaw('COUNT(DISTINCT telefono) as telefonos_unicos')
            ->selectRaw('SUM(CASE WHEN estatus_pago IS NOT NULL THEN 1 ELSE 0 END) as con_estatus_pago')
            ->selectRaw('AVG(monto_com) as promedio_comision')
            ->first();
    }
}
