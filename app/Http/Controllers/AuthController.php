<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ObjResponse;
use App\Models\VW_User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Metodo para validar credenciales e
     * inicar sesión
     * @param Request $request
     * @return \Illuminate\Http\Response $response
     */
    public function login(Request $request, Response $response)
    {
        $field = 'username';
        $value = $request->username;
        if ($request->email) {
            $field = 'email';
            $value = $request->email;
        } elseif ($request->payroll_number) {
            $field = 'payroll_number';
            $value = $request->payroll_number;
        }

        $request->validate([
            $field => 'required',
            'password' => 'required'
        ]);
        $user = VW_User::where("$field", "$value")->where('active', 1)
            ->orderBy('id', 'desc')
            ->first();

        // ->join("roles", "users.role_id", "=", "roles.id")
        // ->select("users.*", "roles.role", "roles.read", "roles.create", "roles.update", "roles.delete", "roles.more_permissions", "roles.page_index")


        if (!$user || !Hash::check($request->password, $user->password)) {

            throw ValidationException::withMessages([
                'message' => 'Credenciales incorrectas'
            ]);
        }
        $token = $user->createToken($user->email, [$user->role])->plainTextToken;
        // dd();
        $response->data = ObjResponse::SuccessResponse();
        $response->data["message"] = "peticion satisfactoria | usuario logeado. " . Auth::user();
        $response->data["result"]["token"] = $token;
        $response->data["result"]["auth"] = $user;
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Metodo para cerrar sesión.
     * @param int $id
     * @return \Illuminate\Http\Response $response
     */
    public function logout(Response $response, bool $all_sessions = false)
    {
        try {
            //  DB::table('personal_access_tokens')->where('tokenable_id', $id)->delete();
            if (!$all_sessions) Auth::user()->currentAccessToken()->delete(); #Elimina solo el token activo
            else auth()->user()->tokens()->delete(); #Utilizar este en caso de que el usuario desee cerrar sesión en todos lados o cambie informacion de su usuario / contraseña

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'peticion satisfactoria | sesión cerrada.';
            $response->data["alert_title"] = "Bye!";
        } catch (\Exception $ex) {
            $msg = "Authontroller ~ logout ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Reegistrarse como Ciudadano.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response $response
     */
    public function signup(Request $request, Response $response)
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
                ]
            ], $id);
            if ($validator->fails()) {
                $response->data = ObjResponse::CatchResponse();
                $response->data["message"] = "Error de validación";
                $response->data["errors"] = $validator->errors();
                return response()->json($response);
            }

            $new_user = User::create([
                'email' => $request->email,
                'username' => $request->username,
                'password' => Hash::make($request->password),
                'role_id' => 6,  //usuario normal - Ciudadano
            ]);
            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'peticion satisfactoria | usuario registrado.';
            $response->data["alert_text"] = "REGISTRO EXITOSO! <br>Bienvenido $request->username!";
        } catch (\Exception $ex) {
            $msg = "Authontroller ~ signup ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Cambiar contraseña usuario.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response $response
     */
    public function changePasswordAuth(Request $request, Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $userAuth = Auth::user();
            $user = User::find($userAuth->id);

            $response->data = ObjResponse::SuccessResponse();
            if (!Hash::check($request->password, $user->password)) {
                $response->data["message"] = 'peticion satisfactoria | la contraseña actual no es correcta.';
                $response->data["alert_icon"] = "error";
                $response->data["alert_text"] = "La contraseña actual que ingresas no es correcta";
                return response()->json($response, $response->data["status_code"]);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();
            auth()->user()->tokens()->delete(); #Utilizar este en caso de que el usuario desee cerrar sesión en todos lados o cambie informacion de su usuario / contraseña

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'peticion satisfactoria | contraseña actualizada.';
            $response->data["alert_text"] = "Contraseña actualizada - todas tus sesiones se cerraran para aplicar cambios.";
        } catch (\Exception $ex) {
            $msg = "Authontroller ~ changePasswordAuth ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }
}
