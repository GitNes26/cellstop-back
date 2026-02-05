<?php

namespace App\Http\Controllers;

use App\Models\ProductDetail;
use App\Models\ObjResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ProductDetailController extends Controller
{
    /**
     * Mostrar lista de historiales de productos.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Response $response, Request $request)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $auth = Auth::user();
            $list = ProductDetail::with([
                'product',
                'import',
                'import.uploadedByUser',
                'uploadedByUser'
            ])
                ->orderBy('created_at', 'desc');

            // Aplicar filtros desde request
            if ($request->has('iccid')) {
                $list->byIccid($request->iccid);
            }

            if ($request->has('telefono')) {
                $list->byTelefono($request->telefono);
            }

            if ($request->has('imei')) {
                $list->byImei($request->imei);
            }

            if ($request->has('estatus_pago')) {
                if (is_array($request->estatus_pago)) {
                    $list->whereIn('estatus_pago', $request->estatus_pago);
                } else {
                    $list->byEstatusPago($request->estatus_pago);
                }
            }

            if ($request->has('import_id')) {
                $list->byImport($request->import_id);
            }

            if ($request->has('product_id')) {
                $list->where('product_id', $request->product_id);
            }

            if ($request->has('search')) {
                $list->search($request->search);
            }

            if ($auth->role_id > 2 && empty($request)) {
                $list->where('active', true);
            }

            // Paginación o todos los registros
            if ($request->has('paginate') && $request->paginate) {
                $list = $list->paginate($request->get('per_page', 50));
            } else {
                $list = $list->get();
            }

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Petición satisfactoria | Lista de historiales de productos.';
            $response->data["result"] = $list;
        } catch (\Exception $ex) {
            $msg = "ProductDetailController ~ index ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Mostrar lista para selector.
     */
    public function selectIndex(Response $response, Request $request)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $list = ProductDetail::where('active', true)
                ->select(
                    'id',
                    DB::raw("
                        CONCAT_WS(
                            ' - ',
                            NULLIF(iccid, ''),
                            NULLIF(telefono, ''),
                            NULLIF(estatus_lin, ''),
                            IFNULL(
                                DATE_FORMAT(fecha_activ, '%Y/%m/%d'),
                                ''
                            )
                        ) as label
                    "),
                    'iccid',
                    'telefono',
                    'estatus_lin',
                    'fecha_activ'
                )
                ->orderBy('created_at', 'desc');

            if ($request->has('import_id')) {
                $list->byImport($request->import_id);
            }

            if ($request->has('product_id')) {
                $list->where('product_id', $request->product_id);
            }

            if ($request->has('estatus_pago')) {
                $list->byEstatusPago($request->estatus_pago);
            }

            $list = $list->get();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Petición satisfactoria | Lista de historiales para selector.';
            $response->data["alert_text"] = "Detalles encontrados";
            $response->data["result"] = $list;
            $response->data["toast"] = false;
        } catch (\Exception $ex) {
            $msg = "ProductDetailController ~ selectIndex ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Crear o Actualizar un nuevo historial de producto.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function createOrUpdate(Request $request, Response $response, Int $id = null)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $validator = $this->validateAvailableData($request, 'product_details', [
                [
                    'field' => 'iccid',
                    'label' => 'ICCID',
                    'rules' => ['required', 'string', 'max:30'],
                    'messages' => [
                        'required' => 'El ICCID es requerido.',
                        'string' => 'El ICCID debe ser texto.',
                        'max' => 'El ICCID no puede superar los 30 caracteres.'
                    ]
                ],
                [
                    'field' => 'product_id',
                    'label' => 'Producto',
                    'rules' => ['nullable', 'exists:products,id'],
                    'messages' => [
                        'exists' => 'El producto seleccionado no existe.'
                    ]
                ],
                [
                    'field' => 'import_id',
                    'label' => 'Importación',
                    'rules' => ['required', 'exists:imports,id'],
                    'messages' => [
                        'required' => 'La importación es requerida.',
                        'exists' => 'La importación seleccionada no existe.'
                    ]
                ]
            ], $id, true);

            if ($validator->fails()) {
                $response->data = ObjResponse::CatchResponse($validator->errors());
                $response->data["message"] = "Error de validación";
                $response->data["errors"] = $validator->errors();
                return response()->json($response);
            }

            $productDetail = ProductDetail::find($id);
            if (!$productDetail) $productDetail = new ProductDetail();

            $productDetail->fill($request->all());
            $productDetail->active = true;
            $productDetail->save();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = $id ? 'Petición satisfactoria | Detalle actualizado.' : 'Petición satisfactoria | Detalle registrado.';
            $response->data["alert_text"] = $id ? 'Detalle actualizado' : 'Detalle registrado';
        } catch (\Exception $ex) {
            $msg = "ProductDetailController ~ createOrUpdate ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Mostrar un historial específico.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Response $response, Int $id, bool $internal = false)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $productDetail = ProductDetail::with([
                'product',
                'import',
                'import.uploadedByUser',
                'uploadedByUser'
            ])->find($id);

            if ($internal) return $productDetail;

            if (!$productDetail) {
                $response->data = ObjResponse::CatchResponse("Detalle no encontrado.");
                $response->data["status_code"] = 404;
                return response()->json($response, 404);
            }

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Petición satisfactoria | Detalle encontrado.';
            $response->data["result"] = $productDetail;
        } catch (\Exception $ex) {
            $msg = "ProductDetailController ~ show ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Eliminar un historial (cambiar estado activo a false).
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function delete(Response $response, Int $id)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            ProductDetail::where('id', $id)
                ->update([
                    'active' => false,
                    'deleted_at' => now()
                ]);

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "Petición satisfactoria | Detalle eliminado.";
            $response->data["alert_text"] = "Detalle eliminado";
        } catch (\Exception $ex) {
            $msg = "ProductDetailController ~ delete ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Activar o desactivar historial.
     */
    public function disEnable(Response $response, Int $id, string $active)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            ProductDetail::where('id', $id)
                ->update([
                    'active' => $active === "reactivar" ? 1 : 0
                ]);

            $description = $active == "reactivar" ? 'reactivado' : 'desactivado';
            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "Petición satisfactoria | Detalle $description.";
            $response->data["alert_text"] = "Detalle $description";
        } catch (\Exception $ex) {
            $msg = "ProductDetailController ~ disEnable ~ Hubo un error -> " . $ex->getMessage();
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

            ProductDetail::whereIn('id', $request->ids)->update([
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
            $msg = "ProductDetailController ~ deleteMultiple ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Importar registros desde Excel en chunks (Carga Masiva)
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
        $importId = $request->import_id ?? null;

        DB::beginTransaction();

        try {
            // Si no se proporciona import_id, crear una nueva importación
            if (!$importId) {
                $importController = new ImportController();
                $import = $importController->createOrUpdate($fileData);
                $importId = $import->id ?? null;
            }

            if (!$importId) {
                throw new \Exception("No se pudo obtener un ID de importación válido.");
            }

            // Procesar datos usando el método del modelo
            $result = ProductDetail::processBulkData($data, $importId);
            Log::error("RESULT: " . json_encode($result));

            if (!empty($result['errores_encontrados'])) {
                // DB::rollBack();
                // $response->data = ObjResponse::CatchResponse("Errores en la carga masiva");
                $response->data = ObjResponse::SuccessResponse();
                $response->data["metrics"]["errors"] = $result['errores_encontrados'];
                $response->data["processed"] = $result['registros_procesados'];
                return response()->json($response, 200);
            }

            // // Actualizar estadísticas de la importación
            // $importController = new ImportController();
            // $importController->updateStats($importId);

            DB::commit();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "{$result['registros_procesados']} registros insertados correctamente.";
            $response->data["alert_text"] = "{$result['registros_procesados']} registros insertados correctamente.";
            $response->data["metrics"] = $result["resumen_ejecucion"];
            // $response->data["metrics"] = [
            //     'processed' => $result['registros_procesados'],
            //     'errors' => count($result['errores_encontrados'])
            // ];
        } catch (\Exception $e) {
            DB::rollBack();
            $msg = "ProductDetailController ~ import ~ Hubo un error -> " . $e->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
            return response()->json($response, 500);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Obtener estadísticas de una importación
     */
    public function getImportStats(Response $response, Int $importId)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $stats = ProductDetail::getImportStats($importId);

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Petición satisfactoria | Estadísticas de importación.';
            $response->data["result"] = $stats;
        } catch (\Exception $ex) {
            $msg = "ProductDetailController ~ getImportStats ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Buscar historiales por ICCID, teléfono o IMEI
     */
    public function search(Response $response, Request $request)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $searchTerm = $request->get('q');

            if (!$searchTerm) {
                $response->data = ObjResponse::CatchResponse("Término de búsqueda requerido.");
                return response()->json($response, 400);
            }

            $list = ProductDetail::with([
                'product',
                'import',
                'import.uploadedByUser'
            ])
                ->search($searchTerm)
                ->active()
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Petición satisfactoria | Resultados de búsqueda.';
            $response->data["result"] = $list;
        } catch (\Exception $ex) {
            $msg = "ProductDetailController ~ search ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Obtener historiales por producto específico
     */
    public function byProduct(Response $response, Int $productId)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $list = ProductDetail::with([
                'import',
            ])
                ->where('product_id', $productId)
                ->active()
                ->orderBy('created_at', 'desc')
                ->orderBy('fecha_evaluacion', 'desc')
                ->orderBy('evaluacion', 'desc')
                ->get();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Petición satisfactoria | Detalle del producto.';
            $response->data["result"] = $list;
        } catch (\Exception $ex) {
            $msg = "ProductDetailController ~ byProduct ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Método auxiliar para respuestas de advertencia
     */
    private function sendWarningResponse(Response $response, string $message)
    {
        $response->data = ObjResponse::DefaultResponse();
        $response->data["message"] = $message;
        $response->data["alert_text"] = $message;
        $response->data["status_code"] = 400;
        return response()->json($response, 400);
    }
}