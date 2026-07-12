<x-layouts.app>
    @php
        $editingUser = $user ?? null;
        $selectedRole = old('role', $editingUser?->roles->first()?->name ?? '');
        $selectedClassrooms = array_map('intval', old('classroom_ids', $editingUser?->assignedClassrooms->pluck('id')->all() ?? []));
    @endphp

    <div class="mx-auto w-full max-w-3xl p-6">
        <flux:heading size="xl">{{ $editingUser ? 'Edit user' : 'Create user' }}</flux:heading>
        <flux:text class="mt-1">Assign one role and the required access link.</flux:text>

        <form
            id="user-administration-form"
            class="mt-6 space-y-5"
            method="POST"
            action="{{ $editingUser ? route('administration.users.update', $editingUser) : route('administration.users.store') }}"
        >
            @csrf
            @if($editingUser)
                @method('PUT')
            @endif

            <flux:input name="name" label="Name" value="{{ old('name', $editingUser?->name ?? '') }}" required />
            <flux:input name="email" type="email" label="Email" value="{{ old('email', $editingUser?->email ?? '') }}" required />
            <flux:input name="password" type="password" label="Password" placeholder="Leave blank to keep current password" :required="!$editingUser" />
            <flux:input name="password_confirmation" type="password" label="Confirm password" :required="!$editingUser" />

            <flux:select id="role-select" name="role" label="Role" required>
                <option value="">Select a role</option>
                @foreach($roles as $role)
                    <option value="{{ $role }}" @selected($selectedRole === $role)>{{ $role }}</option>
                @endforeach
            </flux:select>

            <div id="student-link-field" class="{{ $selectedRole === \App\Models\Role::ROLE_STUDENT ? '' : 'hidden' }}">
                <flux:select name="student_id" label="Student link">
                    <option value="">No student link</option>
                    @foreach($students as $student)
                        <option value="{{ $student->id }}" @selected((string) old('student_id', $editingUser?->student_id ?? '') === (string) $student->id)>
                            {{ $student->student_number }} — {{ $student->last_name }} {{ $student->first_name }}
                        </option>
                    @endforeach
                </flux:select>
            </div>

            <div id="professor-classrooms-field" class="{{ $selectedRole === \App\Models\Role::ROLE_PROFESSOR ? '' : 'hidden' }}">
                <flux:fieldset>
                    <flux:legend>Professor classrooms</flux:legend>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach($classrooms as $classroom)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="classroom_ids[]" value="{{ $classroom->id }}" @checked(in_array($classroom->id, $selectedClassrooms, true))>
                                {{ $classroom->name }}
                            </label>
                        @endforeach
                    </div>
                </flux:fieldset>
            </div>

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
