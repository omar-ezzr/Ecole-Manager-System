<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
        'semester_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(StudentGrade::class);
    }
}
