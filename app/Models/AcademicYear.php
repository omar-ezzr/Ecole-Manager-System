<?php

namespace App\Models;

use App\Support\SchoolPermissions as P;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'is_active' => 'boolean',
    ];

    public function semesters(): HasMany
    {
        return $this->hasMany(Semester::class);
    }

    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class);
    }

    public function classroomSubjects(): HasMany
    {
        return $this->hasMany(ClassroomSubject::class);
    }

    public function studentEnrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeReportableForAttendance(Builder $query, User $user): Builder
    {
        if ($user->can(P::ATTENDANCE_VIEW_ALL)) {
            return $query;
        }

        if ($user->can(P::ATTENDANCE_VIEW_ASSIGNED) && $user->isProfessor()) {
            return $query->whereHas('teachingAssignments', fn (Builder $assignments) => $assignments
                ->where('professor_id', $user->id));
        }

        return $query->whereRaw('1 = 0');
    }
}
