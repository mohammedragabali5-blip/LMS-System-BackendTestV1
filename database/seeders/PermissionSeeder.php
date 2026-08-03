<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            Permission::MANAGE_STUDENTS => 'Manage Students',
            Permission::MANAGE_COURSES => 'Manage Courses',
            Permission::UPLOAD_VIDEOS => 'Upload Videos',
            Permission::DELETE_COURSES => 'Delete Courses',
            Permission::REPORTS => 'Reports',
            Permission::SETTINGS => 'Settings',
        ];

        foreach ($permissions as $key => $label) {
            Permission::updateOrCreate(['key' => $key], ['label' => $label]);
        }
    }
}