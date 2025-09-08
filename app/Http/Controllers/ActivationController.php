<?php

namespace App\Http\Controllers;

use App\Models\Activation;
use Illuminate\Http\Request;

class ActivationController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'chip_id' => 'required|exists:chips,id',
            'activation_type' => 'required|string',
            'activation_date' => 'nullable|date',
        ]);

        return Activation::create([
            'chip_id' => $request->chip_id,
            'user_id' => auth()->id(),
            'activation_type' => $request->activation_type,
            'activation_date' => $request->activation_date ?? now(),
            'status' => 'completada',
            'source' => 'int',
            'active' => true
        ]);
    }
}