<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\StudentEnrollment;
use App\Models\TeachingAssignment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    public function index(Request $request, TeachingAssignment $teachingAssignment)
    {
        $this->authorize('viewForAssignment', [AttendanceRecord::class, $teachingAssignment]);

        $date = $request->filled('date')
            ? $request->validate(['date' => ['required', 'date']])['date']
            : now()->toDateString();
        $date = Carbon::parse($date)->toDateString();
        $enrollments = $this->rosterFor($teachingAssignment, $date);
        $records = AttendanceRecord::query()
            ->whereDate('date', $date)
            ->whereIn('student_enrollment_id', $enrollments->modelKeys())
            ->get()
            ->keyBy('student_enrollment_id');

        $teachingAssignment->load([
            'professor',
            'subject',
            'academicYear',
            'classroom.department.school',
        ]);

        return view('attendance.index', [
            'assignment' => $teachingAssignment,
            'date' => $date,
            'enrollments' => $enrollments,
            'records' => $records,
            'statusLabels' => AttendanceRecord::STATUS_LABELS,
            'canManage' => $request->user()->can('createForAssignment', [
                AttendanceRecord::class,
                $teachingAssignment,
            ]),
        ]);
    }

    public function store(Request $request, TeachingAssignment $teachingAssignment)
    {
        $this->authorize('createForAssignment', [AttendanceRecord::class, $teachingAssignment]);

        $data = $request->validate([
            'date' => ['required', 'date'],
            'attendance' => ['required', 'array', 'min:1'],
            'attendance.*.student_enrollment_id' => [
                'required',
                'integer',
                'distinct',
                'exists:student_enrollments,id',
            ],
            'attendance.*.status' => ['required', 'string', Rule::in(AttendanceRecord::STATUSES)],
            'attendance.*.note' => ['nullable', 'string', 'max:1000'],
        ]);

        $date = Carbon::parse($data['date'])->toDateString();
        $rows = collect($data['attendance']);

        DB::transaction(function () use ($rows, $date, $teachingAssignment): void {
            $enrollments = StudentEnrollment::query()
                ->whereIn('id', $rows->pluck('student_enrollment_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $errors = $this->attendanceContextErrors($rows, $enrollments, $teachingAssignment, $date);

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            $existingRecords = AttendanceRecord::query()
                ->whereDate('date', $date)
                ->whereIn('student_enrollment_id', $enrollments->keys())
                ->lockForUpdate()
                ->get();

            foreach ($existingRecords as $record) {
                $this->authorize('update', $record);
            }

            $timestamp = now();
            AttendanceRecord::query()->upsert(
                $rows->map(fn (array $row) => [
                    'student_enrollment_id' => (int) $row['student_enrollment_id'],
                    'date' => $date,
                    'status' => $row['status'],
                    'note' => filled($row['note'] ?? null)
                        ? strip_tags((string) $row['note'])
                        : null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ])->all(),
                ['student_enrollment_id', 'date'],
                ['status', 'note', 'updated_at']
            );
        });

        return redirect()
            ->route('teaching-assignments.attendance.index', [
                'teaching_assignment' => $teachingAssignment->id,
                'date' => $date,
            ])
            ->with('success', 'Attendance saved successfully.');
    }

    /**
     * @return Collection<int, StudentEnrollment>
     */
    private function rosterFor(TeachingAssignment $teachingAssignment, string $date): Collection
    {
        return StudentEnrollment::query()
            ->where('classroom_id', $teachingAssignment->classroom_id)
            ->where('academic_year_id', $teachingAssignment->academic_year_id)
            ->whereDate('enrolled_at', '<=', $date)
            ->where(fn ($enrollments) => $enrollments
                ->whereNull('left_at')
                ->orWhereDate('left_at', '>=', $date))
            ->with('student')
            ->get()
            ->sortBy('student.student_number')
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  Collection<int, StudentEnrollment>  $enrollments
     * @return array<string, string>
     */
    private function attendanceContextErrors(
        Collection $rows,
        Collection $enrollments,
        TeachingAssignment $teachingAssignment,
        string $date
    ): array {
        $errors = [];

        foreach ($rows as $index => $row) {
            $enrollment = $enrollments->get((int) $row['student_enrollment_id']);

            if (! $enrollment
                || $enrollment->classroom_id !== $teachingAssignment->classroom_id
                || $enrollment->academic_year_id !== $teachingAssignment->academic_year_id) {
                $errors["attendance.{$index}.student_enrollment_id"] =
                    'The selected enrollment does not belong to this teaching assignment.';

                continue;
            }

            if (! $enrollment->coversDate($date)) {
                $errors["attendance.{$index}.student_enrollment_id"] =
                    'The attendance date is outside the selected enrollment period.';
            }
        }

        return $errors;
    }
}
