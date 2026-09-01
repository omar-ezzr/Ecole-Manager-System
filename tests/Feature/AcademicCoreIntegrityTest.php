<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentGrade;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AcademicCoreIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_assignment_syncs_professor_classroom_visibility(): void
    {
        $professor = User::factory()->create();
        $professor->assignRole(Role::ROLE_PROFESSOR);
        $assignment = TeachingAssignment::factory()->create([
            'professor_id' => $professor->id,
            'classroom_id' => Classroom::firstOrFail()->id,
            'subject_id' => Subject::firstOrFail()->id,
            'academic_year_id' => AcademicYear::active()->firstOrFail()->id,
        ]);

        $this->assertTrue($professor->fresh()->assignedClassrooms()->whereKey($assignment->classroom_id)->exists());
    }

    public function test_deleting_last_assignment_detaches_professor_from_classroom_visibility(): void
    {
        $professor = User::factory()->create();
        $professor->assignRole(Role::ROLE_PROFESSOR);
        $assignment = TeachingAssignment::factory()->create([
            'professor_id' => $professor->id,
            'classroom_id' => Classroom::firstOrFail()->id,
            'subject_id' => Subject::factory()->create(['code' => 'DET1'])->id,
            'academic_year_id' => AcademicYear::active()->firstOrFail()->id,
        ]);

        $assignment->delete();

        $this->assertFalse($professor->fresh()->assignedClassrooms()->whereKey($assignment->classroom_id)->exists());
    }

    public function test_moving_last_assignment_removes_stale_classroom_visibility(): void
    {
        [$oldClassroom, $newClassroom] = Classroom::orderBy('id')->limit(2)->get()->all();
        $professor = User::factory()->create();
        $professor->assignRole(Role::ROLE_PROFESSOR);
        $assignment = TeachingAssignment::factory()->create([
            'professor_id' => $professor->id,
            'classroom_id' => $oldClassroom->id,
            'subject_id' => Subject::factory()->create(['code' => 'MOVE-SCOPE'])->id,
            'academic_year_id' => AcademicYear::active()->firstOrFail()->id,
        ]);

        $assignment->update(['classroom_id' => $newClassroom->id]);

        $professor->refresh();
        $this->assertFalse($professor->assignedClassrooms()->whereKey($oldClassroom->id)->exists());
        $this->assertTrue($professor->assignedClassrooms()->whereKey($newClassroom->id)->exists());
    }

    public function test_seeded_active_year_for_current_date_is_2026_2027(): void
    {
        $academicYear = AcademicYear::active()->firstOrFail();

        $this->assertSame('2026-2027', $academicYear->name);
        $this->assertSame('2026-09-01', $academicYear->starts_at->toDateString());
        $this->assertSame('2027-07-31', $academicYear->ends_at->toDateString());
    }

    public function test_seeded_semesters_cover_the_active_academic_year(): void
    {
        $academicYear = AcademicYear::active()->firstOrFail();
        $sequences = Semester::where('academic_year_id', $academicYear->id)->orderBy('sequence')->pluck('sequence')->all();

        $this->assertSame([1, 2], $sequences);
    }

    public function test_student_grades_schema_has_assignment_column_foreign_key_and_unique_index(): void
    {
        $this->assertTrue(Schema::hasColumn('student_grades', 'teaching_assignment_id'));

        $foreignKey = collect(Schema::getForeignKeys('student_grades'))
            ->first(fn (array $key) => $key['columns'] === ['teaching_assignment_id']);

        $this->assertNotNull($foreignKey);
        $this->assertSame('teaching_assignments', $foreignKey['foreign_table']);
        $this->assertSame(['id'], $foreignKey['foreign_columns']);

        $hasLogicalGradeIndex = collect(Schema::getIndexes('student_grades'))->contains(
            fn (array $index) => $index['unique']
                && $index['columns'] === ['student_id', 'semester_id', 'teaching_assignment_id']
        );

        $this->assertTrue($hasLogicalGradeIndex);
    }

    public function test_assignment_grade_derives_subject_and_resolves_complete_academic_context(): void
    {
        $academicYear = AcademicYear::active()->firstOrFail();
        $semester = Semester::where('academic_year_id', $academicYear->id)->firstOrFail();
        $classroom = Classroom::firstOrFail();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);
        $professor = User::factory()->create();
        $professor->assignRole(Role::ROLE_PROFESSOR);
        $subject = Subject::factory()->create(['code' => 'CTX-SUBJECT']);
        $otherSubject = Subject::factory()->create(['code' => 'CTX-OTHER']);
        $assignment = TeachingAssignment::factory()->create([
            'professor_id' => $professor->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $grade = StudentGrade::create([
            'student_id' => $student->id,
            'teaching_assignment_id' => $assignment->id,
            'semester_id' => $semester->id,
            'subject_id' => $otherSubject->id,
            'grade' => 14,
            'coefficient' => 2,
        ])->fresh(['student', 'semester.academicYear', 'subject', 'teachingAssignment.professor', 'teachingAssignment.classroom', 'teachingAssignment.academicYear']);

        $this->assertSame($student->id, $grade->student->id);
        $this->assertSame($semester->id, $grade->semester->id);
        $this->assertSame($academicYear->id, $grade->semester->academicYear->id);
        $this->assertSame($subject->id, $grade->subject->id);
        $this->assertSame($assignment->id, $grade->teachingAssignment->id);
        $this->assertSame($classroom->id, $grade->teachingAssignment->classroom->id);
        $this->assertSame($professor->id, $grade->teachingAssignment->professor->id);
        $this->assertSame($academicYear->id, $grade->teachingAssignment->academicYear->id);
    }

    public function test_database_rejects_duplicate_logical_assignment_grade(): void
    {
        $academicYear = AcademicYear::active()->firstOrFail();
        $semester = Semester::where('academic_year_id', $academicYear->id)->firstOrFail();
        $classroom = Classroom::firstOrFail();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);
        $assignment = TeachingAssignment::factory()->create([
            'classroom_id' => $classroom->id,
            'academic_year_id' => $academicYear->id,
        ]);

        StudentGrade::factory()->forAssignment($assignment, $student, $semester)->create();

        $this->expectException(QueryException::class);

        StudentGrade::factory()->forAssignment($assignment, $student, $semester)->create();
    }
}
