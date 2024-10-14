<?php

namespace App\Http\Controllers;

use App\Exports\ObjectExport;
use App\Models\Objects;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExelexportController extends Controller
{
    public function  index($id, Request $request)
    {
                $object = Objects::findOrFail($id);
                return Excel::download(new ObjectExport($id , $request->branch_id), 'data.xlsx'); // Download the file

    }
}
