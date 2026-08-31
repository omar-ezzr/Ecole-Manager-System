<?php

namespace App\View\Components;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentGrade;
use App\Support\SchoolPermissions as P;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class StudentSemesterGrades extends Component
{
    protected Student $student;

    public function __construct(string $id)
    {
        $this->student = Student::findOrFail($id);
    }

    public function render(): View|Closure|string
    {
        $grades = collect([1, 2])
            ->map(fn (int $position) => $this->gradeForSemester($position))
            ->values()
            ->all();

        return view('components.student-semester-grades', compact('grades'));
    }

    private function gradeForSemester(int $position): float
    {
        $user = auth()->user();

        if (! $user
            || (! $user->can(P::GRADES_ALL)
                && (! $user->can(P::GRADES_OWN) || $user->student_id !== $this->student->id))) {
            return 0;
        }

        $academicYearId = AcademicYear::active()->value('id');

        if (! $academicYearId) {
            return 0;
        }

        $grades = StudentGrade::query()
            ->join('semesters', 'student_grades.semester_id', '=', 'semesters.id')
            ->where('student_grades.student_id', $this->student->id)
            ->whereNull('student_grades.teaching_assignment_id')
            ->whereNull('student_grades.subject_id')
            ->where('semesters.academic_year_id', $academicYearId)
            ->where('semesters.position', $position)
            ->select('student_grades.*')
            ->get();

        return StudentGrade::weightedAverage($grades) ?? 0;
    }
}
