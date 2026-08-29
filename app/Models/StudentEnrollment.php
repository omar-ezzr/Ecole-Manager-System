<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class StudentEnrollment extends Model
{
    protected $fillable = [
        'student_id',
        'classroom_id',
        'academic_year_id',
        'enrolled_at',
        'left_at',
    ];

    protected $casts = [
        'student_id' => 'integer',
        'classroom_id' => 'integer',
        'academic_year_id' => 'integer',
        'enrolled_at' => 'date',
        'left_at' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (StudentEnrollment $enrollment): void {
            if ($enrollment->left_at !== null
                && $enrollment->enrolled_at !== null
                && $enrollment->left_at->lt($enrollment->enrolled_at)) {
                throw new InvalidArgumentException('An enrollment cannot end before it starts.');
            }

            if ($enrollment->left_at === null
                && static::query()
                    ->where('student_id', $enrollment->student_id)
                    ->whereNull('left_at')
                    ->when($enrollment->exists, fn (Builder $query) => $query->whereKeyNot($enrollment->getKey()))
                    ->exists()) {
                throw new InvalidArgumentException('A student cannot have more than one active enrollment.');
            }
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function coversDate(DateTimeInterface|string $date): bool
    {
        $attendanceDate = Carbon::parse($date)->startOfDay();

        return ! $attendanceDate->lt($this->enrolled_at)
            && ($this->left_at === null || ! $attendanceDate->gt($this->left_at));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('left_at');
    }
}
