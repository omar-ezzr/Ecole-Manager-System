<x-layouts.app>
    <div class="mx-auto max-w-3xl p-6">
        <flux:heading size="xl">Edit semester</flux:heading>
        <form method="POST" action="{{ route('semesters.update', $semester) }}" class="mt-6 space-y-4">
            @csrf
            @method('PUT')
            <label class="block text-sm">Academic Year
                <select name="academic_year_id" class="mt-2 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                    @foreach($academicYears as $academicYear)
                        <option value="{{ $academicYear->id }}" @selected(old('academic_year_id', $semester->academic_year_id) == $academicYear->id)>{{ $academicYear->name }}</option>
                    @endforeach
                </select>
            </label>
            <flux:input label="Name" name="name" value="{{ old('name', $semester->name) }}" />
            <flux:input type="date" label="Start Date" name="starts_at" value="{{ old('starts_at', $semester->starts_at->toDateString()) }}" />
            <flux:input type="date" label="End Date" name="ends_at" value="{{ old('ends_at', $semester->ends_at->toDateString()) }}" />
            <flux:input type="number" label="Sequence" name="sequence" value="{{ old('sequence', $semester->sequence) }}" />
            <flux:button type="submit" variant="primary">Update semester</flux:button>
        </form>
    </div>
</x-layouts.app>
