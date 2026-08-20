<x-layouts.app>
    <div class="mx-auto max-w-5xl space-y-6 p-6">
        <div>
            <flux:heading size="xl">{{ $academicYear->name }}</flux:heading>
            <flux:text class="mt-1">{{ $academicYear->starts_at->toDateString() }} to {{ $academicYear->ends_at->toDateString() }}</flux:text>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">Semesters</flux:heading>
            <ul class="mt-4 space-y-2">
                @forelse($academicYear->semesters->sortBy('sequence') as $semester)
                    <li>{{ $semester->name }} ({{ $semester->starts_at->toDateString() }} - {{ $semester->ends_at->toDateString() }})</li>
                @empty
                    <li class="text-zinc-500">No semesters configured.</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-layouts.app>
