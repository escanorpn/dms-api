<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    
   public function run(): void
{
    User::updateOrCreate(
        ['email' => 'vector.pn@gmail.com'],
        [
            'name' => 'Super Admin',
            'password' => Hash::make('vector.pn@gmail.com'),
            'role' => 'super_admin',
            'password_updated' => true
        ]
    );
}
}
