<x-layouts.app>
    <div class="mx-auto max-w-3xl p-6">
        <flux:heading size="xl">Edit academic year</flux:heading>
        <form method="POST" action="{{ route('academic-years.update', $academicYear) }}" class="mt-6 space-y-4">
            @csrf
            @method('PUT')
            <flux:input label="Name" name="name" value="{{ old('name', $academicYear->name) }}" />
            <flux:input type="date" label="Start Date" name="starts_at" value="{{ old('starts_at', $academicYear->starts_at->toDateString()) }}" />
            <flux:input type="date" label="End Date" name="ends_at" value="{{ old('ends_at', $academicYear->ends_at->toDateString()) }}" />
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $academicYear->is_active) ? 'checked' : '' }}><span>Active</span></label>
            <flux:button type="submit" variant="primary">Update academic year</flux:button>
        </form>
    </div>
</x-layouts.app>
