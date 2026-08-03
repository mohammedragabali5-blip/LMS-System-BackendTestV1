<?php

use App\Http\Controllers\Api\Admin\AssistantController;
use App\Http\Controllers\Api\Admin\AuditLogController;
use App\Http\Controllers\Api\Admin\BackupController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Api\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Api\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Api\Admin\SubscriptionController;
use App\Http\Controllers\Api\Assistant\CourseAssignmentController;
use App\Http\Controllers\Api\Assistant\CourseController as AssistantCourseController;
use App\Http\Controllers\Api\Assistant\ReportController as AssistantReportController;
use App\Http\Controllers\Api\Assistant\StudentController as AssistantStudentController;
// 👇 NEW: added CategoryController for assistant
use App\Http\Controllers\Api\Assistant\CategoryController as AssistantCategoryController;
use App\Http\Controllers\Api\Auth\EmailVerificationController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\PasswordController;
use App\Http\Controllers\Api\Auth\RegisterController;

use App\Http\Controllers\Api\Student\DashboardController;
use App\Http\Controllers\Api\Student\LessonController;
use App\Http\Controllers\Api\Student\NotificationController;
use App\Http\Controllers\Api\Student\ProfileController;
use App\Http\Controllers\Api\Student\CourseController as StudentCourseController;
use App\Http\Controllers\Api\VideoStreamController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);
Route::post('/forgot-password', [PasswordController::class, 'sendResetLink']);
Route::post('/reset-password', [PasswordController::class, 'reset']);
Route::get('/stream/{token}', [VideoStreamController::class, 'stream'])
    ->name('stream.video');

Route::middleware(['auth:sanctum', 'role:student', 'enrollment.active'])
    ->group(function () {

        Route::get(
            '/courses/{course}/lessons/{lesson}/pdf',
            [LessonController::class, 'downloadPdf']
        )->name('lesson.pdf');
    });
/*
|--------------------------------------------------------------------------
| Email Verification Routes
|--------------------------------------------------------------------------
*/
Route::get(
    '/email/verify/{id}/{hash}',
    [EmailVerificationController::class, 'verify']
)
    ->middleware(['signed'])
    ->name('verification.verify');


Route::middleware('auth:sanctum')->post(
    '/email/verification-notification',
    [EmailVerificationController::class, 'resend']
)
    ->middleware('throttle:6,1');


