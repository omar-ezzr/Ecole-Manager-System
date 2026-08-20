<x-layouts.app>
    <div class="mx-auto max-w-3xl p-6">
        <flux:heading size="xl">Edit subject</flux:heading>
        <form method="POST" action="{{ route('subjects.update', $subject) }}" class="mt-6 space-y-4">
            @csrf
            @method('PUT')
            <flux:input label="Code" name="code" value="{{ old('code', $subject->code) }}" />
            <flux:input label="Name" name="name" value="{{ old('name', $subject->name) }}" />
            <flux:textarea label="Description" name="description">{{ old('description', $subject->description) }}</flux:textarea>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $subject->is_active) ? 'checked' : '' }}>
                <span>Active</span>
            </label>
            <flux:button type="submit" variant="primary">Update subject</flux:button>
        </form>
    </div>
</x-layouts.app>
