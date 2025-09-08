<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'assignment_id' => 'required|exists:assignments,id',
            'buyer_name' => 'nullable|string',
            'buyer_phone' => 'nullable|string',
            'pos_id' => 'nullable|exists:points_of_sale,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'evidence_photo' => 'nullable|file|image'
        ]);

        $sale = Sale::create([
            'assignment_id' => $request->assignment_id,
            'user_id' => auth()->id(),
            'pos_id' => $request->pos_id,
            'buyer_name' => $request->buyer_name,
            'buyer_phone' => $request->buyer_phone,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'evidence_photo' => $request->file('evidence_photo')?->store('sales'),
            'status' => 'completada',
            'active' => true
        ]);

        $sale->assignment->update(['status' => 'vendido']);
        $sale->assignment->product->update(['status' => 'distribuido']);

        return response()->json($sale);
    }
}