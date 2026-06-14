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
            'totalStudents' => Student::count(),
            'totalClassrooms' => Classroom::count(),
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
        $subjectAverages = StudentGrade::query()
            ->join('semesters', 'student_grades.semester_id', '=', 'semesters.id')
            ->join('subjects', 'student_grades.subject_id', '=', 'subjects.id')
            ->where('semesters.position', $position)
            ->whereNotNull('student_grades.grade')
            ->select('subjects.name as label', DB::raw('ROUND(AVG(student_grades.grade), 2) as average_grade'))
            ->groupBy('subjects.id', 'subjects.name')
            ->orderBy('subjects.name')
            ->get();

        if ($subjectAverages->isNotEmpty()) {
            return [
                'labels' => $subjectAverages->pluck('label')->all(),
                'data' => $subjectAverages->pluck('average_grade')->map(fn ($grade) => (float) $grade)->all(),
                'summary' => round((float) $subjectAverages->avg('average_grade'), 2),
                'groupedBy' => 'Subject',
            ];
        }

        $classroomAverages = StudentGrade::query()
            ->join('semesters', 'student_grades.semester_id', '=', 'semesters.id')
            ->join('students', 'student_grades.student_id', '=', 'students.id')
            ->join('classrooms', 'students.classroom_id', '=', 'classrooms.id')
            ->where('semesters.position', $position)
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
