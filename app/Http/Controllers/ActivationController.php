<?php

namespace App\Http\Controllers;

use App\Models\Activation;
use Illuminate\Http\Request;

class ActivationController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'activation_type' => 'required|string',
            'activation_date' => 'nullable|date',
        ]);

        return Activation::create([
            'product_id' => $request->product_id,
            'user_id' => auth()->id(),
            'activation_type' => $request->activation_type,
            'activation_date' => $request->activation_date ?? now(),
            'status' => 'completada',
            'source' => 'int',
            'active' => true
        ]);
    }
}
