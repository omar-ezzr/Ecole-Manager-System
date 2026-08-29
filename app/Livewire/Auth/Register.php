<?php

namespace App\Livewire\Auth;

use App\Models\Role as SchoolRole;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

#[Layout('components.layouts.auth')]
class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                'unique:'.User::class,
            ],
            'password' => [
                'required',
                'string',
                'confirmed',
                Rules\Password::defaults(),
            ],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),

                // Self-registered users start as students.
                'user_type' => SchoolRole::ROLE_STUDENT,

                // Administrator must approve the account.
                'is_active' => false,
            ]);

            $studentRole = Role::query()
                ->where('name', SchoolRole::ROLE_STUDENT)
                ->where('guard_name', 'web')
                ->firstOrFail();

            $user->assignRole($studentRole);

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return $user;
        });

        event(new Registered($user));

        session()->flash(
            'status',
            'Your account has been created. An administrator must approve it before you can log in.'
        );

        $this->redirect(
            route('login', absolute: false),
            navigate: true
        );
    }
}