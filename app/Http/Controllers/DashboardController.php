<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Department;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentGrade;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'totalStudents' => Student::visibleTo(auth()->user())->count(),
            'totalClassrooms' => auth()->user()->isProfessor() ? auth()->user()->assignedClassrooms()->count() : Classroom::count(),
            'totalDepartments' => Department::count(),
            'totalSchools' => School::count(),
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
        $classroomAverages = StudentGrade::query()
            ->join('semesters', 'student_grades.semester_id', '=', 'semesters.id')
            ->join('students', 'student_grades.student_id', '=', 'students.id')
            ->join('classrooms', 'students.classroom_id', '=', 'classrooms.id')
            ->where('semesters.position', $position)
            ->whereIn('students.id', Student::visibleTo(auth()->user())->select('students.id'))
            ->whereNotNull('student_grades.grade')
            ->select('classrooms.name as label', DB::raw('ROUND(AVG(student_grades.grade), 2) as average_grade'))
            ->groupBy('classrooms.id', 'classrooms.name')
            ->orderBy('classrooms.name')
            ->get();

        return [
            'labels' => $classroomAverages->pluck('label')->all(),
            'data' => $classroomAverages->pluck('average_grade')->map(fn ($grade) => (float) $grade)->all(),
            'summary' => $classroomAverages->isEmpty() ? null : round((float) $classroomAverages->avg('average_grade'), 2),
            'groupedBy' => 'Classroom',
        ];
    }
}
