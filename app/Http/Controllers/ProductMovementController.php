<?php

namespace App\Http\Controllers;

use App\Models\ObjResponse;
use App\Models\ProductMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductMovementController extends Controller
{
     /**
     * Mostrar el historial de movimientos de un producto.
     *
     * @param int $id
     * @param \Illuminate\Http\Response $response
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMovementsByProductId(Response $response, int $id)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $product = ProductMovement::with(['executer'])->find($id);

            if (!$product) {
                $response->data = ObjResponse::CatchResponse("Producto no encontrado.");
                $response->data["status_code"] = 404;
                return response()->json($response, 404);
            }

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "Petición satisfactoria | historial de movimientos del producto.";
            $response->data["result"] = $product->movements;
        } catch (\Exception $ex) {
            $msg = "ProductController ~ movements ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }
}