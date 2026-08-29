<?php

namespace App\View\Components;

use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\Classroom;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AbsencesChart extends Component
{
    public function __construct(
        public ?AcademicYear $academicYear = null,
        public ?Classroom $classroom = null,
    ) {}

    public function render(): View|Closure|string
    {
        $user = auth()->user();
        $academicYear = $this->academicYear;

        if (! $academicYear) {
            $reportableYears = AcademicYear::query()
                ->reportableForAttendance($user)
                ->orderByDesc('starts_at')
                ->get();
            $academicYear = $reportableYears->firstWhere('is_active', true)
                ?? $reportableYears->first();
        }
        $classroomCounts = collect();

        if ($academicYear) {
            $query = AttendanceRecord::query()
                ->visibleTo($user)
                ->forAcademicYear($academicYear)
                ->when($this->classroom, fn ($attendance) => $attendance->forClassroom($this->classroom));
            $classroomCounts = AttendanceRecord::classroomStatusCounts(
                $query,
                AttendanceRecord::STATUS_ABSENT
            );
        }

        return view('components.absences-chart', [
            'labels' => $classroomCounts->pluck('label')->all(),
            'absenceTotals' => $classroomCounts->pluck('total')->all(),
        ]);
    }
}
