<x-layouts.app>
    <div class="mx-auto max-w-4xl p-6">
        <flux:heading size="xl">Edit teaching assignment</flux:heading>
        <form method="POST" action="{{ route('teaching-assignments.update', $assignment) }}" class="mt-6 grid gap-4 md:grid-cols-2">
            @csrf
            @method('PUT')
            <label class="block text-sm">Professor
                <select name="professor_id" class="mt-2 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                    @foreach($professors as $professor)
                        <option value="{{ $professor->id }}" @selected($assignment->professor_id == $professor->id)>{{ $professor->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">Classroom
                <select name="classroom_id" class="mt-2 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                    @foreach($classrooms as $classroom)
                        <option value="{{ $classroom->id }}" @selected($assignment->classroom_id == $classroom->id)>{{ $classroom->name }} - {{ $classroom->department->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">Subject
                <select name="subject_id" class="mt-2 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}" @selected($assignment->subject_id == $subject->id)>{{ $subject->code }} - {{ $subject->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">Academic Year
                <select name="academic_year_id" class="mt-2 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                    @foreach($academicYears as $academicYear)
                        <option value="{{ $academicYear->id }}" @selected($assignment->academic_year_id == $academicYear->id)>{{ $academicYear->name }}</option>
                    @endforeach
                </select>
            </label>
            <div class="md:col-span-2">
                <flux:button type="submit" variant="primary">Update assignment</flux:button>
            </div>
        </form>
    </div>
</x-layouts.app>
