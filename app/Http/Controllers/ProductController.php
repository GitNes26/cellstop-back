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
            $list = Product::with(['product_type', 'import', 'creator'])
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
                ->select('id as id', DB::raw("CONCAT(celular, ' - ',iccid,' - ',fecha) as label"), 'location_status')
                ->orderBy('celular', 'asc')
                ->get();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Petición satisfactoria | Lista de productos.';
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
    public function import(Request $request)
    {
        $response = ObjResponse::DefaultResponse();
        $data = $request->all();

        if (!is_array($data) || count($data) === 0) {
            $response["message"] = "No se recibieron registros válidos.";
            return response()->json($response, 400);
        }

        DB::beginTransaction();

        try {
            // Crear registro en la tabla imports
            $importController = new ImportController();
            $import = $importController->createOrUpdate($request[0]['fileData'], null, DB::class);
            $product_type = $request[0]['product_type'];

            $insertBatch = [];
            $insertedCount = 0;
            $duplicatedIccids = [];

            // Obtener ICCIDs ya existentes para evitar duplicados
            $existingIccids = Product::whereIn(
                'iccid',
                array_filter(array_column($data, 'ICCID'))
            )->pluck('iccid')->toArray();

            foreach ($data as $row) {
                $iccid = trim($row['ICCID'] ?? '');

                if (!$iccid) continue; // sin ICCID, se omite

                // Verificar duplicados
                if (in_array($iccid, $existingIccids)) {
                    $duplicatedIccids[] = $iccid;
                    continue;
                }

                // Crear registro nuevo
                $insertBatch[] = [
                    'region' => $row['REGION'] ?? null,
                    'celular' => $row['CELULAR'] ?? null,
                    'iccid' => $iccid,
                    'imei' => $row['IMEI'] ?? null,
                    'fecha' => isset($row['FECHA']) ? $row['FECHA'] : null,
                    'tramite' => $row['TRAMITE'] ?? null,
                    'estatus' => $row['ESTATUS'] ?? null,
                    'comentario' => $row['COMENTARIO'] ?? null,
                    'fza_vta_prepago' => $row['FUERZA DE VENTA PREPAGO'] ?? null,
                    'fza_vta_padre' => $row['FUERZA DE VENTA PADRE'] ?? null,
                    'usuario_externo' => $row['USUARIO'] ?? null,
                    'folio' => $row['FOLIO'] ?? null,
                    'producto' => $row['PRODUCTO'] ?? null,
                    'num_orden' => $row['NUM ORDEN'] ?? null,
                    'estatus_orden' => $row['ESTATUS ORDEN'] ?? null,
                    'motivo_error' => $row['MOTIVO ERROR'] ?? null,
                    'tipo_sim' => $row['TIPO SIM'] ?? null,

                    'model' => $row['MODELO'] ?? null,
                    'brand' => $row['MARCA'] ?? null,
                    'color' => $row['COLOR'] ?? null,

                    'product_type_id' => $product_type,
                    'import_id' => $import->id ?? null,
                ];

                // Insertar en lotes de 500
                if (count($insertBatch) >= 500) {
                    Product::insert($insertBatch);
                    $insertedCount += count($insertBatch);

                    // Registrar bitácora de movimientos
                    foreach ($insertBatch as $p) {
                        ProductMovementService::log(
                            null,
                            'Importación inicial',
                            'Producto importado desde archivo CSV',
                            'N/A',
                            'Stock',
                            ['iccid' => $p['iccid']]
                        );
                    }

                    $insertBatch = []; // limpiar buffer
                }
            }

            // Insertar los registros restantes
            if (!empty($insertBatch)) {
                Product::insert($insertBatch);
                $insertedCount += count($insertBatch);

                foreach ($insertBatch as $p) {
                    ProductMovementService::log(
                        null,
                        'Importación inicial',
                        'Producto importado desde archivo CSV',
                        'N/A',
                        'Stock',
                        ['iccid' => $p['iccid']]
                    );
                }
            }

            DB::commit();

            $response = ObjResponse::SuccessResponse();
            $response["message"] = "{$insertedCount} registros insertados correctamente.";

            // Agregar duplicados si existen
            if (count($duplicatedIccids) > 0) {
                $response["duplicados"] = $duplicatedIccids;
                $response["message"] .= " Se omitieron " . count($duplicatedIccids) . " ICCID(s) duplicado(s).";
            }

            return response()->json($response, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("ProductController ~ import ~ " . $e->getMessage());
            $response = ObjResponse::CatchResponse("Error al procesar los registros -> " . $e->getMessage());
            return response()->json($response, 500);
        }
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
