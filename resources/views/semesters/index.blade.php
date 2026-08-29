<x-layouts.app>
    <div class="mx-auto w-full max-w-6xl space-y-6 p-4 sm:p-6 lg:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <flux:heading size="xl">Semesters</flux:heading>
                <flux:text class="mt-1">Semesters grouped under academic years.</flux:text>
            </div>
            @can('semesters.manage')
                <flux:button href="{{ route('semesters.create') }}" variant="primary" icon="plus">Add Semester</flux:button>
            @endcan
        </div>
        <form method="GET" action="{{ route('semesters.index') }}" class="flex max-w-xl flex-col gap-3 sm:flex-row sm:items-end">
            <flux:select name="academic_year_id" label="Academic Year" class="min-w-0 flex-1">
                <option value="">All academic years</option>
                @foreach($academicYears as $academicYear)
                    <option value="{{ $academicYear->id }}" @selected(request('academic_year_id') == $academicYear->id)>{{ $academicYear->name }}</option>
                @endforeach
            </flux:select>
            <div class="flex gap-2">
                <flux:button type="submit" variant="primary">Apply</flux:button>
                @if(request()->filled('academic_year_id'))
                    <flux:button href="{{ route('semesters.index') }}" variant="ghost">Clear</flux:button>
                @endif
            </div>
        </form>
        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-zinc-50 text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
                        <tr><th scope="col" class="px-5 py-3">Semester</th><th scope="col" class="px-5 py-3">Academic Year</th><th scope="col" class="px-5 py-3">Start Date</th><th scope="col" class="px-5 py-3">End Date</th><th scope="col" class="px-5 py-3">Sequence</th><th scope="col" class="px-5 py-3 text-right">Actions</th></tr>
                    </thead>
                    <tbody class="divide-y dark:divide-zinc-700">
                        @forelse($semesters as $semester)
                            <tr>
                                <td class="px-5 py-4"><a class="font-medium text-teal-700 hover:underline dark:text-teal-300" href="{{ route('semesters.show', $semester) }}">{{ $semester->name }}</a></td>
                                <td class="px-5 py-4">{{ $semester->academicYear->name }}</td>
                                <td class="px-5 py-4">{{ $semester->starts_at->toDateString() }}</td>
                                <td class="px-5 py-4">{{ $semester->ends_at->toDateString() }}</td>
                                <td class="px-5 py-4">{{ $semester->sequence }}</td>
                                <td class="px-5 py-4 text-right">
                                    @can('update', $semester)
                                        <a class="mr-3 font-medium text-teal-700 hover:underline dark:text-teal-300" href="{{ route('semesters.edit', $semester) }}">Edit</a>
                                    @endcan
                                    @can('delete', $semester)
                                        <form class="inline" method="POST" action="{{ route('semesters.destroy', $semester) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-medium text-rose-700 hover:underline dark:text-rose-300" onclick="return confirm('Delete this semester?')">Delete</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-10 text-center text-zinc-500">No semesters found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($semesters->hasPages())
                <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">{{ $semesters->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.app>
