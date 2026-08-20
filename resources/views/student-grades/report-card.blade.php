<x-layouts.app>
    <div class="mx-auto max-w-5xl space-y-6 p-6">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="xl">Report Card</flux:heading>
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
        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-zinc-50 text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
                        <tr><th class="px-5 py-3">Subject</th><th class="px-5 py-3">Grade</th><th class="px-5 py-3">Coefficient</th><th class="px-5 py-3">Professor</th></tr>
                    </thead>
                    <tbody class="divide-y dark:divide-zinc-700">
                        @foreach($grades as $grade)
                            <tr>
                                <td class="px-5 py-4">{{ $grade->subject?->name ?? '—' }}</td>
                                <td class="px-5 py-4">{{ $grade->grade }}</td>
                                <td class="px-5 py-4">{{ $grade->coefficient }}</td>
                                <td class="px-5 py-4">{{ $grade->teachingAssignment?->professor?->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t dark:border-zinc-700">
                        <tr><td class="px-5 py-4 font-medium">Weighted average</td><td colspan="3" class="px-5 py-4 font-medium">{{ $weightedAverage ?? '—' }}</td></tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</x-layouts.app>
