<?php

namespace App\Exports;

use App\Models\Purchase;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;

class AttachmentExport implements FromView
{
    public function view(): View
    {
        $purchases = Purchase::all();

        return view('report.attachment', [
            'purchases' => $purchases
        ]);
    }
}
