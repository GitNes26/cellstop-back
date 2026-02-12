<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Lote;
use App\Models\LoteDetail;
use App\Models\ObjResponse;
use App\Models\VW_User;
use App\Services\ProductMovementService;
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

            $list = LoteDetail::with(['lote.seller', 'product', 'assigner'])
                ->where('unassigned', false)
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
                ->where('unassigned', false)
                ->select('id', 'lote', 'seller_id')
                ->orderBy('lote', 'asc')
                ->get()
                ->map(fn($lote) => [
                    'id' => $lote->id,
                    'label' => "{$lote->lote} - {$lote->seller->full_name}",
                    'seller_id' => $lote->seller->id,
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
            $lote = LoteDetail::with(['lote.seller', 'product', 'assigner'])->find($id);
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
            $query = LoteDetail::with(['lote.seller', 'product', 'assigner'])
                ->where('unassigned', false)
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
            // $validator = $this->validateAvailableData($request, 'product_distribuciones', [
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
            //         'field' => 'product_ids',
            //         'label' => 'Productos',
            //         'rules' => ['required'],
            //         'messages' => [
            //             'required' => 'Debe seleccionar al menos un producto.',
            //             // 'array' => 'Los productos deben enviarse en un array.'
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
            $productIds = $request->input('product_ids', []);
            $lote = Lote::find($loteId);
            $seller = VW_User::find($lote->seller_id);
            $executedAt = null;
            if (isset($request->executed_at)) $executedAt = $request->executed_at;

            // Obtener productos actualmente asignados al vendedor
            $currentProducts = LoteDetail::where('lote_id', $loteId)
                ->where('unassigned', false)
                ->pluck('product_id')->toArray();
            $currentUnassignedProducts = LoteDetail::where('lote_id', $loteId)
                ->where('unassigned', true)
                ->pluck('product_id')->toArray();

            // Productos a asignar y desasignar
            $productsToAssign = array_diff($productIds, $currentProducts);
            $productsToUnassign = array_diff($currentProducts, $productIds);

            // Log::info('LoteDetailController ~ updateLoteAssignment ~ request->all():', $request->all());
            // Log::info('LoteDetailController ~ updateLoteAssignment ~ currentProducts:', $currentProducts);
            // Log::info('LoteDetailController ~ updateLoteAssignment ~ currentUnassignedProducts:', $currentUnassignedProducts);
            // Log::info('LoteDetailController ~ updateLoteAssignment ~ productsToAssign:', $productsToAssign);
            // Log::info('LoteDetailController ~ updateLoteAssignment ~ productsToUnassign:', $productsToUnassign);

            $assigned = [];
            $unassigned = [];

            DB::beginTransaction();

            // DESASIGNAR productos removidos
            if (!empty($productsToUnassign)) {
                $productsToRemove = LoteDetail::where('lote_id', $loteId)
                    ->where('unassigned', false)
                    ->whereIn('product_id', $productsToUnassign)
                    ->get();

                foreach ($productsToRemove as $dist) {
                    $product = Product::find($dist->product_id);
                    if ($product && $product->location_status === 'Asignado') {
                        $origin = $product->location_status;
                        $product->update(['location_status' => 'Stock']);

                        $dist->update([
                            'unassigned' => true,
                        ]);

                        ProductMovementService::log(
                            $product->id,
                            'Desasignación',
                            "Producto devuelto al almacén por {$authUser->username}",
                            $origin,
                            'Stock',
                            $executedAt
                        );

                        $unassigned[] = $product->id;
                    }

                    // $dist->delete();
                }
            }

            // ASIGNAR productos nuevos
            if (!empty($productsToAssign)) {

                foreach ($currentUnassignedProducts as $unassignedProductId) {
                    if (in_array($unassignedProductId, $productsToAssign)) {
                        // Si el producto desasignado está en la lista de asignación, editar el registro en lugar de crear uno nuevo
                        $detail = LoteDetail::where('lote_id', $loteId)
                            ->where('product_id', $unassignedProductId)
                            ->where('unassigned', true)
                            ->first();
                        if ($detail) {
                            $detail->update([
                                'unassigned' => false,
                            ]);
                        }
                    }
                }

                $availableProducts = Product::whereIn('id', $productsToAssign)->get(); #->where('location_status', 'Stock')->get();
                foreach ($availableProducts as $product) {
                    // si hay registrosen LoteDetail pero están como desasignados, reactivar ese registro
                    $existingDetail = LoteDetail::where('lote_id', $loteId)
                        ->where('product_id', $product->id)
                        ->where('active', true)
                        // ->where('unassigned', true)
                        ->first();

                    if ($existingDetail) {
                        $existingDetail->update([
                            'unassigned' => false,
                        ]);
                    } else {
                        LoteDetail::create([
                            'lote_id' => $loteId,
                            'product_id' => $product->id,
                            'assigned_at' => now(),
                            'assigned_by' => $authUser->id,
                            'active' => true
                        ]);
                    }
                    $origin = $product->location_status;
                    $product->update(['location_status' => 'Asignado']);

                    ProductMovementService::log(
                        $product->id,
                        'Asignación',
                        "Producto asignado al vendedor {$seller->full_name}",
                        $origin,
                        'Asignado',
                        $executedAt
                    );

                    // // Opcional: Log adicional para debugging
                    // Log::info("ASIGNAR producto", [
                    //     'product_id' => $product->id,
                    //     'iccid' => $product->iccid,
                    //     'Asignación',
                    //     "Producto asignado al vendedor {$seller->full_name}",
                    //     $origin,
                    //     'Asignado'
                    // ]);

                    $assigned[] = $product->id;
                }
            }

            DB::commit();

            // PREPARAR listados para TransferList
            $allProducts = Product::whereIn('id', array_merge($productIds, $currentProducts))->get();
            $left = $allProducts->where('location_status', 'Stock')->map(fn($c) => $c->id)->values();
            $right = LoteDetail::where('lote_id', $loteId)
                ->where('unassigned', false)
                ->pluck('product_id');

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
            Log::error("ProductController ~ updateLoteAssignment ~ " . $th->getMessage());
            $response->data = ObjResponse::CatchResponse("Error al actualizar asignaciones -> " . $th->getMessage());
        }

        return response()->json($response, $response->data["status_code"]);
    }

    public function createMultipleManually(Request $request, Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();
        $authUser = auth()->user();


        $countRegisters = sizeof($request->ids);
        $executedAt = null;
        if (isset($request->ids['executed_at'])) $executedAt = $request->ids['executed_at'];
        // Log::info($request->ids);
        // Log::info($request["executed_at"]);
        // Log::info("executedAt: " . $executedAt);

        // Log::info("registros: " . $countRegisters);
        DB::beginTransaction();

        try {
            $processedCount = 0;
            $alreadyPorted = [];


            // Buscar productos por su id
            $products = Product::whereIn('id', $request->ids)
                ->where('active', true)
                ->get();

            // Log::info("products: " . json_encode($products, true));

            $index = 0;
            foreach ($products as $product) {
                // Guardar el estado anterior
                $previousStatus = $product->location_status;

                // Obtener Lote
                $lote = Lote::find($request->lote_id);
                //Obtener vendedor
                $seller = VW_User::find($lote->seller_id);

                // Actualizar producto a "Asignado", si es la fecha de ejecucion es mayor a la fecha de actualizacion del porducto, se asigna pero con fecha de asignacion futura
                if ($executedAt && $executedAt > $product->updated_at) {
                    $product->update([
                        'location_status' => 'Asignado',
                        'updated_at' => now()
                    ]);
                }


                // Registrar en tabla de LoteDEtails
                LoteDetail::create([
                    'lote_id' => $lote->id,
                    'product_id' => $product->id,
                    'assigned_at' => now(),
                    'assigned_by' => $authUser->id,
                    'active' => true
                ]);

                //obtener los movimientos del producto para obtener el status anterior a la asignacion manual segun lafecha de ejecucion, si no hay movimientos anteriores, se toma el status del producto antes de la actualizacion
                $lastMovement = ProductMovementService::getMovementsByProductId($product->id)
                    ->where('executed_at', '<=', $executedAt ?? now())
                    ->sortByDesc('executed_at')
                    ->first();
                // Registrar movimiento
                ProductMovementService::log(
                    $product->id,
                    'Asignación',
                    "Producto asignado manualmente al vendedor {$seller->full_name}",
                    $lastMovement->destination,
                    'Asignado',
                    $executedAt
                );


                $index++;
                $processedCount++;
            }

            DB::commit();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "Procesados {$processedCount} registros asignados manualmente.";
            $response->data["alert_text"] = "{$processedCount} productos marcados como Asignado Manual.";

            // Agregar métricas detalladas
            $response->data["metrics"] = [
                'registros_totales' => count($products),
                'asignados' => $processedCount,
                // 'no_encontrados' => 0,
                // 'asignados_anteriormente' => count($alreadyPorted),
                'errores' => 0
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            $msg = "LoteDetailController ~ createMultipleManually ~ Hubo un error -> " . $e->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
            return response()->json($response, 500);
        }

        return response()->json($response, $response->data["status_code"]);
    }
}
