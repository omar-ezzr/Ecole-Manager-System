<x-layouts.app>
    @vite('resources/css/app.css')
    <div class="p-6">
        <flux:heading level="3" size="xl">Edit School <flux:badge variant="solid" size="lg" color="zinc">{{ $school->name }}</flux:badge></flux:heading>
        <flux:text class="mt-2">Use this page to update school information.</flux:text>
    </div>

    <form action="{{ route('schools.update', $school->id) }}" method="POST">
        @csrf
        @method('PUT')
        <flux:fieldset>
            <div class="space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <flux:input label="Name" name="name" value="{{ $school->name }}" placeholder="Enter the school name" />
                    <flux:input label="Country" name="country" value="{{ $school->country }}" placeholder="Enter the country" />
                    <flux:input label="Region" name="region" value="{{ $school->region }}" placeholder="Enter the region" />
                    <flux:input label="City" name="city" value="{{ $school->city }}" placeholder="Enter the city" />
                    <flux:input label="Address" name="address" value="{{ $school->address }}" placeholder="Enter the address" />
                </div>

                <flux:button variant="primary" type="submit" class="w-full p-2">Save Changes</flux:button>
            </div>
        </flux:fieldset>
    </form>
</x-layouts.app>
