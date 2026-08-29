<x-layouts.app>
    <div class="mx-auto w-full max-w-5xl space-y-6 p-4 sm:p-6 lg:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <flux:heading level="1" size="xl">Report Card</flux:heading>
                <flux:text class="mt-1">{{ $semester->academicYear->name }} · {{ $semester->name }}</flux:text>
            </div>
            <flux:button href="{{ route('student-grades.results', ['student' => $student, 'academic_year_id' => $semester->academic_year_id]) }}" variant="ghost" icon="arrow-left">Back to Results</flux:button>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">Student and Academic Context</flux:heading>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <div class="text-sm text-zinc-500">Student</div>
                    <div class="font-medium">{{ $student->last_name }} {{ $student->first_name }}</div>
                </div>
                <div>
                    <div class="text-sm text-zinc-500">Student Number</div>
                    <div class="font-medium">{{ $student->student_number }}</div>
                </div>
                <div>
                    <div class="text-sm text-zinc-500">School</div>
                    <div class="font-medium">{{ $student->classroom->department->school->name }}</div>
                </div>
                <div>
                    <div class="text-sm text-zinc-500">Department</div>
                    <div class="font-medium">{{ $student->classroom->department->name }}</div>
                </div>
                <div>
                    <div class="text-sm text-zinc-500">Classroom</div>
                    <div class="font-medium">{{ $student->classroom->name }}</div>
                </div>
                <div>
                    <div class="text-sm text-zinc-500">Academic Year</div>
                    <div class="font-medium">{{ $semester->academicYear->name }}</div>
                </div>
                <div>
                    <div class="text-sm text-zinc-500">Semester</div>
                    <div class="font-medium">{{ $semester->name }}</div>
                </div>
            </div>
        </div>
        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-left text-sm">
                    <thead class="border-b bg-zinc-50 text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
                        <tr><th scope="col" class="px-5 py-3">Subject</th><th scope="col" class="px-5 py-3">Grade</th><th scope="col" class="px-5 py-3">Type</th><th scope="col" class="px-5 py-3">Coefficient</th><th scope="col" class="px-5 py-3">Weighted Result</th><th scope="col" class="px-5 py-3">Professor</th></tr>
                    </thead>
                    <tbody class="divide-y dark:divide-zinc-700">
                        @forelse($grades as $grade)
                            <tr>
                                <td class="px-5 py-4">{{ $grade->subject?->name ?? '—' }}</td>
                                <td class="px-5 py-4">{{ $grade->grade }}</td>
                                <td class="px-5 py-4">{{ $grade->type ?? '—' }}</td>
                                <td class="px-5 py-4">{{ $grade->coefficient }}</td>
                                <td class="px-5 py-4">{{ $grade->weightedResult() ?? '—' }}</td>
                                <td class="px-5 py-4">{{ $grade->teachingAssignment?->professor?->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-10 text-center text-zinc-500">No grades are available for this semester.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="border-t dark:border-zinc-700">
                        <tr><td class="px-5 py-4 font-medium">Semester Average</td><td colspan="5" class="px-5 py-4 font-semibold">{{ $weightedAverage ?? '—' }}</td></tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</x-layouts.app>
