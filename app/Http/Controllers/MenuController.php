<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\ObjResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MenuController extends Controller
{
    /**
     * Mostrar lista de menus por rol activos para acomodar en el menu lateral.
     *
     * @return \Illuminate\Http\Response $response
     */
    public function getMenusByRole(String $pages_read, Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {

            $list = Menu::where('menus.active', true)
                ->orderBy('menus.order', 'asc')->get();
            if ($pages_read != "todas") {
                $menus_ids = rtrim($pages_read, ",");
                $menus_ids = explode(",", $menus_ids);
                // print_r($menus_ids) ;
                $list = Menu::where('menus.active', true)
                    ->whereIn("menus.id", $menus_ids)
                    ->orderBy('menus.order', 'asc')->get();
            }
            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Peticion satisfactoria | Lista de menus por rol.';
            $response->data["result"] = $list;
        } catch (\Exception $ex) {
            $msg = "MenuController ~ getMenusByRole ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Obtener id de la pagina por su url.
     *
     * @return \Illuminate\Http\Response $response
     */
    public function getIdByUrl(Request $request, Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $menu = Menu::where('url', $request->url)->where('active', 1)->select("id")->first();
            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Peticion satisfactoria | Lista de menus.';
            $response->data["result"] = $menu;
        } catch (\Exception $ex) {
            $msg = "MenuController ~ getIdByUrl ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Mostrar listado menus principales para un selector.
     *
     * @return \Illuminate\Http\Response $response
     */
    public function getHeadersMenusSelect(Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $list = Menu::where('active', true)->where('belongs_to', 0)
                ->select('menus.id as id', 'menus.menu as label')
                ->orderBy('menus.menu', 'asc')->get();
            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Peticion satisfactoria | Lista de menus';
            $response->data["result"] = $list;
        } catch (\Exception $ex) {
            $msg = "MenuController ~ getHeadersMenusSelect ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Mostrar listado para el selector en Roles y Permisos,
     * ayuda a determinar la ruta por defecto que tendra el rol .
     *
     * @return \Illuminate\Http\Response $response
     */
    public function selectIndexToRoles(Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $list = Menu::where('menus.active', true)->where('menus.belongs_to', '>', 0)
                ->leftJoin('menus as patern', 'menus.belongs_to', '=', 'patern.id')
                ->select(
                    "menus.id as id",
                    DB::raw("CONCAT(patern.menu,' : ', menus.menu) as label")
                )
                ->orderBy('menus.menu', 'asc')->get();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Peticion satisfactoria | Lista de menus';
            $response->data["result"] = $list;
        } catch (\Exception $ex) {
            $msg = "MenuController ~ selectIndexToRoles ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }


    //#region CRUD
    /**
     * Mostrar lista de menus.
     *
     * @return \Illuminate\Http\Response $response
     */
    public function index(Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $list = Menu::leftJoin('menus as patern', 'menus.belongs_to', '=', 'patern.id')
                ->select('menus.*', 'patern.menu as patern')
                ->orderBy('menus.id', 'asc')->get();
            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Peticion satisfactoria | Lista de menus.';
            $response->data["result"] = $list;
        } catch (\Exception $ex) {
            $msg = "MenuController ~ index ~ Hubo un error -> " . $ex->getMessage();
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
            $list = Menu::where('active', true)
                ->select('menus.id as id', 'menus.menu as label')
                ->orderBy('menus.menu', 'asc')->get();
            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Peticion satisfactoria | Lista de menus';
            $response->data["result"] = $list;
        } catch (\Exception $ex) {
            $msg = "MenuController ~ selectIndex ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Crear o Actualizar menu.
     *
     * @return \Illuminate\Http\Response $response
     */
    public function createOrUpdate(Request $request, Response $response, Int $id = null)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $validator = $this->validateAvailableData($request, 'menus', [
                [
                    'field' => 'menu',
                    'label' => 'Menú',
                    'rules' => ['string'],
                    'messages' => [
                        'string' => 'El menú debe ser texto.',
                    ]
                ]
            ], $id);
            if ($validator->fails()) {
                $response->data = ObjResponse::CatchResponse();
                $response->data["message"] = "Error de validación";
                $response->data["errors"] = $validator->errors();
                return response()->json($response);
            }

            $menu = Menu::find($id);
            if (!$menu) $menu = new Menu();
            $menu->fill($request->all());

            $menu->save();

            // $new_others_permissions = "";
            // if (strlen($request->others_permissions) > 1) {
            //     $others_permissions = explode(",", $request->others_permissions);
            //     foreach ($others_permissions as $op) {
            //         $trim_op = trim($op);
            //         $new_others_permissions .= "$menu->id@$trim_op, ";
            //     }
            //     rtrim($new_others_permissions, ", ");
            //     return $new_others_permissions;
            // }
            // if ($request->others_permissions) $menu->others_permissions = $request->others_permissions;
            // $menu->save();


            $response->data = ObjResponse::SuccessResponse();

            $response->data["message"] = $id > 0 ? 'peticion satisfactoria | menu editado.' : 'peticion satisfactoria | menu registrado.';
            $response->data["alert_text"] = $id > 0 ? "Menú editado" : "Menú registrado";
        } catch (\Exception $ex) {
            $msg = "MenuController ~ createOrUpdate ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Mostrar menu.
     *
     * @param   int $id
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response $response
     */
    public function show(Request $request, Response $response, Int $id, bool $internal = false)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $id_menu = $id;
            if ($internal == 1) $id_menu = $request->page_index;
            $menu = Menu::where('menus.id', $id_menu)
                ->leftJoin('menus as patern', 'menus.belongs_to', '=', 'patern.id')
                ->select('menus.*', 'patern.menu as patern')
                ->first();

            if ($internal) return $menu;

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'peticion satisfactoria | menu encontrado.';
            $response->data["result"] = $menu;
        } catch (\Exception $ex) {
            $msg = "MenuController ~ show ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * "Activar o Desactivar" (cambiar estado activo=1/0) menu.
     *
     * @param  int $id
     * @param  int $active
     * @return \Illuminate\Http\Response $response
     */
    public function disEnable(Response $response, Int $id, string $active)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            Menu::where('id', $id)
                ->update([
                    'active' => $active === "reactivar" ? 1 : 0
                ]);

            $description = $active == "reactivar" ? 'reactivado' : 'desactivado';
            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "peticion satisfactoria | menu $description.";
            $response->data["alert_text"] = "Menú $description";
        } catch (\Exception $ex) {
            $msg = "MenuController ~ disEnable ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }
    //#endregion CRUD
}
