<?php

namespace App\View\Components;

use App\Models\HealthRecord;
use App\Models\Student;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class HealthRecordStatistics extends Component
{
    public function render(): View|Closure|string
    {
        $types = ['Ophtalmologie', 'Odontologie', 'Dermatologie et Affections', 'Asthenie', 'Fievre', 'Podologie'];
        $visibleStudentIds = Student::visibleTo(auth()->user())->select('students.id');
        $countsByType = HealthRecord::query()
            ->whereIn('student_id', $visibleStudentIds)
            ->whereIn('type', $types)
            ->selectRaw('type, COUNT(*) as aggregate')
            ->groupBy('type')
            ->pluck('aggregate', 'type');
        $counts = collect($types)
            ->map(fn (string $type): int => (int) $countsByType->get($type, 0))
            ->all();

        return view('components.health-record-statistics', compact('types', 'counts'));
    }
}
