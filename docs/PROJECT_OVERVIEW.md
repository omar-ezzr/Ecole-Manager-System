# Project Overview

Ecole Manager is a Laravel 12 application for school administration. The frontend is server-rendered Blade with Livewire 3, Livewire Flux components, Tailwind CSS, Vite assets, and Chart.js dashboard charts.

## Active Domain

```text
School
└── Department
    └── Classroom
        └── Student
            ├── StudentEnrollment
            │   └── AttendanceRecord
            ├── StudentGrade
            └── HealthRecord
```

Daily attendance is stored in `attendance_records` and resolved through historical `student_enrollments`. The retired `students.absences_count` field is removed from the active schema; compatible legacy workbook headers are ignored rather than converted into attendance history.

## Current Models

- `School`
- `Department`
- `Classroom`
- `Student`
- `StudentEnrollment`
- `AttendanceRecord`
- `StudentGrade`
- `HealthRecord`
- `Semester`
- `Subject`
- `User`
- `Role`

## Current Roles

- `Operational Administrator`
- `Director`
- `Professor`
- `Service Secretariat`
- `Student`

Authorization is enforced with Spatie permissions, route middleware, model policies, controller `$this->authorize(...)` calls, and Blade permission checks.

`Service Secretariat` can view schools, departments, and classrooms but cannot manage hierarchy records. Existing `Service Secrétaire` role assignments are migrated to `Service Secretariat`.

Grade creation is authorized against the target student, so assigned professors can create grades only for students in their assigned classrooms.

Excel templates are tracked in `resources/templates`, uploads are limited to 10 MB, student imports use numeric `classroom_id` values only, and empty/header-only workbooks are rejected.

Referenced schools, departments, and classrooms produce conflict or validation responses when an authorized user tries to delete them; this is separate from authorization denial.

## Current Main Routes

- `/dashboard`
- `/students`
- `/classrooms`
- `/departments`
- `/schools`
- `/health-records`
- `/administration/users`
- `/students/search`
- `/import-excel`
- `/import-health-records`
- `/import-semester-1`
- `/import-semester-2`
- `/import-semester-3`
- `/import-semester-4`
- `/import-semesters-5-6`

The legacy training-domain resources are removed from the active app. Historical mentions are retained only in the defensive removal migration and removal regression tests.
