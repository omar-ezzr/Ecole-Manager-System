<x-layouts.app>
    <div class="p-6">
        <flux:heading level="3" size="xl">Health Record</flux:heading>
        <flux:text class="mt-2">Student: {{ $healthRecord->student?->last_name }} {{ $healthRecord->student?->first_name }}</flux:text>
        <flux:text class="mt-2">Date: {{ $healthRecord->date }}</flux:text>
        <flux:text class="mt-2">Consultation category: {{ $healthRecord->type }}</flux:text>
        <flux:text class="mt-2">Prescription: {{ $healthRecord->medical_prescription }}</flux:text>
    </div>
</x-layouts.app>
