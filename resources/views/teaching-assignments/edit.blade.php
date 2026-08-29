<x-layouts.app>
    <div class="mx-auto w-full max-w-4xl space-y-6 p-4 sm:p-6 lg:p-8">
        <div>
            <flux:heading level="1" size="xl">Edit Teaching Assignment</flux:heading>
            <flux:text class="mt-1">Update the assignment context while preserving authorized academic records.</flux:text>
        </div>
        <form method="POST" action="{{ route('teaching-assignments.update', $assignment) }}" class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            @csrf
            @method('PUT')
            <div class="grid gap-5 p-5 sm:p-6 md:grid-cols-2">
                <flux:select name="professor_id" label="Professor" required>
                    @foreach($professors as $professor)
                        <option value="{{ $professor->id }}" @selected(old('professor_id', $assignment->professor_id) == $professor->id)>{{ $professor->name }}</option>
                    @endforeach
                </flux:select>
                <flux:select name="classroom_id" label="Classroom" required>
                    @foreach($classrooms as $classroom)
                        <option value="{{ $classroom->id }}" @selected(old('classroom_id', $assignment->classroom_id) == $classroom->id)>{{ $classroom->name }} — {{ $classroom->department->name }}</option>
                    @endforeach
                </flux:select>
                <flux:select name="subject_id" label="Subject" required>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}" @selected(old('subject_id', $assignment->subject_id) == $subject->id)>{{ $subject->code }} — {{ $subject->name }}</option>
                    @endforeach
                </flux:select>
                <flux:select name="academic_year_id" label="Academic Year" required>
                    @foreach($academicYears as $academicYear)
                        <option value="{{ $academicYear->id }}" @selected(old('academic_year_id', $assignment->academic_year_id) == $academicYear->id)>{{ $academicYear->name }}</option>
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
