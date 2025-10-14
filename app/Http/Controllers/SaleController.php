<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Services\ChipMovementService;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'chip_id' => 'required|exists:chips,id',
            'buyer_name' => 'nullable|string',
            'buyer_phone' => 'nullable|string',
            'pos_id' => 'nullable|exists:points_of_sale,id',
            'lat' => 'nullable|numeric',
            'lon' => 'nullable|numeric',
            'evidence_photo' => 'nullable|file|image'
        ]);

        $sale = Sale::create([
            'chip_id' => $request->chip_id,
            'seller_id' => auth()->id(),
            'pos_id' => $request->pos_id,
            'buyer_name' => $request->buyer_name,
            'buyer_phone' => $request->buyer_phone,
            'lat' => $request->lat,
            'lon' => $request->lon,
            'ubication' => $request->ubication,
            'evidence_photo' => $request->file('evidence_photo')?->store('sales'),
            'status' => 'completada',
            'active' => true
        ]);

        // $this->ImageUp($request, 'evidence_photo', "sales", $sale->id, 'evidence_sale', $id == null ? true : false, "noImage.png", $sale);


        $origin = $sale->chip->location_status;
        $sale->chip->update(['location_status' => 'Vendido']);

        ChipMovementService::log(
            $chip->id,
            'Vendido',
            "Chip vendido al cliente {$sale->pointOfSale->name}",
            $origin,
            'Cliente'
        );
        // $sale->chip->product->update(['status' => 'distribuido']);

        return response()->json($sale);
    }
}
