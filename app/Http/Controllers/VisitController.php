<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use App\Models\ObjResponse;
use App\Models\PointOfSale;
use App\Models\Product;
use App\Services\ProductMovementService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VisitController extends Controller
{
    /**
     * Mostrar lista de visitas.
     */
    public function index(Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $auth = Auth::user();

            $list = Visit::with(['products', 'seller', 'point_of_sale'])
                ->orderBy('id', 'desc');

            if ($auth->role_id > 2) {
                $list = $list->where("active", true);
            }

            $list = $list->get();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Petición satisfactoria | Lista de visitas.';
            $response->data["result"] = $list;
        } catch (\Exception $ex) {
            $msg = "VisitController ~ index ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Listado para select.
     */
    public function selectIndex(Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $list = Visit::where('active', true)
                ->select(
                    'id',
                    DB::raw("CONCAT('Visita ', id, ' - ', visit_type, ' - ', IFNULL(contact_name, 'Sin contacto')) as label")
                )
                ->orderBy('id', 'desc')
                ->get();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Petición satisfactoria | Lista de visitas.';
            $response->data["alert_text"] = "Visitas encontradas";
            $response->data["result"] = $list;
            $response->data["toast"] = false;
        } catch (\Exception $ex) {
            $msg = "VisitController ~ selectIndex ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Crear o actualizar visita.
     */
    public function createOrUpdate(Request $request, Response $response, Int $id = null)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            // Validaciones básicas
            $validator = $this->validateAvailableData($request, 'visits', [
                // [
                //     'field' => 'contact_phone',
                //     'label' => 'Teléfono de contacto',
                //     'rules' => ['nullable', 'string', 'max:10', 'min:10'],
                //     'messages' => [
                //         'string' => 'El número celular debe ser texto.',
                //         'max' => 'El número celular no puede tener más de 10 caracteres.',
                //         'min' => 'El número celular debe tener al menos 10 caracteres.',
                //     ]
                // ],
                [
                    'field' => 'visit_type',
                    'label' => 'Tipo de visita',
                    'rules' => ['required', 'in:Distribución,Monitoreo']
                ]
            ], $id);

            if ($validator->fails()) {
                $response->data = ObjResponse::CatchResponse($validator->errors());
                $response->data["message"] = "Error de validación";
                $response->data["errors"] = $validator->errors();
                return response()->json($response);
            }

            DB::beginTransaction();

            $visit = Visit::find($id);
            if (!$visit) $visit = new Visit();

            // Si se recibe un array de productos, guardarlo como JSON
            if ($request->has('product_ids') && is_array($request->product_ids)) {
                $request->merge([
                    'product_ids' => json_encode($request->product_ids)
                ]);
            }

            $visit->fill($request->except(['evidence_photo_file']));

            $visit->save();

            // Subida de evidencia (si aplica)
            if ($request->hasFile('evidence_photo_file')) {
                $this->ImageUp(
                    $request,
                    'evidence_photo_file',
                    "visits",
                    $visit->id,
                    'EVIDENCIA',
                    $id == null ? true : false,
                    "noImage.png",
                    $visit
                );
            }

            // //buscar productos por su id ($request->product_ids = "[4,6,8]") hay que tratarlo como array
            // // Buscar producto relacionado si existe product_id
            // $product = Product::find($id);
            // if (!$product) $product = new Product();
            // $product->location_status = 'Distribuido';

            // $pos = PointOfSale::find($request->pos_id);

            // if ($id === null) {
            //     ProductMovementService::log(
            //         $product->id,
            //         'Distribuido',
            //         "El producto se encuentra en el punto de venta $pos->name",
            //         'Asignado',
            //         'Distribuido'
            //     );
            // }
            // product_ids puede venir como string "[4,6,8]" o como array [4,6,8]
            $productsIds = is_array($request->product_ids)
                ? $request->product_ids
                : json_decode($request->product_ids, true);

            // if (empty($productsIds)) {
            //     return response()->json([
            //         "success" => false,
            //         "message" => "No se recibieron productos"
            //     ]);
            // }

            $pos = PointOfSale::find($request->pos_id);

            foreach ($productsIds as $id) {

                $product = Product::find($id);
                if (!$product) continue;

                $product->location_status = 'Distribuido';
                $product->save();

                ProductMovementService::log(
                    $product->id,
                    'Distribución',
                    "El producto se encuentra en el punto de venta $pos->name",
                    'Asignado',
                    'Distribuido'
                );
            }


            DB::commit();


            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = $id > 0 ? 'Petición satisfactoria | visita editada.' : 'Petición satisfactoria | visita registrada.';
            $response->data["alert_text"] = $id > 0 ? "Visita editada" : "Visita registrada";
        } catch (\Exception $ex) {
            DB::rollBack();
            $msg = "VisitController ~ createOrUpdate ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Mostrar detalle de una visita.
     */
    public function show(Request $request, Response $response, Int $id, bool $internal = false)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $visit = Visit::with(['products', 'seller', 'point_of_sale'])->find($id);
            if ($internal) return $visit;

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Petición satisfactoria | visita encontrada.';
            $response->data["result"] = $visit;
        } catch (\Exception $ex) {
            $msg = "VisitController ~ show ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Eliminar visita (soft delete).
     */
    public function delete(Response $response, Int $id)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            Visit::where('id', $id)
                ->update([
                    'active' => false,
                    'deleted_at' => now()
                ]);

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "Petición satisfactoria | visita eliminada.";
            $response->data["alert_text"] = "Visita eliminada";
        } catch (\Exception $ex) {
            $msg = "VisitController ~ delete ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Activar o desactivar visita.
     */
    public function disEnable(Response $response, Int $id, string $active)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            Visit::where('id', $id)
                ->update([
                    'active' => $active === "reactivar" ? 1 : 0
                ]);

            $description = $active == "reactivar" ? 'reactivada' : 'desactivada';
            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "Petición satisfactoria | visita $description.";
            $response->data["alert_text"] = "Visita $description";
        } catch (\Exception $ex) {
            $msg = "VisitController ~ disEnable ~ Hubo un error -> " . $ex->getMessage();
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
            $countDeleted = sizeof($request->ids);
            Visit::whereIn('id', $request->ids)->update([
                'active' => false,
                'deleted_at' => now(),
            ]);

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = $countDeleted == 1 ? 'Petición satisfactoria | registro eliminado.' : "Petición satisfactoria | registros eliminados ($countDeleted).";
            $response->data["alert_text"] = $countDeleted == 1 ? 'Registro eliminado' : "Registros eliminados ($countDeleted)";
        } catch (\Exception $ex) {
            $msg = "VisitController ~ deleteMultiple ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }

        return response()->json($response, $response->data["status_code"]);
    }
}
