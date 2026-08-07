<?php

namespace App\Models;

use App\Support\SchoolPermissions as P;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

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
        'absences_count',
        'appreciation',
    ];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function healthRecords(): HasMany
    {
        return $this->hasMany(HealthRecord::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(StudentGrade::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can(P::STUDENTS_ALL)) {
            return $query;
        }

        if ($user->can(P::STUDENTS_ASSIGNED)) {
            return $query->whereHas('classroom.professors', fn (Builder $classrooms) => $classrooms->whereKey($user->getKey()));
        }

        if ($user->can(P::STUDENTS_OWN)) {
            return $query->whereKey($user->student_id ?? 0);
        }

        return $query->whereRaw('1 = 0');
    }
}
