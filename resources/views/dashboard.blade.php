<x-layouts.app>
    <div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <section class="relative overflow-hidden rounded-2xl bg-zinc-950 text-white shadow-sm">
            <img src="{{ asset('images/school-dashboard-hero.png') }}" alt="Modern school administration office" class="absolute inset-0 h-full w-full object-cover opacity-70">
            <div class="absolute inset-0 bg-gradient-to-r from-zinc-950 via-zinc-950/75 to-zinc-950/20"></div>
            <div class="relative grid min-h-[320px] gap-8 p-6 sm:p-8 lg:grid-cols-[1.2fr_.8fr] lg:p-10">
                <div class="flex max-w-2xl flex-col justify-center">
                    <span class="mb-4 w-fit rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-medium text-teal-100">Academic year 2024-2025</span>
                    <h1 class="text-3xl font-semibold tracking-tight sm:text-5xl">School management, with every record in reach.</h1>
                    <p class="mt-4 max-w-xl text-sm leading-6 text-zinc-200 sm:text-base">Track students, classrooms, departments, health records, absences, and grades from one focused dashboard.</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <flux:button href="{{ route('students.index') }}" icon="user" variant="primary" wire:navigate>View students</flux:button>
                        @if(Auth::user()->email== env('EMAIL_AUTH'))
                            <flux:button href="{{ route('students.create') }}" icon="plus" variant="ghost" wire:navigate>Add student</flux:button>
                        @endif
                    </div>
                </div>
                <div class="hidden items-end justify-end lg:flex">
                    <div class="w-full max-w-sm rounded-2xl border border-white/15 bg-white/10 p-5 backdrop-blur">
                        <p class="text-sm font-medium text-teal-100">Today overview</p>
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
                <flux:text class="mt-2">All student records for 2024-2025</flux:text>
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
            <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900">
                <div class="border-b border-zinc-100 p-5 dark:border-white/10">
                    <flux:heading>Absence Days by Classroom</flux:heading>
                    <flux:text class="mt-1">Attendance risk visible by class.</flux:text>
                </div>
                <div class="p-4">
                    <x-absences-chart />
                </div>
            </div>
        </div>

        <section class="grid gap-4 overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900 lg:grid-cols-[.9fr_1.1fr]">
            <img src="{{ asset('images/school-building.svg') }}" alt="School building" class="h-full min-h-80 w-full object-cover">
            <div class="p-6 lg:p-8">
                <x-article />
            </div>
        </section>

        <div class="flex items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-zinc-950 dark:text-white">Student grade charts</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Semester averages help spot progress and support needs early.</p>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ([1, 2, 3, 4, 5, 6] as $position)
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
