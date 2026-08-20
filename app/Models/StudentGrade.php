<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentGrade extends Model
{
    use HasFactory;

    private const SEMESTER_AVERAGE_SLOT = 1;

    protected $fillable = [
        'student_id',
        'teaching_assignment_id',
        'semester_id',
        'subject_id',
        'semester_average_slot',
        'grade',
        'type',
        'coefficient',
        'appreciation',
    ];

    protected $casts = [
        'student_id' => 'integer',
        'teaching_assignment_id' => 'integer',
        'semester_id' => 'integer',
        'subject_id' => 'integer',
        'semester_average_slot' => 'integer',
        'grade' => 'decimal:2',
        'coefficient' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (StudentGrade $studentGrade): void {
            /*
             * subject_id = null means this row stores the complete
             * semester average rather than a subject grade.
             */
            $studentGrade->semester_average_slot =
                $studentGrade->subject_id === null && $studentGrade->teaching_assignment_id === null
                    ? self::SEMESTER_AVERAGE_SLOT
                    : null;

            if ($studentGrade->teaching_assignment_id !== null && $studentGrade->subject_id === null) {
                $studentGrade->subject_id = $studentGrade->teachingAssignment?->subject_id;
            }
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function teachingAssignment(): BelongsTo
    {
        return $this->belongsTo(TeachingAssignment::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
