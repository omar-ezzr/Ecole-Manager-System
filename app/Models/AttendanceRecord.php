<?php

namespace App\Models;

use App\Support\SchoolPermissions as P;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class AttendanceRecord extends Model
{
    public const STATUS_PRESENT = 'present';

    public const STATUS_ABSENT = 'absent';

    public const STATUS_LATE = 'late';

    public const STATUS_EXCUSED = 'excused';

    public const STATUSES = [
        self::STATUS_PRESENT,
        self::STATUS_ABSENT,
        self::STATUS_LATE,
        self::STATUS_EXCUSED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_PRESENT => 'Present',
        self::STATUS_ABSENT => 'Absent',
        self::STATUS_LATE => 'Late',
        self::STATUS_EXCUSED => 'Excused',
    ];

    protected $fillable = [
        'student_enrollment_id',
        'date',
        'status',
        'note',
    ];

    protected $casts = [
        'student_enrollment_id' => 'integer',
        'date' => 'date',
    ];

    public function studentEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class);
    }

    public function scopeForAcademicYear(Builder $query, AcademicYear|int $academicYear): Builder
    {
        $academicYearId = $academicYear instanceof AcademicYear ? $academicYear->id : $academicYear;

        return $query->whereHas('studentEnrollment', fn (Builder $enrollments) => $enrollments
            ->where('academic_year_id', $academicYearId));
    }

    public function scopeForClassroom(Builder $query, Classroom|int $classroom): Builder
    {
        $classroomId = $classroom instanceof Classroom ? $classroom->id : $classroom;

        return $query->whereHas('studentEnrollment', fn (Builder $enrollments) => $enrollments
            ->where('classroom_id', $classroomId));
    }

    public function scopeForStudent(Builder $query, Student|int $student): Builder
    {
        $studentId = $student instanceof Student ? $student->id : $student;

        return $query->whereHas('studentEnrollment', fn (Builder $enrollments) => $enrollments
            ->where('student_id', $studentId));
    }

    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        if (! in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException("Unsupported attendance status [{$status}].");
        }

        return $query->where('attendance_records.status', $status);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can(P::ATTENDANCE_VIEW_ALL)) {
            return $query;
        }

        if ($user->can(P::ATTENDANCE_VIEW_ASSIGNED) && $user->isProfessor()) {
            return $query->whereHas('studentEnrollment', fn (Builder $enrollments) => $enrollments
                ->whereHas('classroom.teachingAssignments', fn (Builder $assignments) => $assignments
                    ->where('professor_id', $user->id)
                    ->whereColumn('teaching_assignments.academic_year_id', 'student_enrollments.academic_year_id')));
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * @return array{present: int, absent: int, late: int, excused: int, recorded: int}
     */
    public static function summarize(Builder $query): array
    {
        $counts = (clone $query)
            ->selectRaw('attendance_records.status, COUNT(attendance_records.id) as aggregate')
            ->groupBy('attendance_records.status')
            ->pluck('aggregate', 'status');

        $summary = collect(self::STATUSES)
            ->mapWithKeys(fn (string $status) => [$status => (int) $counts->get($status, 0)])
            ->all();
        $summary['recorded'] = array_sum($summary);

        return $summary;
    }

    /**
     * @return Collection<int, array{classroom_id: int, label: string, total: int}>
     */
    public static function classroomStatusCounts(Builder $query, string $status): Collection
    {
        return (clone $query)
            ->withStatus($status)
            ->join('student_enrollments', 'attendance_records.student_enrollment_id', '=', 'student_enrollments.id')
            ->join('classrooms', 'student_enrollments.classroom_id', '=', 'classrooms.id')
            ->selectRaw('classrooms.id as classroom_id, classrooms.name as label, COUNT(attendance_records.id) as aggregate')
            ->groupBy('classrooms.id', 'classrooms.name')
            ->orderBy('classrooms.name')
            ->get()
            ->map(fn (AttendanceRecord $row): array => [
                'classroom_id' => (int) $row->getAttribute('classroom_id'),
                'label' => (string) $row->getAttribute('label'),
                'total' => (int) $row->getAttribute('aggregate'),
            ]);
    }

    protected function date(): Attribute
    {
        return Attribute::make(
            set: fn (mixed $value) => Carbon::parse($value)->toDateString(),
        );
    }
}
