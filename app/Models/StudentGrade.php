<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class StudentGrade extends Model
{
    use HasFactory;

    public const MIN_GRADE = 0;

    public const MAX_GRADE = 20;

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

            if ($studentGrade->teaching_assignment_id !== null) {
                $studentGrade->subject_id = $studentGrade->teachingAssignment()->value('subject_id');
            }
        });
    }

    public function scopeForAcademicResults(Builder $query, Student $student): Builder
    {
        return $query
            ->where('student_id', $student->id)
            ->whereNotNull('teaching_assignment_id')
            ->whereHas('teachingAssignment', fn (Builder $assignments) => $assignments
                ->whereColumn('teaching_assignments.subject_id', 'student_grades.subject_id')
                ->whereHas('academicYear.semesters', fn (Builder $semesters) => $semesters
                    ->whereColumn('semesters.id', 'student_grades.semester_id')));
    }

    public function scopeForAcademicYear(Builder $query, AcademicYear $academicYear): Builder
    {
        return $query
            ->whereHas('semester', fn (Builder $semesters) => $semesters
                ->where('academic_year_id', $academicYear->id))
            ->whereHas('teachingAssignment', fn (Builder $assignments) => $assignments
                ->where('academic_year_id', $academicYear->id));
    }

    public function scopeForSemester(Builder $query, Semester $semester): Builder
    {
        return $query
            ->where('semester_id', $semester->id)
            ->whereHas('teachingAssignment', fn (Builder $assignments) => $assignments
                ->where('academic_year_id', $semester->academic_year_id));
    }

    /**
     * @param  Collection<int, StudentGrade>  $grades
     */
    public static function weightedAverage(Collection $grades): ?float
    {
        $weightedGrades = $grades->filter(fn (StudentGrade $grade) => $grade->grade !== null
            && (float) $grade->coefficient > 0);
        $totalCoefficient = (float) $weightedGrades->sum(
            fn (StudentGrade $grade) => (float) $grade->coefficient
        );

        if ($totalCoefficient <= 0) {
            return null;
        }

        $weightedTotal = (float) $weightedGrades->sum(
            fn (StudentGrade $grade) => $grade->weightedResult()
        );

        return round($weightedTotal / $totalCoefficient, 2);
    }

    public static function isWithinRange(mixed $grade): bool
    {
        return is_numeric($grade)
            && (float) $grade >= self::MIN_GRADE
            && (float) $grade <= self::MAX_GRADE;
    }

    public function weightedResult(): ?float
    {
        if ($this->grade === null || $this->coefficient === null) {
            return null;
        }

        return round((float) $this->grade * (float) $this->coefficient, 2);
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
