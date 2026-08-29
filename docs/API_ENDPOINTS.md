# API / Route Endpoints

The active application is a Laravel 12 Blade/Livewire web app with a small Sanctum-protected API user endpoint and an API registration endpoint.

## Web Routes

| Method | Route | Controller | Authorization |
|---|---|---|---|
| GET | `/dashboard` | `DashboardController` | `dashboard.view.global` or `dashboard.view.scoped` |
| Resource | `/students` | `StudentController` | Student permissions plus `StudentPolicy` |
| GET | `/students/search` | `StudentSearchController@search` | Student view permissions |
| Resource | `/classrooms` | `ClassroomController` | Classroom permissions plus `ClassroomPolicy` |
| Resource | `/departments` | `DepartmentController` | Department permissions plus `DepartmentPolicy` |
| Resource | `/schools` | `SchoolController` | School permissions plus `SchoolPolicy` |
| Resource | `/health-records` | `HealthRecordController` | Health-record permissions plus `HealthRecordPolicy` |
| Resource | `/administration/users` | `UserController` | User permissions plus `UserPolicy` |
| GET | `/absences` | Closure view | `attendance.view_all` or `attendance.view_assigned` |

## Import Routes

| Method | Route | Controller | Purpose |
|---|---|---|---|
| POST | `/import-excel` | `ImportController@import` | Import students and semester averages |
| POST | `/import-health-records` | `InsertModule@healthRecords` | Import health records |
| POST | `/import-semester-1` | `InsertModule@semester1` | Import Semester 1 averages |
| POST | `/import-semester-2` | `InsertModule@semester2` | Import Semester 2 averages |
| POST | `/import-semester-3` | `InsertModule@semester3` | Import Semester 3 averages |
| POST | `/import-semester-4` | `InsertModule@semester4` | Import Semester 4 averages |
| POST | `/import-semesters-5-6` | `InsertModule@semesters5And6` | Import Semester 5 and 6 averages |

All import and template routes require `students.import`.
Excel uploads are limited to 10 MB. Student imports require numeric `classroom_id` values only and reject empty/header-only workbooks.

## Template Routes

- `/templates/students`
- `/templates/health-records`
- `/templates/semester-1`
- `/templates/semester-2`
- `/templates/semester-3`
- `/templates/semester-4`
- `/templates/semesters-5-6`

Template downloads read the tracked source files from `resources/templates`.

## API Routes

- `GET /api/user`: authenticated Sanctum user.
- `POST /api/auth/register`: student-role registration through `Auth\RegisterController`.

## Auth And Settings

Login, registration, password reset, email verification, logout, profile, password, appearance, and user-update settings routes are provided by the existing Laravel/Livewire auth stack.
