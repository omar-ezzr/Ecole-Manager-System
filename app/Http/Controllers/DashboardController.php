<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Department;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentGrade;
use App\Support\SchoolPermissions as P;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        $visibleStudents = Student::visibleTo($user);
        $visibleClassrooms = Classroom::query();

        if (! $user->can(P::CLASSROOMS_ALL)) {
            if ($user->can(P::CLASSROOMS_ASSIGNED)) {
                $visibleClassrooms->whereHas('professors', fn ($professors) => $professors
                    ->whereKey($user->id));
            } else {
                $visibleClassrooms->whereIn('id', (clone $visibleStudents)->select('classroom_id'));
            }
        }

        return view('dashboard', [
            'totalStudents' => (clone $visibleStudents)->count(),
            'totalClassrooms' => (clone $visibleClassrooms)->count(),
            'totalDepartments' => $user->can(P::DEPARTMENTS_VIEW)
                ? Department::count()
                : Department::whereHas('classrooms', fn ($classrooms) => $classrooms
                    ->whereIn('classrooms.id', (clone $visibleClassrooms)->select('classrooms.id')))->count(),
            'totalSchools' => $user->can(P::SCHOOLS_VIEW)
                ? School::count()
                : School::whereHas('departments.classrooms', fn ($classrooms) => $classrooms
                    ->whereIn('classrooms.id', (clone $visibleClassrooms)->select('classrooms.id')))->count(),
            'semesterAverageCharts' => $this->semesterAverageCharts(),
        ]);
    }

    private function semesterAverageCharts(): array
    {
        return collect(range(1, 6))
            ->mapWithKeys(fn (int $position) => [$position => $this->semesterAverageChart($position)])
            ->all();
    }

    private function semesterAverageChart(int $position): array
    {
        $user = auth()->user();
        $query = StudentGrade::query()
            ->join('semesters', 'student_grades.semester_id', '=', 'semesters.id')
            ->join('students', 'student_grades.student_id', '=', 'students.id')
            ->join('classrooms', 'students.classroom_id', '=', 'classrooms.id')
            ->where('semesters.position', $position)
            ->whereIn('students.id', Student::visibleTo($user)->select('students.id'))
            ->whereNotNull('student_grades.grade')
            ->select('classrooms.name as label', DB::raw('ROUND(AVG(student_grades.grade), 2) as average_grade'))
            ->groupBy('classrooms.id', 'classrooms.name')
            ->orderBy('classrooms.name');

        if ($user->can(P::GRADES_ALL) || $user->can(P::GRADES_OWN)) {
            $query->whereNull('student_grades.teaching_assignment_id')
                ->whereNull('student_grades.subject_id');
        } elseif ($user->can(P::GRADES_ASSIGNED)) {
            $query->join('teaching_assignments', 'student_grades.teaching_assignment_id', '=', 'teaching_assignments.id')
                ->where('teaching_assignments.professor_id', $user->id)
                ->whereColumn('teaching_assignments.classroom_id', 'students.classroom_id')
                ->whereColumn('teaching_assignments.academic_year_id', 'semesters.academic_year_id')
                ->whereColumn('teaching_assignments.subject_id', 'student_grades.subject_id');
        } else {
            $query->whereRaw('1 = 0');
        }

        $classroomAverages = $query->get();

        return [
            'labels' => $classroomAverages->pluck('label')->all(),
            'data' => $classroomAverages->pluck('average_grade')->map(fn ($grade) => (float) $grade)->all(),
            'summary' => $classroomAverages->isEmpty() ? null : round((float) $classroomAverages->avg('average_grade'), 2),
            'groupedBy' => 'Classroom',
        ];
    }
}
