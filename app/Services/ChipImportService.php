<?php

namespace App\Services;

use App\Imports\ProductImport;
use App\Models\Import;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class ProductImportService
{
   public function handleImport($file, $fileType)
   {
      $path = $file->store('imports');

      $import = Import::create([
         'file_name' => $file->getClientOriginalName(),
         'file_type' => $fileType,
         'uploaded_by' => auth()->id(),
         'active' => true,
      ]);

      if ($fileType === 'imp') {
         Excel::import(new ProductImport($import->id), $file);
      }

      return $import;
   }
}