/*
|--------------------------------------------------------------------------
|Student
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout']);

    Route::middleware('role:student')->prefix('student')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/courses', [StudentCourseController::class, 'index']);
        Route::put('/profile', [ProfileController::class, 'update']);
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::patch('/notifications/{id}/read', [NotificationController::class, 'markRead']);

        Route::middleware('enrollment.active')->prefix('courses/{course}')->group(function () {
            Route::get('/', [StudentCourseController::class, 'show']);
            Route::post('/lessons/{lesson}/playback', [LessonController::class, 'requestPlayback']);
            Route::put('/lessons/{lesson}/progress', [LessonController::class, 'updateProgress']);
        });
    });
    Route::middleware('role:assistant,admin')->prefix('assistant')->group(function () {
        Route::middleware('permission:manage_students')->group(function () {
            Route::get('/students', [AssistantStudentController::class, 'index']);
            Route::post('/students', [AssistantStudentController::class, 'store']);
            Route::get('/students/{student}', [AssistantStudentController::class, 'show']);
            Route::put('/students/{student}', [AssistantStudentController::class, 'update']);
            Route::patch('/students/{student}/disable', [AssistantStudentController::class, 'disable']);
            Route::patch('/students/{student}/enable', [AssistantStudentController::class, 'enable']);

            Route::delete('/students/{student}', [AssistantStudentController::class, 'destroy']);
            Route::patch('/students/{student}/restore', [AssistantStudentController::class, 'restore'])
                ->withTrashed();

            Route::post('/assignments', [CourseAssignmentController::class, 'store']);
            Route::patch('/assignments/{enrollment}/extend', [CourseAssignmentController::class, 'extend']);
            Route::delete('/assignments/{enrollment}', [CourseAssignmentController::class, 'destroy']);
        });

        Route::middleware('permission:manage_courses')->group(function () {
            Route::get('/courses', [AssistantCourseController::class, 'index']);
            Route::get('/courses/{course}', [AssistantCourseController::class, 'show']);
            Route::post('/courses', [AssistantCourseController::class, 'store']);

            Route::put('/courses/{course}', [AssistantCourseController::class, 'update']);
               Route::delete('/courses/{course}', [AssistantCourseController::class, 'destroy']);
            Route::post('/courses/{course}/thumbnail', [AssistantCourseController::class, 'uploadThumbnail']);
            Route::patch('/courses/{course}/lessons/reorder', [AssistantCourseController::class, 'reorderLessons']);

            Route::get('/categories', [AssistantCategoryController::class, 'index']);
            Route::post('/categories', [AssistantCategoryController::class, 'store']);
          Route::patch('/categories/{category}', [AssistantCategoryController::class, 'update']);
             Route::delete('/categories/{category}', [AssistantCategoryController::class, 'destroy']);

        });

        Route::middleware('permission:upload_videos')->group(function () {
            Route::post('/courses/{course}/lessons', [AssistantCourseController::class, 'storeLesson']);
        });

        Route::middleware('permission:delete_courses')->group(function () {
            Route::delete('/courses/{course}/lessons/{lesson}', [AssistantCourseController::class, 'destroyLesson']);
        });

        Route::middleware('permission:reports')->group(function () {
            Route::get('/courses/{course}/report', [AssistantReportController::class, 'studentProgress']);
        });
    });

    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/assistants', [AssistantController::class, 'index']);
        Route::post('/assistants', [AssistantController::class, 'store']);
        Route::put('/assistants/{assistant}', [AssistantController::class, 'update']);
        Route::delete('/assistants/{assistant}', [AssistantController::class, 'destroy']);
        Route::put('/assistants/{assistant}/permissions', [AssistantController::class, 'assignPermissions']);


        Route::get('/students', [AdminStudentController::class, 'index']);
        Route::post('/students', [AdminStudentController::class, 'store']);
        Route::put('/students/{student}', [AdminStudentController::class, 'update']);
        Route::delete('/students/{student}', [AdminStudentController::class, 'destroy']);
        Route::patch('/students/{id}/restore', [AdminStudentController::class, 'restore']);


        Route::get('/courses', [AdminCourseController::class, 'index']);
        Route::post('/courses', [AdminCourseController::class, 'store']);

        Route::post('/courses/{course}/thumbnail', [AssistantCourseController::class, 'uploadThumbnail']);
        Route::patch('/courses/{course}/archive', [AdminCourseController::class, 'archive']);
        Route::post('/courses/{course}/lessons', [AssistantCourseController::class, 'storeLesson']);

        Route::patch('/courses/{course}/lessons/reorder', [AssistantCourseController::class, 'reorderLessons']);

        Route::delete('/courses/{course}/lessons/{lesson}', [AssistantCourseController::class, 'destroyLesson']);

        Route::get('/courses/{course}', [AdminCourseController::class, 'show']);
        Route::put('/courses/{course}', [AdminCourseController::class, 'update']);
        Route::delete('/courses/{course}', [AdminCourseController::class, 'destroy']); // keep only this one

        Route::get('/categories', [CategoryController::class, 'index']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::get('/categories/{category}', [AssistantCategoryController::class, 'show']);
        Route::put('/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

        Route::post('/subscriptions/assign', [SubscriptionController::class, 'assign']);
        Route::post('/subscriptions/bulk-assign', [SubscriptionController::class, 'bulkAssign']);
        Route::patch('/subscriptions/{enrollment}/renew', [SubscriptionController::class, 'renew']);
        Route::patch('/subscriptions/{enrollment}/revoke', [SubscriptionController::class, 'revoke']);

        Route::get('/reports/dashboard', [AdminReportController::class, 'dashboardStatistics']);
        Route::get('/reports/students', [AdminReportController::class, 'studentReports']);
        Route::get('/reports/courses', [AdminReportController::class, 'courseReports']);

        Route::get('/audit-logs', [AuditLogController::class, 'index']);

        Route::post('/backups', [BackupController::class, 'create']);
        Route::post('/backups/restore', [BackupController::class, 'restore']);
    });
});
