<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'mohammedragabali5@gmail.com'],
            [
                'fname' => 'Mohammed',
                'lname'=>"ragab Ali" ,
                'phone' => '01064922104',
                'password' => Hash::make('AdminMo2000'),
                'role' => 'admin',
                'status' => 'active',
            ]
        ) ;
    }
}