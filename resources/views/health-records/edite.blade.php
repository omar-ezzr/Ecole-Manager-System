<x-layouts.app>
    <div class="p-6">
        <flux:heading level="3" size="xl">Edit Health Record <flux:badge icon="information-circle"></flux:badge></flux:heading>
        <flux:text class="mt-2">Use this page to update a student health record.</flux:text>
    </div>

    <flux:fieldset>
        <form action="{{ route('health-records.update', $healthRecord->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <flux:input label="Student ID" name="student_number" value="{{ $healthRecord->student?->student_number }}" placeholder="Enter the student ID" />
                    <flux:input label="Date" name="date" type="date" value="{{ $healthRecord->date }}" max="2050-12-31" min="2020-09-10" placeholder="Enter the date" />
                    <flux:select label="Consultation category" name="type">
                        <option value="{{ $healthRecord->type }}">{{ $healthRecord->type }}</option>
                        <option value="Ophtalmologie">Ophtalmologie</option>
                        <option value="Odontologie">Odontologie</option>
                        <option value="Dermatologie et Affections">Dermatologie et Affections</option>
                        <option value="Asthenie">Asthenie</option>
                        <option value="Fievre">Fievre</option>
                        <option value="Podologie">Podologie</option>
                    </flux:select>
                    <flux:textarea label="Medical Prescription" name="medical_prescription" value="{{ $healthRecord->medical_prescription }}" placeholder="Enter the medical prescription" />
                </div>

                <flux:button variant="primary" type="submit" class="w-full p-2">Save Changes</flux:button>
            </div>
        </form>
    </flux:fieldset>
</x-layouts.app>
