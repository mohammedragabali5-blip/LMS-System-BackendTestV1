<?php

namespace Database\Seeders;

use Database\Seeders\PermissionSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $this->call([
             PermissionSeeder::class,  
            UserSeeder::class, 
                
            CategorySeeder::class,    
            CourseSeeder::class,      
            EnrollmentSeeder::class,  
            NotificationSeeder::class, 
            DemoAuditLogSeeder::class,
        ]);
    }
}
