<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\ObjResponse;
use App\Models\Situation;
use App\Models\VW_Situation;
use App\Models\VW_User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SituationController extends Controller
{
    /**
     * Mostrar lista de situaciones.
     *
     * @return \Illuminate\Http\Response $response
     */
    public function index(Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $auth = Auth::user();
            $userEmployee = VW_User::where('id', $auth->id)->first();
            if (!$userEmployee) {
                $response->data = ObjResponse::SuccessResponse();
                $response->data["message"] = 'Peticion satisfactoria | Lista de situaciones.';
                $response->data["alert_text"] = "Vincula tu usuario a un empleado para saber a que departamento perteneces.";
                $response->data["result"] = [];
                return response()->json($response, $response->data["status_code"]);
            }
            // Log::info("userEmployee" . $userEmployee);
            $departmentByUser = Department::find($userEmployee->department_id);


            // $list = VW_Situation::orderBy('id', 'desc');
            $list = Situation::with([
                'requester',
                'subcategory',
                // 'situationSetting',
                'register',
                'authorizer',
                'followUper',
                'rejecter',
                'familyData',
                'livingData',
                'economicData',
                'documentsData',
                'evidencesData',
                'receipt'
            ])->orderBy('id', 'desc');
            if ($auth->role_id > 3) $list = $list->where("active", true);
            if (!\Str::contains($userEmployee->more_permissions, ['Ver Todas las Situaciones', 'todas'])) $list = $list->where('folio', 'like', $departmentByUser->letters . "-%");
            $list = $list->get();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Peticion satisfactoria | Lista de situaciones.';
            $response->data["result"] = $list;
        } catch (\Exception $ex) {
            $msg = "SituationController ~ index ~ Hubo un error -> " . $ex->getMessage();
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
            $list = VW_Situation::where('active', true)
                ->select('id as id', 'situation as label')
                ->orderBy('situation', 'asc')->get();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'peticion satisfactoria | lista de situaciones.';
            $response->data["alert_text"] = "Situaciones encontradas";
            $response->data["result"] = $list;
            $response->data["toast"] = false;
        } catch (\Exception $ex) {
            $msg = "SituationController ~ selectIndex ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Crear o Actualizar situacion.
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
            $userAuth = Auth::user();
            Log::info("userAuth:" . $userAuth);
            // $duplicate = $this->validateAvailableData($request->full_name, $request->cellphone, $id);
            // if ($duplicate["result"] == true) {
            //     $response->data = $duplicate;
            //     return response()->json($response);
            // }

            $folio = $this->getLastFolio($request->letters);
            // var_dump($folio);
            $numFolio = 0;
            if ($folio != 0) {
                $parts = explode("-", $folio);
                $numFolio = (int)end($parts);
            }
            $numFolio += 1;
            $folio = sprintf("%s-%d", $request->letters, $numFolio);

            $situation = Situation::find($id);
            if (!$situation) {
                $situation = new Situation();
            }

            // $situation->fill($request->all());
            $situation->folio = $folio;
            $situation->requester_id = $request->requester_id;
            $situation->subcategory_id = $request->subcategory_id;
            if (is_null($id)) $situation->registered_by = $userAuth->id;
            $situation->description = $request->description;
            $situation->save();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = $id > 0 ? 'peticion satisfactoria | situacion editada.' : 'peticion satisfactoria | situacion registrada.';
            $response->data["alert_text"] = $id > 0 ? "Situación editada" : "<h3>Situación registrada. </br> folio: <b>$situation->folio</b></h3>";
            $response->data["result"] = $situation;
        } catch (\Exception $ex) {
            $msg = "SituationController ~ createOrUpdate ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    public function followUp(Request $request, Response $response, Int $id)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $userAuth = Auth::user();

            $situation = Situation::find($id);
            // Log::info("situacion: " . $situation);

            if ((int)$request->current_page == 2) {
                $situation->fill($request->all());
                $situation->status = "EN SEGUIMIENTO";
                $situation->follow_up_by = $userAuth->id;
                $situation->follow_up_at = date('Y-m-d H:i:s');
            } elseif ((bool)$request->finish) {
                $situation->status = "CERRADO";
                $situation->end_date = date('Y-m-d H:i:s');
            } else {
                $situation->fill($request->all());
            }
            // $situation->requester_id = $request->requester_id;
            $situation->save();
            // Log::info("situacion editada: " . $situation);


            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'situacion editada.';
            $response->data["alert_text"] = "Sección completada";
        } catch (\Exception $ex) {
            $msg = "SituationController ~ followUp ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Mostrar situacion.
     *
     * @param   int $id
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response $response
     */
    public function show(Request $request, Response $response, String $column, String $value, bool $internal = false)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $Situation = Situation::where($column, $value)->first();
            // Log::info("SituationController ~ show ~ Situation" . json_encode($Situation));
            $situation = $Situation::with([
                'requester',
                'subcategory',
                // 'situationSetting',
                'register',
                'authorizer',
                'followUper',
                'rejecter',
                'familyData',
                'livingData',
                'economicData',
                'documentsData',
                'evidencesData',
                'receipt'
            ])->findOrFail($Situation->id);

            if ($internal) return $situation;
            // Log::info("SituationController ~ show ~ situtation" . json_encode($situation));

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'peticion satisfactoria | situacion encontrada.';
            $response->data["result"] = $situation;
        } catch (\Exception $ex) {
            $msg = "SituationController ~ show ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * "Eliminar" (cambiar estado activo=0) situacion.
     *
     * @param  int $id
     * @param  int $active
     * @return \Illuminate\Http\Response $response
     */
    public function delete(Response $response, Int $id)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            Situation::where('id', $id)
                ->update([
                    'active' => false,
                    'deleted_at' => date('Y-m-d H:i:s')
                ]);

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "peticion satisfactoria | situacion eliminada.";
            $response->data["alert_text"] = "Situación eliminada";
        } catch (\Exception $ex) {
            $msg = "SituationController ~ delete ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * "Activar o Desactivar" (cambiar estado activo=1/0).
     *
     * @param  int $id
     * @param  int $active
     * @return \Illuminate\Http\Response $response
     */
    public function disEnable(Response $response, Int $id, string $active)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            Situation::where('id', $id)
                ->update([
                    'active' => $active === "reactivar" ? 1 : 0
                ]);

            $description = $active == "reactivar" ? 'reactivado' : 'desactivado';
            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "peticion satisfactoria | situacion $description.";
            $response->data["alert_text"] = "Situación $description";
        } catch (\Exception $ex) {
            $msg = "SituationController ~ disEnable ~ Hubo un error -> " . $ex->getMessage();
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
            // echo "$request->ids";
            // $deleteIds = explode(',', $ids);
            $countDeleted = sizeof($request->ids);
            Situation::whereIn('id', $request->ids)->update([
                'active' => false,
                'deleted_at' => date('Y-m-d H:i:s'),
            ]);
            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = $countDeleted == 1 ? 'peticion satisfactoria | registro eliminado.' : "peticion satisfactoria | registros eliminados ($countDeleted).";
            $response->data["alert_text"] = $countDeleted == 1 ? 'Registro eliminado' : "Registros eliminados  ($countDeleted)";
        } catch (\Exception $ex) {
            $msg = "SituationController ~ deleteMultiple ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Obtener el ultimo folio.
     *
     * @return \Illuminate\Http\Int $folio
     */
    private function getLastFolio(string $letters = null)
    {
        try {
            if (!$letters) {
                return 0; // Si no hay prefijo, regresar 0
            }

            $folio = Situation::where('active', true)->where('folio', 'like', "$letters-%")
                ->selectRaw("MAX(CAST(SUBSTRING_INDEX(folio, '-', -1) AS UNSIGNED)) as max_folio")
                ->value('max_folio');

            // Log::info("getLastFolio ~ folio:" . $folio);
            return $folio ?? 0; // Si no hay folio, regresar 0
        } catch (\Exception $ex) {
            $msg =  "SituationController ~ getLastFolio ~ Error al obtener Ultimo Folio: " . $ex->getMessage();
            Log::error($msg);
            return $msg;
        }
    }

    public function saveFirmRequester(Request $request, Response $response, Int $id)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $situation = Situation::find($id);
            // Log::info("situacion: " . $situation);
            $this->ImageUp($request, 'img_firm_requester', "situations/$request->folio", null, "FirmaSolicitante", $request->id == null ? true : false, "noImage.png", $situation);
            // Log::info("situacion editada: " . $situation);

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'firma cargada.';
            $response->data["alert_text"] = "Firma Cargada";
        } catch (\Exception $ex) {
            $msg = "SituationController ~ saveFirmRequester ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    public function authorizationOrRejection(Request $request, Response $response, Int $id)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $userAuth = Auth::user();
            $situation = Situation::find($id);
            // Log::info("situacion: " . $situation);

            $situation->fill($request->all());
            if ((bool)$request->finish) $situation->status = "CERRADO";
            if ((bool)$request->authorization) {
                $situation->authorized_by = $userAuth->id;
                $situation->authorized_comment = $request->authorized_comment;
                $situation->authorized_at = date('Y-m-d H:i:s');
            } else {
                $situation->status = "RECHAZADO";
                $situation->rejected_by = $userAuth->id;
                $situation->rejected_comment = $request->rejected_comment;
                $situation->rejected_at = date('Y-m-d H:i:s');
            }
            $situation->save();


            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'situacion autorizada/rechazada.';
            $response->data["alert_text"] = (bool)$request->authorization ? "Solicitud autorizada" : "Solicitud Rechazada";
        } catch (\Exception $ex) {
            $msg = "SituationController ~ authorizationOrRejection ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Mostrar historial de situaciones por ciudadano.
     *
     * @return \Illuminate\Http\Response $response
     */
    public function history(Request $request, Response $response, Int $personal_info_id)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $list = Situation::with([
                'requester',
                'subcategory',
                // 'situationSetting',
                'register',
                'authorizer',
                'followUper',
                'rejecter',
                'familyData',
                'livingData',
                'economicData',
                'documentsData',
                'evidencesData',
                'receipt'
            ])->orderBy('id', 'desc');
            $list = $list->where("active", true)->where('requester_id', $personal_info_id)->get();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Peticion satisfactoria | Historial de situaciones.';
            $response->data["result"] = $list;
        } catch (\Exception $ex) {
            $msg = "SituationController ~ history ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    public function returnStatusToSituation(Request $request, Response $response, Int $id)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            DB::statement("call sp_return_status_to_situation(?)", [$id]);


            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Caso Re-abierto.';
        } catch (\Exception $ex) {
            $msg = "SituationController ~ authorizationOrRejection ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Mostrar situacion.
     *
     * @param   int $id
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response $response
     */
    public function getPreviousSituation(Request $request, Response $response, Int $id, bool $internal = false)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            // Obtener la situación actual y la anterior en una sola consulta
            $currentSituation = Situation::findOrFail($id);

            $previousSituation = Situation::where("active", true)->where('id', '<>', $currentSituation->id)
                ->where('requester_id', $currentSituation->requester_id)
                ->with([
                    'requester',
                    'subcategory',
                    'register',
                    'authorizer',
                    'followUper',
                    'rejecter',
                    'familyData',
                    'livingData',
                    'economicData',
                    'documentsData',
                    'evidencesData',
                    'receipt'
                ])
                ->orderBy('id', 'desc')
                ->first();

            // if (!$previousSituation) {
            //     throw new \Exception('No se encontró una situación previa');
            // }

            if ($internal) return $previousSituation;
            // Log::info("SituationController ~ getPreviousSituation ~ situtation" . json_encode($previousSituation));

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'peticion satisfactoria | situacion previa encontrada.';
            $response->data["alert_text"] = 'Situación previa encontrada.';
            $response->data["result"] = $previousSituation;
        } catch (\Exception $ex) {
            $msg = "SituationController ~ getPreviousSituation ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }
}
