# API / Route Endpoints

This Laravel 12 application uses Blade, Livewire, Flux, Eloquent, and standard Laravel controllers.

## Main Web Routes

| Method | Route | Controller | Purpose |
|---|---|---|---|
| GET | `/dashboard` | Closure in `routes/web.php` | Main school dashboard |
| Resource | `/students` | `StudentController` | Manage students |
| Resource | `/classrooms` | `ClassroomController` | Manage classrooms |
| Resource | `/departments` | `DepartmentController` | Manage departments |
| Resource | `/schools` | `SchoolController` | Manage schools |
| Resource | `/health-records` | `HealthRecordController` | Manage student health records |
| GET | `/students/search` | `StudentSearchController` | Search students by number or name |

## Import Routes

| Method | Route | Controller | Purpose |
|---|---|---|---|
| POST | `/import-excel` | `ImportController@import` | Import students and semester averages |
| POST | `/import-health-records` | `InsertModule@healthRecords` | Import health records |
| POST | `/import-semester-1` | `InsertModule@semester1` | Import Semester 1 grades |
| POST | `/import-semester-2` | `InsertModule@semester2` | Import Semester 2 grades |
| POST | `/import-semester-3` | `InsertModule@semester3` | Import Semester 3 grades |
| POST | `/import-semester-4` | `InsertModule@semester4` | Import Semester 4 grades |
| POST | `/import-semesters-5-6` | `InsertModule@semesters5And6` | Import Semester 5 and Semester 6 grades |

## Auth Routes

Login, registration, password reset, email verification, logout, and settings routes are kept from the existing Laravel/Livewire auth stack.
