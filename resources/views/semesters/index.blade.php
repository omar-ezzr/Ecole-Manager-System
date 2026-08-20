<x-layouts.app>
    <div class="mx-auto w-full max-w-6xl space-y-6 p-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <flux:heading size="xl">Semesters</flux:heading>
                <flux:text class="mt-1">Semesters grouped under academic years.</flux:text>
            </div>
            @can('semesters.manage')
                <flux:button href="{{ route('semesters.create') }}" variant="primary" icon="plus">Create semester</flux:button>
            @endcan
        </div>
        <form method="GET" class="max-w-sm">
            <label class="mb-2 block text-sm text-zinc-500">Academic Year</label>
            <select name="academic_year_id" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                <option value="">All academic years</option>
                @foreach($academicYears as $academicYear)
                    <option value="{{ $academicYear->id }}" @selected(request('academic_year_id') == $academicYear->id)>{{ $academicYear->name }}</option>
                @endforeach
            </select>
        </form>
        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-zinc-50 text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
                        <tr><th class="px-5 py-3">Semester Name</th><th class="px-5 py-3">Academic Year</th><th class="px-5 py-3">Start</th><th class="px-5 py-3">End</th><th class="px-5 py-3">Sequence</th><th class="px-5 py-3 text-right">Actions</th></tr>
                    </thead>
                    <tbody class="divide-y dark:divide-zinc-700">
                        @foreach($semesters as $semester)
                            <tr>
                                <td class="px-5 py-4"><a href="{{ route('semesters.show', $semester) }}">{{ $semester->name }}</a></td>
                                <td class="px-5 py-4">{{ $semester->academicYear->name }}</td>
                                <td class="px-5 py-4">{{ $semester->starts_at->toDateString() }}</td>
                                <td class="px-5 py-4">{{ $semester->ends_at->toDateString() }}</td>
                                <td class="px-5 py-4">{{ $semester->sequence }}</td>
                                <td class="px-5 py-4 text-right">
                                    @can('update', $semester)
                                        <a class="mr-3 text-teal-600" href="{{ route('semesters.edit', $semester) }}">Edit</a>
                                    @endcan
                                    @can('delete', $semester)
                                        <form class="inline" method="POST" action="{{ route('semesters.destroy', $semester) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-rose-600" onclick="return confirm('Delete this semester?')">Delete</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $semesters->links() }}</div>
        </div>
    </div>
</x-layouts.app>
