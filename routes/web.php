<?php

use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\HealthRecordController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\InsertModule;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentSearchController;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\UpdateUsers;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// search students
Route::get('students/search',[StudentSearchController::class , 'search'])->middleware(['auth','verified'])->name('students.search');

// route of main models
Route::resource('students', StudentController::class)->middleware(['auth', 'verified']);
Route::resource('classrooms', ClassroomController::class)->middleware(['auth', 'verified']);
Route::resource('departments', DepartmentController::class)->middleware(['auth', 'verified']);
Route::resource('schools', SchoolController::class)->middleware(['auth', 'verified']);
Route::resource('health-records', HealthRecordController::class)->middleware(['auth', 'verified']);

//dashboard route
Route::get('dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

// routes/web.php for excel importation
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/import-excel', [ImportController::class, 'import'])->name('excel.import');
    Route::post('/import-health-records', [InsertModule::class, 'healthRecords'])->name('excel.importHealthRecords');
    Route::post('/import-semester-1', [InsertModule::class, 'semester1'])->name('excel.importSemester1');
    Route::post('/import-semester-2', [InsertModule::class, 'semester2'])->name('excel.importSemester2');
    Route::post('/import-semester-3', [InsertModule::class, 'semester3'])->name('excel.importSemester3');
    Route::post('/import-semester-4', [InsertModule::class, 'semester4'])->name('excel.importSemester4');
    Route::post('/import-semesters-5-6', [InsertModule::class, 'semesters5And6'])->name('excel.importSemesters5And6');
});


Route::get('/absences',function(){
    return view('charts.absences');
});

// Mount the Livewire component inside dashboard (Livewire handles it automatically)
Route::middleware(['auth'])->group(function () {
    Route::get('settings/profile',Profile::class)->name('settings.profile');
    Route::get('settings/password',Password::class)->name('settings.password');
    Route::get('settings/appearance',Appearance::class)->name('settings.appearance');
    Route::get('settings/update-users',UpdateUsers::class)->name('settings.update-users');
});




$downloadTemplate = function (string $fileName, string $missingMessage) {
    $filePath = storage_path("app/public/{$fileName}");

    abort_unless(file_exists($filePath), 404, $missingMessage);

    return response()->download($filePath, $fileName, [
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        'Pragma' => 'no-cache',
        'Expires' => '0',
    ]);
};

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

Route::get('/templates/semester-3', fn () => $downloadTemplate(
    'semester-3-template.xlsx',
    'Semester 3 template file not found.'
))->name('templates.semester-3');

Route::get('/templates/semester-4', fn () => $downloadTemplate(
    'semester-4-template.xlsx',
    'Semester 4 template file not found.'
))->name('templates.semester-4');

Route::get('/templates/semesters-5-6', fn () => $downloadTemplate(
    'semesters-5-6-template.xlsx',
    'Semesters 5 and 6 template file not found.'
))->name('templates.semesters-5-6');





require __DIR__.'/auth.php';
