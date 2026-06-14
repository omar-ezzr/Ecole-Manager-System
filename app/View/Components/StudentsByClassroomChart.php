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
        $counts = collect(range(1, 13))
            ->map(fn (int $classroomId) => Student::where('classroom_id', $classroomId)->count())
            ->values()
            ->all();

        return view('components.students-by-classroom-chart', compact('counts'));
    }
}
