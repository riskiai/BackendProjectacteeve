<?php

namespace App\Exports\Tax;

use App\Models\Purchase;
use App\Models\Tax;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;

class PphExport implements FromView
{
    public function view(): View
    {
        $purchases = Purchase::whereHas('taxPph')->get();

        return view('report.tax.pph', [
            'purchases' => $purchases
        ]);
    }
}
