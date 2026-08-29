<x-layouts.app>
    @php($currentEnrollment = $student->enrollments->first(fn ($enrollment) => $enrollment->left_at === null))

    <div class="mx-auto w-full max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <flux:heading level="1" size="xl">{{ $student->last_name }} {{ $student->first_name }}</flux:heading>
                <flux:text class="mt-1">Student ID {{ $student->student_number }}</flux:text>
            </div>
            <div class="flex flex-wrap gap-2">
                @canany(['grades.view_all', 'grades.view_assigned', 'grades.view_own'])
                    <flux:button href="{{ route('student-grades.results', $student) }}" icon="chart-bar-square">View Results</flux:button>
                @endcanany
                @can('update', $student)
                    <flux:button href="{{ route('students.edit', $student) }}" icon="pencil-square" variant="primary">Edit</flux:button>
                @endcan
                <flux:button href="{{ route('students.index') }}" variant="ghost" icon="arrow-left">Back to Students</flux:button>
            </div>
        </div>

        <section class="grid gap-4 lg:grid-cols-[1.5fr_.75fr]">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 sm:p-6">
                <flux:heading size="lg">Student Information</flux:heading>
                <dl class="mt-5 grid gap-x-6 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach([
                        'Student ID' => $student->student_number,
                        'Phone' => $student->phone,
                        'Email' => $student->email,
                        'City' => $student->city,
                        'Address' => $student->address,
                        'Diploma' => $student->diploma,
                        'Academic Level' => $student->education_level,
                        'Height' => $student->height !== null ? $student->height.' cm' : null,
                        'Weight' => $student->weight !== null ? $student->weight.' kg' : null,
                    ] as $label => $value)
                        <div>
                            <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ $label }}</dt>
                            <dd class="mt-1 font-medium text-zinc-950 dark:text-white">{{ filled($value) ? $value : '—' }}</dd>
                        </div>
                    @endforeach
                </dl>

                @if(filled($student->appreciation) || filled($student->appreciation_score))
                    <div class="mt-6 border-t border-zinc-200 pt-5 dark:border-zinc-700">
                        <div class="grid gap-4 sm:grid-cols-[10rem_1fr]">
                            <div>
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">Evaluation Score</div>
                                <div class="mt-1 font-medium">{{ $student->appreciation_score ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">Evaluation Comment</div>
                                <div class="mt-1 font-medium">{{ $student->appreciation ?: '—' }}</div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-900 sm:p-6">
                <flux:heading size="lg">Student Identification</flux:heading>
                <flux:text class="mt-1">Scan to view the stored identification details.</flux:text>
                <div class="mt-5 flex justify-center overflow-hidden" role="img" aria-label="QR code for {{ $student->last_name }} {{ $student->first_name }}">
                    {!! $qrcode !!}
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 sm:p-6">
            <flux:heading size="lg">Current Enrollment</flux:heading>
            @if($currentEnrollment)
                <dl class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Academic Year</dt>
                        <dd class="mt-1 font-medium">{{ $currentEnrollment->academicYear->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Classroom</dt>
                        <dd class="mt-1 font-medium">{{ $currentEnrollment->classroom->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Enrolled At</dt>
                        <dd class="mt-1 font-medium">{{ $currentEnrollment->enrolled_at->format('Y-m-d') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Status</dt>
                        <dd class="mt-1"><flux:badge color="emerald">Current</flux:badge></dd>
                    </div>
                </dl>
            @else
                <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">No current enrollment is available.</p>
            @endif
        </section>

        <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 p-5 dark:border-zinc-700">
                <flux:heading size="lg">Enrollment History</flux:heading>
                <flux:text class="mt-1">Historical classroom membership by academic year.</flux:text>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead class="bg-zinc-50 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                        <tr>
                            <th scope="col" class="px-5 py-3">Academic Year</th>
                            <th scope="col" class="px-5 py-3">Classroom</th>
                            <th scope="col" class="px-5 py-3">Enrolled At</th>
                            <th scope="col" class="px-5 py-3">Left At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($student->enrollments as $enrollment)
                            <tr>
                                <td class="px-5 py-4">{{ $enrollment->academicYear->name }}</td>
                                <td class="px-5 py-4">{{ $enrollment->classroom->name }}</td>
                                <td class="px-5 py-4">{{ $enrollment->enrolled_at->format('Y-m-d') }}</td>
                                <td class="px-5 py-4">
                                    @if($enrollment->left_at)
                                        {{ $enrollment->left_at->format('Y-m-d') }}
                                    @else
                                        <flux:badge color="emerald">Current</flux:badge>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-zinc-500">No enrollment history is available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @if($canViewSemesterAverages)
            <section id="academic-results" class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 p-5 dark:border-zinc-700">
                    <flux:heading size="lg">Academic Results</flux:heading>
                    <flux:text class="mt-1">Semester averages for the active academic year.</flux:text>
                </div>
                <div class="p-4 sm:p-6">
                    @once
                        <script src="{{ asset('js/chart.js') }}"></script>
                    @endonce
                    <x-student-semester-grades :id="$student->id" />
                </div>
            </section>
        @endif

        @if($canViewAttendanceSummary)
            <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 sm:p-6">
                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <flux:heading size="lg">Attendance Summary</flux:heading>
                        <flux:text class="mt-1">Recorded daily attendance for this student.</flux:text>
                    </div>
                    @if($attendanceAcademicYears->isNotEmpty())
                        <form method="GET" action="{{ route('students.show', $student) }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                            <flux:select name="attendance_academic_year_id" label="Academic Year">
                                @foreach($attendanceAcademicYears as $academicYear)
                                    <option value="{{ $academicYear->id }}" @selected($selectedAttendanceYear?->is($academicYear))>{{ $academicYear->name }}</option>
                                @endforeach
                            </flux:select>
                            <flux:button type="submit" variant="primary">Apply</flux:button>
                        </form>
                    @endif
                </div>

                <dl class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    @foreach([
                        'present' => 'Present',
                        'absent' => 'Absent',
                        'late' => 'Late',
                        'excused' => 'Excused',
                        'recorded' => 'Recorded Days',
                    ] as $key => $label)
                        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800" data-attendance-metric="{{ $key }}">
                            <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ $label }}</dt>
                            <dd class="mt-1 text-2xl font-semibold">{{ $attendanceSummary[$key] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        @endif

        @if($canViewHealthRecords)
            <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 p-5 dark:border-zinc-700">
                    <flux:heading size="lg">Health Records</flux:heading>
                    <flux:text class="mt-1">Authorized health consultations linked to this student.</flux:text>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] text-left text-sm">
                        <thead class="bg-zinc-50 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                            <tr>
                                <th scope="col" class="px-5 py-3">Date</th>
                                <th scope="col" class="px-5 py-3">Consultation Type</th>
                                <th scope="col" class="px-5 py-3">Medical Prescription</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse($healthRecords as $healthRecord)
                                <tr>
                                    <td class="px-5 py-4">{{ $healthRecord->date }}</td>
                                    <td class="px-5 py-4">{{ $healthRecord->type }}</td>
                                    <td class="px-5 py-4">{{ $healthRecord->medical_prescription ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-5 py-10 text-center text-zinc-500">No health records are available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
</x-layouts.app>
