# Database Context

The application uses Laravel Eloquent migrations and seeders for a school-management schema.

## Core Tables

- `schools`: `id`, `name`, `country`, `region`, `city`, `address`, timestamps.
- `departments`: `id`, `school_id`, `name`, `address`, timestamps.
- `classrooms`: `id`, `department_id`, `name`, `address`, timestamps.
- `students`: `id`, `classroom_id`, `student_number`, `first_name`, `last_name`, `phone`, `email`, `diploma`, `city`, `address`, `education_level`, `height`, `weight`, `appreciation_score`, `absences_count`, `appreciation`, timestamps.
- `health_records`: `id`, `student_id`, `date`, `type`, `medical_prescription`, timestamps.
- `semesters`: `id`, `name`, `code`, `position`, timestamps.
- `subjects`: `id`, `semester_id`, `name`, `code`, timestamps.
- `student_grades`: `id`, `student_id`, `semester_id`, `subject_id`, `semester_average_slot`, `grade`, `appreciation`, timestamps.
- `users`: includes nullable unique `student_id` for student-user account linking.
- `classroom_professor`: `classroom_id`, `professor_id`, timestamps.
- Spatie permission tables from `2025_03_23_135348_create_permission_tables.php`.

## Relationships

- `School::departments()`
- `Department::school()`
- `Department::classrooms()`
- `Classroom::department()`
- `Classroom::students()`
- `Classroom::professors()`
- `Student::classroom()`
- `Student::grades()`
- `Student::healthRecords()`
- `Student::user()`
- `StudentGrade::student()`
- `StudentGrade::semester()`
- `StudentGrade::subject()`
- `HealthRecord::student()`
- `User::student()`
- `User::assignedClassrooms()`

## Foreign Key Behavior

- `departments.school_id`: restricted on delete by corrective migration.
- `classrooms.department_id`: restricted on delete by corrective migration.
- `students.classroom_id`: restricted on delete by corrective migration.
- `student_grades.student_id`: cascades on student delete.
- `health_records.student_id`: cascades on student delete.
- `users.student_id`: nulls on student delete.
- `classroom_professor.classroom_id` and `professor_id`: cascade on delete.

## Legacy Removal

`database/migrations/2026_07_23_000000_remove_compagnie_and_groupement_domain.php` defensively removes old legacy tables and columns when present. Its rollback recreates historical structure only for migration reversibility and must not be treated as a production restoration path.

`database/migrations/2026_07_23_010000_restrict_school_hierarchy_foreign_keys.php` changes the parent hierarchy from the repository-original cascade delete behavior to restrict delete behavior. Its `down()` method restores that repository-original cascade behavior and does not attempt to preserve manual FK changes made outside migrations.

`database/migrations/2026_07_23_020000_migrate_service_secretaire_role.php` safely upgrades existing `Service Secrétaire` Spatie role assignments and permissions to `Service Secretariat`.
