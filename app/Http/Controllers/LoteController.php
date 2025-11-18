<?php

namespace App\Http\Controllers;

use App\Models\Lote;
use App\Models\ObjResponse;
use App\Models\VW_User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LoteController extends Controller
{
    /**
     * Mostrar lista de lotes.
     *
     * @return \Illuminate\Http\Response $response
     */
    public function index(Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();

        try {
            $auth = Auth::user();

            $list = Lote::with(['seller', 'creator'])
                ->orderBy('id', 'desc');

            // Si el usuario no es administrador, sólo mostrar activos
            if ($auth->role_id > 2) {
                $list = $list->where('active', true);
            }

            $list = $list->get();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "Petición satisfactoria | Lista de lotes.";
            $response->data["result"] = $list;
        } catch (\Exception $ex) {
            $msg = "LoteController ~ index ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Mostrar listado para un selector (por ejemplo, combos o selects).
     *
     * @return \Illuminate\Http\Response $response
     */
    public function selectIndex(Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();

        try {
            $list = Lote::where('active', true)
                ->with('seller:id,username,full_name')
                ->select('id', 'lote', 'seller_id', 'folio', 'lada', 'quantity', 'description', 'preactivation_date')
                ->orderBy('lote', 'asc')
                ->get()
                ->map(fn($lote) => [
                    'id' => $lote->id,
                    'label' => "#{$lote->lote} - {$lote->seller->full_name}",
                    'seller_id' => $lote->seller->id,
                    'folio' => $lote->folio,
                    'lada' => $lote->lada,
                    'quantity' => $lote->quantity,
                    'description' => $lote->description,
                    'preactivation_date' => $lote->preactivation_date,
                ]);

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "Petición satisfactoria | Lista de lotes.";
            $response->data["alert_text"] = "Lotes encontrados";
            $response->data["result"] = $list;
            $response->data["toast"] = false;
        } catch (\Exception $ex) {
            $msg = "LoteController ~ selectIndex ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Crear o actualizar un lote.
     *
     * @param \Illuminate\Http\Request $request
     * @param Int $id
     *
     * @return \Illuminate\Http\Response $response
     */
    public function createOrUpdate(Request $request, Response $response, Int $id = null)
    {
        $response->data = ObjResponse::DefaultResponse();

        try {
            $validator = $this->validateAvailableData($request, 'lotes', [
                [
                    'field' => 'lote',
                    'label' => 'Nombre del lote',
                    'rules' => ['required', 'string', 'max:100'],
                    'messages' => [
                        'required' => 'El nombre del lote es obligatorio.',
                        'string' => 'El nombre del lote debe ser texto.',
                        'max' => 'El nombre del lote no puede exceder 100 caracteres.'
                    ]
                ],
                [
                    'field' => 'seller_id',
                    'label' => 'Vendedor',
                    'rules' => ['nullable', 'integer'],
                    'messages' => [
                        'integer' => 'El ID del vendedor debe ser numérico.',
                    ]
                ]
            ], $id);

            if ($validator->fails()) {
                $response->data = ObjResponse::CatchResponse($validator->errors());
                $response->data["message"] = "Error de validación";
                $response->data["errors"] = $validator->errors();
                return response()->json($response);
            }

            $authUser = Auth::user();
            $lote = Lote::find($id);

            if (!$lote) {
                $lote = new Lote();
                $lote->created_by = $authUser->id;
                $lote->active = true;
            }

            // $lote->fill($request->only(['lote', 'seller_id', 'description', 'folio', 'lada', 'preactivation_date', 'quantity']));
            $lote->fill($request->all());
            $lote->save();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = $id > 0
                ? "Petición satisfactoria | Lote actualizado."
                : "Petición satisfactoria | Lote registrado.";
            $response->data["alert_text"] = $id > 0 ? "Lote actualizado" : "Lote registrado";
        } catch (\Exception $ex) {
            $msg = "LoteController ~ createOrUpdate ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Mostrar un lote por ID.
     *
     * @param   int $id
     * @return \Illuminate\Http\Response $response
     */
    public function show(Request $request, Response $response, Int $id, bool $internal = false)
    {
        $response->data = ObjResponse::DefaultResponse();

        try {
            $lote = Lote::with(['seller', 'creator'])->find($id);
            if ($internal) return $lote;

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "Petición satisfactoria | Lote encontrado.";
            $response->data["result"] = $lote;
        } catch (\Exception $ex) {
            $msg = "LoteController ~ show ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * "Eliminar" (cambiar estado active=false) un lote.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response $response
     */
    public function delete(Response $response, Int $id)
    {
        $response->data = ObjResponse::DefaultResponse();

        try {
            Lote::where('id', $id)
                ->update([
                    'active' => false,
                    'deleted_at' => now(),
                ]);

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "Petición satisfactoria | Lote eliminado.";
            $response->data["alert_text"] = "Lote eliminado";
        } catch (\Exception $ex) {
            $msg = "LoteController ~ delete ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Activar o desactivar un lote.
     *
     * @param  int $id
     * @param  string $active
     * @return \Illuminate\Http\Response $response
     */
    public function disEnable(Response $response, Int $id, string $active)
    {
        $response->data = ObjResponse::DefaultResponse();

        try {
            Lote::where('id', $id)
                ->update([
                    'active' => $active === "reactivar" ? 1 : 0
                ]);

            $description = $active === "reactivar" ? "reactivado" : "desactivado";
            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "Petición satisfactoria | Lote $description.";
            $response->data["alert_text"] = "Lote $description";
        } catch (\Exception $ex) {
            $msg = "LoteController ~ disEnable ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Eliminar múltiples registros (soft delete).
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response $response
     */
    public function deleteMultiple(Request $request, Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();

        try {
            $countDeleted = count($request->ids);

            Lote::whereIn('id', $request->ids)->update([
                'active' => false,
                'deleted_at' => now(),
            ]);

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = $countDeleted == 1
                ? "Petición satisfactoria | Lote eliminado."
                : "Petición satisfactoria | Lotes eliminados ($countDeleted).";
            $response->data["alert_text"] = $countDeleted == 1
                ? "Lote eliminado"
                : "Lotes eliminados ($countDeleted)";
        } catch (\Exception $ex) {
            $msg = "LoteController ~ deleteMultiple ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }
}
