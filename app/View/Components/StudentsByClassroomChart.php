<?php

namespace App\View\Components;

use App\Models\Student;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class StudentsByClassroomChart extends Component
{
    public function render(): View|Closure|string
    {
        $classrooms = Student::visibleTo(auth()->user())
            ->join('classrooms', 'students.classroom_id', '=', 'classrooms.id')
            ->selectRaw('classrooms.name as label, COUNT(students.id) as student_count')
            ->groupBy('classrooms.id', 'classrooms.name')
            ->orderBy('classrooms.name')
            ->get();

        return view('components.students-by-classroom-chart', [
            'labels' => $classrooms->pluck('label')->all(),
            'counts' => $classrooms->pluck('student_count')->map(fn ($count) => (int) $count)->all(),
        ]);
    }
}
