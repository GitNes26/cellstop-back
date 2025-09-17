<?php

namespace App\Services;

use App\Imports\ChipImport;
use App\Models\Import;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class ChipImportService
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
         Excel::import(new ChipImport($import->id), $file);
      }

      return $import;
   }
}
