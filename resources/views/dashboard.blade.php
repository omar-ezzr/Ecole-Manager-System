<x-layouts.app>
    <div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <section class="relative overflow-hidden rounded-2xl bg-zinc-950 text-white shadow-sm">
            <img src="{{ asset('images/school-dashboard-hero.png') }}" alt="Modern school administration office" class="absolute inset-0 h-full w-full object-cover opacity-70">
            <div class="absolute inset-0 bg-gradient-to-r from-zinc-950 via-zinc-950/75 to-zinc-950/20"></div>
            <div class="relative grid min-h-[320px] gap-8 p-6 sm:p-8 lg:grid-cols-[1.2fr_.8fr] lg:p-10">
                <div class="flex max-w-2xl flex-col justify-center">
                    <span class="mb-4 w-fit rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-medium text-teal-100">Academic overview</span>
                    <h1 class="text-3xl font-semibold tracking-tight sm:text-5xl">School management, with every record in reach.</h1>
                    <p class="mt-4 max-w-xl text-sm leading-6 text-zinc-200 sm:text-base">Track students, classrooms, departments, health records, attendance, and grades from one focused dashboard.</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <flux:button href="{{ route('students.index') }}" icon="user" variant="primary" wire:navigate>View students</flux:button>
                        @can('students.create')
                            <flux:button href="{{ route('students.create') }}" icon="plus" variant="ghost" wire:navigate>Add student</flux:button>
                        @endcan
                    </div>
                </div>
                <div class="hidden items-end justify-end lg:flex">
                    <div class="w-full max-w-sm rounded-2xl border border-white/15 bg-white/10 p-5 backdrop-blur">
                        <p class="text-sm font-medium text-teal-100">Current overview</p>
                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div class="rounded-xl bg-white/15 p-4">
                                <p class="text-xs text-zinc-300">Students</p>
                                <p class="mt-1 text-3xl font-semibold">{{ $totalStudents }}</p>
                            </div>
                            <div class="rounded-xl bg-white/15 p-4">
                                <p class="text-xs text-zinc-300">Classes</p>
                                <p class="mt-1 text-3xl font-semibold">{{ $totalClassrooms }}</p>
                            </div>
                            <div class="rounded-xl bg-white/15 p-4">
                                <p class="text-xs text-zinc-300">Departments</p>
                                <p class="mt-1 text-3xl font-semibold">{{ $totalDepartments }}</p>
                            </div>
                            <div class="rounded-xl bg-white/15 p-4">
                                <p class="text-xs text-zinc-300">Schools</p>
                                <p class="mt-1 text-3xl font-semibold">{{ $totalSchools }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-teal-900/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <flux:heading>Total Students</flux:heading>
                    <span class="rounded-lg bg-teal-50 px-2.5 py-1 text-xs font-medium text-teal-700 dark:bg-teal-400/10 dark:text-teal-200">Active</span>
                </div>
                <p class="mt-4 text-4xl font-semibold text-zinc-950 dark:text-white">{{ $totalStudents }}</p>
                <flux:text class="mt-2">Student records in your authorized scope</flux:text>
            </div>
            <div class="rounded-2xl border border-blue-900/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <flux:heading>Classrooms</flux:heading>
                    <span class="rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 dark:bg-blue-400/10 dark:text-blue-200">Groups</span>
                </div>
                <p class="mt-4 text-4xl font-semibold text-zinc-950 dark:text-white">{{ $totalClassrooms }}</p>
                <flux:text class="mt-2">Organized by class and level</flux:text>
            </div>
            <div class="rounded-2xl border border-amber-900/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <flux:heading>Departments</flux:heading>
                    <span class="rounded-lg bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 dark:bg-amber-400/10 dark:text-amber-200">Admin</span>
                </div>
                <p class="mt-4 text-4xl font-semibold text-zinc-950 dark:text-white">{{ $totalDepartments }}</p>
                <flux:text class="mt-2">Department structure and tracking</flux:text>
            </div>
            <div class="rounded-2xl border border-rose-900/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <flux:heading>Schools</flux:heading>
                    <span class="rounded-lg bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700 dark:bg-rose-400/10 dark:text-rose-200">Network</span>
                </div>
                <p class="mt-4 text-4xl font-semibold text-zinc-950 dark:text-white">{{ $totalSchools }}</p>
                <flux:text class="mt-2">School information and campuses</flux:text>
            </div>
        </div>

        @if($canViewAttendanceReporting)
            <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
                    <div>
                        <flux:heading size="lg">Attendance Summary</flux:heading>
                        <flux:text class="mt-1">Counts come from recorded daily attendance in the selected academic context.</flux:text>
                    </div>
                    <form method="GET" action="{{ route('dashboard') }}" class="grid gap-3 sm:grid-cols-[minmax(12rem,1fr)_minmax(12rem,1fr)_auto] sm:items-end">
                        <label class="grid gap-1 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            Academic Year
                            <select name="attendance_academic_year_id" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                                @foreach($attendanceAcademicYears as $academicYear)
                                    <option value="{{ $academicYear->id }}" @selected($selectedAttendanceYear?->is($academicYear))>{{ $academicYear->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="grid gap-1 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            Classroom
                            <select name="attendance_classroom_id" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                                <option value="">All authorized classrooms</option>
                                @foreach($attendanceClassrooms as $classroom)
                                    <option value="{{ $classroom->id }}" @selected($selectedAttendanceClassroom?->is($classroom))>{{ $classroom->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <flux:button type="submit" variant="primary">Apply</flux:button>
                    </form>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    @foreach ([
                        'present' => 'Present',
                        'absent' => 'Absent',
                        'late' => 'Late',
                        'excused' => 'Excused',
                        'recorded' => 'Recorded Days',
                    ] as $key => $label)
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50/70 p-4 dark:border-zinc-700 dark:bg-zinc-800/60" data-attendance-metric="{{ $key }}">
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $label }}</p>
                            <p class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ $attendanceSummary[$key] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900">
                <div class="border-b border-zinc-100 p-5 dark:border-white/10">
                    <flux:heading>Students by Classroom</flux:heading>
                    <flux:text class="mt-1">Distribution across active classes.</flux:text>
                </div>
                <div class="p-4">
                    <x-students-by-classroom-chart />
                </div>
            </div>
            @if($canViewAttendanceReporting)
                <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900">
                    <div class="border-b border-zinc-100 p-5 dark:border-white/10">
                        <flux:heading>Recorded Absences by Classroom</flux:heading>
                        <flux:text class="mt-1">Daily absent records for the selected academic context.</flux:text>
                    </div>
                    <div class="p-4">
                        <x-absences-chart :academic-year="$selectedAttendanceYear" :classroom="$selectedAttendanceClassroom" />
                    </div>
                </div>
            @endif
        </div>

        <section class="grid gap-4 overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900 lg:grid-cols-[.9fr_1.1fr]">
            <img src="{{ asset('images/school-building.svg') }}" alt="School building" class="h-full min-h-80 w-full object-cover">
            <div class="p-6 lg:p-8">
                <x-article />
            </div>
        </section>

        <div class="flex items-end justify-between gap-4">
            <div>
                <flux:heading size="lg">Student Grade Charts</flux:heading>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Semester averages help spot progress and support needs early.</p>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ([1, 2] as $position)
                <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900">
                    <div class="border-b border-zinc-100 p-4 dark:border-white/10">
                        <flux:heading>Semester {{ $position }} Average</flux:heading>
                    </div>
                    <div class="p-3">
                        <x-semester-average-chart :position="$position" :chart="$semesterAverageCharts[$position] ?? []" />
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.app>
