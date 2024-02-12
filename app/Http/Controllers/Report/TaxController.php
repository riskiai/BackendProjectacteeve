<?php

namespace App\Http\Controllers\Report;

use App\Exports\Tax\PphExport;
use App\Exports\Tax\PpnExport;
use App\Facades\Constant;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class TaxController extends Controller
{
    public function ppn(Request $request)
    {
        if ($request->type == Constant::EXPORT_PDF) {
            return Excel::download(new PpnExport, date('YmdHis') . '.pdf');
        }

        return Excel::download(new PpnExport, date('YmdHis') . '.xlsx');
    }

    public function pph(Request $request)
    {
        if ($request->type == Constant::EXPORT_PDF) {
            return Excel::download(new PphExport, date('YmdHis') . '.pdf');
        }

        return Excel::download(new PphExport, date('YmdHis') . '.xlsx');
    }
}
