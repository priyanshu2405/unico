<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\SupportOption;

class SupportOptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $options = [
            ['type' => 'topic', 'name' => 'Booking Process'],
            ['type' => 'topic', 'name' => 'Payment Issue'],
            ['type' => 'topic', 'name' => 'Technical Support'],
            ['type' => 'topic', 'name' => 'Account Access'],
            ['type' => 'issue', 'name' => 'No exams found'],
            ['type' => 'issue', 'name' => 'Voucher not working'],
            ['type' => 'issue', 'name' => 'Location mismatch'],
            ['type' => 'issue', 'name' => 'Other Issue'],
        ];

        foreach ($options as $option) {
            SupportOption::create($option);
        }
    }
}
