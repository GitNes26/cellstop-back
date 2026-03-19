<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Portability; // Asumo que tienes este modelo para registrar portabilidades
use App\Models\Import;
use App\Models\ObjResponse;
use App\Services\ProductMovementService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use function PHPUnit\Framework\isNull;

class PortabilityController extends Controller
{
    /**
     * Importar registros de portabilidad desde Excel
     */
    public function import(Request $request, Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();

        if (!$request->has('data') || !is_array($request->data) || count($request->data) === 0) {
            $response->data["alert_text"] = "No se recibieron registros válidos.";
            $response->data["message"] = "No se recibieron registros válidos.";
            return response()->json($response, 400);
        }

        $data = $request->data;
        $fileData = $request->fileData ?? null;

        // Log::info("data" . json_encode($data, true));
        // return;

        DB::beginTransaction();

        try {
            // Crear registro en tabla imports
            $importController = new ImportController();
            $import = $importController->createOrUpdate($fileData);
            $executedAt = null;
            if (isset($request->executed_at)) $executedAt = $request->executed_at;

            $processedCount = 0;
            $portedProducts = []; // Productos que se portaron
            $notFoundNumbers = []; // Números no encontrados en el sistema
            $alreadyPorted = []; // Productos ya portados
            $errors = []; // Otros errores

            // Obtener todos los números telefónicos del lote para consulta eficiente
            $allNumbers = array_filter(array_map(function ($row) {
                return trim($row['telefono'] ?? $row['TELEFONO'] ?? '');
            }, $data));

            // Buscar productos por números telefónicos
            $productsByPhone = Product::whereIn('celular', $allNumbers)
                ->where('active', true)
                ->get()
                ->keyBy('celular');

            // Procesar en lotes de 500 para mejor rendimiento
            $chunks = array_chunk($data, 500);

            foreach ($chunks as $chunkIndex => $chunk) {
                foreach ($chunk as $rowIndex => $row) {
                    $originalIndex = ($chunkIndex * 500) + $rowIndex;

                    try {
                        // Obtener número telefónico (aceptar diferentes nombres de columna)
                        $telefono = trim($row['telefono'] ?? $row['TELEFONO'] ?? $row['Telefono'] ?? $row['Número'] ?? '');

                        if (empty($telefono)) {
                            $errors[] = [
                                'index' => $originalIndex,
                                'telefono' => $telefono,
                                'message' => 'Número telefónico es requerido'
                            ];
                            continue;
                        }

                        // Buscar producto por número telefónico
                        $product = $productsByPhone[$telefono] ?? null;

                        if (!$product) {
                            $notFoundNumbers[] = [
                                'index' => $originalIndex,
                                'telefono' => $telefono,
                                'iccid' => $row['iccid'] ?? $row['ICCID'] ?? 'N/A',
                                'fecha_portabilidad' => $row['fecha'] ?? $row['FECHA'] ?? 'N/A'
                            ];
                            continue;
                        }

                        // Verificar si ya está portado
                        if ($product->activation_status === 'Portado') {
                            $alreadyPorted[] = [
                                'index' => $originalIndex,
                                'telefono' => $telefono,
                                'iccid' => $product->iccid,
                                'product_id' => $product->id,
                                'already_ported_at' => $product->updated_at
                            ];
                            continue;
                        }

                        // Guardar el estado anterior
                        $previousStatus = $product->activation_status;

                        // Actualizar producto a "Portado"
                        $product->update([
                            'activation_status' => 'Portado',
                            // 'fecha' => !empty($row['fecha']) ? $row['fecha'] : now()->format('Y-m-d'),
                            'evaluations_rejected' => null,
                            'updated_at' => now()
                        ]);

                        // Registrar movimiento
                        ProductMovementService::log(
                            $product->id,
                            'Portabilidad',
                            "Producto portado - Número: {$telefono}, ICCID: {$product->iccid}",
                            $previousStatus,
                            'Portado',
                            $row['fecha_portacion'] // $executedAt
                        );

                        // Opcional: Registrar en tabla de portabilidades
                        $this->createPortabilityRecord($product, $row, $import->id ?? null);

                        $portedProducts[] = [
                            'index' => $originalIndex,
                            'product_id' => $product->id,
                            'telefono' => $telefono,
                            'iccid' => $product->iccid,
                            'previous_status' => $previousStatus,
                            'ported_at' => now()
                        ];

                        $processedCount++;
                    } catch (\Exception $e) {
                        $errors[] = [
                            'index' => $originalIndex,
                            'telefono' => $telefono ?? 'N/A',
                            'message' => 'Error procesando registro: ' . $e->getMessage()
                        ];
                        continue;
                    }
                }
            }

            // Actualizar estadísticas de la importación
            if ($import) {
                $import->update([
                    'processed_count' => $processedCount,
                    'failed_count' => count($notFoundNumbers) + count($errors),
                    'notes' => json_encode([
                        'ported_products' => $portedProducts,
                        'not_found_numbers' => $notFoundNumbers,
                        'already_ported' => $alreadyPorted,
                        'errors' => $errors
                    ])
                ]);
            }

            DB::commit();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "Procesados {$processedCount} registros de portabilidad.";
            $response->data["alert_text"] = "{$processedCount} productos marcados como Portado.";

            // Agregar métricas detalladas
            $response->data["metrics"] = [
                'registros_totales' => count($data),
                'portados' => $processedCount,
                'no_encontrados' => count($notFoundNumbers),
                'portados_anteriormente' => count($alreadyPorted),
                'errores' => count($errors)
            ];

            // Opcional: retornar listas para el frontend
            if (!empty($notFoundNumbers)) {
                $response->data["not_found_numbers"] = array_slice($notFoundNumbers, 0, 50); // Limitar a 50 para no saturar
            }

            if (!empty($alreadyPorted)) {
                $response->data["already_ported"] = array_slice($alreadyPorted, 0, 50);
            }

            if (!empty($errors)) {
                $response->data["errors"] = array_slice($errors, 0, 50);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $msg = "PortabilityController ~ import ~ Hubo un error -> " . $e->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
            return response()->json($response, 500);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Crear Portabilidad manual
     */
    public function createMultipleManually(Request $request, Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();
        Log::info($request);
        $countRegisters = sizeof($request->ids);
        $executedAt = null;
        if (isset($request->ids['executed_at'])) $executedAt = $request->ids['executed_at'];
        Log::info($request->ids['ids']);
        // return
        // Log::info($request["executed_at"]);
        // Log::info("executedAt: " . $executedAt);

        // Log::info("registros: " . $countRegisters);
        DB::beginTransaction();

        try {
            $processedCount = 0;
            $alreadyPorted = [];


            // Buscar productos por su id
            $products = Product::whereIn('id', $request->ids['ids'])
                ->where('active', true)
                ->get();

            // Log::info("products: " . json_encode($products, true));

            $index = 0;
            foreach ($products as $product) {
                // Verificar si ya está portado
                if ($product->activation_status === 'Portado') {
                    $alreadyPorted[] = [
                        'index' => $index,
                        'telefono' => $product->telefono,
                        'iccid' => $product->iccid,
                        'product_id' => $product->id,
                        'already_ported_at' => $product->updated_at
                    ];
                    $index++;
                    continue;
                }

                // Guardar el estado anterior
                $previousStatus = $product->activation_status;

                // Actualizar producto a "Portado"
                $product->update([
                    'activation_status' => 'Portado',
                    // 'fecha' => now()->format('Y-m-d'),
                    'evaluations_rejected' => null,
                    'updated_at' => now()
                ]);

                // Registrar movimiento
                ProductMovementService::log(
                    $product->id,
                    'Portabilidad Manual',
                    "Producto portado manualmente por criterio de evaluaciones - Número: {$product->telefono}, ICCID: {$product->iccid}",
                    $previousStatus,
                    'Portado',
                    $executedAt
                );

                // Opcional: Registrar en tabla de portabilidades
                $this->createPortabilityRecord($product, null, $product->import_id ?? null);

                $index++;
                $processedCount++;
            }

            DB::commit();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "Procesados {$processedCount} registros portabilidad manual.";
            $response->data["alert_text"] = "{$processedCount} productos marcados como Portado Manual.";

            // Agregar métricas detalladas
            $response->data["metrics"] = [
                'registros_totales' => count($products),
                'portados' => $processedCount,
                'no_encontrados' => 0,
                'portados_anteriormente' => count($alreadyPorted),
                'errores' => 0
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            $msg = "PortabilityController ~ createMultipleManually ~ Hubo un error -> " . $e->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
            return response()->json($response, 500);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Crear registro en tabla de portabilidades
     */
    private function createPortabilityRecord(Product $product, array $row = null, $importId = null)
    {

        // Si tienes tabla 'portabilities', crea el registro
        try {
            // $fecha_activacion = !isNull($row) ? $row['fecha_activacion'] : (!empty($product->fecha) ? $product->fecha : null);
            $fecha_activacion = (!is_null($row) && isset($row['fecha_activacion']) && !empty($row['fecha_activacion']))
                ? $row['fecha_activacion']
                : ((!is_null($row) && isset($row['executed_at']) && !empty($row['executed_at']))
                    ? $row['executed_at']
                    : (!empty($product->fecha) ? $product->fecha : null));

            $fecha_portacion = !isNull($row) ? $row['fecha_portacion'] : (!empty($product->fecha) ? $product->fecha : nullnow()->format('Y-m-d'));

            Portability::create([
                'product_id' => $product->id,
                'phone_number' => $product->celular,
                // 'iccid' => $product->iccid,
                'activation_date' => $fecha_activacion,
                'portability_date' => $fecha_portacion,
                // 'operador_origen' => $row['operador_origen'] ?? $row['OPERADOR_ORIGEN'] ?? null,
                // 'operador_destino' => $row['operador_destino'] ?? $row['OPERADOR_DESTINO'] ?? null,
                // 'folio_portabilidad' => $row['folio'] ?? $row['FOLIO'] ?? null,
                // 'estatus_portabilidad' => $row['estatus'] ?? $row['ESTATUS'] ?? 'COMPLETADA',
                'import_id' => $importId,
                // 'created_by' => Auth::id(),
                'active' => true
            ]);
        } catch (\Exception $e) {
            Log::warning("No se pudo crear registro de portabilidad: " . $e->getMessage());
        }
    }

    /**
     * Obtener historial de portabilidades de un producto
     */
    public function getProductPortabilityHistory(Response $response, $productId)
    {
        $response->data = ObjResponse::DefaultResponse();

        try {
            $product = Product::find($productId);

            if (!$product) {
                $response->data = ObjResponse::CatchResponse("Producto no encontrado");
                $response->data["status_code"] = 404;
                return response()->json($response, 404);
            }

            $history = [];

            // Buscar movimientos de portabilidad
            $movements = DB::table('product_movements')
                ->where('product_id', $productId)
                ->where('action', 'Portabilidad')
                ->orderBy('created_at', 'desc')
                ->get();

            // Si existe tabla portabilities, obtener registros
            $portabilities = Portability::where('product_id', $productId)
                ->orderBy('created_at', 'desc')
                ->get();

            $history['portabilities'] = $portabilities;

            $history['movements'] = $movements;
            $history['product'] = [
                'id' => $product->id,
                'iccid' => $product->iccid,
                'telefono' => $product->celular,
                'current_status' => $product->activation_status,
                'ported_at' => $product->activation_status === 'Portado' ? $product->updated_at : null
            ];

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Historial de portabilidad del producto';
            $response->data["result"] = $history;
        } catch (\Exception $ex) {
            $msg = "PortabilityController ~ getProductPortabilityHistory ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Revertir portabilidad de un producto
     */
    public function revertPortability(Response $response, $productId)
    {
        $response->data = ObjResponse::DefaultResponse();
        DB::beginTransaction();

        try {
            $auth = Auth::user();
            $product = Product::find($productId);
            $executedAt = null;
            if (isset($request->executed_at)) $executedAt = $request->executed_at;

            if (!$product) {
                $response->data = ObjResponse::CatchResponse("Producto no encontrado");
                $response->data["status_code"] = 404;
                return response()->json($response, 404);
            }

            if ($product->activation_status !== 'Portado') {
                $response->data = ObjResponse::CatchResponse("El producto no está en estado Portado");
                return response()->json($response, 400);
            }

            // Determinar estado anterior (podrías guardarlo en un campo o inferirlo)
            $previousStatus = 'Activado'; // O 'Pre-activado' según tu lógica

            // Revertir estado
            $product->update([
                'activation_status' => $previousStatus,
                'updated_at' => now()
            ]);

            // Registrar movimiento de reversión
            ProductMovementService::log(
                $product->id,
                'Reversión de Portabilidad',
                "Portabilidad revertida por {$auth->name}",
                'Portado',
                $previousStatus,
                $executedAt
            );

            DB::commit();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Portabilidad revertida exitosamente';
            $response->data["alert_text"] = 'Estado del producto restaurado';
            $response->data["result"] = [
                'product_id' => $product->id,
                'iccid' => $product->iccid,
                'new_status' => $previousStatus
            ];
        } catch (\Exception $ex) {
            DB::rollBack();
            $msg = "PortabilityController ~ revertPortability ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Obtener reporte de portabilidades por fecha
     */
    public function getPortabilityReport(Response $response, Request $request)
    {
        $response->data = ObjResponse::DefaultResponse();

        try {
            $startDate = $request->get('start_date', now()->subMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', now()->format('Y-m-d'));

            $query = Product::where('activation_status', 'Portado')
                ->whereBetween('updated_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->with(['product_type', 'import']);

            if ($request->has('product_type_id')) {
                $query->where('product_type_id', $request->product_type_id);
            }

            $portedProducts = $query->get();

            $report = [
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ],
                'total_ported' => $portedProducts->count(),
                'by_product_type' => $portedProducts->groupBy('product_type.name')->map(function ($items) {
                    return $items->count();
                }),
                'by_day' => $portedProducts->groupBy(function ($item) {
                    return $item->updated_at->format('Y-m-d');
                })->map(function ($items) {
                    return $items->count();
                }),
                'products' => $portedProducts->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'iccid' => $product->iccid,
                        'telefono' => $product->celular,
                        'ported_at' => $product->updated_at,
                        'product_type' => $product->product_type->name ?? 'N/A',
                        'import' => $product->import->file_name ?? 'N/A'
                    ];
                })
            ];

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Reporte de portabilidades';
            $response->data["result"] = $report;
        } catch (\Exception $ex) {
            $msg = "PortabilityController ~ getPortabilityReport ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }
}