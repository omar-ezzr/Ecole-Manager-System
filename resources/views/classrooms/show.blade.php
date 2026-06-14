<x-layouts.app>
    <div class="p-6">
        <flux:heading level="3" size="xl">Classroom: {{ $classroom->name }}</flux:heading>
        <flux:text class="mt-2">Address: {{ $classroom->address }}</flux:text>
        <flux:text class="mt-2">Department: {{ $classroom->department_id }}</flux:text>
    </div>
</x-layouts.app>
