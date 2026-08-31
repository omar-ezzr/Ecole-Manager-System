<x-layouts.app>
    <div class="mx-auto w-full max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <flux:heading level="1" size="xl">{{ $academicYear->name }}</flux:heading>
                    <flux:badge color="{{ $academicYear->is_active ? 'emerald' : 'zinc' }}">{{ $academicYear->is_active ? 'Active' : 'Inactive' }}</flux:badge>
                </div>
                <flux:text class="mt-1">{{ $academicYear->starts_at->toDateString() }} to {{ $academicYear->ends_at->toDateString() }}</flux:text>
            </div>
            <div class="flex flex-wrap gap-2">
                <flux:button href="{{ route('student-grades.index', ['academic_year_id' => $academicYear->id]) }}" icon="chart-bar-square">View Results</flux:button>
                @can('update', $academicYear)
                    <flux:button href="{{ route('academic-years.edit', $academicYear) }}" icon="pencil-square" variant="primary">Edit</flux:button>
                    @unless($academicYear->is_active)
                        <form method="POST" action="{{ route('academic-years.activate', $academicYear) }}">
                            @csrf
                            <flux:button type="submit" icon="check-circle">Activate</flux:button>
                        </form>
                    @endunless
                @endcan
                <flux:button href="{{ route('academic-years.index') }}" variant="ghost" icon="arrow-left">Back</flux:button>
            </div>
        </div>

        <section class="grid gap-4 md:grid-cols-2">
            @foreach($academicYear->semesters->sortBy('sequence')->take(2) as $semester)
                <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-center justify-between gap-3">
                        <flux:heading size="lg">{{ $semester->name }}</flux:heading>
                        <flux:badge color="teal">S{{ $semester->sequence }}</flux:badge>
                    </div>
                    <div class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ $semester->starts_at->format('M Y') }} - {{ $semester->ends_at->format('M Y') }}</div>
                </div>
            @endforeach
        </section>

        <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 p-5 dark:border-zinc-700">
                <flux:heading size="lg">Classrooms</flux:heading>
                <flux:text class="mt-1">Active classrooms and historical classrooms connected to {{ $academicYear->name }}.</flux:text>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-left text-sm">
                    <thead class="bg-zinc-50 text-zinc-500 dark:bg-zinc-800">
                        <tr><th class="px-5 py-3">Classroom</th><th class="px-5 py-3">Department</th><th class="px-5 py-3">Students</th><th class="px-5 py-3">Subjects</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Actions</th></tr>
                    </thead>
                    <tbody class="divide-y dark:divide-zinc-700">
                        @forelse($classrooms as $classroom)
                            <tr>
                                <td class="px-5 py-4 font-medium">{{ $classroom->name }}</td>
                                <td class="px-5 py-4">{{ $classroom->department?->name ?? '—' }}</td>
                                <td class="px-5 py-4">{{ $classroom->enrolled_students_count }}</td>
                                <td class="px-5 py-4">{{ $classroom->assigned_subjects_count }}</td>
                                <td class="px-5 py-4"><flux:badge color="{{ $classroom->is_active ? 'emerald' : 'zinc' }}">{{ $classroom->is_active ? 'Active' : 'Archived' }}</flux:badge></td>
                                <td class="px-5 py-4 text-right">
                                    <a class="mr-3 font-medium text-teal-700 hover:underline dark:text-teal-300" href="{{ route('student-grades.index', ['academic_year_id' => $academicYear->id, 'classroom_id' => $classroom->id]) }}">View Results</a>
                                    <a class="font-medium text-teal-700 hover:underline dark:text-teal-300" href="{{ route('classrooms.show', ['classroom' => $classroom, 'academic_year_id' => $academicYear->id]) }}">Manage Subjects</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-10 text-center text-zinc-500">No classrooms available.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts.app>
