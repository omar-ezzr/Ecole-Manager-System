<x-layouts.app>
    @vite('resources/css/app.css')
    <div class="p-6">
        <flux:heading level="3" size="xl">Create School <flux:badge icon="document"></flux:badge></flux:heading>
        <flux:text class="mt-2">Use this page to create a new school.</flux:text>
    </div>

    <form action="{{ route('schools.store') }}" method="POST">
        @csrf
        <flux:fieldset>
            <div class="space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <flux:input label="Name" name="name" value="{{ old('name') }}" placeholder="Enter the school name" />
                    <flux:input label="Country" name="country" value="{{ old('country') }}" placeholder="Enter the country" />
                    <flux:input label="Region" name="region" value="{{ old('region') }}" placeholder="Enter the region" />
                    <flux:input label="City" name="city" value="{{ old('city') }}" placeholder="Enter the city" />
                    <flux:input label="Address" name="address" value="{{ old('address') }}" placeholder="Enter the address" />
                </div>

                <flux:button variant="primary" type="submit" class="w-full p-2">Add School</flux:button>
            </div>
        </flux:fieldset>
    </form>
</x-layouts.app>
