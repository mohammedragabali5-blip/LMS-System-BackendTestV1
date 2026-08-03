<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Mathematics', 'Programming', 'Physics', 'Chemistry', 'Biology',
            'History', 'Languages', 'Design', 'Business', 'Music',
        ]).' '.fake()->numberBetween(1, 999); // keep unique across repeated runs

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
