<?php

namespace App\View\Components;

use App\Models\Classroom;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class StudentsByClassroomChart extends Component
{
    public function render(): View|Closure|string
    {
        $classrooms = Classroom::withCount('students')->orderBy('name')->get();
        return view('components.students-by-classroom-chart', ['labels' => $classrooms->pluck('name')->all(), 'counts' => $classrooms->pluck('students_count')->all()]);
    }
}
