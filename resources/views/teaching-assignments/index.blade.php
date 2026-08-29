<x-layouts.app>
    <div class="mx-auto w-full max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <flux:heading size="xl">{{ auth()->user()->isProfessor() ? 'My Teaching Assignments' : 'Teaching Assignments' }}</flux:heading>
                <flux:text class="mt-1">Professor, classroom, subject, and academic year mappings.</flux:text>
            </div>
            @can('teaching_assignments.manage')
                <flux:button href="{{ route('teaching-assignments.create') }}" variant="primary" icon="plus">Add Teaching Assignment</flux:button>
            @endcan
        </div>
        <form method="GET" action="{{ route('teaching-assignments.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-[repeat(4,minmax(0,1fr))_auto] xl:items-end">
            <flux:select name="academic_year_id" label="Academic Year">
                <option value="">All academic years</option>
                @foreach($academicYears as $academicYear)
                    <option value="{{ $academicYear->id }}" @selected(request('academic_year_id') == $academicYear->id)>{{ $academicYear->name }}</option>
                @endforeach
            </flux:select>
            <flux:select name="professor_id" label="Professor">
                <option value="">All professors</option>
                @foreach($professors as $professor)
                    <option value="{{ $professor->id }}" @selected(request('professor_id') == $professor->id)>{{ $professor->name }}</option>
                @endforeach
            </flux:select>
            <flux:select name="classroom_id" label="Classroom">
                <option value="">All classrooms</option>
                @foreach($classrooms as $classroom)
                    <option value="{{ $classroom->id }}" @selected(request('classroom_id') == $classroom->id)>{{ $classroom->name }}</option>
                @endforeach
            </flux:select>
            <flux:select name="subject_id" label="Subject">
                <option value="">All subjects</option>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}" @selected(request('subject_id') == $subject->id)>{{ $subject->code }} - {{ $subject->name }}</option>
                @endforeach
            </flux:select>
            <div class="flex flex-wrap gap-2">
                <flux:button type="submit" variant="primary">Apply</flux:button>
                @if(request()->hasAny(['academic_year_id', 'professor_id', 'classroom_id', 'subject_id']))
                    <flux:button href="{{ route('teaching-assignments.index') }}" variant="ghost">Clear</flux:button>
                @endif
            </div>
        </form>
        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1100px] text-left text-sm">
                    <thead class="border-b bg-zinc-50 text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
                        <tr><th scope="col" class="px-5 py-3">Professor</th><th scope="col" class="px-5 py-3">Subject</th><th scope="col" class="px-5 py-3">Classroom</th><th scope="col" class="px-5 py-3">Department</th><th scope="col" class="px-5 py-3">School</th><th scope="col" class="px-5 py-3">Academic Year</th><th scope="col" class="px-5 py-3">Current Students</th><th scope="col" class="px-5 py-3 text-right">Actions</th></tr>
                    </thead>
                    <tbody class="divide-y dark:divide-zinc-700">
                        @forelse($assignments as $assignment)
                            <tr>
                                <td class="px-5 py-4">{{ $assignment->professor->name }}</td>
                                <td class="px-5 py-4">{{ $assignment->subject->code }} - {{ $assignment->subject->name }}</td>
                                <td class="px-5 py-4"><a class="font-medium text-teal-700 hover:underline dark:text-teal-300" href="{{ route('teaching-assignments.show', $assignment) }}">{{ $assignment->classroom->name }}</a></td>
                                <td class="px-5 py-4">{{ $assignment->classroom->department->name }}</td>
                                <td class="px-5 py-4">{{ $assignment->classroom->department->school->name }}</td>
                                <td class="px-5 py-4">{{ $assignment->academicYear->name }}</td>
                                <td class="px-5 py-4">{{ $assignment->classroom->students_count }}</td>
                                <td class="px-5 py-4 text-right">
                                    <a class="mr-3 font-medium text-teal-700 hover:underline dark:text-teal-300" href="{{ route('teaching-assignments.show', $assignment) }}">View</a>
                                    @can('update', $assignment)
                                        <a class="mr-3 font-medium text-teal-700 hover:underline dark:text-teal-300" href="{{ route('teaching-assignments.edit', $assignment) }}">Edit</a>
                                    @endcan
                                    @can('delete', $assignment)
                                        <form class="inline" method="POST" action="{{ route('teaching-assignments.destroy', $assignment) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-medium text-rose-700 hover:underline dark:text-rose-300" onclick="return confirm('Delete this assignment?')">Delete</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-5 py-10 text-center text-zinc-500">No teaching assignments found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($assignments->hasPages())
                <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">{{ $assignments->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.app>
