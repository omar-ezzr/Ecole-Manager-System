<x-layouts.app>
    <div class="mx-auto w-full max-w-7xl space-y-6 p-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ auth()->user()->isProfessor() ? 'My Teaching Assignments' : 'Teaching Assignments' }}</flux:heading>
                <flux:text class="mt-1">Professor, classroom, subject, and academic year mappings.</flux:text>
            </div>
            @can('teaching_assignments.manage')
                <flux:button href="{{ route('teaching-assignments.create') }}" variant="primary" icon="plus">Create assignment</flux:button>
            @endcan
        </div>
        <form method="GET" class="grid gap-4 md:grid-cols-4">
            <select name="academic_year_id" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                <option value="">All academic years</option>
                @foreach($academicYears as $academicYear)
                    <option value="{{ $academicYear->id }}" @selected(request('academic_year_id') == $academicYear->id)>{{ $academicYear->name }}</option>
                @endforeach
            </select>
            <select name="professor_id" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                <option value="">All professors</option>
                @foreach($professors as $professor)
                    <option value="{{ $professor->id }}" @selected(request('professor_id') == $professor->id)>{{ $professor->name }}</option>
                @endforeach
            </select>
            <select name="classroom_id" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                <option value="">All classrooms</option>
                @foreach($classrooms as $classroom)
                    <option value="{{ $classroom->id }}" @selected(request('classroom_id') == $classroom->id)>{{ $classroom->name }}</option>
                @endforeach
            </select>
            <select name="subject_id" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                <option value="">All subjects</option>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}" @selected(request('subject_id') == $subject->id)>{{ $subject->code }} - {{ $subject->name }}</option>
                @endforeach
            </select>
        </form>
        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-zinc-50 text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
                        <tr><th class="px-5 py-3">Professor</th><th class="px-5 py-3">Subject</th><th class="px-5 py-3">Classroom</th><th class="px-5 py-3">Department</th><th class="px-5 py-3">School</th><th class="px-5 py-3">Academic Year</th><th class="px-5 py-3">Students</th><th class="px-5 py-3 text-right">Actions</th></tr>
                    </thead>
                    <tbody class="divide-y dark:divide-zinc-700">
                        @foreach($assignments as $assignment)
                            <tr>
                                <td class="px-5 py-4">{{ $assignment->professor->name }}</td>
                                <td class="px-5 py-4">{{ $assignment->subject->code }} - {{ $assignment->subject->name }}</td>
                                <td class="px-5 py-4"><a href="{{ route('teaching-assignments.show', $assignment) }}">{{ $assignment->classroom->name }}</a></td>
                                <td class="px-5 py-4">{{ $assignment->classroom->department->name }}</td>
                                <td class="px-5 py-4">{{ $assignment->classroom->department->school->name }}</td>
                                <td class="px-5 py-4">{{ $assignment->academicYear->name }}</td>
                                <td class="px-5 py-4">{{ $assignment->classroom->students_count }}</td>
                                <td class="px-5 py-4 text-right">
                                    @can('update', $assignment)
                                        <a class="mr-3 text-teal-600" href="{{ route('teaching-assignments.edit', $assignment) }}">Edit</a>
                                    @endcan
                                    @can('delete', $assignment)
                                        <form class="inline" method="POST" action="{{ route('teaching-assignments.destroy', $assignment) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-rose-600" onclick="return confirm('Delete this assignment?')">Delete</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $assignments->links() }}</div>
        </div>
    </div>
</x-layouts.app>
