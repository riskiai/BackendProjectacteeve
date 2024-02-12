<?php

namespace App\Http\Controllers\Report;

use App\Exports\AttachmentExport;
use App\Facades\Constant;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AttachmentController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        if ($request->type == Constant::EXPORT_PDF) {
            return Excel::download(new AttachmentExport, date('YmdHis') . '.pdf');
        }

        return Excel::download(new AttachmentExport, date('YmdHis') . '.xlsx');
    }
}
