<?php

namespace App\View\Components;

use App\Models\Student;
use App\Models\StudentGrade;
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
        $grades = collect(range(1, 6))
            ->map(fn (int $position) => $this->gradeForSemester($position))
            ->values()
            ->all();

        return view('components.student-semester-grades', compact('grades'));
    }

    private function gradeForSemester(int $position): float
    {
        return (float) StudentGrade::query()
            ->join('semesters', 'student_grades.semester_id', '=', 'semesters.id')
            ->where('student_grades.student_id', $this->student->id)
            ->where('semesters.position', $position)
            ->avg('student_grades.grade');
    }
}
