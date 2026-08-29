<x-layouts.app>
    <div class="mx-auto w-full max-w-3xl space-y-6 p-4 sm:p-6 lg:p-8">
        <div>
            <flux:heading level="1" size="xl">Edit Semester</flux:heading>
            <flux:text class="mt-1">Update {{ $semester->name }} and keep its dates within the academic year.</flux:text>
        </div>
        <form method="POST" action="{{ route('semesters.update', $semester) }}" class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            @csrf
            @method('PUT')
            <div class="space-y-5 p-5 sm:p-6">
                <flux:select name="academic_year_id" label="Academic Year" required>
                    @foreach($academicYears as $academicYear)
                        <option value="{{ $academicYear->id }}" @selected(old('academic_year_id', $semester->academic_year_id) == $academicYear->id)>{{ $academicYear->name }}</option>
                    @endforeach
                </flux:select>
                <flux:input label="Name" name="name" value="{{ old('name', $semester->name) }}" required />
                <div class="grid gap-5 sm:grid-cols-2">
                    <flux:input type="date" label="Start Date" name="starts_at" value="{{ old('starts_at', $semester->starts_at->toDateString()) }}" required />
                    <flux:input type="date" label="End Date" name="ends_at" value="{{ old('ends_at', $semester->ends_at->toDateString()) }}" required />
                </div>
                <flux:input type="number" min="1" label="Sequence" name="sequence" value="{{ old('sequence', $semester->sequence) }}" description="Display and calculation order within the academic year." required />
            </div>
            <div class="flex justify-end gap-3 border-t border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                <flux:button href="{{ route('semesters.index') }}" variant="ghost">Cancel</flux:button>
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </div>
</x-layouts.app>
