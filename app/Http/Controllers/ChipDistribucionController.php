<?php

namespace App\Http\Controllers;

use App\Models\Chip;
use App\Models\ChipDistribucion;
use App\Models\ObjResponse;
use App\Models\VW_User;
use App\Services\ChipMovementService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChipDistribucionController extends Controller
{
    public function updatePackageAssignment(Request $request, Response $response)
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
            $chipIds = $request->input('chip_ids', []);
            $sellerId = $request->input('seller_id');
            $seller = VW_User::find($sellerId);

            // Obtener chips actualmente asignados al vendedor
            $currentChips = ChipDistribucion::where('seller_id', $sellerId)->pluck('chip_id')->toArray();

            // Chips a asignar y desasignar
            $chipsToAssign = array_diff($chipIds, $currentChips);
            $chipsToUnassign = array_diff($currentChips, $chipIds);

            $assigned = [];
            $unassigned = [];

            DB::beginTransaction();

            // ASIGNAR chips nuevos
            if (!empty($chipsToAssign)) {
                $availableChips = Chip::whereIn('id', $chipsToAssign)->where('location_status', 'Stock')->get();

                foreach ($availableChips as $chip) {
                    ChipDistribucion::create([
                        'chip_id' => $chip->id,
                        'seller_id' => $sellerId,
                        'lote_id' => $request->lote_id,
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

            // DESASIGNAR chips removidos
            if (!empty($chipsToUnassign)) {
                $chipsToRemove = ChipDistribucion::where('seller_id', $sellerId)
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

            DB::commit();

            // PREPARAR listados para TransferList
            $allChips = Chip::whereIn('id', array_merge($chipIds, $currentChips))->get();
            $left = $allChips->where('location_status', 'Stock')->map(fn($c) => $c->id)->values();
            $right = ChipDistribucion::where('seller_id', $sellerId)->pluck('chip_id');

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
            Log::error("ChipController ~ updatePackageAssignment ~ " . $th->getMessage());
            $response->data = ObjResponse::CatchResponse("Error al actualizar asignaciones -> " . $th->getMessage());
        }

        return response()->json($response, $response->data["status_code"]);
    }




    // public function assignPackage(Request $request, Response $response)
    // {
    //     $response->data = ObjResponse::DefaultResponse();

    //     try {
    //         // Validación usando la misma estructura que createOrUpdate
    //         $validator = $this->validateAvailableData($request, 'chip_distribuciones', [
    //             [
    //                 'field' => 'seller_id',
    //                 'label' => 'Vendedor',
    //                 'rules' => ['required', 'integer', 'exists:users,id'],
    //                 'messages' => [
    //                     'required' => 'El vendedor es obligatorio.',
    //                     'integer' => 'El ID del vendedor debe ser un número.',
    //                     'exists' => 'El vendedor no existe.'
    //                 ]
    //             ],
    //             [
    //                 'field' => 'chip_ids',
    //                 'label' => 'Chips',
    //                 'rules' => ['required', 'array', 'min:1'],
    //                 'messages' => [
    //                     'required' => 'Debe seleccionar al menos un chip.',
    //                     'array' => 'Los chips deben enviarse en un array.',
    //                     'min' => 'Debe seleccionar al menos un chip.'
    //                 ]
    //             ]
    //         ]);

    //         if ($validator->fails()) {
    //             $response->data = ObjResponse::CatchResponse($validator->errors());
    //             $response->data["message"] = "Error de validación";
    //             return response()->json($response);
    //         }

    //         $authUser = auth()->user();
    //         $chipIds = $request->input('chip_ids', []);
    //         $sellerId = $request->input('seller_id');

    //         $chips = Chip::whereIn('id', $chipIds)
    //             ->where('estado', 'disponible')
    //             ->get();

    //         if ($chips->isEmpty()) {
    //             $response->data = ObjResponse::CatchResponse('No hay chips disponibles para asignar.');
    //             return response()->json($response, 400);
    //         }

    //         DB::beginTransaction();

    //         foreach ($chips as $chip) {
    //             ChipDistribucion::create([
    //                 'chip_id' => $chip->id,
    //                 'seller_id' => $sellerId,
    //                 'lote_id' => $chip->import_id,
    //                 'assigned_at' => now(),
    //                 'assigned_by' => $authUser->id,
    //                 'active' => true
    //             ]);

    //             $chip->update(['estado' => 'asignado']);

    //             // Bitácora
    //             ChipMovementService::log(
    //                 $chip->id,
    //                 'Asignación',
    //                 "Chip asignado a vendedor ID: {$sellerId}",
    //                 $authUser->id,
    //                 'Distribución'
    //             );
    //         }

    //         DB::commit();

    //         $response->data = ObjResponse::SuccessResponse();
    //         $response->data["message"] = "Chips asignados correctamente.";
    //         $response->data["total"] = count($chips);
    //     } catch (\Throwable $th) {
    //         DB::rollBack();
    //         Log::error("ChipController ~ assignPackage ~ " . $th->getMessage());
    //         $response->data = ObjResponse::CatchResponse("Error al asignar chips -> " . $th->getMessage());
    //     }

    //     return response()->json($response, $response->data["status_code"]);
    // }


    public function reverseAssignment(Request $request, Response $response, int $seller_id)
    {
        $response->data = ObjResponse::DefaultResponse();

        try {
            $authUser = auth()->user();

            DB::beginTransaction();

            $assignments = ChipDistribucion::where('seller_id', $seller_id)->get();

            foreach ($assignments as $asignacion) {
                $chip = Chip::find($asignacion->chip_id);
                if ($chip) {
                    $chip->update(['estado' => 'disponible']);

                    ChipMovementService::log(
                        $chip->id,
                        'Reversión de asignación',
                        "Chip devuelto al stock desde vendedor ID: {$seller_id}",
                        $authUser->id,
                        'Distribución'
                    );
                }

                $asignacion->delete();
            }

            DB::commit();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "Asignaciones revertidas correctamente.";
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("ChipController ~ reverseAssignment ~ " . $th->getMessage());
            $response->data = ObjResponse::CatchResponse("Error al revertir asignaciones -> " . $th->getMessage());
        }

        return response()->json($response, $response->data["status_code"]);
    }
}