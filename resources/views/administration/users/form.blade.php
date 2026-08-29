<x-layouts.app>
    @php
        $editingUser = $user ?? null;
        $selectedRole = old('role', $editingUser?->roles->first()?->name ?? '');
        $isActive = (bool) old('is_active', $editingUser?->is_active ?? true);
        $protectOwnAdministrator = $editingUser
            && auth()->user()->is($editingUser)
            && $editingUser->isOperationalAdministrator();

        if ($protectOwnAdministrator) {
            $selectedRole = \App\Models\Role::ROLE_ADMINISTRATOR;
            $isActive = true;
        }
    @endphp

    <div class="mx-auto w-full max-w-3xl space-y-6 p-4 sm:p-6 lg:p-8">
        <div>
            <flux:heading level="1" size="xl">{{ $editingUser ? 'Edit User' : 'Add User' }}</flux:heading>
            <flux:text class="mt-1">Assign one role, account status, and a Student link when required.</flux:text>
        </div>

        <form
            id="user-administration-form"
            class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
            method="POST"
            action="{{ $editingUser ? route('administration.users.update', $editingUser) : route('administration.users.store') }}"
            x-data="{ role: @js($selectedRole) }"
        >
            @csrf
            @if($editingUser)
                @method('PUT')
            @endif

            <div class="space-y-5 p-5 sm:p-6">
            <flux:input name="name" label="Name" value="{{ old('name', $editingUser?->name ?? '') }}" autocomplete="name" required />
            <flux:input name="email" type="email" label="Email" value="{{ old('email', $editingUser?->email ?? '') }}" autocomplete="email" required />
            <flux:input name="password" type="password" label="{{ $editingUser ? 'New Password' : 'Password' }}" placeholder="{{ $editingUser ? 'Leave blank to keep the current password' : '' }}" autocomplete="new-password" :required="!$editingUser" />
            <flux:input name="password_confirmation" type="password" label="Confirm Password" autocomplete="new-password" :required="!$editingUser" />

            <flux:select id="role-select" name="role" label="Role" x-model="role" required>
                <option value="">Select a role</option>
                @foreach($roles as $role)
                    <option
                        value="{{ $role }}"
                        @selected($selectedRole === $role)
                        @disabled($protectOwnAdministrator && $role !== \App\Models\Role::ROLE_ADMINISTRATOR)
                    >{{ $role }}</option>
                @endforeach
            </flux:select>

            <div
                id="student-link-field"
                x-show="role === @js(\App\Models\Role::ROLE_STUDENT)"
                @if($selectedRole !== \App\Models\Role::ROLE_STUDENT) style="display: none" @endif
            >
                <flux:select name="student_id" label="Student" x-bind:required="role === @js(\App\Models\Role::ROLE_STUDENT)">
                    <option value="">Select a student</option>
                    @foreach($students as $student)
                        <option value="{{ $student->id }}" @selected((string) old('student_id', $editingUser?->student_id ?? '') === (string) $student->id)>
                            {{ $student->student_number }} — {{ $student->last_name }} {{ $student->first_name }}
                        </option>
                    @endforeach
                </flux:select>
            </div>

            <input type="hidden" name="is_active" value="{{ $protectOwnAdministrator ? 1 : 0 }}">
            <flux:checkbox name="is_active" value="1" label="Active Account" @checked($isActive) @disabled($protectOwnAdministrator) />
            @if($protectOwnAdministrator)
                <flux:text class="text-sm">Your administrator role and active status cannot be removed from your own account.</flux:text>
            @endif

            </div>
            <div class="flex justify-end gap-3 border-t border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                <flux:button href="{{ route('administration.users.index') }}" variant="ghost">Cancel</flux:button>
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </div>

</x-layouts.app>
