<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PersonalInfoController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Models\ObjResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/', function (Request $request) {
    return "API LARAVEL :)";
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/signup', [AuthController::class, 'signup']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/checkLoggedIn', function (Response $response, Request $request) {
        $response->data = ObjResponse::SuccessResponse();
        $id = Auth::user()->id;
        if ($id < 1 || !$id) {
            throw ValidationException::withMessages([
                'message' => false
            ]);
        }
        if ($request->url) {
            $response->data = ObjResponse::DefaultResponse();
            try {
                $menu = Menu::where('url', $request->url)->where('active', 1)->select("id")->first();
                $response->data = ObjResponse::SuccessResponse();
                $response->data["message"] = 'Peticion satisfactoria | validar inicio de sesión.';
                $response->data["result"] = $menu;
            } catch (\Exception $ex) {
                $response->data = ObjResponse::CatchResponse($ex->getMessage());
            }
            return response()->json($response, $response->data["status_code"]);
        }
        return response()->json($response, $response->data["status_code"]);
    });
    Route::get('/logout', [AuthController::class, 'logout']);
    Route::post('/changePasswordAuth', [AuthController::class, 'changePasswordAuth']);

    Route::prefix("menus")->group(function () {
        Route::get("/", [MenuController::class, 'index']);
        Route::get("/getMenusByRole/{pages_read}", [MenuController::class, 'getMenusByRole']);
        Route::get("/getHeadersMenusSelect", [MenuController::class, 'getHeadersMenusSelect']);
        Route::get("/selectIndexToRoles", [MenuController::class, 'selectIndexToRoles']);
        Route::post("/createOrUpdate/{id?}", [MenuController::class, 'createOrUpdate']);
        Route::get("/id/{id}", [MenuController::class, 'show']);
        Route::get("/disEnable/{id}/{active}", [MenuController::class, 'disEnable']);

        Route::post("/getIdByUrl", [MenuController::class, 'getIdByUrl']);
    });

    Route::prefix("roles")->group(function () {
        Route::get("/", [RoleController::class, 'index']);
        Route::get("/selectIndex", [RoleController::class, 'selectIndex']);
        Route::post("/createOrUpdate/{id?}", [RoleController::class, 'createOrUpdate']);
        Route::get("/id/{id}", [RoleController::class, 'show']);
        Route::get("/delete/{id}", [RoleController::class, 'delete']);
        Route::get("/disEnable/{id}/{active}", [RoleController::class, 'disEnable']);
        Route::get("/deleteMultiple", [RoleController::class, 'deleteMultiple']);

        Route::post("/updatePermissions", [RoleController::class, 'updatePermissions']);
    });

    Route::prefix("departments")->group(function () {
        Route::get("/", [DepartmentController::class, 'index']);
        Route::get("/selectIndex", [DepartmentController::class, 'selectIndex']);
        Route::post("/createOrUpdate/{id?}", [DepartmentController::class, 'createOrUpdate']);
        Route::get("/id/{id}", [DepartmentController::class, 'show']);
        Route::get("/delete/{id}", [DepartmentController::class, 'delete']);
        Route::get("/disEnable/{id}/{active}", [DepartmentController::class, 'disEnable']);
        Route::get("/deleteMultiple", [DepartmentController::class, 'deleteMultiple']);
    });

    Route::prefix("positions")->group(function () {
        Route::get("/", [PositionController::class, 'index']);
        Route::get("/selectIndex", [PositionController::class, 'selectIndex']);
        Route::post("/createOrUpdate/{id?}", [PositionController::class, 'createOrUpdate']);
        Route::get("/id/{id}", [PositionController::class, 'show']);
        Route::get("/delete/{id}", [PositionController::class, 'delete']);
        Route::get("/disEnable/{id}/{active}", [PositionController::class, 'disEnable']);
        Route::get("/deleteMultiple", [PositionController::class, 'deleteMultiple']);
    });

    Route::prefix("employees")->group(function () {
        Route::get("/", [EmployeeController::class, 'index']);
        Route::get("/selectIndex", [EmployeeController::class, 'selectIndex']);
        Route::post("/createOrUpdate/{id?}", [EmployeeController::class, 'createOrUpdate']);
        Route::get("/id/{id}", [EmployeeController::class, 'show']);
        Route::get("/delete/{id}", [EmployeeController::class, 'delete']);
        Route::get("/disEnable/{id}/{active}", [EmployeeController::class, 'disEnable']);
        Route::get("/deleteMultiple", [EmployeeController::class, 'deleteMultiple']);
    });

    Route::prefix("users")->group(function () {
        Route::get("/", [UserController::class, 'index']);
        Route::get("/selectIndexByRole/{role_id}", [UserController::class, 'selectIndexByRole']);
        Route::get("/selectIndex", [UserController::class, 'selectIndex']);
        Route::post("/createOrUpdate/{id?}", [UserController::class, 'createOrUpdate']);
        Route::get("/id/{id}", [UserController::class, 'show']);
        Route::get("/delete/{id}", [UserController::class, 'delete']);
        Route::get("/disEnable/{id}/{active}", [UserController::class, 'disEnable']);
        Route::get("/deleteMultiple", [UserController::class, 'deleteMultiple']);
    });

    Route::prefix("personalInfo")->group(function () {
        Route::get("/", [PersonalInfoController::class, 'index']);
        Route::get("/selectIndex", [PersonalInfoController::class, 'selectIndex']);
        Route::post("/createOrUpdate/{id?}", [PersonalInfoController::class, 'createOrUpdate']);
        Route::get("/id/{id}", [PersonalInfoController::class, 'show']);
        Route::get("/delete/{id}", [PersonalInfoController::class, 'delete']);
        Route::get("/disEnable/{id}/{active}", [PersonalInfoController::class, 'disEnable']);
        Route::get("/deleteMultiple", [PersonalInfoController::class, 'deleteMultiple']);
    });

    // ----------------- RUTAS BASICAS -----------------
});

Route::post('/notifications', [NotificationController::class, 'store'])->middleware('auth:sanctum');
