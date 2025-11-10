<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ObjResponse;
use App\Services\ProductMovementService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    /**
     * Mostrar lista de productos.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $auth = Auth::user();
            $list = Product::with(['product_type', 'import'])
                ->orderBy('id', 'desc');

            if ($auth->role_id > 2) {
                $list = $list->where('active', true);
            }

            $list = $list->get();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Petición satisfactoria | Lista de productos.';
            $response->data["result"] = $list;
        } catch (\Exception $ex) {
            $msg = "ProductController ~ index ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Mostrar lista para selector.
     */
    public function selectIndex(Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $list = Product::where('active', true)
                ->select(
                    'id',
                    DB::raw("
                        CONCAT_WS(
                            ' - ',
                            NULLIF(celular, ''),
                            NULLIF(iccid, ''),
                            IFNULL(
                                DATE_FORMAT(fecha, '%Y/%m/%d'),
                                ''
                            )
                        ) as label
                    "),
                    'location_status',
                    'activation_status'
                )
                ->orderBy('celular', 'asc')
                ->get();


            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Petición satisfactoria | Lista de productos para selector.';
            $response->data["alert_text"] = "Productos encontrados";
            $response->data["result"] = $list;
            $response->data["toast"] = false;
        } catch (\Exception $ex) {
            $msg = "ProductController ~ selectIndex ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Crear o Actualizar un nuevo producto.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function createOrUpdate(Request $request, Response $response, Int $id = null)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $validator = $this->validateAvailableData($request, 'products', [
                [
                    'field' => 'iccid',
                    'label' => 'ICCID',
                    'rules' => ['string', 'max:30'],
                    'messages' => [
                        'string' => 'El ICCID debe ser texto.',
                        'max' => 'El ICCID no puede superar los 30 caracteres.'
                    ]
                ],
                [
                    'field' => 'celular',
                    'label' => 'Celular',
                    'rules' => ['string', 'max:10'],
                    'messages' => [
                        'string' => 'El celular deben ser solo números.',
                        'max' => 'El celular no puede superar los 10 caracteres.'
                    ]
                ],
            ], $id, true);

            if ($validator->fails()) {
                $response->data = ObjResponse::CatchResponse($validator->errors());
                $response->data["message"] = "Error de validación";
                $response->data["errors"] = $validator->errors();
                return response()->json($response);
            }

            $product = Product::find($id);
            if (!$product) $product = new Product();

            $product->fill($request->all());
            $product->active = true;
            $product->save();

            if ($id === null) {
                ProductMovementService::log(
                    $product->id,
                    'Importación inicial',
                    'Producto importado desde archivo CSV',
                    'N/A',
                    'Stock'
                );
            }

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = $id ? 'Petición satisfactoria | Producto actualizado.' : 'Petición satisfactoria | Producto registrado.';
            $response->data["alert_text"] = $id ? 'Producto actualizado' : 'Producto registrado';
        } catch (\Exception $ex) {
            $msg = "ProductController ~ createOrUpdate ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Mostrar un producto específico.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Response $response, Int $id, bool $internal = false)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $product = Product::with(['product_type', 'import'])->find($id);
            if ($internal) return $product;

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Petición satisfactoria | Producto encontrado.';
            $response->data["result"] = $product;
        } catch (\Exception $ex) {
            $msg = "ProductController ~ show ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Eliminar un producto (cambiar estado activo a false).
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function delete(Response $response, Int $id)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            Product::where('id', $id)
                ->update([
                    'active' => false,
                    'deleted_at' => now()
                ]);

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "Petición satisfactoria | Producto eliminado.";
            $response->data["alert_text"] = "Producto eliminado";
        } catch (\Exception $ex) {
            $msg = "ProductController ~ delete ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Activar o desactivar producto.
     */
    public function disEnable(Response $response, Int $id, string $active)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            Product::where('id', $id)
                ->update([
                    'active' => $active === "reactivar" ? 1 : 0
                ]);

            $description = $active == "reactivar" ? 'reactivado' : 'desactivado';
            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "Petición satisfactoria | Producto $description.";
            $response->data["alert_text"] = "Producto $description";
        } catch (\Exception $ex) {
            $msg = "ProductController ~ disEnable ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Eliminar múltiples registros.
     */
    public function deleteMultiple(Request $request, Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $countDeleted = count($request->ids);

            Product::whereIn('id', $request->ids)->update([
                'active' => false,
                'deleted_at' => now()
            ]);

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = $countDeleted == 1
                ? 'Petición satisfactoria | Registro eliminado.'
                : "Petición satisfactoria | Registros eliminados ($countDeleted).";

            $response->data["alert_text"] = $countDeleted == 1
                ? 'Registro eliminado'
                : "Registros eliminados ($countDeleted)";
        } catch (\Exception $ex) {
            $msg = "ProductController ~ deleteMultiple ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Importar registros desde Excel en chunks
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
        $productTypeId = $request->product_type_id ?? null;

        $transaction = DB::class;
        $transaction::beginTransaction();
        // DB::beginTransaction();

        try {
            // Crear registro en tabla imports
            $importController = new ImportController();
            $import = $importController->createOrUpdate($fileData);

            $insertedCount = 0;
            $duplicatedIccids = [];
            $newProducts = [];

            // Obtener ICCIDs existentes
            $existingIccids = Product::whereIn(
                'iccid',
                array_filter(array_column($data, 'ICCID'))
            )->pluck('iccid')->toArray();

            // Procesar en lotes de 500
            $chunks = array_chunk($data, 500);

            foreach ($chunks as $chunk) {
                $batch = [];

                foreach ($chunk as $row) {
                    $iccid = trim($row['ICCID'] ?? '');
                    if (!$iccid) continue;

                    if (in_array($iccid, $existingIccids)) {
                        $duplicatedIccids[] = $iccid;
                        continue;
                    }

                    $batch[] = [
                        'region' => $row['Región'] ?? null,
                        'celular' => $row['Celular'] ?? null,
                        'iccid' => $iccid,
                        'imei' => $row['IMEI'] ?? null,
                        'fecha' => isset($row['Fecha']) ? $row['Fecha'] : null,
                        'tramite' => $row['Trámite'] ?? null,
                        'estatus' => $row['Estatus'] ?? null,
                        'comentario' => $row['Comentario'] ?? null,
                        'fza_vta_prepago' => $row['Fuerza de Venta Prepago'] ?? null,
                        'fza_vta_padre' => $row['Fuerza de Venta Padre'] ?? null,
                        'usuario' => $row['Usuario'] ?? null,
                        'folio' => $row['Folio'] ?? null,
                        'producto' => $row['Producto'] ?? null,
                        'num_orden' => $row['Núm Orden'] ?? null,
                        'estatus_orden' => $row['Estatus orden'] ?? null,
                        'motivo_error' => $row['Motivo error'] ?? null,
                        'tipo_sim' => $row['Tipo SIM'] ?? null,
                        'modelo' => $row['Modelo'] ?? null,
                        'marca' => $row['Marca'] ?? null,
                        'color' => $row['Color'] ?? null,
                        'product_type_id' => $productTypeId,
                        'import_id' => $import->id ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                Log::error($batch);

                if (!empty($batch)) {
                    // Insertar y recuperar IDs reales
                    DB::table('products')->insert($batch);

                    // Obtener IDs insertados (solo los nuevos ICCID)
                    $newlyInserted = Product::whereIn('iccid', array_column($batch, 'iccid'))->get(['id', 'iccid']);

                    foreach ($newlyInserted as $product) {
                        ProductMovementService::log(
                            $product->id,
                            'Importación inicial',
                            'Producto importado desde archivo Excel',
                            'N/A',
                            'Stock'
                        );
                    }

                    $insertedCount += count($batch);
                    $newProducts = array_merge($newProducts, $newlyInserted->toArray());
                }
            }

            DB::commit();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "{$insertedCount} registros insertados correctamente.";
            $response->data["alert_text"] = "{$insertedCount} registros insertados correctamente.";

            if (count($duplicatedIccids) > 0) {
                $response->data["duplicados"] = $duplicatedIccids;
                $response->data["message"] .= " Se omitieron " . count($duplicatedIccids) . " ICCID(s) duplicado(s).";
                $response->data["alert_text"] .= " Se omitieron " . count($duplicatedIccids) . " ICCID(s) duplicado(s).";
            }

            // $response->data["productos_insertados"] = $newProducts;

            return response()->json($response, 200);
        } catch (\Exception $e) {
            $transaction::rollBack();
            Log::error("ProductController ~ import ~ " . $e->getMessage());
            $response = ObjResponse::CatchResponse("Error al procesar los registros -> " . $e->getMessage());
            return response()->json($response, 500);
        }
    }


    /**
     * Pre Activar múltiples registros.
     */
    public function preActivation(Request $request, Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $iccidList = array_unique($request->data);
            $requestedCount = count($iccidList);

            // Validaciones
            if ($requestedCount === 0) {
                return $this->sendWarningResponse($response, 'No hay ICCIDs para procesar');
            }

            if ($requestedCount > 5000) {
                return $this->sendWarningResponse($response, 'Límite excedido: Máximo 5000 registros por lote');
            }

            DB::beginTransaction();

            $chunkSize = 500; // Procesar en chunks de 200 para mejor rendimiento
            $totalUpdated = 0;
            $notFoundIccids = [];
            $alreadyActivated = 0;

            // Procesar por chunks para mejor manejo de memoria
            collect($iccidList)->chunk($chunkSize)->each(function ($chunk) use (&$totalUpdated, &$notFoundIccids, &$alreadyActivated) {
                $iccidChunk = $chunk->toArray();

                // Obtener productos del chunk actual
                $products = Product::whereIn('iccid', $iccidChunk)
                    ->get(['id', 'iccid', 'activation_status'])
                    ->keyBy('iccid');

                // Identificar ICCIDs no encontrados en este chunk
                $foundIccids = $products->pluck('iccid')->toArray();
                $notFoundIccids = array_merge($notFoundIccids, array_diff($iccidChunk, $foundIccids));

                if ($products->isNotEmpty()) {
                    $productIds = $products->pluck('id')->toArray();

                    // Actualizar productos que no estén ya pre-activados
                    $chunkUpdated = Product::whereIn('id', $productIds)
                        ->where('activation_status', '!=', 'Pre-activado')
                        ->update([
                            'fecha' => now(),
                            'activation_status' => "Pre-activado",
                            'updated_at' => now()
                        ]);

                    $totalUpdated += $chunkUpdated;
                    $alreadyActivated += ($products->count() - $chunkUpdated);

                    // Crear logs para los productos actualizados en este chunk
                    $updatedProducts = $products->take($chunkUpdated);
                    foreach ($updatedProducts as $product) {
                        ProductMovementService::log(
                            $product->id,
                            'Pre-activación',
                            "Producto Pre-activado - ICCID: {$product->iccid}",
                            'Stock',
                            'Stock',
                            auth()->id()
                        );
                    }
                }
            });

            DB::commit();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = $alreadyActivated == 1
                ? 'Petición satisfactoria | Registro pre-activado.'
                : "Petición satisfactoria | Registros pre-activados ($alreadyActivated).";

            $response->data["alert_text"] = $alreadyActivated == 1
                ? 'Registro pre-activado'
                : "Registros pre-activados ($alreadyActivated)";

            $response->data["metrics"] = [
                'requested' => $requestedCount,
                'updated' => $totalUpdated,
                'not_found' => count($notFoundIccids),
                'already_activated' => $alreadyActivated
            ];
        } catch (\Exception $ex) {
            DB::rollBack();
            $msg = "ProductController ~ preActivation ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }




    /**
     * Mostrar el historial de movimientos de un producto.
     *
     * @param int $id
     * @param \Illuminate\Http\Response $response
     * @return \Illuminate\Http\JsonResponse
     */
    public function movements(Response $response, int $id)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $product = Product::with(['movements.executer'])->find($id);

            if (!$product) {
                $response->data = ObjResponse::CatchResponse("Producto no encontrado.");
                $response->data["status_code"] = 404;
                return response()->json($response, 404);
            }

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "Petición satisfactoria | historial de movimientos del producto.";
            $response->data["result"] = $product->movements;
        } catch (\Exception $ex) {
            $msg = "ProductController ~ movements ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }
    /*  
    // después de crear o actualizar el producto:
    ProductMovementService::log(
        $product->id,
        'Importación inicial',
        'Producto importado desde archivo CSV',
        'N/A',
        'Stock'
    );

    // al asignar a un vendeodr
    ProductMovementService::log(
        $product->id,
        'Asignación a vendedor',
        "Producto asignado al vendedor {$seller->name}",
        'Stock',
        $seller->name
    );

    // devolucion o reasignacion
    ProductMovementService::log(
        $product->id,
        'Devolución',
        "Producto devuelto al almacén por {$seller->name}",
        $seller->name,
        'Stock'
    );

    // venta
    ProductMovementService::log(
        $product->id,
        'Vendido',
        'Producto vendido a cliente final',
        $seller->name ?? 'Stock',
        'Cliente final'
    );


    */
}
