<x-layouts.app>
    <div class="mx-auto w-full max-w-3xl space-y-6 p-4 sm:p-6 lg:p-8">
        <div>
            <flux:heading level="1" size="xl">Add Academic Year</flux:heading>
            <flux:text class="mt-1">Define the dates and choose whether this is the active academic year.</flux:text>
        </div>
        <form method="POST" action="{{ route('academic-years.store') }}" class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            @csrf
            <div class="space-y-5 p-5 sm:p-6">
                <flux:input label="Name" name="name" value="{{ old('name') }}" placeholder="Example: 2026-2027" required />
                <div class="grid gap-5 sm:grid-cols-2">
                    <flux:input type="date" label="Start Date" name="starts_at" value="{{ old('starts_at') }}" required />
                    <flux:input type="date" label="End Date" name="ends_at" value="{{ old('ends_at') }}" required />
                </div>
                <flux:checkbox name="is_active" value="1" label="Set as active academic year" @checked(old('is_active')) />
            </div>
            <div class="flex justify-end gap-3 border-t border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                <flux:button href="{{ route('academic-years.index') }}" variant="ghost">Cancel</flux:button>
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </div>
</x-layouts.app>
