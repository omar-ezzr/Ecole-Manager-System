<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Role as SchoolRole;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with(['roles', 'student', 'assignedClassrooms'])
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', '%'.$request->string('search').'%')
                ->orWhere('email', 'like', '%'.$request->string('search').'%')))
            ->orderBy('name')->paginate(15)->withQueryString();

        return view('administration.users.index', compact('users'));
    }

    public function create()
    {
        return view('administration.users.form', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'user_type' => $data['role'],
                'student_id' => $data['student_id'] ?? null,
            ]);
            $user->syncRoles([$data['role']]);
            $user->assignedClassrooms()->sync($data['classroom_ids'] ?? []);
        });

        return to_route('administration.users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        return view('administration.users.form', array_merge(['user' => $user->load('assignedClassrooms')], $this->formData()));
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user);
        $oldAdmin = $user->isOperationalAdministrator();
        $newAdmin = $data['role'] === SchoolRole::ROLE_ADMINISTRATOR;
        if ($oldAdmin && ! $newAdmin && User::role(SchoolRole::ROLE_ADMINISTRATOR)->count() <= 1) {
            return back()->withErrors(['role' => 'The last Operational Administrator cannot be demoted.'])->withInput();
        }

        DB::transaction(function () use ($data, $user) {
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'user_type' => $data['role'],
                'student_id' => $data['student_id'] ?? null,
            ] + (isset($data['password']) ? ['password' => $data['password']] : []));
            $user->syncRoles([$data['role']]);
            $user->assignedClassrooms()->sync($data['classroom_ids'] ?? []);
        });

        return to_route('administration.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        abort_if(auth()->id() === $user->id, 422, 'You cannot delete your own account.');
        abort_if($user->isOperationalAdministrator() && User::role(SchoolRole::ROLE_ADMINISTRATOR)->count() <= 1, 422, 'The last Operational Administrator cannot be deleted.');
        $user->delete();
        return back()->with('success', 'User deleted successfully.');
    }

    private function formData(): array
    {
        return ['roles' => $this->assignableRoles(), 'students' => Student::orderBy('last_name')->get(), 'classrooms' => Classroom::orderBy('name')->get()];
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::defaults()],
            'role' => [
                'required',
                'string',
                Rule::exists('roles', 'name')->where(fn ($query) => $query->where('guard_name', 'web')),
            ],
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
            'classroom_ids' => ['nullable', 'array'],
            'classroom_ids.*' => ['integer', 'exists:classrooms,id'],
        ]);

        if ($data['role'] === SchoolRole::ROLE_STUDENT) {
            $data['classroom_ids'] = [];
        } elseif ($data['role'] === SchoolRole::ROLE_PROFESSOR) {
            $data['student_id'] = null;
        } else {
            $data['student_id'] = null;
            $data['classroom_ids'] = [];
        }

        return $data;
    }

    private function assignableRoles(): array
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }
}
