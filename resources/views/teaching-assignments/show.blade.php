<x-layouts.app>
    <div class="mx-auto max-w-7xl space-y-6 p-6">
        <div>
            <flux:heading size="xl">{{ auth()->user()->isProfessor() ? 'My Teaching Assignment' : 'Teaching Assignment' }}</flux:heading>
            <flux:text class="mt-1">{{ $assignment->subject->code }} - {{ $assignment->subject->name }} | {{ $assignment->classroom->name }} | {{ $assignment->academicYear->name }}</flux:text>
        </div>
        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900"><div class="text-sm text-zinc-500">Professor</div><div class="mt-1 font-medium">{{ $assignment->professor->name }}</div></div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900"><div class="text-sm text-zinc-500">Classroom</div><div class="mt-1 font-medium">{{ $assignment->classroom->name }}</div></div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900"><div class="text-sm text-zinc-500">Department</div><div class="mt-1 font-medium">{{ $assignment->classroom->department->name }}</div></div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900"><div class="text-sm text-zinc-500">School</div><div class="mt-1 font-medium">{{ $assignment->classroom->department->school->name }}</div></div>
        </div>
        <form method="GET" class="max-w-sm">
            <label class="mb-2 block text-sm text-zinc-500">Semester</label>
            <select name="semester_id" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                @foreach($semesters as $semester)
                    <option value="{{ $semester->id }}" @selected($selectedSemester?->id === $semester->id)>{{ $semester->name }}</option>
                @endforeach
            </select>
        </form>
        @if($selectedSemester)
            <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="overflow-x-auto">
                    <form method="POST" action="{{ route('student-grades.store') }}">
                        @csrf
                        <input type="hidden" name="teaching_assignment_id" value="{{ $assignment->id }}">
                        <input type="hidden" name="semester_id" value="{{ $selectedSemester->id }}">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b bg-zinc-50 text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
                                <tr><th class="px-5 py-3">Student Number</th><th class="px-5 py-3">Student Name</th><th class="px-5 py-3">Grade</th><th class="px-5 py-3">Type</th><th class="px-5 py-3">Coefficient</th><th class="px-5 py-3">Results</th></tr>
                            </thead>
                            <tbody class="divide-y dark:divide-zinc-700">
                                @foreach($assignment->classroom->students as $index => $student)
                                    @php($grade = $existingGrades->get($student->id))
                                    <tr>
                                        <td class="px-5 py-4">{{ $student->student_number }}<input type="hidden" name="grades[{{ $index }}][student_id]" value="{{ $student->id }}"></td>
                                        <td class="px-5 py-4">{{ $student->last_name }} {{ $student->first_name }}</td>
                                        @if($canManageGrades)
                                            <td class="px-5 py-4"><input class="w-28 rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900" type="number" step="0.01" min="0" name="grades[{{ $index }}][grade]" value="{{ old("grades.$index.grade", $grade?->grade) }}"></td>
                                            <td class="px-5 py-4"><input class="w-32 rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900" type="text" name="grades[{{ $index }}][type]" value="{{ old("grades.$index.type", $grade?->type ?? 'Exam') }}"></td>
                                            <td class="px-5 py-4"><input class="w-24 rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900" type="number" step="0.01" min="0.01" name="grades[{{ $index }}][coefficient]" value="{{ old("grades.$index.coefficient", $grade?->coefficient ?? 1) }}"></td>
                                        @else
                                            <td class="px-5 py-4">{{ $grade?->grade ?? '—' }}</td>
                                            <td class="px-5 py-4">{{ $grade?->type ?? '—' }}</td>
                                            <td class="px-5 py-4">{{ $grade?->coefficient ?? '—' }}</td>
                                        @endif
                                        <td class="px-5 py-4"><a class="text-teal-600" href="{{ route('student-grades.results', $student) }}">Results</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if($canManageGrades)
                            <div class="border-t p-4">
                                <flux:button type="submit" variant="primary">Save grades</flux:button>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>
