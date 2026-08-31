<?php

use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\HealthRecordController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\InsertModule;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentGradeController;
use App\Http\Controllers\StudentSearchController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TeachingAssignmentController;
use App\Http\Controllers\UserController;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\UpdateUsers;
use App\Models\User;
use App\Support\SchoolPermissions as P;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    $userResource = fn () => Route::resource('administration/users', UserController::class)
        ->names('administration.users')
        ->parameters(['users' => 'user']);

    $userResource()->only(['index'])->middleware('permission:'.P::USERS_VIEW);
    $userResource()->only(['create', 'store'])->middleware('permission:'.P::USERS_CREATE);
    $userResource()->only(['edit', 'update'])->middleware('permission:'.P::USERS_UPDATE);
    $userResource()->only(['destroy'])->middleware('permission:'.P::USERS_DELETE);

    $studentViewPermissions = P::STUDENTS_ALL.'|'.P::STUDENTS_ASSIGNED.'|'.P::STUDENTS_OWN;
    Route::get('students/search', [StudentSearchController::class, 'search'])
        ->middleware('permission:'.$studentViewPermissions)
        ->name('students.search');
    Route::resource('students', StudentController::class)->only(['create', 'store'])
        ->middleware('permission:'.P::STUDENTS_CREATE);
    Route::resource('students', StudentController::class)->only(['edit', 'update'])
        ->middleware('permission:'.P::STUDENTS_UPDATE);
    Route::resource('students', StudentController::class)->only(['destroy'])
        ->middleware('permission:'.P::STUDENTS_DELETE);
    Route::resource('students', StudentController::class)->only(['index', 'show'])
        ->middleware('permission:'.$studentViewPermissions);

    $classroomViewPermissions = P::CLASSROOMS_ALL.'|'.P::CLASSROOMS_ASSIGNED;
    Route::resource('classrooms', ClassroomController::class)->except(['index', 'show'])
        ->middleware('permission:'.P::CLASSROOMS_MANAGE);
    Route::resource('classrooms', ClassroomController::class)->only(['index', 'show'])
        ->middleware('permission:'.$classroomViewPermissions);
    Route::post('classrooms/{classroom}/subjects', [ClassroomController::class, 'assignSubject'])
        ->middleware('permission:'.P::CLASSROOMS_MANAGE)
        ->name('classrooms.subjects.store');
    Route::delete('classrooms/{classroom}/subjects/{classroom_subject}', [ClassroomController::class, 'removeSubject'])
        ->middleware('permission:'.P::CLASSROOMS_MANAGE)
        ->name('classrooms.subjects.destroy');

    Route::resource('departments', DepartmentController::class)->except(['index', 'show'])
        ->middleware('permission:'.P::DEPARTMENTS_MANAGE);
    Route::resource('departments', DepartmentController::class)->only(['index', 'show'])
        ->middleware('permission:'.P::DEPARTMENTS_VIEW);

    Route::resource('schools', SchoolController::class)->except(['index', 'show'])
        ->middleware('permission:'.P::SCHOOLS_MANAGE);
    Route::resource('schools', SchoolController::class)->only(['index', 'show'])
        ->middleware('permission:'.P::SCHOOLS_VIEW);

    Route::resource('health-records', HealthRecordController::class)->except(['index', 'show'])
        ->middleware('permission:'.P::HEALTH_MANAGE);
    Route::resource('health-records', HealthRecordController::class)->only(['index', 'show'])
        ->middleware('permission:'.P::HEALTH_VIEW);

    Route::resource('subjects', SubjectController::class)->only(['create', 'store', 'edit', 'update', 'destroy'])
        ->middleware('permission:'.P::SUBJECTS_MANAGE);
    Route::resource('subjects', SubjectController::class)->only(['index', 'show'])
        ->middleware('permission:'.P::SUBJECTS_VIEW);

    Route::resource('academic-years', AcademicYearController::class)->only(['create', 'store', 'edit', 'update', 'destroy'])
        ->middleware('permission:'.P::ACADEMIC_YEARS_MANAGE);
    Route::resource('academic-years', AcademicYearController::class)->only(['index', 'show'])
        ->middleware('permission:'.P::ACADEMIC_YEARS_VIEW);
    Route::post('academic-years/{academic_year}/activate', [AcademicYearController::class, 'activate'])
        ->middleware('permission:'.P::ACADEMIC_YEARS_MANAGE)
        ->name('academic-years.activate');

    Route::resource('semesters', SemesterController::class)->only(['create', 'store', 'edit', 'update', 'destroy'])
        ->middleware('permission:'.P::SEMESTERS_MANAGE);
    Route::resource('semesters', SemesterController::class)->only(['index', 'show'])
        ->middleware('permission:'.P::SEMESTERS_VIEW);

    Route::resource('teaching-assignments', TeachingAssignmentController::class)->only(['create', 'store', 'edit', 'update', 'destroy'])
        ->middleware('permission:'.P::TEACHING_ASSIGNMENTS_MANAGE);
    Route::resource('teaching-assignments', TeachingAssignmentController::class)->only(['index', 'show'])
        ->middleware('permission:'.P::TEACHING_ASSIGNMENTS_VIEW_ALL.'|'.P::TEACHING_ASSIGNMENTS_VIEW_OWN);
    Route::get('teaching-assignments/{teaching_assignment}/attendance', [AttendanceController::class, 'index'])
        ->middleware('permission:'.P::ATTENDANCE_VIEW_ALL.'|'.P::ATTENDANCE_VIEW_ASSIGNED)
        ->name('teaching-assignments.attendance.index');
    Route::post('teaching-assignments/{teaching_assignment}/attendance', [AttendanceController::class, 'store'])
        ->middleware('permission:'.P::ATTENDANCE_MANAGE_ALL.'|'.P::ATTENDANCE_MANAGE_ASSIGNED)
        ->name('teaching-assignments.attendance.store');

    Route::resource('student-grades', StudentGradeController::class)->only(['index'])
        ->middleware('permission:'.P::GRADES_ALL.'|'.P::GRADES_ASSIGNED.'|'.P::GRADES_OWN);
    Route::resource('student-grades', StudentGradeController::class)->only(['store'])
        ->middleware('permission:'.P::GRADES_MANAGE_ALL.'|'.P::GRADES_MANAGE_ASSIGNED);
    Route::get('students/{student}/results', [StudentGradeController::class, 'results'])
        ->middleware('permission:'.P::GRADES_ALL.'|'.P::GRADES_ASSIGNED.'|'.P::GRADES_OWN)
        ->name('student-grades.results');
    Route::get('students/{student}/report-cards/{semester}', [StudentGradeController::class, 'reportCard'])
        ->middleware('permission:'.P::GRADES_ALL.'|'.P::GRADES_ASSIGNED.'|'.P::GRADES_OWN)
        ->name('student-grades.report-card');
});

