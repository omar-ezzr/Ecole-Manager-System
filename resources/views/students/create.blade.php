<x-layouts.app>
    <div class="mx-auto w-full max-w-6xl space-y-8 p-4 sm:p-6 lg:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <flux:heading level="1" size="xl">Add Student</flux:heading>
                <flux:text class="mt-1">Create a student record and its current academic-year enrollment.</flux:text>
            </div>
            <flux:button href="{{ route('students.index') }}" variant="ghost" icon="arrow-left">Back to Students</flux:button>
        </div>

        <form action="{{ route('students.store') }}" method="POST" class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            @csrf

            <div class="space-y-8 p-5 sm:p-6">
                <section>
                    <flux:heading size="lg">Student Information</flux:heading>
                    <flux:text class="mt-1">Identity, contact details, and current classroom.</flux:text>

                    <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <flux:input label="Last Name" name="last_name" value="{{ old('last_name') }}" autocomplete="family-name" required />
                        <flux:input label="First Name" name="first_name" value="{{ old('first_name') }}" autocomplete="given-name" required />
                        <flux:input label="Student ID" name="student_number" value="{{ old('student_number') }}" required />
                        <flux:select label="Classroom" name="classroom_id" required>
                            <option value="">Select a classroom</option>
                            @foreach($classrooms as $classroom)
                                <option value="{{ $classroom->id }}" @selected((string) old('classroom_id') === (string) $classroom->id)>{{ $classroom->name }}</option>
                            @endforeach
                        </flux:select>
                        <flux:input label="Phone" type="tel" name="phone" value="{{ old('phone') }}" autocomplete="tel" required />
                        <flux:input label="Email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required />
                        <flux:input label="Diploma" name="diploma" value="{{ old('diploma') }}" required />
                        <flux:input label="City" name="city" value="{{ old('city') }}" autocomplete="address-level2" required />
                        <flux:input label="Address" name="address" value="{{ old('address') }}" autocomplete="street-address" required />
                        <flux:select label="Academic Level" name="education_level" required>
                            <option value="">Select a level</option>
                            @foreach(['Bac', 'Bac +2', 'Bac +3', 'Bac +4', 'Bac +5'] as $level)
                                <option value="{{ $level }}" @selected(old('education_level') === $level)>{{ $level }}</option>
                            @endforeach
                        </flux:select>
                        <flux:input label="Height (cm)" type="number" min="0" name="height" value="{{ old('height') }}" required />
                        <flux:input label="Weight (kg)" type="number" min="0" name="weight" value="{{ old('weight') }}" required />
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
                                value="{{ old('semester_'.$position) }}"
                                placeholder="Optional"
                            />
                        @endforeach
                    </div>

                    <div class="mt-5 grid gap-5 sm:grid-cols-2">
                        <flux:input label="Evaluation Score" type="number" step="0.01" name="appreciation_score" value="{{ old('appreciation_score') }}" placeholder="Optional" />
                        <flux:textarea label="Evaluation Comment" name="appreciation" placeholder="Optional">{{ old('appreciation') }}</flux:textarea>
                    </div>
                </section>
            </div>

            <div class="flex flex-wrap justify-end gap-3 border-t border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                <flux:button href="{{ route('students.index') }}" variant="ghost">Cancel</flux:button>
                <flux:button variant="primary" type="submit">Save</flux:button>
            </div>
        </form>

        @can('students.import')
            <section class="space-y-5">
                <div>
                    <flux:heading size="lg">Excel Imports</flux:heading>
                    <flux:text class="mt-1">Use the tracked templates so required IDs and fields are validated correctly.</flux:text>
                </div>

                @if(session('import_errors') && count(session('import_errors')) > 0)
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-100" role="alert">
                        <p class="font-semibold">Import warnings</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach(array_slice(session('import_errors'), 0, 20) as $warning)
                                <li>{{ $warning }}</li>
                            @endforeach
                        </ul>
                        @if(count(session('import_errors')) > 20)
                            <p class="mt-2 text-xs">Showing the first 20 of {{ count(session('import_errors')) }} warnings.</p>
                        @endif
                    </div>
                @endif

                <div class="grid gap-4 lg:grid-cols-2">
                    <form action="{{ route('excel.import') }}" method="POST" enctype="multipart/form-data" class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                        @csrf
                        <flux:heading>Import Students</flux:heading>
                        <flux:text class="mt-1">Download the <flux:link href="{{ route('templates.students') }}">student template</flux:link>, then upload the completed workbook.</flux:text>
                        <div class="mt-4 space-y-3">
                            <flux:input type="file" name="excel_file" label="Student workbook" accept=".xlsx,.xls,.csv" required />
                            <flux:button variant="primary" type="submit">Import Students</flux:button>
                        </div>
                    </form>

                    <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <flux:heading>Import Guidance</flux:heading>
                        <ul class="mt-3 list-disc space-y-2 pl-5 text-sm text-zinc-600 dark:text-zinc-300">
                            <li>Use strict numeric Classroom IDs from the application.</li>
                            <li>Keep all required template headers unchanged.</li>
                            <li>An active academic year is required for new student enrollments.</li>
                        </ul>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach([
                        ['route' => 'excel.importSemester1', 'field' => 'excel_file_semester_1', 'title' => 'Semester 1', 'template' => 'templates.semester-1'],
                        ['route' => 'excel.importSemester2', 'field' => 'excel_file_semester_2', 'title' => 'Semester 2', 'template' => 'templates.semester-2'],
                    ] as $import)
                        <form action="{{ route($import['route']) }}" method="POST" enctype="multipart/form-data" class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                            @csrf
                            <flux:heading>{{ $import['title'] }}</flux:heading>
                            <flux:text class="mt-1">Use the <flux:link href="{{ route($import['template']) }}">matching template</flux:link>.</flux:text>
                            <div class="mt-4 space-y-3">
                                <flux:input type="file" name="{{ $import['field'] }}" label="{{ $import['title'] }} workbook" accept=".xlsx,.xls,.csv" required />
                                <flux:button type="submit">Import</flux:button>
                            </div>
                        </form>
                    @endforeach
                </div>
            </section>
        @endcan
    </div>
</x-layouts.app>
