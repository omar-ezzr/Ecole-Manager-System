<?php

namespace App\Support;

final class SchoolPermissions
{
    public const DASHBOARD_GLOBAL = 'dashboard.view.global';

    public const DASHBOARD_SCOPED = 'dashboard.view.scoped';

    public const USERS_VIEW = 'users.view';

    public const USERS_CREATE = 'users.create';

    public const USERS_UPDATE = 'users.update';

    public const USERS_DELETE = 'users.delete';

    public const USERS_ASSIGN_ROLES = 'users.assign_roles';

    public const STUDENTS_ALL = 'students.view_all';

    public const STUDENTS_ASSIGNED = 'students.view_assigned';

    public const STUDENTS_OWN = 'students.view_own';

    public const STUDENTS_CREATE = 'students.create';

    public const STUDENTS_UPDATE = 'students.update';

    public const STUDENTS_DELETE = 'students.delete';

    public const STUDENTS_IMPORT = 'students.import';

    public const SCHOOLS_VIEW = 'schools.view';

    public const SCHOOLS_MANAGE = 'schools.manage';

    public const DEPARTMENTS_VIEW = 'departments.view';

    public const DEPARTMENTS_MANAGE = 'departments.manage';

    public const CLASSROOMS_ALL = 'classrooms.view_all';

    public const CLASSROOMS_ASSIGNED = 'classrooms.view_assigned';

    public const CLASSROOMS_MANAGE = 'classrooms.manage';

    public const CLASSROOMS_ASSIGN = 'classrooms.assign_professors';

    public const SUBJECTS_VIEW = 'subjects.view';

    public const SUBJECTS_MANAGE = 'subjects.manage';

    public const ACADEMIC_YEARS_VIEW = 'academic_years.view';

    public const ACADEMIC_YEARS_MANAGE = 'academic_years.manage';

    public const SEMESTERS_VIEW = 'semesters.view';

    public const SEMESTERS_MANAGE = 'semesters.manage';

    public const TEACHING_ASSIGNMENTS_VIEW_ALL = 'teaching_assignments.view_all';

    public const TEACHING_ASSIGNMENTS_VIEW_OWN = 'teaching_assignments.view_own';

    public const TEACHING_ASSIGNMENTS_MANAGE = 'teaching_assignments.manage';

    public const GRADES_ALL = 'grades.view_all';

    public const GRADES_ASSIGNED = 'grades.view_assigned';

    public const GRADES_OWN = 'grades.view_own';

    public const GRADES_MANAGE_ALL = 'grades.manage_all';

    public const GRADES_MANAGE_ASSIGNED = 'grades.manage_assigned';

    public const ATTENDANCE_VIEW_ALL = 'attendance.view_all';

    public const ATTENDANCE_VIEW_ASSIGNED = 'attendance.view_assigned';

    public const ATTENDANCE_MANAGE_ALL = 'attendance.manage_all';

    public const ATTENDANCE_MANAGE_ASSIGNED = 'attendance.manage_assigned';

    public const HEALTH_VIEW = 'health_records.view';

    public const HEALTH_MANAGE = 'health_records.manage';

    public const REPORTS_VIEW = 'reports.view';

    public const IMPORTS_MANAGE = 'students.import';

    public static function all(): array
    {
        return array_values((new \ReflectionClass(self::class))->getConstants());
    }
}
