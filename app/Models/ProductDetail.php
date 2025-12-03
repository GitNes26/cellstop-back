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
        $productsToUpdate = [];

        foreach ($details as $index => $detail) {
            try {
                $iccid = trim($detail['ICCID']);
                $estatusPago = trim($detail['ESTATUS_PAGO'] ?? '');

                // Validar datos requeridos
                if (empty($iccid)) {
                    $errors[] = "Registro {$index}: ICCID es requerido";
                    continue;
                }

                // Buscar producto relacionado si existe product_id
                $product = null;
                $productId = null;
                if (!empty($detail['product_id'])) {
                    $product = Product::find($detail['product_id']);
                    // Log::info("Product->" . json_encode($product));
                    if ($product) {
                        // $errors[] = "Registro {$index}: Producto no encontrado";
                        // continue;
                        $productId = $product->id;
                    }
                } elseif (!empty($iccid)) {
                    $product = Product::where('iccid', $iccid)->first();
                    if (!$product) {
                        $errors[] = "Registro {$index}: Producto no encontrado";
                        continue;
                    }
                    $productId = $product->id;
                }

                // Verificar si debemos actualizar el producto
                if ($estatusPago === 'PAGADA' && $product->activation_status === 'Pre-activado' &&   !collect($productsToUpdate)->pluck('product.id')->contains($productId)) {
                    $productsToUpdate[] = [
                        'product' => $product,
                        'detail_data' => $detail
                    ];
                }

                // Crear el detalle de linea
                $processed[] = [
                    'product_id' => $productId,
                    'filtro' => $detail['FILTRO'] ?? null,
                    'telefono' => $detail['TELEFONO'] ?? null,
                    'imei' => $detail['IMEI'] ?? null,
                    'iccid' => $detail['ICCID'],
                    'estatus_lin' => $detail['ESTATUS_LIN'] ?? null,
                    'movimiento' => $detail['MOVIMIENTO'] ?? null,
                    'fecha_activ' => $detail['FECHA_ACTIV'] ?? null,
                    'fecha_prim_llam' => $detail['FECHA_PRIM_LLAM'] ?? null,
                    'fecha_dol' => $detail['FECHA_DOL'] ?? null,
                    'estatus_pago' => $detail['ESTATUS_PAGO'] ?? null,
                    'motivo_estatus' => $detail['MOTIVO_ESTATUS'] ?? null,
                    'monto_com' => $detail['MONTO_COM'] ?? null,
                    'tipo_comision' => $detail['TIPO_COMISION'] ?? null,
                    'evaluacion' => $detail['EVALUACION'] ?? null,
                    'fza_vta_pago' => $detail['FZA_VTA_PAGO'] ?? null,
                    'fecha_evaluacion' => $detail['FECHA_EVALUACION'] ?? null,
                    'folio_factura' => $detail['FOLIO_FACTURA'] ?? null,
                    'fecha_publicacion' => $detail['FECHA_PUBLICACION'] ?? null,

                    'import_id' => $importId,
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            } catch (\Exception $e) {
                $errors[] = "Registro {$index}: " . $e->getMessage();
            }
        }

        // Insertar todos los detalles
        if (empty($errors)) {
            self::insert($processed);

            // Actualizar productos después de insertar los detalles
            self::updateProductsFromDetalles($productsToUpdate);
        }

        return [
            'processed' => count($processed),
            'errors' => $errors,
            'products_updated' => count($productsToUpdate)
        ];
    }

    /**
     * Actualizar productos basado en los detalles con estatus PAGADA
     */
    protected static function updateProductsFromDetalles(array $productsToUpdate)
    {
        $normalizerData = new NormalizerDateService();

        foreach ($productsToUpdate as $item) {
            $product = $item['product'];
            $detail = $item['detail_data'];

            // Actualizar el producto
            $product->update([
                'activation_status' => 'Activado',
                'fecha' => !empty($detail['FECHA_ACTIV']) ? $normalizerData->normalizeDate($detail['FECHA_ACTIV']) : now(),

                'updated_at' => now()
            ]);

            // Registrar el movimiento
            ProductMovementService::log(
                $product->id,
                'Activación automática',
                "Producto activado automáticamente por pago confirmado en detalle de linea - ICCID: {$product->iccid}",
                'Pre-activado',
                'Activado',
                auth()->id()
            );

            // Opcional: Log adicional para debugging
            // Log::info("Producto activado automáticamente", [
            //     'product_id' => $product->id,
            //     'iccid' => $product->iccid,
            //     'estatus_pago' => $detail['ESTATUS_PAGO'],
            //     'fecha_activ' => $detail['FECHA_ACTIV'] ?? 'N/A'
            // ]);
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
