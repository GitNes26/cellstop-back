<?php

namespace App\Http\Controllers;

use App\Models\Chip;
use App\Models\ObjResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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
}
