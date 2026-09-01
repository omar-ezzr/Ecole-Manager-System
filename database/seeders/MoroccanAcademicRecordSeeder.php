<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\StudentEnrollment;
use App\Models\StudentGrade;
use Illuminate\Database\Seeder;
use RuntimeException;

class MoroccanAcademicRecordSeeder extends Seeder
{
    private const COEFFICIENTS = ['MAT' => 2, 'PHY' => 2, 'SVT' => 2, 'FRA' => 1, 'ENG' => 1, 'INF' => 1];
    private const GRADES = [7.5, 9.0, 10.25, 11.5, 12.75, 14.0, 15.5, 16.25, 17.0, 18.5];

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('Moroccan demo academic records are only seeded outside production.');

            return;
        }

        $academicYear = AcademicYear::active()->firstOrFail();
        $semesters = Semester::query()->where('academic_year_id', $academicYear->id)->whereIn('sequence', [1, 2])->orderBy('sequence')->get();
        if ($semesters->count() !== 2) {
            throw new RuntimeException('Moroccan demo academic records require Semester 1 and Semester 2.');
        }

        StudentEnrollment::query()
            ->with(['student', 'classroom.teachingAssignments.subject'])
            ->where('academic_year_id', $academicYear->id)
            ->whereNull('left_at')
            ->whereHas('student', fn ($students) => $students->where('student_number', 'like', 'STU-%'))
            ->orderBy('student_id')
            ->each(function (StudentEnrollment $enrollment) use ($academicYear, $semesters): void {
                $assignments = $enrollment->classroom->teachingAssignments->where('academic_year_id', $academicYear->id)->sortBy('id')->values();
                if ($assignments->count() !== 3) {
                    throw new RuntimeException("Demo classroom {$enrollment->classroom->name} requires three teaching assignments.");
                }

                foreach ($semesters as $semester) {
                    foreach ($assignments as $assignment) {
                        $grade = self::GRADES[(($enrollment->student_id * 3) + ($semester->sequence * 5) + $assignment->id) % count(self::GRADES)];
                        StudentGrade::updateOrCreate(
                            ['student_id' => $enrollment->student_id, 'semester_id' => $semester->id, 'teaching_assignment_id' => $assignment->id],
                            ['subject_id' => $assignment->subject_id, 'grade' => $grade, 'type' => 'Exam', 'coefficient' => self::COEFFICIENTS[$assignment->subject->code], 'appreciation' => $this->appreciation($grade)]
                        );
                    }

                    $subjectGrades = StudentGrade::query()->where('student_id', $enrollment->student_id)->where('semester_id', $semester->id)->whereNotNull('teaching_assignment_id')->get();
                    StudentGrade::updateOrCreate(
                        ['student_id' => $enrollment->student_id, 'semester_id' => $semester->id, 'teaching_assignment_id' => null, 'subject_id' => null],
                        ['grade' => StudentGrade::weightedAverage($subjectGrades), 'coefficient' => 1, 'appreciation' => 'Moyenne semestrielle calculée à partir des évaluations.']
                    );
                }
            });
    }

    private function appreciation(float $grade): string
    {
        return match (true) {
            $grade >= 16 => 'Excellent travail.',
            $grade >= 13 => 'Très bon niveau.',
            $grade >= 10 => 'Bon travail, continuez ainsi.',
            default => 'Des efforts supplémentaires sont nécessaires.',
        };
    }
}
