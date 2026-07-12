<?php

namespace App\View\Components;

use App\Models\Classroom;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AbsencesChart extends Component
{
    public function render(): View|Closure|string
    {
        $classrooms = Classroom::query()->withSum('students', 'absences_count')->orderBy('name')->get();
        return view('components.absences-chart', ['labels' => $classrooms->pluck('name')->all(), 'absenceTotals' => $classrooms->map(fn ($c) => (int) ($c->students_sum_absences_count ?? 0))->all()]);
    }
}
