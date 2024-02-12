<?php

namespace App\Exports;

use App\Models\Purchase;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;

class PurchaseExport implements FromView
{
    protected $type;

    public function __construct($type)
    {
        $this->type = $type;
    }

    public function view(): View
    {
        $purchases = Purchase::where('purchase_id', $this->type)->get();

        return view('report.purchase', [
            'purchases' => $purchases
        ]);
    }
}
