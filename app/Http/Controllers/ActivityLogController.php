<?php
// app/Http/Controllers/ActivityLogController.php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ObjResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ActivityLogController extends Controller
{
   public function index(Request $request, Response $response)
   {
      $response->data = ObjResponse::DefaultResponse();
      try {

         $query = ActivityLog::with(['user', 'model'])
            ->latest();

         // Filtros
         if ($request->has('model') && $request->model) {
            $query->where('model', $request->model);
         }

         if ($request->has('action') && $request->action) {
            $query->where('action', $request->action);
         }

         if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
         }

         if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
         }

         if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
         }

         $activityLogs = $query->paginate(50);
         $users = User::all();
         $actions = ActivityLog::distinct()->pluck('action');

         // return view('activity-logs.index', compact('activityLogs', 'users', 'actions'));
      } catch (\Exception $ex) {
         $msg = "ActivityLogController ~ index ~ Hubo un error -> " . $ex->getMessage();
         Log::error($msg);
         $response->data = ObjResponse::CatchResponse($msg);
      }
      return response()->json($response, $response->data["status_code"]);
   }

   /**
    * ESTADÍSTICAS DE ACTIVIDAD
    */
   public function dashboard()
   {
      $today = Carbon::today();
      $lastWeek = Carbon::today()->subWeek();

      $stats = [
         'today' => ActivityLog::whereDate('created_at', $today)->count(),
         'last_week' => ActivityLog::where('created_at', '>=', $lastWeek)->count(),
         'total' => ActivityLog::count(),
         'top_actions' => ActivityLog::select('action')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('action')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get(),
         'recent_activity' => ActivityLog::with(['user', 'model'])
            ->latest()
            ->limit(10)
            ->get()
      ];

      return view('dashboard', compact('stats'));
   }

   /**
    * EXPORTAR BITÁCORA
    */
   public function export(Request $request)
   {
      $logs = ActivityLog::with(['user', 'model'])
         ->whereDate('created_at', '>=', $request->date_from)
         ->whereDate('created_at', '<=', $request->date_to)
         ->get();

      // Aquí puedes implementar exportación a CSV, Excel, etc.
      return response()->json($logs);
   }
}
