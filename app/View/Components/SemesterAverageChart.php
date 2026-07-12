<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SemesterAverageChart extends Component
{
    public int $position;

    public string $canvasId;

    public array $chart;

    public function __construct(int $position, array $chart = [])
    {
        $this->position = $position;
        $this->canvasId = 'semester'.$position.'AverageChart';
        $this->chart = [
            'labels' => $chart['labels'] ?? [],
            'data' => $chart['data'] ?? [],
            'summary' => $chart['summary'] ?? null,
            'groupedBy' => $chart['groupedBy'] ?? 'Classroom',
        ];
    }

    public function render(): View|Closure|string
    {
        return view('components.semester-average-chart');
    }
}
