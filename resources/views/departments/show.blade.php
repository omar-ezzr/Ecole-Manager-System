<x-layouts.app>
    <div class="p-6">
        <flux:heading level="3" size="xl">Department: {{ $department->name }}</flux:heading>
        <flux:text class="mt-2">Address: {{ $department->address }}</flux:text>
        <flux:text class="mt-2">School: {{ $department->school_id }}</flux:text>
    </div>
</x-layouts.app>
