# Ecole Manager

Ecole Manager is a Laravel 12 school management application for handling school hierarchy, students, semester averages, health records, role-based access, dashboards, and Excel-based bulk imports.

## Stack

- PHP 8.2
- Laravel 12
- Livewire 3
- Livewire Flux
- Blade
- Tailwind CSS 4
- Vite
- Sanctum
- Spatie Laravel Permission
- PhpSpreadsheet
- Chart.js
- Simple QR Code

## Active Domain

```text
School
└── Department
    └── Classroom
        └── Student
            ├── StudentGrade
            └── HealthRecord
```

`Absence` is not implemented as a separate entity. Attendance is currently represented by `students.absences_count`.

## Implemented Models

- `School`
- `Department`
- `Classroom`
- `Student`
- `StudentGrade`
- `HealthRecord`
- `Semester`
- `Subject`
- `User`
- `Role`

## Main Features

- Authentication, registration, email verification, password reset, and user settings.
- Role and permission enforcement with Spatie permissions, middleware, policies, controller authorization, and Blade checks.
- CRUD screens for schools, departments, classrooms, students, health records, and administrative users.
- Dashboard cards and Chart.js visualizations for students, classrooms, schools, departments, semester averages, and absences.
- Excel imports for students, health records, and semester averages.
- Downloadable tracked Excel templates from `resources/templates`.
- QR code generation for student details.
- Scoped access for directors, professors, secretariat users, and student-linked accounts.

## Roles

- `Operational Administrator`: full administration, user management, role assignment, and complete school-management access.
- `Director`: read access to schools, departments, classrooms, students, grades, health records, dashboards, and reports.
- `Professor`: scoped access to assigned classrooms and students, plus assigned grade management.
- `Service Secretariat`: student administration, imports, health-record management, and read access to hierarchy records.
- `Student`: access to the linked student record and permitted self-scoped data.

Existing `Service Secrétaire` assignments are migrated to `Service Secretariat` by `database/migrations/2026_07_23_020000_migrate_service_secretaire_role.php`.

## Routes

Main web areas:

- `/dashboard`
- `/students`
- `/students/search`
- `/classrooms`
- `/departments`
- `/schools`
- `/health-records`
- `/administration/users`
- `/settings/profile`
- `/settings/password`
- `/settings/appearance`
- `/settings/update-users`

Import endpoints:

- `POST /import-excel`
- `POST /import-health-records`
- `POST /import-semester-1`
- `POST /import-semester-2`
- `POST /import-semester-3`
- `POST /import-semester-4`
- `POST /import-semesters-5-6`

Template downloads:

- `/templates/students`
- `/templates/health-records`
- `/templates/semester-1`
- `/templates/semester-2`
- `/templates/semester-3`
- `/templates/semester-4`
- `/templates/semesters-5-6`

API endpoints:

- `GET /api/user`
- `POST /api/auth/register`

## Excel Import Rules

- Uploads are limited to 10 MB.
- Empty or header-only workbooks are rejected.
- Student imports require an existing numeric `classroom_id`.
- Classroom names are not accepted as import identifiers.
- Invalid or missing classroom references are skipped with row-level errors.
- Semester templates identify students by `student_id`, which the importer treats as the current `student_number`.
- Legacy classroom alias headers are not accepted: `compagnie`, `CIE`, `GPT`, `company`, `groupement`.

Health-record import headers:

```text
student_number, date, type, medical_prescription
```

## Database Notes

Current hierarchy keys:

- `departments.school_id`
- `classrooms.department_id`
- `students.classroom_id`
- `student_grades.student_id`
- `health_records.student_id`

Delete behavior:

- Parent hierarchy deletes are restricted when dependent records exist.
- Authorized browser requests return validation/session errors for those conflicts.
- Authorized JSON requests return `409 Conflict`.
- Unauthorized requests still return `403`.
- `student_grades` and `health_records` cascade when a student is deleted.

Legacy cleanup:

- `database/migrations/2026_07_23_000000_remove_compagnie_and_groupement_domain.php` defensively removes the old legacy domain if it exists.
- `database/migrations/2026_07_23_010000_restrict_school_hierarchy_foreign_keys.php` changes the repository-original cascade behavior to restricted deletes on the school hierarchy.

## Local Setup

1. Install dependencies:

```bash
composer install
npm install
```

2. Prepare environment:

```bash
cp .env.example .env
php artisan key:generate
```

3. Run migrations and seeders:

```bash
php artisan migrate --seed
```

4. Start development services:

```bash
composer run dev
```

For a production-style asset build:

```bash
npm run build
php artisan serve
```

## Verification

```bash
php artisan optimize:clear
php artisan migrate:fresh --seed
php artisan route:list
php artisan test
npm run build
```

Automated tests use SQLite in memory through `phpunit.xml`.

## Project Docs

- [Project Overview](docs/PROJECT_OVERVIEW.md)
- [API / Route Endpoints](docs/API_ENDPOINTS.md)
- [Database Context](docs/DATABASE_CONTEXT.md)

## Known Limitations

- There is no dedicated `Absence` model or table yet.
- Grade management is not exposed as a standalone public resource; it currently flows through student create/update and import paths.
- Some repository-root stray files such as `er->getRoleNames();`, `er->name;`, and `name` are present and appear unrelated to the active app.
