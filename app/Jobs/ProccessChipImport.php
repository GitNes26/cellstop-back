<?php

namespace App\Jobs;

use App\Imports\ProductImport;
use App\Models\Import;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessProductImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $importId;

    public function __construct($importId)
    {
        $this->importId = $importId;
    }

    public function handle()
    {
        $import = Import::find($this->importId);

        if ($import) {
            $filePath = storage_path('app/imports/' . $import->file_name);
            Excel::import(new ProductImport($this->importId), $filePath);
        }
    }
}
