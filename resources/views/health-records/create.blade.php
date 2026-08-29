<x-layouts.app>
    <div class="p-6">
        <flux:heading level="3" size="xl">Create Health Record <flux:badge icon="information-circle"></flux:badge></flux:heading>
        <flux:text class="mt-2">Use this page to create a student health record.</flux:text>
    </div>

    <flux:fieldset>
        <form action="{{ route('health-records.store') }}" method="POST">
            @csrf
            <div class="space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <flux:input label="Student ID" name="student_number" value="{{ old('student_number') }}" placeholder="Enter the student ID" />
                    <flux:input label="Date" name="date" type="date" value="{{ old('date') }}" max="2050-12-31" min="2020-09-10" placeholder="Enter the date" />
                    <flux:select label="Consultation category" name="type">
                        <option value="Ophtalmologie">Ophtalmologie</option>
                        <option value="Odontologie">Odontologie</option>
                        <option value="Dermatologie et Affections">Dermatologie et Affections</option>
                        <option value="Asthenie">Asthenie</option>
                        <option value="Fievre">Fievre</option>
                        <option value="Podologie">Podologie</option>
                    </flux:select>
                    <flux:textarea label="Medical Prescription" name="medical_prescription" value="{{ old('medical_prescription') }}" placeholder="Enter the medical prescription" />
                </div>

                <flux:button variant="primary" type="submit" class="w-full p-2">Add Health Record</flux:button>
            </div>
        </form>
    </flux:fieldset>

    <div class="p-6 mt-5">
        <flux:heading level="3" size="xl">Import Health Records with Excel <flux:badge icon="document"></flux:badge></flux:heading>
    </div>
    <form action="{{ route('excel.importHealthRecords') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <flux:input type="file" name="excel_file_health_records" wire:model="logo" label="Importer" accept=".xlsx,.xls,.csv" />
        <flux:button variant="filled" type="submit" class="w-full p-1 mt-2">Import</flux:button>
    </form>
    @if(session('import_errors') && count(session('import_errors')) > 0)
        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-100">
            <p class="font-semibold">Import warnings</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach(array_slice(session('import_errors'), 0, 20) as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
            @if(count(session('import_errors')) > 20)
                <p class="mt-2 text-xs">Showing first 20 of {{ count(session('import_errors')) }} warnings.</p>
            @endif
        </div>
    @endif
    <h1 class="text-2xl mt-5 mb-5 font-bold leading-tight md:text-2xl">Health records file <flux:text>Download the <flux:link href="{{ route('templates.health-records') }}">template</flux:link> before uploading your file.</flux:text></h1>
</x-layouts.app>
