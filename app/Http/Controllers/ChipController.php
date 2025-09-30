<?php

namespace App\Http\Controllers;

use App\Models\Chip;
use App\Models\ObjResponse;
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
        // $chips = Chip::all();
        // return response()->json($chips);

        $response->data = ObjResponse::DefaultResponse();
        try {
            $auth = Auth::user();
            $list = Chip::orderBy('id', 'desc');
            if ($auth->role_id > 2) $list = $list->where("active", true);
            $list = $list->get();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Peticion satisfactoria | Lista de departamentos.';
            $response->data["result"] = $list;
        } catch (\Exception $ex) {
            $msg = "ChipContrller ~ index ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Crear un nuevo chip.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'iccid' => 'required|string|max:30|unique:chips,iccid',
            'imei' => 'nullable|string|max:30',
            'phone_number' => 'nullable|string|max:20',
            'operator' => 'nullable|string|max:50',
            'location_status' => 'required|in:stock,con_vendedor,distribuido',
            'activation_status' => 'required|in:virgen,pre_activado,activado,caducado',
            'active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $chip = Chip::create($request->all());
        return response()->json($chip, 201);
    }

    /**
     * Mostrar un chip específico.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $chip = Chip::findOrFail($id);
        return response()->json($chip);
    }

    /**
     * Actualizar un chip existente.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'sometimes|required|exists:products,id',
            'iccid' => 'sometimes|required|string|max:30|unique:chips,iccid,' . $id,
            'imei' => 'nullable|string|max:30',
            'phone_number' => 'nullable|string|max:20',
            'operator' => 'nullable|string|max:50',
            'location_status' => 'sometimes|required|in:stock,con_vendedor,distribuido',
            'activation_status' => 'sometimes|required|in:virgen,pre_activado,activado,caducado',
            'active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $chip = Chip::findOrFail($id);
        $chip->update($request->all());
        return response()->json($chip);
    }

    /**
     * Eliminar un chip (cambiar estado activo a false).
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $chip = Chip::findOrFail($id);
        $chip->active = false;
        $chip->save();
        return response()->json(['message' => 'Chip eliminado']);
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
            $rowsToInsert = [];

            foreach ($data as $index => $row) {
                // Log::error("el row:" . json_encode($row));
                // // Validación dinámica: reglas por campo
                // $validator = \Validator::make($row, [
                //     "FILTRO" => "nullable",
                //     "TELEFONO" => "required",
                //     "IMEI" => "required",
                //     "ICCID" => "required|min:10",
                //     "ESTATUS LIN" => "nullable|string|max:50",
                //     "MOVIMIENTO" => "nullable|string|max:50",
                //     "FECHA_ACTIV" => "nullable|date",
                //     "FECHA_PRIM_LLAM" => "nullable|date",
                //     "FECHA DOL" => "nullable|date",
                //     "ESTATUS_PAGO" => "nullable|string|max:50",
                //     "MOTIVO_ESTATUS" => "nullable|string|max:255",
                //     "MONTO_COM" => "nullable|numeric",
                //     "TIPO_COMISION" => "nullable|string|max:100",
                //     "EVALUACION" => "nullable|string|max:100",
                //     "FZA_VTA_PAGO" => "nullable|string|max:100",
                //     "FECHA EVALUACION" => "nullable|date",
                //     "FOLIO FACTURA" => "nullable|string|max:100",
                //     "FECHA PUBLICACION" => "nullable|date",
                // ]);

                // if ($validator->fails()) {
                //     DB::rollBack();
                //     $response = ObjResponse::CatchResponse("Error de validación en fila " . ($index + 2));
                //     $response["errors"] = $validator->errors();
                //     return response()->json($response, 422);
                // }

                $rowsToInsert[] = [
                    "product_id" => 1,
                    "FILTRO" => $row["FILTRO"],
                    "TELEFONO" => $row["TELEFONO"],
                    "IMEI" => $row["IMEI"],
                    "ICCID" => $row["ICCID"],
                    "ESTATUS_LIN" => $row["ESTATUS LIN"] ?? null,
                    "MOVIMIENTO" => $row["MOVIMIENTO"] ?? null,
                    "FECHA_ACTIV" => $row["FECHA_ACTIV"],
                    "FECHA_PRIM_LLAM" => $row["FECHA_PRIM_LLAM"] ?? null,
                    "FECHA_DOL" => $row["FECHA DOL"] ?? null,
                    "ESTATUS_PAGO" => $row["ESTATUS_PAGO"] ?? null,
                    "MOTIVO_ESTATUS" => $row["MOTIVO_ESTATUS"] ?? null,
                    "MONTO_COM" => $row["MONTO_COM"] ?? null,
                    "TIPO_COMISION" => $row["TIPO_COMISION"] ?? null,
                    "EVALUACION" => $row["EVALUACION"] ?? null,
                    "FZA_VTA_PAGO" => $row["FZA_VTA_PAGO"] ?? null,
                    "FECHA_EVALUACION" => $row["FECHA EVALUACION"] ?? null,
                    "FOLIO_FACTURA" => $row["FOLIO FACTURA"] ?? null,
                    "FECHA_PUBLICACION" => $row["FECHA PUBLICACION"] ?? null,
                    "created_at" => now(),
                    "updated_at" => now(),
                ];
            }

            // 🚀 Inserción masiva
            Chip::insert($rowsToInsert);

            DB::commit();

            $response = ObjResponse::SuccessResponse();
            $response["message"] = count($rowsToInsert) . " registros insertados correctamente.";
            return response()->json($response, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("ExcelImportController ~ import ~ " . $e->getMessage());
            $response = ObjResponse::CatchResponse("Error al procesar los registros -> " . $e->getMessage());
            return response()->json($response, 500);
        }
    }
}
