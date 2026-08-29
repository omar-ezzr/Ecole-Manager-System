<x-layouts.app>
    <div class="mx-auto w-full max-w-4xl space-y-6 p-4 sm:p-6 lg:p-8">
        <div>
            <flux:heading level="1" size="xl">Add Teaching Assignment</flux:heading>
            <flux:text class="mt-1">Connect a Professor, Classroom, Subject, and Academic Year.</flux:text>
        </div>
        <form method="POST" action="{{ route('teaching-assignments.store') }}" class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            @csrf
            <div class="grid gap-5 p-5 sm:p-6 md:grid-cols-2">
                <flux:select name="professor_id" label="Professor" required>
                    <option value="">Select a Professor</option>
                    @foreach($professors as $professor)
                        <option value="{{ $professor->id }}" @selected((string) old('professor_id') === (string) $professor->id)>{{ $professor->name }}</option>
                    @endforeach
                </flux:select>
                <flux:select name="classroom_id" label="Classroom" required>
                    <option value="">Select a Classroom</option>
                    @foreach($classrooms as $classroom)
                        <option value="{{ $classroom->id }}" @selected((string) old('classroom_id') === (string) $classroom->id)>{{ $classroom->name }} — {{ $classroom->department->name }}</option>
                    @endforeach
                </flux:select>
                <flux:select name="subject_id" label="Subject" required>
                    <option value="">Select a Subject</option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}" @selected((string) old('subject_id') === (string) $subject->id)>{{ $subject->code }} — {{ $subject->name }}</option>
                    @endforeach
                </flux:select>
                <flux:select name="academic_year_id" label="Academic Year" required>
                    <option value="">Select an Academic Year</option>
                    @foreach($academicYears as $academicYear)
                        <option value="{{ $academicYear->id }}" @selected((string) old('academic_year_id') === (string) $academicYear->id)>{{ $academicYear->name }}</option>
                    @endforeach
                </flux:select>
            </div>
            <div class="flex justify-end gap-3 border-t border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                <flux:button href="{{ route('teaching-assignments.index') }}" variant="ghost">Cancel</flux:button>
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </div>
</x-layouts.app>
