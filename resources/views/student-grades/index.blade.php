<x-layouts.app>
    <div class="mx-auto w-full max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <flux:heading level="1" size="xl">Academic Results</flux:heading>
                <flux:text class="mt-1">Open student results by academic year and classroom context.</flux:text>
            </div>
        </div>

        <form method="GET" action="{{ route('student-grades.index') }}" class="grid gap-4 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] md:items-end">
            <flux:select name="academic_year_id" label="Academic Year">
                @foreach($academicYears as $academicYear)
                    <option value="{{ $academicYear->id }}" @selected($selectedYear?->id === $academicYear->id)>{{ $academicYear->name }}</option>
                @endforeach
            </flux:select>
            <flux:select name="classroom_id" label="Classroom">
                <option value="">All classrooms</option>
                @foreach($classrooms as $classroom)
                    <option value="{{ $classroom->id }}" @selected($selectedClassroom?->id === $classroom->id)>{{ $classroom->name }}</option>
                @endforeach
            </flux:select>
            <div class="flex gap-2">
                <flux:button type="submit" variant="primary">Apply</flux:button>
                @if(request()->hasAny(['academic_year_id', 'classroom_id']))
                    <flux:button href="{{ route('student-grades.index') }}" variant="ghost">Clear</flux:button>
                @endif
            </div>
        </form>

        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 p-5 dark:border-zinc-700">
                <flux:heading size="lg">{{ $selectedYear?->name ?? 'No academic year' }}</flux:heading>
                <flux:text class="mt-1">{{ $selectedClassroom?->name ?? 'All classrooms' }}</flux:text>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-left text-sm">
                    <thead class="border-b bg-zinc-50 text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
                        <tr><th scope="col" class="px-5 py-3">Student</th><th scope="col" class="px-5 py-3">Student ID</th><th scope="col" class="px-5 py-3">Current Classroom</th><th scope="col" class="px-5 py-3 text-right">Actions</th></tr>
                    </thead>
                    <tbody class="divide-y dark:divide-zinc-700">
                        @forelse($students as $student)
                            <tr>
                                <td class="px-5 py-4">{{ $student->last_name }} {{ $student->first_name }}</td>
                                <td class="px-5 py-4">{{ $student->student_number }}</td>
                                <td class="px-5 py-4">{{ $student->classroom->name }}</td>
                                <td class="px-5 py-4 text-right"><a class="font-medium text-teal-700 hover:underline dark:text-teal-300" href="{{ route('student-grades.results', ['student' => $student, 'academic_year_id' => $selectedYear?->id]) }}">View Results</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-10 text-center text-zinc-500">No assignment-based results found for this filter.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($students->hasPages())
                <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">{{ $students->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.app>
