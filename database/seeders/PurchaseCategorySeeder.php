<?php

namespace Database\Seeders;

use App\Models\PurchaseCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PurchaseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PurchaseCategory::create([
            'name' => 'Flash Cash',
            'short' => 'FCA',
        ]);
        PurchaseCategory::create([
            'name' => 'Invoice',
            'short' => 'INV',
        ]);
        PurchaseCategory::create([
            'name' => 'Man Power',
            'short' => 'MAP',
        ]);
        PurchaseCategory::create([
            'name' => 'Expense',
            'short' => 'EXP',
        ]);
        PurchaseCategory::create([
            'name' => 'Reimbursement',
            'short' => 'RMB',
        ]);
    }
}
