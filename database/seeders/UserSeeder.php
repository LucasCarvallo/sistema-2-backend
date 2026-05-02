<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate([
            'name' => 'Lucas',
            'email' => 'lucascarvallo333@gmail.com',
            'password' => bcrypt('joakopet333'),
        ]);
    }
}
