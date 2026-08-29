<x-layouts.app>
    @php($statusColors = ['present' => 'emerald', 'absent' => 'rose', 'late' => 'amber', 'excused' => 'blue'])

    <div class="mx-auto w-full max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <flux:heading level="1" size="xl">{{ $canManage ? 'Take Attendance' : 'View Attendance' }}</flux:heading>
                <flux:text class="mt-1">
                    {{ $assignment->subject->code }} - {{ $assignment->subject->name }}
                    | {{ $assignment->classroom->name }}
                    | {{ $assignment->academicYear->name }}
                </flux:text>
            </div>
            <flux:button href="{{ route('teaching-assignments.show', $assignment) }}" variant="ghost" icon="arrow-left">Back to Assignment</flux:button>
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-sm text-zinc-500">Professor</div>
                <div class="mt-1 font-medium">{{ $assignment->professor->name }}</div>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-sm text-zinc-500">Classroom</div>
                <div class="mt-1 font-medium">{{ $assignment->classroom->name }}</div>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-sm text-zinc-500">Academic Year</div>
                <div class="mt-1 font-medium">{{ $assignment->academicYear->name }}</div>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-sm text-zinc-500">Subject</div>
                <div class="mt-1 font-medium">{{ $assignment->subject->name }}</div>
            </div>
        </div>

        <form method="GET" action="{{ route('teaching-assignments.attendance.index', $assignment) }}" class="flex max-w-md flex-col gap-3 sm:flex-row sm:items-end">
            <flux:input type="date" name="date" label="Attendance Date" value="{{ $date }}" class="min-w-0 flex-1" required />
            <flux:button type="submit" variant="primary">View Roster</flux:button>
        </form>

        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            @if($canManage)
                <form method="POST" action="{{ route('teaching-assignments.attendance.store', $assignment) }}">
                    @csrf
                    <input type="hidden" name="date" value="{{ $date }}">
            @endif

            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="border-b bg-zinc-50 text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
                        <tr>
                            <th scope="col" class="px-5 py-3">Student ID</th>
                            <th scope="col" class="px-5 py-3">Student</th>
                            <th scope="col" class="px-5 py-3">Status</th>
                            <th scope="col" class="px-5 py-3">Note</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($enrollments as $index => $enrollment)
                            @php($record = $records->get($enrollment->id))
                            <tr>
                                <td class="px-5 py-4">{{ $enrollment->student->student_number }}</td>
                                <td class="px-5 py-4">{{ $enrollment->student->last_name }} {{ $enrollment->student->first_name }}</td>
                                @if($canManage)
                                    <td class="px-5 py-4">
                                        <input type="hidden" name="attendance[{{ $index }}][student_enrollment_id]" value="{{ $enrollment->id }}">
                                        <label for="attendance-status-{{ $enrollment->id }}" class="sr-only">Attendance status for {{ $enrollment->student->last_name }} {{ $enrollment->student->first_name }}</label>
                                        <select id="attendance-status-{{ $enrollment->id }}" name="attendance[{{ $index }}][status]" class="w-36 rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900" @if($errors->has("attendance.$index.status")) aria-invalid="true" @endif>
                                            @foreach($statusLabels as $status => $label)
                                                <option value="{{ $status }}" @selected(old("attendance.$index.status", $record?->status ?? \App\Models\AttendanceRecord::STATUS_PRESENT) === $status)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error("attendance.$index.status")
                                            <p class="mt-1 text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>
                                        @enderror
                                    </td>
                                    <td class="px-5 py-4">
                                        <label for="attendance-note-{{ $enrollment->id }}" class="sr-only">Attendance note for {{ $enrollment->student->last_name }} {{ $enrollment->student->first_name }}</label>
                                        <input id="attendance-note-{{ $enrollment->id }}" type="text" name="attendance[{{ $index }}][note]" maxlength="1000" value="{{ old("attendance.$index.note", $record?->note) }}" class="w-full min-w-52 rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900" placeholder="Optional note" @if($errors->has("attendance.$index.note")) aria-invalid="true" @endif>
                                        @error("attendance.$index.note")
                                            <p class="mt-1 text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>
                                        @enderror
                                    </td>
                                @else
                                    <td class="px-5 py-4">
                                        @if($record)
                                            <flux:badge color="{{ $statusColors[$record->status] }}">{{ $statusLabels[$record->status] }}</flux:badge>
                                        @else
                                            <flux:badge color="zinc">Not Recorded</flux:badge>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4">{{ $record?->note ?? '—' }}</td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-8 text-center text-zinc-500">No enrolled students are valid for this date.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($canManage)
                    @if($enrollments->isNotEmpty())
                        <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">
                            <flux:button type="submit" variant="primary">Save Attendance</flux:button>
                        </div>
                    @endif
                </form>
            @endif
        </div>
    </div>
</x-layouts.app>
