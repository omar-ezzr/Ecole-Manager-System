<x-layouts.app>
    <div class="mx-auto w-full max-w-3xl space-y-6 p-4 sm:p-6 lg:p-8">
        <div>
            <flux:heading level="1" size="xl">Add Semester</flux:heading>
            <flux:text class="mt-1">Add a dated semester within an existing academic year.</flux:text>
        </div>
        <form method="POST" action="{{ route('semesters.store') }}" class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            @csrf
            <div class="space-y-5 p-5 sm:p-6">
                <flux:select name="academic_year_id" label="Academic Year" required>
                    <option value="">Select an academic year</option>
                    @foreach($academicYears as $academicYear)
                        <option value="{{ $academicYear->id }}" @selected((string) old('academic_year_id') === (string) $academicYear->id)>{{ $academicYear->name }}</option>
                    @endforeach
                </flux:select>
                <flux:input label="Name" name="name" value="{{ old('name') }}" required />
                <div class="grid gap-5 sm:grid-cols-2">
                    <flux:input type="date" label="Start Date" name="starts_at" value="{{ old('starts_at') }}" required />
                    <flux:input type="date" label="End Date" name="ends_at" value="{{ old('ends_at') }}" required />
                </div>
                <flux:input type="number" min="1" label="Sequence" name="sequence" value="{{ old('sequence') }}" description="Display and calculation order within the academic year." required />
            </div>
            <div class="flex justify-end gap-3 border-t border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                <flux:button href="{{ route('semesters.index') }}" variant="ghost">Cancel</flux:button>
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </div>
</x-layouts.app>
