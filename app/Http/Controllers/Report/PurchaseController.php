<?php

namespace App\Http\Controllers\Report;

use App\Exports\PurchaseExport;
use App\Facades\Constant;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class PurchaseController extends Controller
{
    public function event(Request $request)
    {
        if ($request->type == Constant::EXPORT_PDF) {
            return Excel::download(new PurchaseExport(1), date('YmdHis') . '.pdf');
        }

        return Excel::download(new PurchaseExport(1), date('YmdHis') . '.xlsx');
    }

    public function operational(Request $request)
    {
        if ($request->type == Constant::EXPORT_PDF) {
            return Excel::download(new PurchaseExport(2), date('YmdHis') . '.pdf');
        }

        return Excel::download(new PurchaseExport(2), date('YmdHis') . '.xlsx');
    }
}
