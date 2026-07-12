<x-layouts.app>
    <div class="p-6">
        <flux:heading level="3" size="xl">School: {{ $school->name }}</flux:heading>
        <flux:text class="mt-2">Country: {{ $school->country }}</flux:text>
        <flux:text class="mt-2">Region: {{ $school->region }}</flux:text>
        <flux:text class="mt-2">City: {{ $school->city }}</flux:text>
        <flux:text class="mt-2">Address: {{ $school->address }}</flux:text>
    </div>
</x-layouts.app>
