<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(){
        return response()->json(Category::withCount('courses')->get()) ;
    }

   public function store(Request $request)
    {
        $validated = $request->validate(['name' => 'required|string|max:255|unique:categories,name']);
        $category = Category::create(['name' => $validated['name'], 'slug' => Str::slug($validated['name'])]);

        AuditLogService::log('category.created', $category);

        return response()->json($category, 201);
    }
    public function update(Request $request , Category $category){
         $validated = $request->validate(['name' => 'required|string|max:255|unique:categories,name,'.$category->id]);
        $category->update(['name' => $validated['name'], 'slug' => Str::slug($validated['name'])]);

        return response()->json($category);
    }


public function destroy(Category $category)
{
    DB::transaction(function () use ($category) {

        foreach ($category->courses as $course) {

            AuditLogService::log('course.deleted', $course);

            $course->delete();
        }

        AuditLogService::log('category.deleted', $category);

        $category->delete();
    });

    return response()->json([
        'message' => 'Category and all its courses deleted successfully.'
    ]);
}
}