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
        $categories = [
            'Flash Cash',
            'Invoice',
            'Man Power',
            'Expense',
            'Reimbursement',
        ];

        foreach ($categories as $category) {
            PurchaseCategory::create([
                'name' => $category
            ]);
        }
    }
}
