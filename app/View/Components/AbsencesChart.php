<?php

namespace App\View\Components;

use App\Models\Student;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AbsencesChart extends Component
{
    public function render(): View|Closure|string
    {
        $absenceTotals = collect(range(1, 13))
            ->map(fn (int $classroomId) => Student::where('classroom_id', $classroomId)->sum('absences_count'))
            ->values()
            ->all();

        return view('components.absences-chart', compact('absenceTotals'));
    }
}
