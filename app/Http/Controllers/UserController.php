<?php

namespace App\Http\Controllers;

use App\Events\NewNotification;
use App\Models\Notification;
use App\Models\NotificationTarget;
use App\Models\ObjResponse;
use App\Models\User;
use App\Models\VW_User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Mostrar lista de usuarios.
     *
     * @return \Illuminate\Http\Response $response
     */
    public function index(Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $roleAuth = Auth::user()->role_id;
            // $list = VW_User::where("role_id", ">=", $roleAuth)
            // ->orderBy('id', 'desc');
            $list = User::with('role', 'employee')
                ->where("role_id", ">=", $roleAuth)
                ->orderBy('id', 'desc');
            if ($roleAuth > 1) $list = $list->where("active", true);
            $list = $list->get();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Peticion satisfactoria | Lista de usuarios.';
            $response->data["result"] = $list;

            // Http::get(route('api.notifications'));
        } catch (\Exception $ex) {
            $msg = "UserController ~ index ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Mostrar lista de usuarios activos por role
     * uniendo con roles.
     *
     * @return \Illuminate\Http\Response $response
     */
    public function selectIndexByRole(Response $response, Int $role_id)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $roleAuth = Auth::user()->role_id;
            $signo = "=";
            $signo = $role_id == 2 && $roleAuth == 1 ? "<=" : "=";

            $list = User::where('active', true)->where("role_id", $signo, $role_id)
                ->select('id as id', 'username as label')
                ->orderBy('id', 'desc')
                ->get();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'peticion satisfactoria | lista de usuarios.';
            $response->data["alert_text"] = "usuarios encontrados";
            $response->data["result"] = $list;
        } catch (\Exception $ex) {
            $msg = "UserController ~ selectIndexByRole ~ Hubo un error -> " . $ex->getMessage();
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
            $list = User::where('active', true)
                ->select('id as id', 'username as label', 'role_id', 'role')
                ->orderBy('username', 'asc')->get();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'peticion satisfactoria | lista de usuarios.';
            $response->data["alert_text"] = "usuarios encontrados";
            $response->data["result"] = $list;
            $response->data["toast"] = false;
        } catch (\Exception $ex) {
            $msg = "UserController ~ selectIndex ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Crear o Actualizar usuario.
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
            $validator = $this->validateAvailableData($request, 'users', [
                [
                    'field' => 'username',
                    'label' => 'Nombre de usuario',
                    'rules' => ['string'],
                    'messages' => [
                        'string' => 'El nombre de usuario debe ser texto.',
                    ]
                ],
                [
                    'field' => 'email',
                    'label' => 'Correo electrónico',
                    'rules' => ['email'],
                    'messages' => [
                        'email' => 'El correo electrónico no es válido.',
                    ]
                ],
                [
                    'field' => 'employee_id',
                    'label' => 'Empleado',
                    'rules' => [],
                    'messages' => []
                ],
            ], $id);
            if ($validator->fails()) {
                $response->data["message"] = "Error de validación";
                $response->data["errors"] = $validator->errors();
                return response()->json($response);
            }

            $user = User::find($id);
            if (!$user) $user = new User();
            $user->fill($request->only(['email', 'username', 'password', 'role_id']));
            if ((int)$request->employee_id > 0) $user->employee_id = $request->employee_id;
            if ((bool)$request->changePassword && strlen($request->password) > 0) $user->password = Hash::make($request->password);
            $user->save();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = $id > 0 ? 'peticion satisfactoria | usuario editado.' : 'peticion satisfactoria | usuario registrado.';
            $response->data["alert_text"] = $id > 0 ? "Usuario editado" : "Usuario registrado";

            // $this->notificationPush($response->data["alert_text"],$response->data["alert_icon"]);
        } catch (\Exception $ex) {
            $msg = "UserController ~ createOrUpdate ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }


        // crear notificación
        $notification = Notification::create([
            'title' => 'Nueva Orden',
            'message' => 'Se creó una nueva orden: ' . $order->title,
            'type' => 'success',
            'created_by' => auth()->id(),
        ]);

        // asignar destinatarios (ejemplo: rol admin id=1)
        $target = NotificationTarget::create([
            'notification_id' => $notification->id,
            'target_type' => 'role',
            'target_id' => 1,
        ]);

        // obtener usuarios del rol admin
        $userIds = \App\Models\User::where('role_id', 1)->pluck('id')->toArray();

        broadcast(new NewNotification($notification, $userIds))->toOthers();

        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Mostrar usuario.
     *
     * @param   int $id
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response $response
     */
    public function show(Request $request, Response $response, Int $id, bool $internal = false)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            // $id_user = $id;
            // if ($internal == 1) $id_user = $request->page_index;
            $user = User::find($id);

            if ($internal) return $user;

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'peticion satisfactoria | usuario encontrado.';
            $response->data["result"] = $user;
        } catch (\Exception $ex) {
            $msg = "UserController ~ show ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * "Eliminar" (cambiar estado activo=0) usuario.
     *
     * @param  int $id
     * @param  int $active
     * @return \Illuminate\Http\Response $response
     */
    public function delete(Response $response, Int $id)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            User::where('id', $id)
                ->update([
                    'active' => false,
                    'deleted_at' => date('Y-m-d H:i:s')
                ]);

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "peticion satisfactoria | usuario eliminado.";
            $response->data["alert_text"] = "Usuario eliminado";
        } catch (\Exception $ex) {
            $msg = "UserController ~ delete ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * "Activar o Desactivar" (cambiar estado activo=1/0) user.
     *
     * @param  int $id
     * @param  string $active
     * @return \Illuminate\Http\Response $response
     */
    public function disEnable(Response $response, Int $id, string $active)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            User::where('id', $id)
                ->update([
                    'active' => $active === "reactivar" ? 1 : 0
                ]);

            $description = $active == "reactivar" ? 'reactivado' : 'desactivado';
            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "peticion satisfactoria | user $description.";
            $response->data["alert_text"] = "Usuario $description";
        } catch (\Exception $ex) {
            $msg = "UserController ~ disEnable ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Eliminar uno o varios usuarios.
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
            User::whereIn('id', $request->ids)->update([
                'active' => false,
                'deleted_at' => date('Y-m-d H:i:s'),
            ]);
            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = $countDeleted == 1 ? 'peticion satisfactoria | usuario eliminado.' : "peticion satisfactoria | usuarios eliminados ($countDeleted).";
            $response->data["alert_text"] = $countDeleted == 1 ? 'Usuario eliminado' : "Usuarios eliminados  ($countDeleted)";
        } catch (\Exception $ex) {
            $msg = "UserController ~ deleteMultiple ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }
}