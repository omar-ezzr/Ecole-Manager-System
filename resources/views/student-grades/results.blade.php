<x-layouts.app>
    <div class="mx-auto max-w-6xl space-y-6 p-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ auth()->user()->isStudentUser() ? 'My Results' : 'Student Results' }}</flux:heading>
                <flux:text class="mt-1">{{ $student->last_name }} {{ $student->first_name }} | {{ $student->student_number }}</flux:text>
            </div>
            @if($availableYears->isNotEmpty())
                <form method="GET" class="w-full max-w-xs">
                    <label class="mb-2 block text-sm text-zinc-500">Academic Year</label>
                    <select name="academic_year_id" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                        @foreach($availableYears as $academicYear)
                            <option value="{{ $academicYear->id }}" @selected($selectedYear?->id === $academicYear->id)>{{ $academicYear->name }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>
        @foreach($semesterResults as $entry)
            <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between border-b px-5 py-4 dark:border-zinc-700">
                    <div>
                        <div class="font-medium">{{ $entry['semester']->name }}</div>
                        <div class="text-sm text-zinc-500">{{ $selectedYear?->name }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-zinc-500">Weighted average</div>
                        <div class="font-medium">{{ $entry['average'] ?? '—' }}</div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-zinc-50 text-zinc-500 dark:bg-zinc-800">
                            <tr><th class="px-5 py-3">Subject</th><th class="px-5 py-3">Grade</th><th class="px-5 py-3">Type</th><th class="px-5 py-3">Coefficient</th><th class="px-5 py-3">Professor</th></tr>
                        </thead>
                        <tbody class="divide-y dark:divide-zinc-700">
                            @foreach($entry['grades'] as $grade)
                                <tr>
                                    <td class="px-5 py-4">{{ $grade->subject?->name ?? '—' }}</td>
                                    <td class="px-5 py-4">{{ $grade->grade }}</td>
                                    <td class="px-5 py-4">{{ $grade->type ?? '—' }}</td>
                                    <td class="px-5 py-4">{{ $grade->coefficient }}</td>
                                    <td class="px-5 py-4">{{ $grade->teachingAssignment?->professor?->name ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t p-4 dark:border-zinc-700">
                    <a class="text-teal-600" href="{{ route('student-grades.report-card', [$student, $entry['semester']]) }}">Open report card</a>
                </div>
            </div>
        @endforeach
        @if($semesterResults->isEmpty())
            <div class="rounded-xl border border-zinc-200 bg-white p-6 text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">No assignment-based grades are available for this student yet.</div>
        @endif
    </div>
</x-layouts.app>
