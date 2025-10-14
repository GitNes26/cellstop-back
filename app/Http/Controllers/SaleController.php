<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\ObjResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaleController extends Controller
{
    /**
     * Mostrar lista de ventas.
     *
     * @return \Illuminate\Http\Response $response
     */
    public function index(Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $auth = Auth::user();
            $list = Sale::with(['chip', 'seller', 'pointOfSale'])
                ->orderBy('id', 'desc');

            if ($auth->role_id > 2) {
                $list = $list->where("active", true);
            }

            $list = $list->get();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Petición satisfactoria | Lista de ventas.';
            $response->data["result"] = $list;
        } catch (\Exception $ex) {
            $msg = "SaleController ~ index ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Mostrar listado para un selector.
     *
     * @return \Illuminate\Http\Response $response
     */
    public function selectIndex(Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $list = Sale::where('active', true)
                ->select('id', DB::raw("CONCAT(chip_id,' - ',buyer_name) as label"))
                ->orderBy('id', 'desc')
                ->get();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Petición satisfactoria | Lista de ventas.';
            $response->data["alert_text"] = "Ventas encontradas";
            $response->data["result"] = $list;
            $response->data["toast"] = false;
        } catch (\Exception $ex) {
            $msg = "SaleController ~ selectIndex ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Crear o Actualizar venta.
     *
     * @param \Illuminate\Http\Request $request
     * @param Int $id
     * @return \Illuminate\Http\Response $response
     */
    public function createOrUpdate(Request $request, Response $response, Int $id = null)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $validator = $this->validateAvailableData($request, 'sales', [
                [
                    'field' => 'buyer_phone',
                    'label' => 'Teléfono del comprador',
                    'rules' => ['string', 'max:10', 'min:10'],
                    'messages' => [
                        'string' => 'El número celular debe ser texto.',
                        'max' => 'El número celular no puede tener más de 10 caracteres.',
                        'min' => 'El número celular debe tener al menos 10 caracteres.',
                    ]
                ]
            ], $id);

            if ($validator->fails()) {
                $response->data = ObjResponse::CatchResponse($validator->errors());
                $response->data["message"] = "Error de validación";
                $response->data["errors"] = $validator->errors();
                return response()->json($response);
            }

            $sale = Sale::find($id);
            if (!$sale) $sale = new Sale();

            $sale->fill($request->except(['evidence_photo_file']));
            $sale->save();

            // Subida de evidencia, si se manda archivo
            if ($request->hasFile('evidence_photo_file')) {
                $this->ImageUp(
                    $request,
                    'evidence_photo_file',
                    "sales",
                    $sale->id,
                    'EVIDENCIA',
                    $id == null ? true : false,
                    "noImage.png",
                    $sale
                );
            }

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = $id > 0 ? 'Petición satisfactoria | venta editada.' : 'Petición satisfactoria | venta registrada.';
            $response->data["alert_text"] = $id > 0 ? "Venta editada" : "Venta registrada";
        } catch (\Exception $ex) {
            $msg = "SaleController ~ createOrUpdate ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Mostrar venta.
     *
     * @param   int $id
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response $response
     */
    public function show(Request $request, Response $response, Int $id, bool $internal = false)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $sale = Sale::find($id);
            if ($internal) return $sale;

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Petición satisfactoria | venta encontrada.';
            $response->data["result"] = $sale;
        } catch (\Exception $ex) {
            $msg = "SaleController ~ show ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * "Eliminar" (cambiar estado activo=0) venta.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response $response
     */
    public function delete(Response $response, Int $id)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            Sale::where('id', $id)
                ->update([
                    'active' => false,
                    'deleted_at' => date('Y-m-d H:i:s')
                ]);

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "Petición satisfactoria | venta eliminada.";
            $response->data["alert_text"] = "Venta eliminada";
        } catch (\Exception $ex) {
            $msg = "SaleController ~ delete ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * "Activar o Desactivar" (cambiar estado activo=1/0).
     *
     * @param  int $id
     * @param  string $active
     * @return \Illuminate\Http\Response $response
     */
    public function disEnable(Response $response, Int $id, string $active)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            Sale::where('id', $id)
                ->update([
                    'active' => $active === "reactivar" ? 1 : 0
                ]);

            $description = $active == "reactivar" ? 'reactivada' : 'desactivada';
            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "Petición satisfactoria | venta $description.";
            $response->data["alert_text"] = "Venta $description";
        } catch (\Exception $ex) {
            $msg = "SaleController ~ disEnable ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Eliminar uno o varios registros.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response $response
     */
    public function deleteMultiple(Request $request, Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $countDeleted = sizeof($request->ids);
            Sale::whereIn('id', $request->ids)->update([
                'active' => false,
                'deleted_at' => date('Y-m-d H:i:s'),
            ]);

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = $countDeleted == 1 ? 'Petición satisfactoria | registro eliminado.' : "Petición satisfactoria | registros eliminados ($countDeleted).";
            $response->data["alert_text"] = $countDeleted == 1 ? 'Registro eliminado' : "Registros eliminados ($countDeleted)";
        } catch (\Exception $ex) {
            $msg = "SaleController ~ deleteMultiple ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }
}
