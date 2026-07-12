<?php

namespace App\View\Components;

use App\Models\HealthRecord;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class HealthRecordStatistics extends Component
{
    public function render(): View|Closure|string
    {
        $types = ['Ophtalmologie', 'Odontologie', 'Dermatologie et Affections', 'Asthenie', 'Fievre', 'Podologie'];
        $counts = [];

        foreach ($types as $type) {
            $counts[] = HealthRecord::where('type', $type)->count();
        }

        return view('components.health-record-statistics', compact('types', 'counts'));
    }
}
