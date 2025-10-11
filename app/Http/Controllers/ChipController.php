<?php

namespace App\Http\Controllers;

use App\Models\Chip;
use App\Models\ObjResponse;
use App\Services\ChipMovementService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ChipController extends Controller
{
    /**
     * Mostrar lista de chips.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $auth = Auth::user();
            $list = Chip::with(['product', 'import'])
                ->orderBy('id', 'desc');

            if ($auth->role_id > 2) {
                $list = $list->where('active', true);
            }

            $list = $list->get();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Petición satisfactoria | Lista de chips.';
            $response->data["result"] = $list;
        } catch (\Exception $ex) {
            $msg = "ChipController ~ index ~ Hubo un error -> " . $ex->getMessage();
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
            $list = Chip::where('active', true)
                ->select('id as id', DB::raw("CONCAT(filtro) as label"), 'location_status')
                ->orderBy('filtro', 'asc')
                ->get();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Petición satisfactoria | Lista de chips.';
            $response->data["alert_text"] = "Chips encontrados";
            $response->data["result"] = $list;
            $response->data["toast"] = false;
        } catch (\Exception $ex) {
            $msg = "ChipController ~ selectIndex ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Crear o Actualizar un nuevo chip.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function createOrUpdate(Request $request, Response $response, Int $id = null)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $validator = $this->validateAvailableData($request, 'chips', [
                [
                    'field' => 'telefono',
                    'label' => 'Teléfono',
                    'rules' => ['string', 'max:20'],
                    'messages' => [
                        'string' => 'El teléfono debe ser texto.',
                        'max' => 'El teléfono no puede superar los 20 caracteres.'
                    ]
                ],
                [
                    'field' => 'iccid',
                    'label' => 'ICCID',
                    'rules' => ['string', 'max:30'],
                    'messages' => [
                        'string' => 'El ICCID debe ser texto.',
                        'max' => 'El ICCID no puede superar los 30 caracteres.'
                    ]
                ]
            ], $id);

            if ($validator->fails()) {
                $response->data = ObjResponse::CatchResponse($validator->errors());
                $response->data["message"] = "Error de validación";
                $response->data["errors"] = $validator->errors();
                return response()->json($response);
            }

            $chip = Chip::find($id);
            if (!$chip) $chip = new Chip();

            $chip->fill($request->all());
            $chip->active = true;
            $chip->save();

            if ($id === null) {
                ChipMovementService::log(
                    $chip->id,
                    'Importación inicial',
                    'Chip importado desde archivo CSV',
                    'N/A',
                    'Stock'
                );
            }

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = $id ? 'Petición satisfactoria | Chip actualizado.' : 'Petición satisfactoria | Chip registrado.';
            $response->data["alert_text"] = $id ? 'Chip actualizado' : 'Chip registrado';
        } catch (\Exception $ex) {
            $msg = "ChipController ~ createOrUpdate ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Mostrar un chip específico.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Response $response, Int $id, bool $internal = false)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $chip = Chip::with(['product', 'import'])->find($id);
            if ($internal) return $chip;

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Petición satisfactoria | Chip encontrado.';
            $response->data["result"] = $chip;
        } catch (\Exception $ex) {
            $msg = "ChipController ~ show ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Eliminar un chip (cambiar estado activo a false).
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function delete(Response $response, Int $id)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            Chip::where('id', $id)
                ->update([
                    'active' => false,
                    'deleted_at' => now()
                ]);

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "Petición satisfactoria | Chip eliminado.";
            $response->data["alert_text"] = "Chip eliminado";
        } catch (\Exception $ex) {
            $msg = "ChipController ~ delete ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Activar o desactivar chip.
     */
    public function disEnable(Response $response, Int $id, string $active)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            Chip::where('id', $id)
                ->update([
                    'active' => $active === "reactivar" ? 1 : 0
                ]);

            $description = $active == "reactivar" ? 'reactivado' : 'desactivado';
            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "Petición satisfactoria | Chip $description.";
            $response->data["alert_text"] = "Chip $description";
        } catch (\Exception $ex) {
            $msg = "ChipController ~ disEnable ~ Hubo un error -> " . $ex->getMessage();
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

            Chip::whereIn('id', $request->ids)->update([
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
            $msg = "ChipController ~ deleteMultiple ~ Hubo un error -> " . $ex->getMessage();
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

        $transaction = new DB();
        $transaction::beginTransaction();
        try {
            //insertar en la tabla Imports
            // Log::error(json_encode($request[0]));
            $importController = new ImportController();
            $import = $importController->createOrUpdate($request[0]['fileData'], null, $transaction);

            // $rowsToInsert = [];
            $insertedCount = 0;

            foreach ($data as $index => $row) {
                Log::error("el row:" . json_encode($row));
                // Crear chip
                $chip = Chip::create([
                    "product_id" => 1,
                    "filtro" => $row["FILTRO"],
                    "telefono" => $row["TELEFONO"],
                    "imei" => $row["IMEI"],
                    "iccid" => $row["ICCID"],
                    "estatus_lin" => $row["ESTATUS LIN"] ?? null,
                    "movimiento" => $row["MOVIMIENTO"] ?? null,
                    "fecha_activ" => $row["FECHA_ACTIV"],
                    "fecha_prim_llam" => $row["FECHA_PRIM_LLAM"] ?? null,
                    "fecha_dol" => $row["FECHA DOL"] ?? null,
                    "estatus_pago" => $row["ESTATUS_PAGO"] ?? null,
                    "motivo_estatus" => $row["MOTIVO_ESTATUS"] ?? null,
                    "monto_com" => $row["MONTO_COM"] ?? null,
                    "tipo_comision" => $row["TIPO_COMISION"] ?? null,
                    "evaluacion" => $row["EVALUACION"] ?? null,
                    "fza_vta_pago" => $row["FZA_VTA_PAGO"] ?? null,
                    "fecha_evaluacion" => $row["FECHA EVALUACION"] ?? null,
                    "folio_factura" => $row["FOLIO FACTURA"] ?? null,
                    "fecha_publicacion" => $row["FECHA PUBLICACION"] ?? null,
                    "import_id" => $import->id ?? null,
                ]);

                // Registrar movimiento (bitácora)
                ChipMovementService::log(
                    $chip->id,
                    'Importación inicial',
                    'Chip importado desde archivo CSV',
                    'N/A',
                    'Stock'
                );

                $insertedCount++;
            }

            // 🚀 Inserción masiva
            // Chip::insert($rowsToInsert);

            $transaction::commit();

            $response = ObjResponse::SuccessResponse();
            $response["message"] = $insertedCount . " registros insertados correctamente.";
            return response()->json($response, 200);
        } catch (\Exception $e) {
            $transaction::rollBack();
            Log::error("ExcelImportController ~ import ~ " . $e->getMessage());
            $response = ObjResponse::CatchResponse("Error al procesar los registros -> " . $e->getMessage());
            return response()->json($response, 500);
        }
    }


    /**
     * Mostrar el historial de movimientos de un chip.
     *
     * @param int $id
     * @param \Illuminate\Http\Response $response
     * @return \Illuminate\Http\JsonResponse
     */
    public function movements(Response $response, int $id)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $chip = Chip::with(['movements.executer'])->find($id);

            if (!$chip) {
                $response->data = ObjResponse::CatchResponse("Chip no encontrado.");
                $response->data["status_code"] = 404;
                return response()->json($response, 404);
            }

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "Petición satisfactoria | historial de movimientos del chip.";
            $response->data["result"] = $chip->movements;
        } catch (\Exception $ex) {
            $msg = "ChipController ~ movements ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }
    /*  
    // después de crear o actualizar el chip:
    ChipMovementService::log(
        $chip->id,
        'Importación inicial',
        'Chip importado desde archivo CSV',
        'N/A',
        'Stock'
    );

    // al asignar a un vendeodr
    ChipMovementService::log(
        $chip->id,
        'Asignación a vendedor',
        "Chip asignado al vendedor {$seller->name}",
        'Stock',
        $seller->name
    );

    // devolucion o reasignacion
    ChipMovementService::log(
        $chip->id,
        'Devolución',
        "Chip devuelto al almacén por {$seller->name}",
        $seller->name,
        'Stock'
    );

    // venta
    ChipMovementService::log(
        $chip->id,
        'Venta final',
        'Chip vendido a cliente final',
        $seller->name ?? 'Stock',
        'Cliente final'
    );


    */
}
