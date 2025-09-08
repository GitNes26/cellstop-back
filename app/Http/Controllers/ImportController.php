<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
            'file_type' => 'required|in:imp,int'
        ]);

        $file = $request->file('file');
        $path = $file->store('imports');

        $import = Import::create([
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $request->file_type,
            'uploaded_by' => auth()->id(),
            'active' => true,
        ]);

        // Si es tipo "imp"
        if ($request->file_type === 'imp') {
            Excel::import(new ChipImport($import->id), $file);
        }

        // Si es tipo "int"
        if ($request->file_type === 'int') {
            // crear importadores específicos
            // Excel::import(new SalesImport($import->id), $file);
        }

        return response()->json(['message' => 'Archivo importado correctamente']);
    }
}