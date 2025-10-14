<?php

namespace App\Http\Controllers;

use App\Models\Chip;
use App\Models\Lote;
use App\Models\LoteDetail;
use App\Models\ObjResponse;
use App\Models\VW_User;
use App\Services\ChipMovementService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LoteDetailController extends Controller
{
    /**
     * Mostrar lista de detalle de lotes.
     *
     * @return \Illuminate\Http\Response $response
     */
    public function index(Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();

        try {
            $auth = Auth::user();

            $list = LoteDetail::with(['lote.seller', 'chip', 'assigner'])
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
            $msg = "LoteDetailController ~ index ~ Hubo un error -> " . $ex->getMessage();
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
            $list = LoteDetail::where('active', true)
                ->with('seller:id,username,full_name')
                ->select('id', 'lote', 'seller_id')
                ->orderBy('lote', 'asc')
                ->get()
                ->map(fn($lote) => [
                    'id' => $lote->id,
                    'label' => "#{$lote->lote} - {$lote->seller->full_name}",
                    'seller_id' => $lote->seller->id
                ]);
            // ->with('seller:id,username,full_name')
            // ->select('id', DB::raw("CONCAT('#',lote,' - ', seller_id) as label"))
            // ->orderBy('lote', 'asc')
            // ->get()
            // ->map(fn($lote) => [
            //     'id' => $lote->id,
            //     'label' => "#{$lote->lote} - {$lote->seller->username}"
            // ]);

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "Petición satisfactoria | Lista de lotes.";
            $response->data["alert_text"] = "Lotes encontrados";
            $response->data["result"] = $list;
            $response->data["toast"] = false;
        } catch (\Exception $ex) {
            $msg = "LoteDetailController ~ selectIndex ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Mostrar detalles de lote por lote_id.
     *
     * @param   int $id
     * @return \Illuminate\Http\Response $response
     */
    public function show(Request $request, Response $response, Int $id, bool $internal = false)
    {
        $response->data = ObjResponse::DefaultResponse();

        try {
            $lote = LoteDetail::with(['lote.seller', 'chip', 'assigner'])->find($id);
            if ($internal) return $lote;

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "Petición satisfactoria | LoteDetail encontrado.";
            $response->data["result"] = $lote;
        } catch (\Exception $ex) {
            $msg = "LoteDetailController ~ show ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Mostrar detalles de lote por lote_id.
     *
     * @param   int $id
     * @return \Illuminate\Http\Response $response
     */
    public function showByLote(Request $request, Response $response, Int $loteId, bool $internal = false)
    {
        $response->data = ObjResponse::DefaultResponse();

        try {
            $query = LoteDetail::with(['lote.seller', 'chip', 'assigner'])
                ->where("lote_id", $loteId);

            // 🔍 Loguear SQL generado
            // Log::info("showByLote ~ SQL: " . $query->toSql(), $query->getBindings());

            // Luego ejecutar la consulta
            $lote = $query->get();

            if ($internal) return $lote;

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "Petición satisfactoria | LoteDetail encontrado.";
            $response->data["result"] = $lote;
        } catch (\Exception $ex) {
            $msg = "LoteDetailController ~ showByLote ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    public function updateLoteAssignment(Request $request, Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();

        try {
            // Validación estilo createOrUpdate
            // $validator = $this->validateAvailableData($request, 'chip_distribuciones', [
            //     [
            //         'field' => 'seller_id',
            //         'label' => 'Vendedor',
            //         'rules' => ['required', 'integer', 'exists:users,id'],
            //         'messages' => [
            //             'required' => 'El vendedor es obligatorio.',
            //             'integer' => 'El ID del vendedor debe ser un número.',
            //             'exists' => 'El vendedor no existe.'
            //         ]
            //     ],
            //     [
            //         'field' => 'chip_ids',
            //         'label' => 'Chips',
            //         'rules' => ['required'],
            //         'messages' => [
            //             'required' => 'Debe seleccionar al menos un chip.',
            //             // 'array' => 'Los chips deben enviarse en un array.'
            //         ]
            //     ]
            // ]);

            // if ($validator->fails()) {
            //     $response->data = ObjResponse::CatchResponse($validator->errors());
            //     $response->data["message"] = "Error de validación";
            //     return response()->json($response);
            // }

            $authUser = auth()->user();
            $loteId = $request->input('lote_id');
            $chipIds = $request->input('chip_ids', []);
            $lote = Lote::find($loteId);
            $seller = VW_User::find($lote->seller_id);

            // Obtener chips actualmente asignados al vendedor
            $currentChips = LoteDetail::where('lote_id', $loteId)->pluck('chip_id')->toArray();

            // Chips a asignar y desasignar
            $chipsToAssign = array_diff($chipIds, $currentChips);
            $chipsToUnassign = array_diff($currentChips, $chipIds);

            $assigned = [];
            $unassigned = [];

            DB::beginTransaction();

            // DESASIGNAR chips removidos
            if (!empty($chipsToUnassign)) {
                $chipsToRemove = LoteDetail::where('lote_id', $loteId)
                    ->whereIn('chip_id', $chipsToUnassign)
                    ->get();

                foreach ($chipsToRemove as $dist) {
                    $chip = Chip::find($dist->chip_id);
                    if ($chip && $chip->location_status === 'Asignado') {
                        $origin = $chip->location_status;
                        $chip->update(['location_status' => 'Stock']);

                        ChipMovementService::log(
                            $chip->id,
                            'Desasignación',
                            "Chip devuelto al almacén por {$authUser->username}",
                            $origin,
                            'Distribución'
                        );

                        $unassigned[] = $chip->id;
                    }

                    $dist->delete();
                }
            }

            // ASIGNAR chips nuevos
            if (!empty($chipsToAssign)) {
                $availableChips = Chip::whereIn('id', $chipsToAssign)->where('location_status', 'Stock')->get();

                foreach ($availableChips as $chip) {
                    LoteDetail::create([
                        'lote_id' => $loteId,
                        'chip_id' => $chip->id,
                        'assigned_at' => now(),
                        'assigned_by' => $authUser->id,
                        'active' => true
                    ]);

                    $origin = $chip->location_status;
                    $chip->update(['location_status' => 'Asignado']);

                    ChipMovementService::log(
                        $chip->id,
                        'Asignación',
                        "Chip asignado al vendedor {$seller->full_name}",
                        $origin,
                        'Distribución'
                    );

                    $assigned[] = $chip->id;
                }
            }

            DB::commit();

            // PREPARAR listados para TransferList
            $allChips = Chip::whereIn('id', array_merge($chipIds, $currentChips))->get();
            $left = $allChips->where('location_status', 'Stock')->map(fn($c) => $c->id)->values();
            $right = LoteDetail::where('lote_id', $loteId)->pluck('chip_id');

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "Asignaciones actualizadas correctamente.";
            $response->data["assigned"] = $assigned;
            $response->data["unassigned"] = $unassigned;
            $response->data["left"] = $left;
            $response->data["right"] = $right;
            $response->data["total_assigned"] = count($assigned);
            $response->data["total_unassigned"] = count($unassigned);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("ChipController ~ updateLoteAssignment ~ " . $th->getMessage());
            $response->data = ObjResponse::CatchResponse("Error al actualizar asignaciones -> " . $th->getMessage());
        }

        return response()->json($response, $response->data["status_code"]);
    }
}