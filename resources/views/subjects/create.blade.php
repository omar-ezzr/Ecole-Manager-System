<x-layouts.app>
    <div class="mx-auto w-full max-w-3xl space-y-6 p-4 sm:p-6 lg:p-8">
        <div>
            <flux:heading level="1" size="xl">Add Subject</flux:heading>
            <flux:text class="mt-1">Create an academic subject for teaching assignments.</flux:text>
        </div>
        <form method="POST" action="{{ route('subjects.store') }}" class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            @csrf
            <div class="space-y-5 p-5 sm:p-6">
                <div class="grid gap-5 sm:grid-cols-2">
                    <flux:input label="Code" name="code" value="{{ old('code') }}" required />
                    <flux:input label="Name" name="name" value="{{ old('name') }}" required />
                </div>
                <flux:textarea label="Description" name="description" placeholder="Optional">{{ old('description') }}</flux:textarea>
                <flux:checkbox name="is_active" value="1" label="Active subject" @checked(old('is_active', true)) />
            </div>
            <div class="flex justify-end gap-3 border-t border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                <flux:button href="{{ route('subjects.index') }}" variant="ghost">Cancel</flux:button>
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </div>
</x-layouts.app>
