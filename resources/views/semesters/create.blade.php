<x-layouts.app>
    <div class="mx-auto max-w-3xl p-6">
        <flux:heading size="xl">Create semester</flux:heading>
        <form method="POST" action="{{ route('semesters.store') }}" class="mt-6 space-y-4">
            @csrf
            <label class="block text-sm">Academic Year
                <select name="academic_year_id" class="mt-2 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                    @foreach($academicYears as $academicYear)
                        <option value="{{ $academicYear->id }}">{{ $academicYear->name }}</option>
                    @endforeach
                </select>
            </label>
            <flux:input label="Name" name="name" value="{{ old('name') }}" />
            <flux:input type="date" label="Start Date" name="starts_at" value="{{ old('starts_at') }}" />
            <flux:input type="date" label="End Date" name="ends_at" value="{{ old('ends_at') }}" />
            <flux:input type="number" label="Sequence" name="sequence" value="{{ old('sequence') }}" />
            <flux:button type="submit" variant="primary">Save semester</flux:button>
        </form>
    </div>
</x-layouts.app>