// dashboard route
// Global supervisors and scoped roles may open the dashboard; the controller
// still scopes its data according to the authenticated user's role.
Route::get('dashboard', DashboardController::class)->middleware([
    'auth',
    'verified',
    'permission:'.P::DASHBOARD_GLOBAL.'|'.P::DASHBOARD_SCOPED,
])->name('dashboard');

// routes/web.php for excel importation
Route::middleware(['auth', 'verified', 'permission:'.P::STUDENTS_IMPORT])->group(function () {
    Route::post('/import-excel', [ImportController::class, 'import'])->name('excel.import');
    Route::post('/import-health-records', [InsertModule::class, 'healthRecords'])->name('excel.importHealthRecords');
    Route::post('/import-semester-1', [InsertModule::class, 'semester1'])->name('excel.importSemester1');
    Route::post('/import-semester-2', [InsertModule::class, 'semester2'])->name('excel.importSemester2');
});

Route::get('/absences', function () {
    return view('charts.absences');
})->middleware([
    'auth',
    'verified',
    'permission:'.P::ATTENDANCE_VIEW_ALL.'|'.P::ATTENDANCE_VIEW_ASSIGNED,
]);

// Mount the Livewire component inside dashboard (Livewire handles it automatically)
Route::middleware(['auth'])->group(function () {
    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');
    Route::get('settings/update-users', UpdateUsers::class)
        ->middleware(['verified', 'can:viewAny,'.User::class])
        ->name('settings.update-users');
});

$downloadTemplate = function (string $fileName, string $missingMessage) {
    $filePath = resource_path("templates/{$fileName}");

    abort_unless(file_exists($filePath), 404, $missingMessage);

    return response()->download($filePath, $fileName, [
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        'Pragma' => 'no-cache',
        'Expires' => '0',
    ]);
};

Route::middleware(['auth', 'verified', 'permission:'.P::STUDENTS_IMPORT])->group(function () use ($downloadTemplate) {
    Route::get('/templates/students', fn () => $downloadTemplate(
        'students-template.xlsx',
        'Students template file not found.'
    ))->name('templates.students');

    Route::get('/templates/health-records', fn () => $downloadTemplate(
        'health-records-template.xlsx',
        'Health records template file not found.'
    ))->name('templates.health-records');

    Route::get('/templates/semester-1', fn () => $downloadTemplate(
        'semester-1-template.xlsx',
        'Semester 1 template file not found.'
    ))->name('templates.semester-1');

    Route::get('/templates/semester-2', fn () => $downloadTemplate(
        'semester-2-template.xlsx',
        'Semester 2 template file not found.'
    ))->name('templates.semester-2');
});

require __DIR__.'/auth.php';
