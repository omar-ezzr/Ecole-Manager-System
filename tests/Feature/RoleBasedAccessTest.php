<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Support\SchoolPermissions as P;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

class RoleBasedAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_admin_has_the_operational_administrator_role(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();

        $this->assertSame(Role::ROLE_ADMINISTRATOR, $admin->name);
        $this->assertSame([Role::ROLE_ADMINISTRATOR], $admin->getRoleNames()->all());
        $this->assertTrue($admin->can(P::USERS_VIEW));
    }

    public function test_display_name_does_not_grant_operational_administrator_access(): void
    {
        $director = User::factory()->create(['name' => Role::ROLE_ADMINISTRATOR]);
        $director->assignRole(Role::ROLE_DIRECTOR);

        $this->assertFalse($director->hasRole(Role::ROLE_ADMINISTRATOR));
        $this->assertFalse($director->can(P::USERS_VIEW));
        $this->actingAs($director)->get(route('administration.users.index'))->assertForbidden();
    }

    public function test_non_admin_user_cannot_access_user_administration(): void
    {
        $student = User::factory()->create();
        $student->assignRole(Role::ROLE_STUDENT);

        $this->actingAs($student)->get(route('administration.users.index'))->assertForbidden();
        $this->actingAs($student)->get(route('administration.users.create'))->assertForbidden();
        $this->actingAs($student)->post(route('administration.users.store'))->assertForbidden();
    }

    public function test_operational_administrator_can_open_user_administration(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('administration.users.index'))
            ->assertOk()
            ->assertSee('User administration')
            ->assertSee('Create user');
    }

    public function test_operational_administrator_sees_spatie_roles_in_user_form(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();

        $response = $this->actingAs($admin)
            ->get(route('administration.users.create'))
            ->assertOk();

        foreach ([Role::ROLE_ADMINISTRATOR, Role::ROLE_DIRECTOR, Role::ROLE_SECRETARY, Role::ROLE_PROFESSOR, Role::ROLE_STUDENT] as $role) {
            $response->assertSee($role);
        }
    }

    public function test_operational_administrator_can_create_director_user_from_ui(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('administration.users.store'), [
                'name' => 'Director User',
                'email' => 'director-ui@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role' => Role::ROLE_DIRECTOR,
            ])
            ->assertRedirect(route('administration.users.index'));

        $director = User::where('email', 'director-ui@example.com')->firstOrFail();

        $this->assertSame([Role::ROLE_DIRECTOR], $director->getRoleNames()->all());
        $this->assertSame(Role::ROLE_DIRECTOR, $director->user_type);
        $this->assertNull($director->student_id);
        $this->assertTrue($director->assignedClassrooms()->doesntExist());
    }

    public function test_updating_user_from_student_to_director_changes_spatie_role_and_display_type(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $classroom = Classroom::firstOrFail();
        $studentRecord = Student::factory()->create(['classroom_id' => $classroom->id]);
        $user = User::factory()->create([
            'student_id' => $studentRecord->id,
            'user_type' => Role::ROLE_STUDENT,
        ]);
        $user->assignRole(Role::ROLE_STUDENT);
        $user->assignedClassrooms()->sync([$classroom->id]);

        $this->actingAs($admin)
            ->put(route('administration.users.update', $user), [
                'name' => 'Promoted Director',
                'email' => $user->email,
                'password' => '',
                'password_confirmation' => '',
                'role' => Role::ROLE_DIRECTOR,
                'student_id' => $studentRecord->id,
                'classroom_ids' => [$classroom->id],
            ])
            ->assertRedirect(route('administration.users.index'));

        $user->refresh();

        $this->assertSame([Role::ROLE_DIRECTOR], $user->getRoleNames()->all());
        $this->assertSame(Role::ROLE_DIRECTOR, $user->user_type);
        $this->assertNull($user->student_id);
        $this->assertTrue($user->assignedClassrooms()->doesntExist());
    }

    public function test_director_sees_only_read_only_sidebar_links(): void
    {
        $director = User::factory()->create();
        $director->assignRole(Role::ROLE_DIRECTOR);

        $response = $this->actingAs($director)->get(route('students.index'))->assertOk();

        $response->assertSee('Student records')
            ->assertSee('Classrooms')
            ->assertSee('Departments')
            ->assertSee('Schools')
            ->assertSee('Health records')
            ->assertDontSee('User administration')
            ->assertDontSee('Create manually')
            ->assertDontSee('Create record')
            ->assertDontSee('Add student');
    }

    public function test_director_cannot_access_management_routes(): void
    {
        $director = User::factory()->create();
        $director->assignRole(Role::ROLE_DIRECTOR);

        foreach ([
            'administration.users.create',
            'students.create',
            'classrooms.create',
            'departments.create',
            'schools.create',
            'health-records.create',
        ] as $routeName) {
            $this->actingAs($director)->get(route($routeName))->assertForbidden();
        }
    }

    public function test_operational_administrator_retains_management_permissions(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();

        $this->assertTrue($admin->hasRole(Role::ROLE_ADMINISTRATOR));
        $this->assertTrue($admin->can(P::USERS_VIEW));
        $this->assertTrue($admin->can(P::STUDENTS_CREATE));
        $this->assertTrue($admin->can(P::CLASSROOMS_MANAGE));
        $this->assertTrue($admin->can(P::DEPARTMENTS_MANAGE));
        $this->assertTrue($admin->can(P::SCHOOLS_MANAGE));
        $this->assertTrue($admin->can(P::HEALTH_MANAGE));

        $this->actingAs($admin)->get(route('administration.users.create'))->assertOk();
        $this->actingAs($admin)->get(route('students.create'))->assertOk();
        $this->actingAs($admin)->get(route('classrooms.create'))->assertOk();
        $this->actingAs($admin)->get(route('departments.create'))->assertOk();
        $this->actingAs($admin)->get(route('schools.create'))->assertOk();
        $this->actingAs($admin)->get(route('health-records.create'))->assertOk();
    }

    public function test_professor_only_sees_assigned_classrooms_and_students(): void
    {
        [$assignedClassroom, $otherClassroom] = Classroom::orderBy('id')->limit(2)->get()->all();
        $professor = User::factory()->create();
        $professor->assignRole(Role::ROLE_PROFESSOR);
        $professor->assignedClassrooms()->sync([$assignedClassroom->id]);

        $assignedStudent = Student::factory()->create(['classroom_id' => $assignedClassroom->id]);
        $otherStudent = Student::factory()->create(['classroom_id' => $otherClassroom->id]);

        $this->assertTrue($professor->can(P::STUDENTS_ASSIGNED));
        $this->assertSame(
            [$assignedStudent->id],
            Student::visibleTo($professor)->whereKey($assignedStudent->id)->pluck('id')->all()
        );

        $this->actingAs($professor->fresh())
            ->get(route('classrooms.index'))
            ->assertOk()
            ->assertSee($assignedClassroom->name)
            ->assertDontSee($otherClassroom->name);

        $this->actingAs($professor->fresh())->get(route('classrooms.show', $otherClassroom))->assertForbidden();

        $this->actingAs($professor->fresh())
            ->get(route('students.index', ['student_number' => $assignedStudent->student_number]))
            ->assertOk()
            ->assertSee($assignedStudent->student_number)
            ->assertDontSee($otherStudent->student_number)
            ->assertDontSee('Add student')
            ->assertDontSee('Delete Student');

        $this->actingAs($professor->fresh())->get(route('students.show', $otherStudent))->assertForbidden();
    }

    public function test_student_can_only_open_their_linked_student_record(): void
    {
        $classroom = Classroom::firstOrFail();
        $ownStudent = Student::factory()->create(['classroom_id' => $classroom->id]);
        $otherStudent = Student::factory()->create(['classroom_id' => $classroom->id]);
        $user = User::factory()->create(['student_id' => $ownStudent->id]);
        $user->assignRole(Role::ROLE_STUDENT);

        $this->actingAs($user)
            ->get(route('students.index'))
            ->assertOk()
            ->assertSee($ownStudent->student_number)
            ->assertDontSee($otherStudent->student_number);

        $this->actingAs($user)->get(route('students.show', $ownStudent))->assertOk();
        $this->actingAs($user)->get(route('students.show', $otherStudent))->assertForbidden();
    }

    public function test_unlinked_student_user_does_not_see_global_student_data(): void
    {
        $classroom = Classroom::firstOrFail();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);
        $user = User::factory()->create(['student_id' => null]);
        $user->assignRole(Role::ROLE_STUDENT);

        $this->assertTrue($user->can(P::STUDENTS_OWN));
        $this->assertFalse($user->can(P::STUDENTS_ALL));
        $this->assertSame([], Student::visibleTo($user)->whereKey($student->id)->pluck('id')->all());
    }

    public function test_unlinked_student_user_gets_waiting_message_instead_of_raw_forbidden_on_student_list(): void
    {
        $user = User::factory()->create(['student_id' => null]);
        $user->assignRole(Role::ROLE_STUDENT);

        $this->actingAs($user)
            ->get(route('students.index'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('warning', 'Your account is waiting for student record assignment.');

        $this->followingRedirects()
            ->actingAs($user)
            ->get(route('students.index'))
            ->assertOk()
            ->assertSee('Your account is waiting for student record assignment.');
    }

    public function test_unlinked_student_user_gets_waiting_message_on_student_detail_route(): void
    {
        $classroom = Classroom::firstOrFail();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);
        $user = User::factory()->create(['student_id' => null]);
        $user->assignRole(Role::ROLE_STUDENT);

        $this->actingAs($user)
            ->get(route('students.show', $student))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('warning', 'Your account is waiting for student record assignment.');
    }

    public function test_secretary_student_actions_match_seeded_permissions(): void
    {
        $secretary = User::factory()->create();
        $secretary->assignRole(Role::ROLE_SECRETARY);

        $this->assertTrue($secretary->can(P::STUDENTS_CREATE));
        $this->assertTrue($secretary->can(P::STUDENTS_UPDATE));
        $this->assertFalse($secretary->can(P::STUDENTS_DELETE));

        $this->actingAs($secretary)
            ->get(route('students.index'))
            ->assertOk()
            ->assertSee('Add student')
            ->assertDontSee('Delete Student');
    }

    public function test_user_without_student_permissions_cannot_open_students(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('students.index'))->assertForbidden();
    }

    public function test_student_table_contains_only_rows_as_direct_tbody_children(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $response = $this->actingAs($admin)->get(route('students.index'))->assertOk();

        preg_match('/<tbody[^>]*>(.*?)<\/tbody>/si', $response->getContent(), $matches);

        $this->assertArrayHasKey(1, $matches);
        $this->assertStringNotContainsString('<form', $matches[1]);
        $this->assertStringNotContainsString('<dialog', $matches[1]);
        $this->assertStringContainsString('<form', substr($response->getContent(), strpos($response->getContent(), '</table>')));
    }

    public function test_required_route_names_are_unique_and_use_the_user_parameter(): void
    {
        $requiredNames = [
            'administration.users.index',
            'administration.users.create',
            'administration.users.store',
            'administration.users.edit',
            'administration.users.update',
            'administration.users.destroy',
        ];

        $routes = collect(RouteFacade::getRoutes()->getRoutes());

        foreach ($requiredNames as $name) {
            $this->assertSame(1, $routes->where(fn ($route) => $route->getName() === $name)->count());
        }

        $this->assertSame(['user'], RouteFacade::getRoutes()->getByName('administration.users.edit')->parameterNames());
    }
}
