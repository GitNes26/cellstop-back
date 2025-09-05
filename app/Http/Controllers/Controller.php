<?php

namespace App\Http\Controllers;

use App\Models\ObjResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Controller extends BaseController
{
    // use AuthorizesRequests, ValidatesRequests;

    /**
     * Funcion para guardar imagenes acorde al modelo.
     * @param Request $request
     * @param File $requestFileName
     * @param String $dirPath
     * @param Number $id
     * @param String $fileName
     * @param Boolean $create
     * @param String $fakeName
     * 
     * @return string
     */
    public function ImageUp($request, $requestFileName, $dirPath, $id, $fileName, $create, $fakeName, $model)
    {
        // $date = date('Y-m-d H:i:s');
        try {
            $dir = public_path($dirPath);
            $img_name = "";
            if ($request->hasFile($requestFileName)) {
                $img_file = $request->file($requestFileName);
                $dir_path = is_null($id) ? "$dirPath" : "$dirPath/$id";
                $destination = is_null($id) ? "$dir" : "$dir/$id";
                $img_name = $this->ImgUpload($img_file, $destination, $dir_path, is_null($id) ? "$fileName" : "$id-$fileName");
            } else {
                if ($create) $img_name = "$dirPath/$fakeName";
            }
            // Log::info("Controller ~ ImageUp ~ img_name: " . $img_name);
            if ($request->hasFile($requestFileName)) {
                $model->$requestFileName = $img_name;
                $model->save();
            }
            // return $img_name;
        } catch (\Exception $ex) {
            $msg =  "Controller ~ Error al cargar imagen de documentos data: " . $ex->getMessage();
            Log::error("$msg");
            return "$msg";
        }
    }
    /**
     * Funcion para guardar una imagen en directorio fisico, elimina y guarda la nueva al editar la imagen para no guardar muchas
     * imagenes y genera el path que se guardara en la BD
     * 
     * @param $image File es el archivo de la imagen
     * @param $destination String ruta donde se guardara fisicamente el archivo
     * @param $dir String ruta que mandara a la BD
     * @param $imgName String Nombre de como se guardará el archivo fisica y en la BD
     */
    public function ImgUpload($image, $destination, $dir, $imgName)
    {
        try {
            // return "ImgUpload->aqui todo bien";
            $type = "JPG";
            $permissions = 0777;

            if (file_exists("$dir/$imgName.PNG")) {
                // Establecer permisos
                if (chmod("$dir/$imgName.PNG", $permissions)) {
                    @unlink("$dir/$imgName.PNG");
                }
                $type = "JPG";
            } elseif (file_exists("$dir/$imgName.JPG")) {
                // Establecer permisos
                if (chmod("$dir/$imgName.JPG", $permissions)) {
                    @unlink("$dir/$imgName.JPG");
                }
                $type = "PNG";
            }
            $imgName = "$imgName.$type";
            $image->move($destination, $imgName);
            return "$dir/$imgName";
        } catch (\Error $err) {
            $msg = "Controller ~ error en imgUpload(): " . $err->getMessage();
            Log::error($msg);
            return "$msg";
        }
    }


    /**
     * Valida dinámicamente los campos recibidos según las reglas y mensajes personalizados.
     * 
     * @param \Illuminate\Http\Request $request
     * @param string $table Nombre de la tabla a validar.
     * @param array $fields Array de campos a validar, cada campo es un array con 'field', 'label', 'rules' y 'messages'.
     * Ejemplo: [
     *     ['field' => 'username', 'label' => 'Nombre de usuario', 'rules' => ['required', 'string'], 'messages' => ['required' => 'El campo username es obligatorio.', 'string' => 'El nombre de usuario debe ser texto.']]...
     * @param int|null $id ID del registro a excluir de la validación (para actualizaciones).
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function validateAvailableData(Request $request, string $table, array $fields, $id = null)
    {
        $rules = [];
        $messages = [];

        foreach ($fields as $field) {
            $field = $field['field'];
            $label = $field['label'] ?? $field;
            $extraRules = $field['rules'] ?? [];
            $extraMessages = $field['messages'] ?? [];

            $fieldRules = array_merge(
                ['required'],
                $extraRules,
                ["unique:$table,$field," . ($id ?? 'NULL') . ',id,active,1']
            );
            $rules[$field] = $fieldRules;

            $messages["$field.required"] = "El campo $label es obligatorio.";
            $messages["$field.unique"] = "$label no está disponible! - $request[$field] ya existe, intenta con uno diferente.";
            foreach ($extraMessages as $rule => $msg) {
                $messages["$field.$rule"] = $msg;
            }
        }

        return \Validator::make($request->all(), $rules, $messages);
    }

    /**
     * Funcion para verificar que los datos NO se dupliquen en las tablas correspondientes.
     * 
     * @return ObjRespnse|false
     */
    public function checkAvailableData($table, $column, $value, $propTitle, $input, $id, $secondTable = null)
    {
        if ($secondTable) {
            $query = "SELECT count(*) as duplicate FROM $table INNER JOIN $secondTable ON rol_id=rols.id WHERE $column='$value' AND active=1;";
            if ($id != null) $query = "SELECT count(*) as duplicate FROM $table t INNER JOIN $secondTable ON t.rol_id=rols.id WHERE t.$column='$value' AND active=1 AND t.id!=$id";
        } else {
            $query = "SELECT count(*) as duplicate FROM $table WHERE $column='$value' AND active=1";
            if ($id != null) $query = "SELECT count(*) as duplicate FROM $table WHERE $column='$value' AND active=1 AND id!=$id";
        }
        // echo $query;
        $result = DB::select($query)[0];
        //   var_dump($result->duplicate);
        if ((int)$result->duplicate > 0) {
            // echo "entro al duplicate";
            $response = array(
                "result" => true,
                "status_code" => 409,
                "alert_icon" => 'warning',
                "alert_title" => "$propTitle no esta disponible!",
                "alert_text" => "$propTitle no esta disponible! - $value ya existe, intenta con uno diferente.",
                "message" => "duplicate",
                "input" => $input,
                "toast" => false
            );
        } else {
            $response = array(
                "result" => false,
            );
        }
        return $response;
    }

    public function notificationPush($msg, $icon)
    {
        return new StreamedResponse(function () {
            // Datos que quieres enviar (pueden venir de la base de datos u otro servicio)
            $data = new ObjResponse();
            $data['alert_text'] = $msg;
            $data['timestamp'] = now()->toDateTimeString();

            // Envía un evento al cliente
            echo "data: " . json_encode($data) . "\n\n";

            // Forzar el envío del buffer
            ob_flush();
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);
    }
}