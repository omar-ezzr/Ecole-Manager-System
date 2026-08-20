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

    <div class="mx-auto w-full max-w-3xl p-6">
        <flux:heading size="xl">{{ $editingUser ? 'Edit user' : 'Create user' }}</flux:heading>
        <flux:text class="mt-1">Assign one role and the required access link.</flux:text>

        <form
            id="user-administration-form"
            class="mt-6 space-y-5"
            method="POST"
            action="{{ $editingUser ? route('administration.users.update', $editingUser) : route('administration.users.store') }}"
            x-data="{ role: @js($selectedRole) }"
        >
            @csrf
            @if($editingUser)
                @method('PUT')
            @endif

            <flux:input name="name" label="Name" value="{{ old('name', $editingUser?->name ?? '') }}" required />
            <flux:input name="email" type="email" label="Email" value="{{ old('email', $editingUser?->email ?? '') }}" required />
            <flux:input name="password" type="password" label="{{ $editingUser ? 'New password' : 'Password' }}" placeholder="{{ $editingUser ? 'Leave blank to keep current password' : '' }}" :required="!$editingUser" />
            <flux:input name="password_confirmation" type="password" label="Confirm password" :required="!$editingUser" />

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
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" @checked($isActive) @disabled($protectOwnAdministrator)>
                <span>Active account</span>
            </label>
            @if($protectOwnAdministrator)
                <flux:text class="text-sm">Your administrator role and active status cannot be removed from your own account.</flux:text>
            @endif

            @if($errors->any())
                <flux:callout variant="danger">{{ $errors->first() }}</flux:callout>
            @endif

            <div class="flex gap-3">
                <flux:button type="submit" variant="primary">Save user</flux:button>
                <flux:button href="{{ route('administration.users.index') }}">Cancel</flux:button>
            </div>
        </form>
    </div>

</x-layouts.app>
