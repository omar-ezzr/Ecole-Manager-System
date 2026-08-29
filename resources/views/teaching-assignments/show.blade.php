<x-layouts.app>
    <div class="mx-auto w-full max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <flux:heading level="1" size="xl">{{ auth()->user()->isProfessor() ? 'My Teaching Assignment' : 'Teaching Assignment' }}</flux:heading>
                <flux:text class="mt-1">{{ $assignment->subject->code }} — {{ $assignment->subject->name }} · {{ $assignment->classroom->name }} · {{ $assignment->academicYear->name }}</flux:text>
            </div>
            <div class="flex flex-wrap gap-2">
                @canany(['grades.view_all', 'grades.view_assigned', 'grades.view_own'])
                    <flux:button href="#grades" icon="academic-cap">
                        @canany(['grades.manage_all', 'grades.manage_assigned'])
                            Manage Grades
                        @else
                            View Grades
                        @endcanany
                    </flux:button>
                @endcanany
                @can('viewForAssignment', [\App\Models\AttendanceRecord::class, $assignment])
                    <flux:button href="{{ route('teaching-assignments.attendance.index', $assignment) }}" icon="calendar-days" variant="primary">
                        @can('createForAssignment', [\App\Models\AttendanceRecord::class, $assignment])
                            Take Attendance
                        @else
                            View Attendance
                        @endcan
                    </flux:button>
                @endcan
                <flux:button href="{{ route('teaching-assignments.index') }}" icon="arrow-left" variant="ghost">Back</flux:button>
            </div>
        </div>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach([
                'Professor' => $assignment->professor->name,
                'Subject' => $assignment->subject->code.' — '.$assignment->subject->name,
                'Classroom' => $assignment->classroom->name,
                'Department' => $assignment->classroom->department->name,
                'School' => $assignment->classroom->department->school->name,
                'Academic Year' => $assignment->academicYear->name,
            ] as $label => $value)
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $label }}</div>
                    <div class="mt-1 font-medium">{{ $value }}</div>
                </div>
            @endforeach
        </section>

        <form method="GET" action="{{ route('teaching-assignments.show', $assignment) }}" class="flex max-w-xl flex-col gap-3 sm:flex-row sm:items-end">
            <flux:select name="semester_id" label="Semester" class="min-w-0 flex-1">
                @forelse($semesters as $semester)
                    <option value="{{ $semester->id }}" @selected($selectedSemester?->id === $semester->id)>{{ $semester->name }}</option>
                @empty
                    <option value="">No semesters configured</option>
                @endforelse
            </flux:select>
            <flux:button type="submit" variant="primary" :disabled="$semesters->isEmpty()">Apply</flux:button>
        </form>

        <section id="grades" class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-col gap-2 border-b border-zinc-200 p-5 dark:border-zinc-700 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <flux:heading size="lg">Grade Roster</flux:heading>
                    <flux:text class="mt-1">
                        @if($selectedSemester)
                            {{ $selectedSemester->name }} · grades from {{ \App\Models\StudentGrade::MIN_GRADE }} to {{ \App\Models\StudentGrade::MAX_GRADE }}
                        @else
                            Select a configured semester to view grades.
                        @endif
                    </flux:text>
                </div>
                @if($selectedSemester && ! $canManageGrades)
                    <flux:badge color="zinc">Read Only</flux:badge>
                @endif
            </div>

            @if($selectedSemester && $students->isNotEmpty())
                <form method="POST" action="{{ route('student-grades.store') }}">
                    @csrf
                    <input type="hidden" name="teaching_assignment_id" value="{{ $assignment->id }}">
                    <input type="hidden" name="semester_id" value="{{ $selectedSemester->id }}">

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[960px] text-left text-sm">
                            <thead class="border-b bg-zinc-50 text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">
                                <tr>
                                    <th scope="col" class="px-5 py-3">Student ID</th>
                                    <th scope="col" class="px-5 py-3">Student</th>
                                    <th scope="col" class="px-5 py-3">Grade</th>
                                    <th scope="col" class="px-5 py-3">Type</th>
                                    <th scope="col" class="px-5 py-3">Coefficient</th>
                                    <th scope="col" class="px-5 py-3 text-right">Results</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @foreach($students as $index => $student)
                                    @php($grade = $existingGrades->get($student->id))
                                    <tr>
                                        <td class="px-5 py-4">
                                            {{ $student->student_number }}
                                            <input type="hidden" name="grades[{{ $index }}][student_id]" value="{{ $student->id }}">
                                        </td>
                                        <td class="px-5 py-4 font-medium">{{ $student->last_name }} {{ $student->first_name }}</td>
                                        @if($canManageGrades)
                                            <td class="px-5 py-4 align-top">
                                                <label for="grade-{{ $student->id }}" class="sr-only">Grade for {{ $student->last_name }} {{ $student->first_name }}</label>
                                                <input
                                                    id="grade-{{ $student->id }}"
                                                    class="w-28 rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900"
                                                    type="number"
                                                    step="0.01"
                                                    min="{{ \App\Models\StudentGrade::MIN_GRADE }}"
                                                    max="{{ \App\Models\StudentGrade::MAX_GRADE }}"
                                                    name="grades[{{ $index }}][grade]"
                                                    value="{{ old("grades.$index.grade", $grade?->grade) }}"
                                                    @if($errors->has("grades.$index.grade")) aria-invalid="true" @endif
                                                >
                                                @error("grades.$index.grade")
                                                    <p class="mt-1 max-w-44 text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>
                                                @enderror
                                            </td>
                                            <td class="px-5 py-4 align-top">
                                                <label for="grade-type-{{ $student->id }}" class="sr-only">Grade type for {{ $student->last_name }} {{ $student->first_name }}</label>
                                                <input
                                                    id="grade-type-{{ $student->id }}"
                                                    class="w-32 rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900"
                                                    type="text"
                                                    name="grades[{{ $index }}][type]"
                                                    value="{{ old("grades.$index.type", $grade?->type ?? 'Exam') }}"
                                                    @if($errors->has("grades.$index.type")) aria-invalid="true" @endif
                                                >
                                                @error("grades.$index.type")
                                                    <p class="mt-1 max-w-44 text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>
                                                @enderror
                                            </td>
                                            <td class="px-5 py-4 align-top">
                                                <label for="coefficient-{{ $student->id }}" class="sr-only">Coefficient for {{ $student->last_name }} {{ $student->first_name }}</label>
                                                <input
                                                    id="coefficient-{{ $student->id }}"
                                                    class="w-24 rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900"
                                                    type="number"
                                                    step="0.01"
                                                    min="0.01"
                                                    name="grades[{{ $index }}][coefficient]"
                                                    value="{{ old("grades.$index.coefficient", $grade?->coefficient ?? 1) }}"
                                                    @if($errors->has("grades.$index.coefficient")) aria-invalid="true" @endif
                                                >
                                                @error("grades.$index.coefficient")
                                                    <p class="mt-1 max-w-44 text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>
                                                @enderror
                                            </td>
                                        @else
                                            <td class="px-5 py-4">{{ $grade?->grade ?? '—' }}</td>
                                            <td class="px-5 py-4">{{ $grade?->type ?? '—' }}</td>
                                            <td class="px-5 py-4">{{ $grade?->coefficient ?? '—' }}</td>
                                        @endif
                                        <td class="px-5 py-4 text-right">
                                            <a class="font-medium text-teal-700 hover:underline dark:text-teal-300" href="{{ route('student-grades.results', $student) }}">View Results</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($canManageGrades)
                        <div class="flex justify-end border-t border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                            <flux:button type="submit" variant="primary">Save grades</flux:button>
                        </div>
                    @endif
                </form>
            @elseif($selectedSemester)
                <div class="px-5 py-12 text-center">
                    <p class="font-medium text-zinc-950 dark:text-white">No enrolled students found.</p>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">This assignment has no historical enrollment roster for the academic year.</p>
                </div>
            @else
                <div class="px-5 py-12 text-center text-zinc-500">No semesters are configured for this academic year.</div>
            @endif
        </section>
    </div>
</x-layouts.app>
