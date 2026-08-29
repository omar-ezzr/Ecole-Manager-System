<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Department;
use App\Models\HealthRecord;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentGrade;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchoolHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_hierarchy_relationships_are_valid(): void
    {
        $school = School::with('departments.classrooms.students.grades', 'departments.classrooms.students.healthRecords')->firstOrFail();
        $department = $school->departments->first();
        $classroom = $department->classrooms->first();
        $student = $classroom->students->first();
        $grade = $student->grades->first();
        $healthRecord = HealthRecord::whereBelongsTo($student)->first();

        $this->assertInstanceOf(Department::class, $department);
        $this->assertTrue($department->school->is($school));
        $this->assertInstanceOf(Classroom::class, $classroom);
        $this->assertTrue($classroom->department->is($department));
        $this->assertInstanceOf(Student::class, $student);
        $this->assertTrue($student->classroom->is($classroom));
        $this->assertInstanceOf(StudentGrade::class, $grade);
        $this->assertTrue($grade->student->is($student));
        $this->assertInstanceOf(HealthRecord::class, $healthRecord);
        $this->assertTrue($healthRecord->student->is($student));
    }

    public function test_student_cannot_be_created_with_nonexistent_classroom(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('students.store'), $this->studentPayload(['classroom_id' => 999999]))
            ->assertSessionHasErrors('classroom_id');
    }

    public function test_referenced_classroom_cannot_be_deleted(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $classroom = Classroom::has('students')->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('classrooms.destroy', $classroom))
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('classrooms', ['id' => $classroom->id]);
    }

    public function test_fk_migration_down_restores_original_cascade_and_up_restores_restrict(): void
    {
        $migration = require database_path('migrations/2026_07_23_010000_restrict_school_hierarchy_foreign_keys.php');
        $classroom = Classroom::firstOrFail();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);

        $migration->down();
        $classroom->delete();
        $this->assertDatabaseMissing('students', ['id' => $student->id]);

        $migration->up();
        $school = School::create(['name' => 'Restriction Test School']);
        $department = Department::create(['name' => 'Restriction Test Department', 'school_id' => $school->id]);
        $restrictedClassroom = Classroom::create(['name' => 'Restriction Test Classroom', 'department_id' => $department->id]);
        Student::factory()->create(['classroom_id' => $restrictedClassroom->id]);

        $this->expectException(QueryException::class);
        $restrictedClassroom->delete();
    }

    public function test_legacy_routes_and_named_routes_are_absent(): void
    {
        $this->get('/compagnies')->assertNotFound();
        $this->get('/groupements')->assertNotFound();

        $routeNames = collect(Route::getRoutes())->map->getName()->filter()->values();

        $this->assertFalse($routeNames->contains(fn (string $name) => str_starts_with($name, 'compagnies.')));
        $this->assertFalse($routeNames->contains(fn (string $name) => str_starts_with($name, 'groupements.')));
    }

    public function test_schema_has_active_hierarchy_columns_and_removed_legacy_columns_are_absent(): void
    {
        $this->assertFalse(Schema::hasTable('compagnies'));
        $this->assertFalse(Schema::hasTable('groupements'));
        $this->assertFalse(Schema::hasColumn('students', 'compagnie_id'));
        $this->assertFalse(Schema::hasColumn('health_records', 'compagnie_id'));
        $this->assertFalse(Schema::hasColumn('schools', 'groupement_id'));

        $this->assertTrue(Schema::hasColumn('departments', 'school_id'));
        $this->assertTrue(Schema::hasColumn('classrooms', 'department_id'));
        $this->assertTrue(Schema::hasColumn('students', 'classroom_id'));
        $this->assertTrue(Schema::hasColumn('health_records', 'student_id'));
        $this->assertTrue(Schema::hasColumn('student_grades', 'student_id'));
    }

    public function test_school_pages_do_not_expose_removed_domains(): void
    {
        $director = User::factory()->create();
        $director->assignRole(Role::ROLE_DIRECTOR);

        $this->actingAs($director)
            ->get(route('schools.index'))
            ->assertOk()
            ->assertDontSee('Compagnie')
            ->assertDontSee('Groupement')
            ->assertDontSee('CIE')
            ->assertDontSee('GPT');
    }

    private function studentPayload(array $overrides = []): array
    {
        return array_merge([
            'last_name' => 'Hierarchy',
            'first_name' => 'Student',
            'student_number' => 'HIER-001',
            'classroom_id' => Classroom::firstOrFail()->id,
            'phone' => '+212600000001',
            'email' => 'hierarchy@example.com',
            'diploma' => 'Technician Diploma',
            'city' => 'Casablanca',
            'address' => 'Test address',
            'education_level' => 'Bac +2',
            'height' => 170,
            'weight' => 70,
        ], $overrides);
    }
}
