<x-layouts.app>
    <div class="mx-auto w-full max-w-6xl space-y-6 p-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <flux:heading size="xl">Academic Years</flux:heading>
                <flux:text class="mt-1">Configured academic periods and active year selection.</flux:text>
            </div>
            @can('academic_years.manage')
                <flux:button href="{{ route('academic-years.create') }}" variant="primary" icon="plus">Create academic year</flux:button>
            @endcan
        </div>
        @if(session('success'))
            <flux:callout variant="success">{{ session('success') }}</flux:callout>
        @endif
        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-zinc-50 text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
                        <tr><th class="px-5 py-3">Name</th><th class="px-5 py-3">Start Date</th><th class="px-5 py-3">End Date</th><th class="px-5 py-3">Active</th><th class="px-5 py-3 text-right">Actions</th></tr>
                    </thead>
                    <tbody class="divide-y dark:divide-zinc-700">
                        @foreach($academicYears as $academicYear)
                            <tr>
                                <td class="px-5 py-4"><a href="{{ route('academic-years.show', $academicYear) }}">{{ $academicYear->name }}</a></td>
                                <td class="px-5 py-4">{{ $academicYear->starts_at->toDateString() }}</td>
                                <td class="px-5 py-4">{{ $academicYear->ends_at->toDateString() }}</td>
                                <td class="px-5 py-4">{{ $academicYear->is_active ? 'Yes' : 'No' }}</td>
                                <td class="px-5 py-4 text-right">
                                    @can('update', $academicYear)
                                        <a class="mr-3 text-teal-600" href="{{ route('academic-years.edit', $academicYear) }}">Edit</a>
                                        @unless($academicYear->is_active)
                                            <form class="mr-3 inline" method="POST" action="{{ route('academic-years.activate', $academicYear) }}">
                                                @csrf
                                                <button class="text-indigo-600">Activate</button>
                                            </form>
                                        @endunless
                                    @endcan
                                    @can('delete', $academicYear)
                                        <form class="inline" method="POST" action="{{ route('academic-years.destroy', $academicYear) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-rose-600" onclick="return confirm('Delete this academic year?')">Delete</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $academicYears->links() }}</div>
        </div>
    </div>
</x-layouts.app>
