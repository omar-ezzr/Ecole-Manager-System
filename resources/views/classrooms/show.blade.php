<x-layouts.app>
    <div class="mx-auto w-full max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <flux:heading level="1" size="xl">{{ $classroom->name }}</flux:heading>
                    <flux:badge color="{{ $classroom->is_active ? 'emerald' : 'zinc' }}">{{ $classroom->is_active ? 'Active' : 'Archived' }}</flux:badge>
                </div>
                <flux:text class="mt-1">{{ $classroom->department?->name }} · {{ $classroom->address ?: 'No address' }}</flux:text>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('update', $classroom)
                    <flux:button href="{{ route('classrooms.edit', $classroom) }}" icon="pencil-square" variant="primary">Edit</flux:button>
                @endcan
                <flux:button href="{{ route('classrooms.index') }}" variant="ghost" icon="arrow-left">Back</flux:button>
            </div>
        </div>

        @if(session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-400/20 dark:bg-emerald-400/10 dark:text-emerald-100">{{ session('success') }}</div>
        @endif
        @if(session('warning'))
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-100">{{ session('warning') }}</div>
        @endif
        @if($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 dark:border-rose-400/20 dark:bg-rose-400/10 dark:text-rose-100">{{ $errors->first() }}</div>
        @endif

        <form method="GET" action="{{ route('classrooms.show', $classroom) }}" class="flex max-w-xl flex-col gap-3 sm:flex-row sm:items-end">
            <flux:select name="academic_year_id" label="Academic Year" class="min-w-0 flex-1">
                @foreach($academicYears as $academicYear)
                    <option value="{{ $academicYear->id }}" @selected($selectedAcademicYear?->id === $academicYear->id)>{{ $academicYear->name }}</option>
                @endforeach
            </flux:select>
            <flux:button type="submit" variant="primary">Apply</flux:button>
        </form>

        <section class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Current Academic Year</div>
                <div class="mt-1 text-lg font-semibold">{{ $selectedAcademicYear?->name ?? 'None' }}</div>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Current Students</div>
                <div class="mt-1 text-lg font-semibold">{{ $classroom->students->count() }}</div>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Assigned Subjects</div>
                <div class="mt-1 text-lg font-semibold">{{ $assignedSubjects->count() }}</div>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-col gap-4 border-b border-zinc-200 p-5 dark:border-zinc-700 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <flux:heading size="lg">Subjects - {{ $selectedAcademicYear?->name ?? 'No academic year' }}</flux:heading>
                    <flux:text class="mt-1">Subjects assigned to this classroom for the selected academic year.</flux:text>
                </div>
                @can('update', $classroom)
                    @if($selectedAcademicYear)
                        <form method="POST" action="{{ route('classrooms.subjects.store', $classroom) }}" class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-end">
                            @csrf
                            <input type="hidden" name="academic_year_id" value="{{ $selectedAcademicYear->id }}">
                            <flux:select name="subject_id" label="Assign Subject" class="min-w-64" required>
                                <option value="">Select a subject</option>
                                @foreach($availableSubjects as $subject)
                                    <option value="{{ $subject->id }}">{{ $subject->code }} - {{ $subject->name }}</option>
                                @endforeach
                            </flux:select>
                            <flux:button type="submit" variant="primary" icon="plus">Assign</flux:button>
                        </form>
                    @endif
                @endcan
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead class="bg-zinc-50 text-zinc-500 dark:bg-zinc-800">
                        <tr><th class="px-5 py-3">Subject</th><th class="px-5 py-3">Code</th><th class="px-5 py-3">Academic Year</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Actions</th></tr>
                    </thead>
                    <tbody class="divide-y dark:divide-zinc-700">
                        @forelse($assignedSubjects as $assignment)
                            <tr>
                                <td class="px-5 py-4 font-medium">{{ $assignment->subject->name }}</td>
                                <td class="px-5 py-4">{{ $assignment->subject->code }}</td>
                                <td class="px-5 py-4">{{ $assignment->academicYear->name }}</td>
                                <td class="px-5 py-4"><flux:badge color="{{ $assignment->subject->is_active ? 'emerald' : 'zinc' }}">{{ $assignment->subject->is_active ? 'Active' : 'Archived' }}</flux:badge></td>
                                <td class="px-5 py-4 text-right">
                                    @can('update', $classroom)
                                        <form method="POST" action="{{ route('classrooms.subjects.destroy', [$classroom, $assignment]) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-medium text-rose-700 hover:underline dark:text-rose-300" onclick="return confirm('Remove this subject from this classroom for {{ $assignment->academicYear->name }}?')">Remove</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-10 text-center text-zinc-500">No subjects assigned for this academic year.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="grid gap-6 lg:grid-cols-2">
            <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 p-5 dark:border-zinc-700"><flux:heading size="lg">Students</flux:heading></div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <tbody class="divide-y dark:divide-zinc-700">
                            @forelse($classroom->students as $student)
                                <tr><td class="px-5 py-4"><a class="font-medium text-teal-700 hover:underline dark:text-teal-300" href="{{ route('students.show', $student) }}">{{ $student->student_number }} · {{ $student->last_name }} {{ $student->first_name }}</a></td></tr>
                            @empty
                                <tr><td class="px-5 py-10 text-center text-zinc-500">No current students.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 p-5 dark:border-zinc-700"><flux:heading size="lg">Teaching Assignments</flux:heading></div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <tbody class="divide-y dark:divide-zinc-700">
                            @forelse($teachingAssignments as $assignment)
                                <tr><td class="px-5 py-4"><a class="font-medium text-teal-700 hover:underline dark:text-teal-300" href="{{ route('teaching-assignments.show', $assignment) }}">{{ $assignment->subject->code }} · {{ $assignment->professor->name }}</a></td></tr>
                            @empty
                                <tr><td class="px-5 py-10 text-center text-zinc-500">No teaching assignments for this academic year.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>
