<?php

namespace Database\Seeders;

use App\Models\ContactType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContactTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = ['Vendor', 'Man Power', 'Client'];

        foreach ($types as $type) {
            ContactType::create([
                'name' => $type
            ]);
        }
    }
}
