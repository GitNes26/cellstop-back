<?php

namespace App\Http\Controllers;

use App\Models\PointOfSale;
use App\Models\ObjResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PointOfSaleController extends Controller
{
    /**
     * Mostrar lista de puntos de venta.
     *
     * @return \Illuminate\Http\Response $response
     */
    public function index(Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $auth = Auth::user();

            $list = PointOfSale::orderBy('id', 'desc');
            if ($auth->role_id > 2) $list = $list->where('active', true);
            $list = $list->get();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Petición satisfactoria | Lista de puntos de venta.';
            $response->data["result"] = $list;
        } catch (\Exception $ex) {
            $msg = "PointOfSaleController ~ index ~ Hubo un error -> " . $ex->getMessage();
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
            $list = PointOfSale::where('active', true)
                ->select('id', DB::raw("CONCAT(name, ' - ', address) as label"))
                ->orderBy('name', 'asc')
                ->get();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Petición satisfactoria | Lista de puntos de venta.';
            $response->data["alert_text"] = "Puntos de venta encontrados";
            $response->data["result"] = $list;
            $response->data["toast"] = false;
        } catch (\Exception $ex) {
            $msg = "PointOfSaleController ~ selectIndex ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Crear o Actualizar un punto de venta.
     *
     * @param \Illuminate\Http\Request $request
     * @param Int|null $id
     * 
     * @return \Illuminate\Http\Response $response
     */
    public function createOrUpdate(Request $request, Response $response, Int $id = null)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $validator = $this->validateAvailableData($request, 'points_of_sale', [
                [
                    'field' => 'name',
                    'label' => 'Nombre del punto de venta',
                    'rules' => ['required', 'string', 'max:255'],
                    'messages' => [
                        'required' => 'El nombre del punto de venta es obligatorio.',
                        'string' => 'El nombre debe ser texto.',
                        'max' => 'El nombre no puede tener más de 255 caracteres.',
                    ]
                ],
                [
                    'field' => 'contact_phone',
                    'label' => 'Teléfono de contacto',
                    'rules' => ['nullable', 'string', 'max:10', 'min:10'],
                    'messages' => [
                        'string' => 'El número de teléfono debe ser texto.',
                        'max' => 'El número de teléfono no puede tener más de 10 caracteres.',
                        'min' => 'El número de teléfono debe tener al menos 10 caracteres.',
                    ]
                ]
            ], $id);

            if ($validator->fails()) {
                $response->data = ObjResponse::CatchResponse($validator->errors());
                $response->data["message"] = "Error de validación";
                $response->data["errors"] = $validator->errors();
                return response()->json($response);
            }

            $point = PointOfSale::find($id);
            if (!$point) $point = new PointOfSale();

            $point->fill($request->all());
            $point->save();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = $id > 0
                ? 'Petición satisfactoria | Punto de venta editado.'
                : 'Petición satisfactoria | Punto de venta registrado.';
            $response->data["alert_text"] = $id > 0 ? "Punto de venta editado" : "Punto de venta registrado";
        } catch (\Exception $ex) {
            $msg = "PointOfSaleController ~ createOrUpdate ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Mostrar un punto de venta.
     *
     * @param  int $id
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response $response
     */
    public function show(Request $request, Response $response, Int $id, bool $internal = false)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $point = PointOfSale::find($id);
            if ($internal) return $point;

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Petición satisfactoria | Punto de venta encontrado.';
            $response->data["result"] = $point;
        } catch (\Exception $ex) {
            $msg = "PointOfSaleController ~ show ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * "Eliminar" (cambiar estado activo=0) punto de venta.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response $response
     */
    public function delete(Response $response, Int $id)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            PointOfSale::where('id', $id)
                ->update([
                    'active' => false,
                    'deleted_at' => date('Y-m-d H:i:s')
                ]);

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "Petición satisfactoria | Punto de venta eliminado.";
            $response->data["alert_text"] = "Punto de venta eliminado";
        } catch (\Exception $ex) {
            $msg = "PointOfSaleController ~ delete ~ Hubo un error -> " . $ex->getMessage();
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
            PointOfSale::where('id', $id)
                ->update([
                    'active' => $active === "reactivar" ? 1 : 0
                ]);

            $description = $active === "reactivar" ? 'reactivado' : 'desactivado';
            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "Petición satisfactoria | Punto de venta $description.";
            $response->data["alert_text"] = "Punto de venta $description";
        } catch (\Exception $ex) {
            $msg = "PointOfSaleController ~ disEnable ~ Hubo un error -> " . $ex->getMessage();
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
            PointOfSale::whereIn('id', $request->ids)->update([
                'active' => false,
                'deleted_at' => date('Y-m-d H:i:s'),
            ]);

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = $countDeleted == 1
                ? 'Petición satisfactoria | registro eliminado.'
                : "Petición satisfactoria | registros eliminados ($countDeleted).";
            $response->data["alert_text"] = $countDeleted == 1
                ? 'Registro eliminado'
                : "Registros eliminados ($countDeleted)";
        } catch (\Exception $ex) {
            $msg = "PointOfSaleController ~ deleteMultiple ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }
}