<x-layouts.app>
    <div class="mx-auto w-full max-w-6xl space-y-6 p-4 sm:p-6 lg:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <flux:heading size="xl">Academic Years</flux:heading>
                <flux:text class="mt-1">Configured academic periods and active year selection.</flux:text>
            </div>
            @can('academic_years.manage')
                <flux:button href="{{ route('academic-years.create') }}" variant="primary" icon="plus">Add Academic Year</flux:button>
            @endcan
        </div>
        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-zinc-50 text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
                        <tr><th scope="col" class="px-5 py-3">Name</th><th scope="col" class="px-5 py-3">Start Date</th><th scope="col" class="px-5 py-3">End Date</th><th scope="col" class="px-5 py-3">Status</th><th scope="col" class="px-5 py-3 text-right">Actions</th></tr>
                    </thead>
                    <tbody class="divide-y dark:divide-zinc-700">
                        @forelse($academicYears as $academicYear)
                            <tr>
                                <td class="px-5 py-4"><a class="font-medium text-teal-700 hover:underline dark:text-teal-300" href="{{ route('academic-years.show', $academicYear) }}">{{ $academicYear->name }}</a></td>
                                <td class="px-5 py-4">{{ $academicYear->starts_at->toDateString() }}</td>
                                <td class="px-5 py-4">{{ $academicYear->ends_at->toDateString() }}</td>
                                <td class="px-5 py-4"><flux:badge color="{{ $academicYear->is_active ? 'emerald' : 'zinc' }}">{{ $academicYear->is_active ? 'Active' : 'Inactive' }}</flux:badge></td>
                                <td class="px-5 py-4 text-right">
                                    @can('update', $academicYear)
                                        <a class="mr-3 font-medium text-teal-700 hover:underline dark:text-teal-300" href="{{ route('academic-years.edit', $academicYear) }}">Edit</a>
                                        @unless($academicYear->is_active)
                                            <form class="mr-3 inline" method="POST" action="{{ route('academic-years.activate', $academicYear) }}">
                                                @csrf
                                                <button type="submit" class="font-medium text-indigo-700 hover:underline dark:text-indigo-300">Activate</button>
                                            </form>
                                        @endunless
                                    @endcan
                                    @can('delete', $academicYear)
                                        <form class="inline" method="POST" action="{{ route('academic-years.destroy', $academicYear) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-medium text-rose-700 hover:underline dark:text-rose-300" onclick="return confirm('Delete this academic year?')">Delete</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-10 text-center text-zinc-500">No academic years found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($academicYears->hasPages())
                <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">{{ $academicYears->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.app>
