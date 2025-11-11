<?php

namespace App\Http\Controllers;

use App\Models\Import;
use App\Models\ObjResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportController extends Controller
{
    /**
     * Mostrar lista de importaciones.
     */
    public function index(Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $auth = Auth::user();

            $list = Import::with(['user'])
                ->orderBy('id', 'desc');

            if ($auth->role_id > 2) $list = $list->where("active", true);
            $list = $list->get();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Petición satisfactoria | Lista de importaciones.';
            $response->data["result"] = $list;
        } catch (\Exception $ex) {
            $msg = "ImportController ~ index ~ Error: " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Mostrar listado simple para selector.
     */
    public function selectIndex(Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $list = Import::where('active', true)
                ->select('id', 'name as label')
                ->orderBy('name', 'asc')
                ->get();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Petición satisfactoria | Lista de archivos importados.';
            $response->data["alert_text"] = "Importaciones encontradas";
            $response->data["result"] = $list;
            $response->data["toast"] = false;
        } catch (\Exception $ex) {
            $msg = "ImportController ~ selectIndex ~ Error: " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Crear o actualizar una importación.
     */
    // public function createOrUpdate(Request $request, Response $response, Int $id = null)
    public function createOrUpdate($importData, ?Int $id = null /* DB $transaction */)
    {
        // $response->data = ObjResponse::DefaultResponse();
        try {
            // $validator = $this->validateAvailableData($importData, 'imports', [
            //     [
            //         'field' => 'name',
            //         'label' => 'Nombre del archivo',
            //         'rules' => ['required', 'string', 'max:255'],
            //         'messages' => [
            //             'required' => 'El nombre del archivo es obligatorio.',
            //             'string' => 'El nombre debe ser texto.',
            //             'max' => 'El nombre no puede superar los 255 caracteres.'
            //         ]
            //     ]
            // ], $id);

            // if ($validator->fails()) {
            //     $response->data = ObjResponse::CatchResponse($validator->errors());
            //     $response->data["message"] = "Error de validación";
            //     $response->data["errors"] = $validator->errors();
            //     return response()->json($response);
            // }

            $import = Import::find($id);
            if (!$import) $import = new Import();

            $import->name = $importData['name'];
            $import->type = $importData['type'];
            $import->size = $importData['size'];
            $import->last_modified = $importData['lastModified'];
            $import->uploaded_by = Auth::id();
            $import->active = true;
            $import->save();
            return $import;

            // $response->data = ObjResponse::SuccessResponse();
            // $response->data["message"] = $id ? 'Petición satisfactoria | Archivo actualizado.' : 'Petición satisfactoria | Archivo importado.';
            // $response->data["alert_text"] = $id ? "Archivo actualizado" : "Archivo registrado";
        } catch (\Exception $ex) {
            // $transaction::rollBack();
            $msg = "ImportController ~ createOrUpdate ~ Error: " . $ex->getMessage();
            Log::error($msg);
            // $response->data = ObjResponse::CatchResponse($msg);
        }
        // return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Mostrar una importación.
     */
    public function show(Request $request, Response $response, Int $id, bool $internal = false)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $import = Import::with(['user'])->find($id);
            if ($internal) return $import;

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Petición satisfactoria | Archivo encontrado.';
            $response->data["result"] = $import;
        } catch (\Exception $ex) {
            $msg = "ImportController ~ show ~ Error: " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Eliminar (soft delete) una importación.
     */
    public function delete(Response $response, Int $id)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            Import::where('id', $id)
                ->update([
                    'active' => false,
                    'deleted_at' => now()
                ]);

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "Petición satisfactoria | Archivo eliminado.";
            $response->data["alert_text"] = "Archivo eliminado";
        } catch (\Exception $ex) {
            $msg = "ImportController ~ delete ~ Error: " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Activar o desactivar importación.
     */
    public function disEnable(Response $response, Int $id, string $active)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            Import::where('id', $id)
                ->update(['active' => $active === "reactivar" ? 1 : 0]);

            $description = $active == "reactivar" ? 'reactivado' : 'desactivado';
            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "Petición satisfactoria | Archivo $description.";
            $response->data["alert_text"] = "Archivo $description";
        } catch (\Exception $ex) {
            $msg = "ImportController ~ disEnable ~ Error: " . $ex->getMessage();
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
            $countDeleted = count($request->ids);
            Import::whereIn('id', $request->ids)->update([
                'active' => false,
                'deleted_at' => now(),
            ]);

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = $countDeleted == 1
                ? 'Petición satisfactoria | Registro eliminado.'
                : "Petición satisfactoria | Registros eliminados ($countDeleted).";
            $response->data["alert_text"] = $countDeleted == 1
                ? 'Registro eliminado'
                : "Registros eliminados ($countDeleted)";
        } catch (\Exception $ex) {
            $msg = "ImportController ~ deleteMultiple ~ Error: " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    public function storeNoUtilizada(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
            'file_type' => 'required|in:imp,int'
        ]);

        $file = $request->file('file');
        $path = $file->store('imports');

        $import = Import::create([
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $request->file_type,
            'uploaded_by' => auth()->id(),
            'active' => true,
        ]);

        // Si es tipo "imp"
        if ($request->file_type === 'imp') {
            Excel::import(new ProductImport($import->id), $file);
        }

        // Si es tipo "int"
        if ($request->file_type === 'int') {
            // crear importadores específicos
            // Excel::import(new VisitsImport($import->id), $file);
        }

        return response()->json(['message' => 'Archivo importado correctamente']);
    }
}
