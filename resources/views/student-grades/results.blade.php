<x-layouts.app>
    <div class="mx-auto w-full max-w-6xl space-y-6 p-4 sm:p-6 lg:p-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <flux:heading size="xl">{{ auth()->user()->isStudentUser() ? 'My Results' : 'Student Results' }}</flux:heading>
                <flux:text class="mt-1">{{ $student->last_name }} {{ $student->first_name }} | {{ $student->student_number }}</flux:text>
                @if($historicalClassroom)
                    <flux:text class="mt-1">{{ $selectedYear?->name }} classroom: {{ $historicalClassroom->name }}</flux:text>
                @endif
            </div>
            <div class="flex w-full flex-col gap-3 sm:flex-row sm:items-end lg:w-auto">
                @if($availableYears->isNotEmpty())
                    <form method="GET" action="{{ route('student-grades.results', $student) }}" class="flex flex-1 items-end gap-2 lg:min-w-80">
                        <flux:select name="academic_year_id" label="Academic Year" class="min-w-0 flex-1">
                        @foreach($availableYears as $academicYear)
                            <option value="{{ $academicYear->id }}" @selected($selectedYear?->id === $academicYear->id)>{{ $academicYear->name }}</option>
                        @endforeach
                        </flux:select>
                        <flux:button type="submit" variant="primary">Apply</flux:button>
                    </form>
                @endif
                <flux:button href="{{ auth()->user()->isStudentUser() ? route('dashboard') : route('student-grades.index') }}" variant="ghost" icon="arrow-left">Back</flux:button>
            </div>
        </div>
        @foreach($semesterResults as $entry)
            <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between border-b px-5 py-4 dark:border-zinc-700">
                    <div>
                        <div class="font-medium">{{ $entry['semester']->name }}</div>
                        <div class="text-sm text-zinc-500">{{ $selectedYear?->name }} · {{ $historicalClassroom?->name ?? 'Historical classroom unavailable' }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-zinc-500">Semester Average</div>
                        <div class="font-medium">{{ $entry['average'] ?? '—' }}</div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[820px] text-left text-sm">
                        <thead class="bg-zinc-50 text-zinc-500 dark:bg-zinc-800">
                            <tr><th scope="col" class="px-5 py-3">Subject</th><th scope="col" class="px-5 py-3">Grade</th><th scope="col" class="px-5 py-3">Type</th><th scope="col" class="px-5 py-3">Coefficient</th><th scope="col" class="px-5 py-3">Weighted Result</th><th scope="col" class="px-5 py-3">Professor</th></tr>
                        </thead>
                        <tbody class="divide-y dark:divide-zinc-700">
                            @foreach($entry['grades'] as $grade)
                                <tr>
                                    <td class="px-5 py-4">{{ $grade->subject?->name ?? '—' }}</td>
                                    <td class="px-5 py-4">{{ $grade->grade }}</td>
                                    <td class="px-5 py-4">{{ $grade->type ?? '—' }}</td>
                                    <td class="px-5 py-4">{{ $grade->coefficient }}</td>
                                    <td class="px-5 py-4">{{ $grade->weightedResult() ?? '—' }}</td>
                                    <td class="px-5 py-4">{{ $grade->teachingAssignment?->professor?->name ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-end border-t border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:button href="{{ route('student-grades.report-card', [$student, $entry['semester']]) }}" icon="document-text">View Report Card</flux:button>
                </div>
            </div>
        @endforeach
        @if($semesterResults->isEmpty())
            <div class="rounded-xl border border-zinc-200 bg-white px-5 py-12 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="font-medium text-zinc-950 dark:text-white">No results found.</p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">No assignment-based grades are available for this student and academic year.</p>
            </div>
        @endif
    </div>
</x-layouts.app>
