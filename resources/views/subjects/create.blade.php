<x-layouts.app>
    <div class="mx-auto max-w-3xl p-6">
        <flux:heading size="xl">Create subject</flux:heading>
        <form method="POST" action="{{ route('subjects.store') }}" class="mt-6 space-y-4">
            @csrf
            <flux:input label="Code" name="code" value="{{ old('code') }}" />
            <flux:input label="Name" name="name" value="{{ old('name') }}" />
            <flux:textarea label="Description" name="description">{{ old('description') }}</flux:textarea>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                <span>Active</span>
            </label>
            <flux:button type="submit" variant="primary">Save subject</flux:button>
        </form>
    </div>
</x-layouts.app>
