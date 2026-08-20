<x-layouts.app>
    <div class="mx-auto max-w-4xl space-y-6 p-6">
        <div>
            <flux:heading size="xl">{{ $subject->name }}</flux:heading>
            <flux:text class="mt-1">{{ $subject->code }}</flux:text>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="grid gap-4 md:grid-cols-2">
                <div><span class="text-sm text-zinc-500">Status</span><div class="mt-1">{{ $subject->is_active ? 'Active' : 'Inactive' }}</div></div>
                <div><span class="text-sm text-zinc-500">Description</span><div class="mt-1">{{ $subject->description ?: '—' }}</div></div>
            </div>
        </div>
    </div>
</x-layouts.app>
