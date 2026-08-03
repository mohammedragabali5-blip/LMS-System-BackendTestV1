<?php

namespace App\Http\Controllers\Api\Assistant;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Course;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        return response()->json(
            Category::withCount('courses')->orderBy('name')->get()
        );
    }

    // NEW
    public function show(Category $category)
    {
        return response()->json(
            $category->loadCount('courses')->load('courses')
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        AuditLogService::log(
            'category.created',
            $category,
            "Created category '{$category->name}' (assistant)"
        );

        return response()->json($category, 201);
    }

    // NEW
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,'.$category->id,
        ]);

        $category->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        AuditLogService::log('category.updated', $category, "Updated category '{$category->name}'", $validated);

        return response()->json($category);
    }


    public function destroy(Category $category)
    {
        DB::transaction(function () use ($category) {
            $courseController = app(CourseController::class);
            $courses = Course::where('category_id', $category->id)->with('lessons')->get();

            foreach ($courses as $course) {
                $courseController->deleteCourseFilesAndLessons($course);

                AuditLogService::log(
                    'course.deleted',
                    $course,
                    "Deleted course '{$course->title}' (cascaded from deleting category '{$category->name}')"
                );

                $course->delete();
            }

            AuditLogService::log(
                'category.deleted',
                $category,
                "Deleted category '{$category->name}' and {$courses->count()} course(s) inside it"
            );

            $category->delete();
        });

        return response()->json(['message' => 'Category and its courses deleted.']);
    }
}
