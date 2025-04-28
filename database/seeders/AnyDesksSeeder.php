<?php

namespace Database\Seeders;

use App\Models\AnyDesk;
use Illuminate\Database\Seeder;

class AnyDesksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        AnyDesk::factory()
            ->count(15)
            ->create();
    }
}
