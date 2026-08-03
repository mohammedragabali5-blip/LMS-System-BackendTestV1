<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::where('role', 'admin')
            ->where('email', '!=', 'mohammedragabali5@gmail.com')
            ->update(['role' => 'student']);

        $admin = User::updateOrCreate(
            ['email' => 'mohammedragabali5@gmail.com'],
            [
                'fname' => 'Mohammed',
                'lname' => 'Ragab Ali',
                'phone' => '01064922104',
                'password' => Hash::make('AdminMo2000'),
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at'=>now()
            ]
        );

        $assistant = User::updateOrCreate(
            ['email' => 'assistant@example.com'],
            [
                'fname' => 'Aya',
                'lname' => 'Naser',
                'phone' => '555-0100',
                'password' => Hash::make('Password123!'),
                'role' => 'assistant',
                'status' => 'active',
            ]
        );

        $permissions = Permission::whereIn('key', [
            Permission::MANAGE_STUDENTS,
            Permission::MANAGE_COURSES,
            Permission::UPLOAD_VIDEOS,
            Permission::DELETE_COURSES,
            Permission::REPORTS,
        ])->pluck('id');

        $syncData = [];

        foreach ($permissions as $permissionId) {
            $syncData[$permissionId] = [
                'granted_by' => $admin->id,
            ];
        }

        $assistant->permissions()->sync($syncData);

        User::updateOrCreate(
            ['email' => 'student@example.com'],
            [
                'fname' => 'Sam',
                'lname' => 'Kamal',
                'phone' => '555-0200',
                'password' => Hash::make('Password123!'),
                'role' => 'student',
                'status' => 'active',
            ]
        );

        User::updateOrCreate(
            ['email' => 'nina@example.com'],
            [
                'fname' => 'Nina',
                'lname' => 'Elmohamady',
                'phone' => null,
                'password' => Hash::make('Password123!'),
                'role' => 'student',
                'status' => 'inactive',
            ]
        );

        User::factory()
            ->student()
            ->active()
            ->count(25)
            ->create();

        User::factory()
            ->student()
            ->inactive()
            ->count(5)
            ->create();

        User::factory()
            ->student()
            ->disabled()
            ->count(3)
            ->create();

        User::factory()
            ->assistant()
            ->count(2)
            ->create();
    }
}
