<?php

namespace App\Models;

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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
