<x-layouts.app>
    <div class="mx-auto w-full max-w-6xl space-y-6 p-4 sm:p-6 lg:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <flux:heading level="1" size="xl">Classrooms</flux:heading>
                <flux:text class="mt-1">Active classroom records and department assignments.</flux:text>
            </div>
            @can('classrooms.manage')
                <flux:button href="{{ route('classrooms.create') }}" variant="primary" icon="plus">Add Classroom</flux:button>
            @endcan
        </div>
        @if(session('warning'))
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-100">{{ session('warning') }}</div>
        @endif
        @if(session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-400/20 dark:bg-emerald-400/10 dark:text-emerald-100">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 dark:border-rose-400/20 dark:bg-rose-400/10 dark:text-rose-100">{{ $errors->first() }}</div>
        @endif

        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-zinc-50 text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
                        <tr><th class="px-5 py-3">Name</th><th class="px-5 py-3">Address</th><th class="px-5 py-3">Department</th><th class="px-5 py-3 text-right">Actions</th></tr>
                    </thead>
                    <tbody class="divide-y dark:divide-zinc-700">
                        @forelse ($classrooms as $classroom)
                            <tr>
                                <td class="px-5 py-4"><a class="font-medium text-teal-700 hover:underline dark:text-teal-300" href="{{ route('classrooms.show', $classroom) }}">{{ $classroom->name }}</a></td>
                                <td class="px-5 py-4">{{ $classroom->address ?: '—' }}</td>
                                <td class="px-5 py-4">{{ $classroom->department?->name ?? '—' }}</td>
                                <td class="px-5 py-4 text-right">
                                    <a class="mr-3 font-medium text-teal-700 hover:underline dark:text-teal-300" href="{{ route('classrooms.show', $classroom) }}">View</a>
                                    @can('update', $classroom)
                                        <a class="mr-3 font-medium text-teal-700 hover:underline dark:text-teal-300" href="{{ route('classrooms.edit', $classroom) }}">Edit</a>
                                    @endcan
                                    @can('delete', $classroom)
                                        <form method="POST" action="{{ route('classrooms.destroy', $classroom) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-medium text-rose-700 hover:underline dark:text-rose-300" onclick="return confirm('Delete this classroom? Classrooms with academic history will be archived instead.')">Delete</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-10 text-center text-zinc-500">No active classrooms found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.app>
