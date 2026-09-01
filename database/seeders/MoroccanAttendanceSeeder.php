<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\StudentEnrollment;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MoroccanAttendanceSeeder extends Seeder
{
    private const DATES = ['2026-09-07', '2026-09-08', '2026-09-09', '2026-09-10', '2026-09-11', '2026-09-14', '2026-09-15', '2026-09-16', '2026-09-17', '2026-09-18'];

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('Moroccan demo attendance is only seeded outside production.');

            return;
        }

        $academicYear = AcademicYear::active()->firstOrFail();
        StudentEnrollment::query()
            ->where('academic_year_id', $academicYear->id)
            ->whereNull('left_at')
            ->whereHas('student', fn ($students) => $students->where('student_number', 'like', 'STU-%'))
            ->orderBy('student_id')
            ->each(function (StudentEnrollment $enrollment): void {
                foreach (self::DATES as $dateIndex => $date) {
                    if (! $enrollment->coversDate($date)) {
                        continue;
                    }

                    [$status, $note] = $this->statusFor($enrollment->student_id, $dateIndex);
                    AttendanceRecord::updateOrCreate(
                        ['student_enrollment_id' => $enrollment->id, 'date' => Carbon::parse($date)->toDateString()],
                        ['status' => $status, 'note' => $note]
                    );
                }
            });
    }

    /** @return array{string, ?string} */
    private function statusFor(int $studentId, int $dateIndex): array
    {
        return match (($studentId + ($dateIndex * 7)) % 20) {
            0 => [AttendanceRecord::STATUS_ABSENT, 'Absence non justifiée.'],
            1 => [AttendanceRecord::STATUS_ABSENT, 'Absence signalée.'],
            2 => [AttendanceRecord::STATUS_LATE, 'Retard de 15 minutes.'],
            3 => [AttendanceRecord::STATUS_EXCUSED, 'Absence justifiée.'],
            default => [AttendanceRecord::STATUS_PRESENT, null],
        };
    }
}
