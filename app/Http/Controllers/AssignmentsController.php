<?php

namespace App\Http\Controllers;

use App\Models\Assignments;
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
            Assignments::create([
                'user_id' => $request->user_id,
                'product_id' => $pid,
                'status' => 'asignado',
                'active' => true
            ]);

            Product::where('id', $pid)->update(['status' => 'asignado']);
        }

        return response()->json(['message' => 'Productos asignados']);
    }
}
