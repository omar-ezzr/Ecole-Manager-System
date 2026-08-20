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
        $classrooms = Student::visibleTo(auth()->user())
            ->join('classrooms', 'students.classroom_id', '=', 'classrooms.id')
            ->selectRaw('classrooms.name as label, COALESCE(SUM(students.absences_count), 0) as absence_total')
            ->groupBy('classrooms.id', 'classrooms.name')
            ->orderBy('classrooms.name')
            ->get();

        return view('components.absences-chart', [
            'labels' => $classrooms->pluck('label')->all(),
            'absenceTotals' => $classrooms->pluck('absence_total')->map(fn ($total) => (int) $total)->all(),
        ]);
    }
}
