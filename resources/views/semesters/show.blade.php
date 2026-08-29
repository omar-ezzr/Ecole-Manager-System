<x-layouts.app>
    <div class="mx-auto w-full max-w-4xl space-y-6 p-4 sm:p-6 lg:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <flux:heading level="1" size="xl">{{ $semester->name }}</flux:heading>
                <flux:text class="mt-1">{{ $semester->academicYear->name }}</flux:text>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('update', $semester)
                    <flux:button href="{{ route('semesters.edit', $semester) }}" icon="pencil-square" variant="primary">Edit</flux:button>
                @endcan
                <flux:button href="{{ route('semesters.index') }}" variant="ghost" icon="arrow-left">Back</flux:button>
            </div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="grid gap-4 md:grid-cols-2">
                <div><span class="text-sm text-zinc-500">Start</span><div class="mt-1">{{ $semester->starts_at->toDateString() }}</div></div>
                <div><span class="text-sm text-zinc-500">End</span><div class="mt-1">{{ $semester->ends_at->toDateString() }}</div></div>
                <div><span class="text-sm text-zinc-500">Sequence</span><div class="mt-1">{{ $semester->sequence }}</div></div>
            </div>
        </div>
    </div>
</x-layouts.app>
