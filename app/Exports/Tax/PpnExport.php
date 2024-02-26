<?php

namespace App\Exports\Tax;

use App\Models\Purchase;
use App\Models\Tax;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithMapping;

class PpnExport implements FromView
{
    public function view(): View
    {
        $purchases = Purchase::where('ppn', '!=', null)->get();

        return view('report.tax.ppn', [
            'purchases' => $purchases
        ]);
    }
}
