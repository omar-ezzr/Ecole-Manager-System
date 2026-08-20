<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->assertSame([1, 2, 3, 4, 5, 6], $sequences);
    }
}
