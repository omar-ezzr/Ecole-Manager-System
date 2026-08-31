<x-layouts.app>
    @php($semesterGrades = $student->semesterAverages->mapWithKeys(fn ($grade) => ['semester_'.$grade->semester?->sequence => $grade->grade]))

    <div class="mx-auto w-full max-w-6xl space-y-6 p-4 sm:p-6 lg:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <flux:heading level="1" size="xl">Edit Student</flux:heading>
                <flux:text class="mt-1">{{ $student->last_name }} {{ $student->first_name }} · {{ $student->student_number }}</flux:text>
            </div>
            <flux:button href="{{ route('students.show', $student) }}" variant="ghost" icon="arrow-left">Back to Student</flux:button>
        </div>

        <form action="{{ route('students.update', $student) }}" method="POST" class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            @csrf
            @method('PUT')

            <div class="space-y-8 p-5 sm:p-6">
                <section>
                    <flux:heading size="lg">Student Information</flux:heading>
                    <flux:text class="mt-1">Changing the classroom records a historical enrollment transfer in the active academic year.</flux:text>

                    <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <flux:input label="Last Name" name="last_name" value="{{ old('last_name', $student->last_name) }}" autocomplete="family-name" required />
                        <flux:input label="First Name" name="first_name" value="{{ old('first_name', $student->first_name) }}" autocomplete="given-name" required />
                        <flux:input label="Student ID" name="student_number" value="{{ old('student_number', $student->student_number) }}" required />
                        <flux:select label="Classroom" name="classroom_id" required>
                            <option value="">Select a classroom</option>
                            @foreach($classrooms as $classroom)
                                <option value="{{ $classroom->id }}" @selected((string) old('classroom_id', $student->classroom_id) === (string) $classroom->id)>{{ $classroom->name }}</option>
                            @endforeach
                        </flux:select>
                        <flux:input label="Phone" type="tel" name="phone" value="{{ old('phone', $student->phone) }}" autocomplete="tel" required />
                        <flux:input label="Email" type="email" name="email" value="{{ old('email', $student->email) }}" autocomplete="email" required />
                        <flux:input label="Diploma" name="diploma" value="{{ old('diploma', $student->diploma) }}" required />
                        <flux:input label="City" name="city" value="{{ old('city', $student->city) }}" autocomplete="address-level2" required />
                        <flux:input label="Address" name="address" value="{{ old('address', $student->address) }}" autocomplete="street-address" required />
                        <flux:select label="Academic Level" name="education_level" required>
                            <option value="">Select a level</option>
                            @foreach(['Bac', 'Bac +2', 'Bac +3', 'Bac +4', 'Bac +5'] as $level)
                                <option value="{{ $level }}" @selected(old('education_level', $student->education_level) === $level)>{{ $level }}</option>
                            @endforeach
                        </flux:select>
                        <flux:input label="Height (cm)" type="number" min="0" name="height" value="{{ old('height', $student->height) }}" required />
                        <flux:input label="Weight (kg)" type="number" min="0" name="weight" value="{{ old('weight', $student->weight) }}" required />
                    </div>
                </section>

                <section class="border-t border-zinc-200 pt-6 dark:border-zinc-700">
                    <flux:heading size="lg">Academic Information</flux:heading>
                    <flux:text class="mt-1">Optional semester averages use the validated 0–20 grade scale.</flux:text>

                    <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($semesters as $semester)
                            @php($position = $semester->sequence)
                            <flux:input
                                label="Semester {{ $position }}"
                                type="number"
                                min="0"
                                max="20"
                                step="0.01"
                                name="semester_{{ $position }}"
                                value="{{ old('semester_'.$position, $semesterGrades['semester_'.$position] ?? '') }}"
                                placeholder="Optional"
                            />
                        @endforeach
                    </div>

                    <div class="mt-5 grid gap-5 sm:grid-cols-2">
                        <flux:input label="Evaluation Score" type="number" step="0.01" name="appreciation_score" value="{{ old('appreciation_score', $student->appreciation_score) }}" placeholder="Optional" />
                        <flux:textarea label="Evaluation Comment" name="appreciation" placeholder="Optional">{{ old('appreciation', $student->appreciation) }}</flux:textarea>
                    </div>
                </section>
            </div>

            <div class="flex flex-wrap justify-end gap-3 border-t border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                <flux:button href="{{ route('students.show', $student) }}" variant="ghost">Cancel</flux:button>
                <flux:button variant="primary" type="submit">Save</flux:button>
            </div>
        </form>
    </div>
</x-layouts.app>
