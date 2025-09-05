<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\ObjResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RoleController extends Controller
{
    /**
     * Mostrar lista de roles.
     *
     * @return \Illuminate\Http\Response $response
     */
    public function index(Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $roleAuth = Auth::user()->role_id;
            $list = Role::where("id", ">=", $roleAuth)
                ->orderBy('id', 'desc')
                ->get();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Peticion satisfactoria | Lista de roles.';
            $response->data["result"] = $list;
        } catch (\Exception $ex) {
            $msg = "RoleController ~ index ~ Hubo un error -> " . $ex->getMessage();
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
            $roleAuth = Auth::user()->role_id;
            $list = Role::where('active', true)->where("id", ">=", $roleAuth)
                ->select('id as id', 'role as label')
                ->orderBy('role', 'asc')->get();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'peticion satisfactoria | lista de roles.';
            $response->data["alert_text"] = "roles encontrados";
            $response->data["result"] = $list;
            $response->data["toast"] = false;
        } catch (\Exception $ex) {
            $msg = "RoleController ~ selectIndex ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Crear o Actualizar rol.
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
            $validator = $this->validateAvailableData($request, 'roles', [
                [
                    'field' => 'role',
                    'label' => 'Nombre del rol',
                    'rules' => ['string'],
                    'messages' => [
                        'string' => 'El nombre del rol debe ser texto.',
                    ]
                ]
            ], $id);
            if ($validator->fails()) {
                $response->data = ObjResponse::CatchResponse();
                $response->data["message"] = "Error de validación";
                $response->data["errors"] = $validator->errors();
                return response()->json($response);
            }

            $rol = Role::find($id);
            if (!$rol) $rol = new Role();
            $rol->fill($request->all());
            $rol->save();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = $id > 0 ? 'peticion satisfactoria | rol editado.' : 'peticion satisfactoria | rol registrado.';
            $response->data["alert_text"] = $id > 0 ? "Rol editado" : "Rol registrado";
        } catch (\Exception $ex) {
            $msg = "RoleController ~ createOrUpdate ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Actualizar permisos.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response $response
     */
    public function updatePermissions(Request $request, Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $role = Role::find($request->id);
            $role->read = $request->read;
            $role->create = $request->create;
            $role->update = $request->update;
            $role->delete = $request->delete;
            $role->more_permissions = $request->more_permissions;

            $role->save();

            // deleteTokenByAbility
            DB::table('personal_access_tokens')->whereJsonContains('abilities', $role->role)->delete(); #Utilizar para cuando cambian permisos de un rol

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'peticion satisfactoria | permisos actualizado.';
            $response->data["alert_text"] = 'Permisos actualizados';
        } catch (\Exception $ex) {
            $msg = "RoleController ~ updatePermissions ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Mostrar rol.
     *
     * @param   int $id
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response $response
     */
    public function show(Request $request, Response $response, Int $id, bool $internal = false)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $id_rol = $id;
            // if ($internal == 1) $id_rol = $request->page_index;
            $rol = Role::find($id_rol);

            if ($internal) return $rol;

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'peticion satisfactoria | rol encontrado.';
            $response->data["result"] = $rol;
        } catch (\Exception $ex) {
            $msg = "RoleController ~ show ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * "Eliminar" (cambiar estado activo=0) rol.
     *
     * @param  int $id
     * @param  int $active
     * @return \Illuminate\Http\Response $response
     */
    public function delete(Response $response, Int $id)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            Role::where('id', $id)
                ->update([
                    'active' => false,
                    'deleted_at' => date('Y-m-d H:i:s')
                ]);

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "peticion satisfactoria | rol eliminado.";
            $response->data["alert_text"] = "Rol eliminado";
        } catch (\Exception $ex) {
            $msg = "RoleController ~ delete ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * "Activar o Desactivar" (cambiar estado activo=1/0) rol.
     *
     * @param  int $id
     * @param  int $active
     * @return \Illuminate\Http\Response $response
     */
    public function disEnable(Response $response, Int $id, string $active)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            Role::where('id', $id)
                ->update([
                    'active' => $active === "reactivar" ? 1 : 0
                ]);

            $description = $active == "reactivar" ? 'reactivado' : 'desactivado';
            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "peticion satisfactoria | rol $description.";
            $response->data["alert_text"] = "Rol $description";
        } catch (\Exception $ex) {
            $msg = "RoleController ~ disEnable ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Eliminar uno o varios roles.
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
            Role::whereIn('id', $request->ids)->update([
                'active' => false,
                'deleted_at' => date('Y-m-d H:i:s'),
            ]);
            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = $countDeleted == 1 ? 'peticion satisfactoria | rol eliminado.' : "peticion satisfactoria | roles eliminados ($countDeleted).";
            $response->data["alert_text"] = $countDeleted == 1 ? 'Rol eliminado' : "Roles eliminados  ($countDeleted)";
        } catch (\Exception $ex) {
            $msg = "RoleController ~ deleteMultiple ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }
}
