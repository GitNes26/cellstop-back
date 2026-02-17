<?php

namespace App\Http\Controllers;

use App\Models\Lote;
use App\Models\LoteDetail;
use App\Models\ObjResponse;
use App\Models\Product;
use App\Models\VW_User;
use App\Services\ProductMovementService;
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
    public function index(Response $response, Request $request)
    {
        $response->data = ObjResponse::DefaultResponse();

        try {
            $auth = Auth::user();

            $list = Lote::with(['seller', 'creator'])
                ->orderBy('id', 'desc');

            if ($request->has('seller_id')) {
                if (is_array($request->seller_id)) {
                    $list = $list->whereIn('seller_id', $request->seller_id);
                } else {
                    $list = $list->where('seller_id', $request->seller_id);
                }
            }

            if ($auth->role_id == 3) {
                $list = $list->where('seller_id', $auth->id);
            }

            // Si el usuario no es administrador, sólo mostrar activos
            if ($auth->role_id > 2) {
                $list = $list->where('active', true);
            }

            // Log::info('LoteController ~ index ~ SQL: ' . $list->toSql());
            // Log::info('LoteController ~ index ~ Bindings: ' . implode(', ', $list->getBindings()));
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
        $auth = Auth::user();

        try {
            $list = Lote::where('active', true)
                ->with('seller:id,username,full_name')
                ->select('id', 'lote', 'seller_id', 'folio', 'lada', 'quantity', 'description', 'preactivation_date')
                ->orderBy('lote', 'asc');

            if ($auth->role_id == 3) {
                $list = $list->where('seller_id', $auth->id);
            }

            $list = $list->get()
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
            $authUser = Auth::user();
            $executedAt = now();
            $unassigned = [];

            Lote::where('id', $id)
                ->update([
                    'active' => false,
                    'deleted_at' => $executedAt,
                ]);

            // if (!empty($productsToUnassign)) {
            // Log::info('LoteDetailController ~ updateLoteAssignment ~ DESASIGNAR productos');
            $productsToRemove = LoteDetail::where('lote_id', $id)
                ->where('unassigned', '!=', true)
                ->get();
            // Log::info('LoteDetailController ~ updateLoteAssignment ~ productsToRemove:', json_decode($productsToRemove, true));

            foreach ($productsToRemove as $dist) {
                // Log::info('LoteDetailController ~ updateLoteAssignment ~ DESASIGNAR producto_id: ' . $dist->product_id);
                $product = Product::find($dist->product_id);
                if ($product) { // && $product->location_status === 'Asignado') {
                    // Log::info('LoteDetailController ~ updateLoteAssignment ~ DESASIGNAR producto encontrado:', ['product_id' => $product->id, 'iccid' => $product->iccid, 'location_status' => $product->location_status]);
                    $origin = $product->location_status;
                    $product->update(['location_status' => 'Stock']);

                    $dist->update([
                        'unassigned' => true,
                    ]);

                    ProductMovementService::log(
                        $product->id,
                        'Desasignación',
                        "Producto devuelto al almacén por eliminacion de lote por {$authUser->username}",
                        $origin,
                        'Stock',
                        $executedAt
                    );

                    $unassigned[] = $product->id;
                }
            }
            // }

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "Petición satisfactoria | Lote eliminado.";
            $response->data["alert_text"] = "Lote eliminado, productos desasignados: " . (isset($unassigned) ? count($unassigned) : 0);
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


    /**
     * Mostrar lista de lotes.
     *
     * @return \Illuminate\Http\Response $response
     */
    public function indexByUserId(Response $response, int $seller_id = 0)
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

            if ($seller_id > 0) {
                $list = $list->where('seller_id', $seller_id);
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
}
