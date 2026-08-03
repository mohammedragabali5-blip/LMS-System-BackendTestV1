<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Matches the examples given in the requirements doc (AD-008).
        foreach (['Mathematics', 'Programming', 'Physics'] as $name) {
            Category::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }
    }
}
