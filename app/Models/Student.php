<?php

namespace App\Models;

use App\Support\SchoolPermissions as P;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use LogicException;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'last_name',
        'first_name',
        'student_number',
        'classroom_id',
        'phone',
        'email',
        'diploma',
        'city',
        'address',
        'education_level',
        'height',
        'weight',
        'appreciation_score',
        'appreciation',
    ];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function currentEnrollment(): HasOne
    {
        return $this->hasOne(StudentEnrollment::class)->whereNull('left_at');
    }

    public function healthRecords(): HasMany
    {
        return $this->hasMany(HealthRecord::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(StudentGrade::class);
    }

    public function semesterAverages(): HasMany
    {
        return $this->hasMany(StudentGrade::class)
            ->whereNull('teaching_assignment_id')
            ->whereNull('subject_id');
    }

    public function teachingAssignments(): HasManyThrough
    {
        return $this->hasManyThrough(
            TeachingAssignment::class,
            Classroom::class,
            'id',
            'classroom_id',
            'classroom_id',
            'id'
        );
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public static function createWithEnrollment(
        array $attributes,
        AcademicYear $academicYear,
        DateTimeInterface|string|null $enrolledAt = null
    ): self {
        return DB::transaction(function () use ($attributes, $academicYear, $enrolledAt): self {
            $student = static::query()->create($attributes);
            $student->enrollments()->create([
                'classroom_id' => $student->classroom_id,
                'academic_year_id' => $academicYear->id,
                'enrolled_at' => static::enrollmentDate($enrolledAt),
                'left_at' => null,
            ]);

            return $student->load('currentEnrollment');
        });
    }

    public function updateWithEnrollment(
        array $attributes,
        AcademicYear $academicYear,
        DateTimeInterface|string|null $effectiveAt = null
    ): self {
        return DB::transaction(function () use ($attributes, $academicYear, $effectiveAt): self {
            $student = static::query()->lockForUpdate()->findOrFail($this->getKey());
            $activeEnrollments = $student->enrollments()->active()->lockForUpdate()->get();

            if ($activeEnrollments->count() > 1) {
                throw new LogicException('A student cannot have more than one active enrollment.');
            }

            $activeEnrollment = $activeEnrollments->first();
            $classroomId = (int) ($attributes['classroom_id'] ?? $student->classroom_id);
            $contextChanged = $activeEnrollment === null
                || $activeEnrollment->classroom_id !== $classroomId
                || $activeEnrollment->academic_year_id !== $academicYear->id;

            if ($contextChanged && $activeEnrollment) {
                $leftAt = Carbon::parse(static::enrollmentDate($effectiveAt));

                if ($leftAt->lt($activeEnrollment->enrolled_at)) {
                    $leftAt = $activeEnrollment->enrolled_at->copy();
                }

                $activeEnrollment->update(['left_at' => $leftAt->toDateString()]);
            }

            $student->update($attributes);

            if ($contextChanged) {
                $student->enrollments()->create([
                    'classroom_id' => $classroomId,
                    'academic_year_id' => $academicYear->id,
                    'enrolled_at' => static::enrollmentDate($effectiveAt),
                    'left_at' => null,
                ]);
            }

            $this->setRawAttributes($student->getAttributes(), true);

            return $student->load('currentEnrollment');
        });
    }

    public function hasEnrollmentFor(int $classroomId, int $academicYearId): bool
    {
        if ($this->relationLoaded('enrollments')) {
            return $this->enrollments->contains(fn (StudentEnrollment $enrollment) => $enrollment->classroom_id === $classroomId
                && $enrollment->academic_year_id === $academicYearId
            );
        }

        return $this->enrollments()
            ->where('classroom_id', $classroomId)
            ->where('academic_year_id', $academicYearId)
            ->exists();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can(P::STUDENTS_ALL)) {
            return $query;
        }

        if ($user->can(P::STUDENTS_ASSIGNED)) {
            return $query->where(function (Builder $students) use ($user): void {
                $students->whereHas('enrollments', fn (Builder $enrollments) => $enrollments
                    ->whereHas('classroom.teachingAssignments', fn (Builder $assignments) => $assignments
                        ->where('professor_id', $user->getKey())
                        ->whereColumn('teaching_assignments.academic_year_id', 'student_enrollments.academic_year_id')))
                    ->orWhereHas('classroom.professors', fn (Builder $professors) => $professors
                        ->whereKey($user->getKey()));
            });
        }

        if ($user->can(P::STUDENTS_OWN)) {
            return $query->whereKey($user->student_id ?? 0);
        }

        return $query->whereRaw('1 = 0');
    }

    private static function enrollmentDate(DateTimeInterface|string|null $date): string
    {
        return Carbon::parse($date ?? now())->toDateString();
    }
}
