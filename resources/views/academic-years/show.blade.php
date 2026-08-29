<x-layouts.app>
    <div class="mx-auto w-full max-w-5xl space-y-6 p-4 sm:p-6 lg:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <flux:heading level="1" size="xl">{{ $academicYear->name }}</flux:heading>
                <flux:text class="mt-1">{{ $academicYear->starts_at->toDateString() }} to {{ $academicYear->ends_at->toDateString() }}</flux:text>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('update', $academicYear)
                    <flux:button href="{{ route('academic-years.edit', $academicYear) }}" icon="pencil-square" variant="primary">Edit</flux:button>
                @endcan
                <flux:button href="{{ route('academic-years.index') }}" variant="ghost" icon="arrow-left">Back</flux:button>
            </div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">Semesters</flux:heading>
            <ul class="mt-4 space-y-2">
                @forelse($academicYear->semesters->sortBy('sequence') as $semester)
                    <li class="flex flex-col justify-between gap-1 rounded-lg bg-zinc-50 px-4 py-3 dark:bg-zinc-800 sm:flex-row sm:items-center">
                        <span class="font-medium">{{ $semester->name }}</span>
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $semester->starts_at->toDateString() }} to {{ $semester->ends_at->toDateString() }}</span>
                    </li>
                @empty
                    <li class="text-zinc-500">No semesters configured.</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-layouts.app>
