# Database Context

The application has been refactored to a generic school management domain.

## Core Tables

- `schools`: `id`, `name`, `country`, `region`, `city`, `address`, timestamps.
- `departments`: `id`, `name`, `address`, `school_id`, timestamps.
- `classrooms`: `id`, `name`, `address`, `department_id`, timestamps.
- `students`: `id`, `last_name`, `first_name`, `student_number`, `classroom_id`, `phone`, `email`, `diploma`, `city`, `address`, `education_level`, `height`, `weight`, `appreciation_score`, `absences_count`, `appreciation`, timestamps.
- `health_records`: `id`, `student_id`, `date`, `type`, `medical_prescription`, timestamps.

## Grade Tables

- `semesters`: `id`, `name`, `code`, `position`, timestamps.
- `subjects`: `id`, `name`, `code`, `semester_id`, timestamps.
- `student_grades`: `id`, `student_id`, `semester_id`, `subject_id`, `grade`, `appreciation`, timestamps.

## Relationships

- `School` has many `Department`.
- `Department` belongs to `School` and has many `Classroom`.
- `Classroom` belongs to `Department` and has many `Student`.
- `Student` belongs to `Classroom`, has many `HealthRecord`, and has many `StudentGrade`.
- `HealthRecord` belongs to `Student`.
- `Semester` has many `Subject` and `StudentGrade`.
- `Subject` belongs to `Semester` and has many `StudentGrade`.
- `StudentGrade` belongs to `Student`, `Semester`, and optionally `Subject`.

Legacy migrations remain in history, then `2026_06_13_000003_refactor_school_domain_schema.php` renames legacy tables/columns and creates the normalized semester grade schema.
