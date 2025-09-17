<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Product;
use Illuminate\Http\Request;

class AssignmentsController extends Controller
{
   public function assign(Request $request)
   {
      $request->validate([
         'user_id' => 'required|exists:users,id',
         'product_ids' => 'required|array'
      ]);

      foreach ($request->product_ids as $pid) {
         Assignment::create([
            'user_id' => $request->user_id,
            'product_id' => $pid,
            'status' => 'asignado',
            'active' => true
         ]);

         Product::where('id', $pid)->update(['status' => 'asignado']);
      }

      return response()->json(['message' => 'Productos asignados']);
   }

   public function unassign(Request $request)
   {
      $request->validate([
         'user_id' => 'required|exists:users,id',
         'product_ids' => 'required|array'
      ]);

      foreach ($request->product_ids as $pid) {
         Assignment::where('user_id', $request->user_id)
            ->where('product_id', $pid)
            ->delete();

         Product::where('id', $pid)->update(['status' => 'disponible']);
      }

      return response()->json(['message' => 'Productos desasignados']);
   }

   public function index(Request $request)
   {
      $assignments = Assignment::with('product')->get();
      return response()->json($assignments);
   }
}
