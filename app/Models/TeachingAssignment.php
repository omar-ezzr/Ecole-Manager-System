<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeachingAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'professor_id',
        'classroom_id',
        'subject_id',
        'academic_year_id',
    ];

    protected static function booted(): void
    {
        static::saved(function (TeachingAssignment $assignment): void {
            $assignment->professor?->assignedClassrooms()->syncWithoutDetaching([$assignment->classroom_id]);
        });

        static::updated(function (TeachingAssignment $assignment): void {
            $previousProfessorId = (int) $assignment->getOriginal('professor_id');
            $previousClassroomId = (int) $assignment->getOriginal('classroom_id');

            if ($previousProfessorId === $assignment->professor_id
                && $previousClassroomId === $assignment->classroom_id) {
                return;
            }

            if (! static::query()
                ->where('professor_id', $previousProfessorId)
                ->where('classroom_id', $previousClassroomId)
                ->exists()) {
                User::find($previousProfessorId)?->assignedClassrooms()->detach($previousClassroomId);
            }
        });

        static::deleted(function (TeachingAssignment $assignment): void {
            if (! static::query()
                ->where('professor_id', $assignment->professor_id)
                ->where('classroom_id', $assignment->classroom_id)
                ->exists()) {
                $assignment->professor?->assignedClassrooms()->detach($assignment->classroom_id);
            }
        });
    }

    public function professor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professor_id');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(StudentGrade::class);
    }
}
