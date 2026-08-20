<x-layouts.app>
    <div class="mx-auto w-full max-w-6xl space-y-6 p-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <flux:heading size="xl">Subjects</flux:heading>
                <flux:text class="mt-1">Academic subjects and availability.</flux:text>
            </div>
            @can('subjects.manage')
                <flux:button href="{{ route('subjects.create') }}" variant="primary" icon="plus">Create subject</flux:button>
            @endcan
        </div>

        @if(session('success'))
            <flux:callout variant="success">{{ session('success') }}</flux:callout>
        @endif

        <form method="GET">
            <flux:input name="search" value="{{ request('search') }}" placeholder="Search by code or name" icon="magnifying-glass" />
        </form>

        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-zinc-50 text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
                        <tr>
                            <th class="px-5 py-3">Code</th>
                            <th class="px-5 py-3">Name</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-zinc-700">
                        @forelse($subjects as $subject)
                            <tr>
                                <td class="px-5 py-4 font-medium">{{ $subject->code }}</td>
                                <td class="px-5 py-4"><a href="{{ route('subjects.show', $subject) }}">{{ $subject->name }}</a></td>
                                <td class="px-5 py-4">
                                    <flux:badge color="{{ $subject->is_active ? 'emerald' : 'zinc' }}">{{ $subject->is_active ? 'Active' : 'Inactive' }}</flux:badge>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    @can('update', $subject)
                                        <a class="mr-3 text-teal-600" href="{{ route('subjects.edit', $subject) }}">Edit</a>
                                    @endcan
                                    @can('delete', $subject)
                                        <form class="inline" method="POST" action="{{ route('subjects.destroy', $subject) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-rose-600" onclick="return confirm('Delete this subject?')">Delete</button>
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
            <div class="p-4">{{ $subjects->links() }}</div>
        </div>
    </div>
</x-layouts.app>
