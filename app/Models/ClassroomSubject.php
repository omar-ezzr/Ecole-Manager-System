<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassroomSubject extends Model
{
    protected $fillable = [
        'classroom_id',
        'subject_id',
        'academic_year_id',
    ];

    protected $casts = [
        'classroom_id' => 'integer',
        'subject_id' => 'integer',
        'academic_year_id' => 'integer',
    ];

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
}
