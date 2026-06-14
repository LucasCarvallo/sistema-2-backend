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
        User::updateOrCreate([
            'email' => 'lucascarvallo333@gmail.com',
        ], [
            'name' => 'Lucas',
            'password' => bcrypt('joakopet333'),
        ]);

        User::updateOrCreate([
            'email' => 'test@mail.com',
        ], [
            'name' => 'Test',
            'password' => bcrypt('test'),
        ]);
    }
}
