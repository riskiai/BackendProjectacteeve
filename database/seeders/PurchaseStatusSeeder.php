<?php

namespace Database\Seeders;

use App\Models\PurchaseStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PurchaseStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $status = [
            'Awaiting',
            'Verified',
            'Open',
            'Overdue',
            'Due Date',
            'Rejected',
            'Paid',
        ];

        foreach ($status as $status) {
            PurchaseStatus::create([
                'name' => $status
            ]);
        }
    }
}
