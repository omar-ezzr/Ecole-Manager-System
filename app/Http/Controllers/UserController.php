<?php

namespace App\Http\Controllers;

use App\Models\Role as SchoolRole;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $users = User::with(['roles', 'student'])
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', '%'.$request->string('search').'%')
                ->orWhere('email', 'like', '%'.$request->string('search').'%')))
            ->orderBy('name')->paginate(15)->withQueryString();

        return view('administration.users.index', compact('users'));
    }

    public function create()
    {
        $this->authorize('create', User::class);

        return view('administration.users.form', $this->formData());
    }

    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        $data = $this->validated($request);
        DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'user_type' => $data['role'],
                'is_active' => $data['is_active'] ?? true,
                'student_id' => $data['student_id'] ?? null,
            ]);
            $user->syncRoles([$data['role']]);
        });

        return to_route('administration.users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);

        return view('administration.users.form', array_merge(['user' => $user], $this->formData($user)));
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $data = $this->validated($request, $user);
        $oldAdmin = $user->isOperationalAdministrator();
        $newAdmin = $data['role'] === SchoolRole::ROLE_ADMINISTRATOR;

        if ($user->is(auth()->user()) && $oldAdmin && ! $newAdmin) {
            return back()->withErrors(['role' => 'You cannot remove your own Operational Administrator role.'])->withInput();
        }

        if ($user->is(auth()->user()) && $oldAdmin && ! ($data['is_active'] ?? $user->is_active)) {
            return back()->withErrors(['is_active' => 'You cannot deactivate your own account.'])->withInput();
        }

        if ($oldAdmin && ! $newAdmin && User::role(SchoolRole::ROLE_ADMINISTRATOR)->count() <= 1) {
            return back()->withErrors(['role' => 'The last Operational Administrator cannot be demoted.'])->withInput();
        }

        DB::transaction(function () use ($data, $user) {
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'user_type' => $data['role'],
                'is_active' => $data['is_active'] ?? $user->is_active,
                'student_id' => $data['student_id'] ?? null,
            ] + (isset($data['password']) ? ['password' => $data['password']] : []));
            $user->syncRoles([$data['role']]);
        });

        return to_route('administration.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        abort_if(auth()->id() === $user->id, 422, 'You cannot delete your own account.');
        abort_if($user->isOperationalAdministrator() && User::role(SchoolRole::ROLE_ADMINISTRATOR)->count() <= 1, 422, 'The last Operational Administrator cannot be deleted.');
        $user->delete();

        return back()->with('success', 'User deleted successfully.');
    }

    private function formData(?User $user = null): array
    {
        $students = Student::query()
            ->where(function ($query) use ($user) {
                $query->whereDoesntHave('user');

                if ($user?->student_id) {
                    $query->orWhereKey($user->student_id);
                }
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return ['roles' => $this->assignableRoles(), 'students' => $students];
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
                Rule::in(SchoolRole::supportedNames()),
                Rule::exists('roles', 'name')->where(fn ($query) => $query->where('guard_name', 'web')),
            ],
            'student_id' => [
                Rule::requiredIf($request->input('role') === SchoolRole::ROLE_STUDENT),
                'nullable',
                'integer',
                Rule::exists('students', 'id'),
                Rule::unique('users', 'student_id')->ignore($user?->id),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($data['role'] !== SchoolRole::ROLE_STUDENT) {
            $data['student_id'] = null;
        }

        return $data;
    }

    private function assignableRoles(): array
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', SchoolRole::supportedNames())
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }
}
