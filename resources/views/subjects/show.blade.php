<x-layouts.app>
    <div class="mx-auto w-full max-w-4xl space-y-6 p-4 sm:p-6 lg:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <flux:heading level="1" size="xl">{{ $subject->name }}</flux:heading>
                <flux:text class="mt-1">{{ $subject->code }}</flux:text>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('update', $subject)
                    <flux:button href="{{ route('subjects.edit', $subject) }}" icon="pencil-square" variant="primary">Edit</flux:button>
                @endcan
                <flux:button href="{{ route('subjects.index') }}" variant="ghost" icon="arrow-left">Back</flux:button>
            </div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="grid gap-4 md:grid-cols-2">
                <div><span class="text-sm text-zinc-500">Status</span><div class="mt-1"><flux:badge color="{{ $subject->is_active ? 'emerald' : 'zinc' }}">{{ $subject->is_active ? 'Active' : 'Inactive' }}</flux:badge></div></div>
                <div><span class="text-sm text-zinc-500">Description</span><div class="mt-1">{{ $subject->description ?: '—' }}</div></div>
            </div>
        </div>
    </div>
</x-layouts.app>
