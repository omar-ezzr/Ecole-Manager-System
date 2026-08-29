<x-layouts.app>
    <div class="mx-auto w-full max-w-6xl space-y-6 p-4 sm:p-6 lg:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <flux:heading size="xl">Subjects</flux:heading>
                <flux:text class="mt-1">Academic subjects and availability.</flux:text>
            </div>
            @can('subjects.manage')
                <flux:button href="{{ route('subjects.create') }}" variant="primary" icon="plus">Add Subject</flux:button>
            @endcan
        </div>

        <form method="GET" action="{{ route('subjects.index') }}" class="flex max-w-xl flex-col gap-3 sm:flex-row sm:items-end">
            <flux:input name="search" label="Search Subjects" value="{{ request('search') }}" placeholder="Code or name" icon="magnifying-glass" class="min-w-0 flex-1" />
            <div class="flex gap-2">
                <flux:button type="submit" variant="primary">Search</flux:button>
                @if(request()->filled('search'))
                    <flux:button href="{{ route('subjects.index') }}" variant="ghost">Clear</flux:button>
                @endif
            </div>
        </form>

        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-zinc-50 text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
                        <tr>
                            <th scope="col" class="px-5 py-3">Code</th>
                            <th scope="col" class="px-5 py-3">Name</th>
                            <th scope="col" class="px-5 py-3">Status</th>
                            <th scope="col" class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-zinc-700">
                        @forelse($subjects as $subject)
                            <tr>
                                <td class="px-5 py-4 font-medium">{{ $subject->code }}</td>
                                <td class="px-5 py-4"><a class="font-medium text-teal-700 hover:underline dark:text-teal-300" href="{{ route('subjects.show', $subject) }}">{{ $subject->name }}</a></td>
                                <td class="px-5 py-4">
                                    <flux:badge color="{{ $subject->is_active ? 'emerald' : 'zinc' }}">{{ $subject->is_active ? 'Active' : 'Inactive' }}</flux:badge>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    @can('update', $subject)
                                        <a class="mr-3 font-medium text-teal-700 hover:underline dark:text-teal-300" href="{{ route('subjects.edit', $subject) }}">Edit</a>
                                    @endcan
                                    @can('delete', $subject)
                                        <form class="inline" method="POST" action="{{ route('subjects.destroy', $subject) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-medium text-rose-700 hover:underline dark:text-rose-300" onclick="return confirm('Delete this subject?')">Delete</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-10 text-center text-zinc-500">No subjects found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($subjects->hasPages())
                <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">{{ $subjects->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.app>
