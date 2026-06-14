<x-layouts.app>
    @vite('resources/css/app.css')
    <div class="p-6">
        <flux:heading level="3" size="xl">Edit Department <flux:badge variant="solid" size="lg" color="zinc">{{ $department->name }}</flux:badge></flux:heading>
        <flux:text class="mt-2">Use this page to update department information.</flux:text>
    </div>

    <form action="{{ route('departments.update', $department->id) }}" method="POST">
        @csrf
        @method('PUT')
        <flux:fieldset>
            <div class="space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <flux:input label="Name" name="name" value="{{ $department->name }}" placeholder="Enter the department name" />
                    <flux:input label="Address" name="address" value="{{ $department->address }}" placeholder="Enter the address" />
                    <flux:input label="Number" name="id" type="number" value="{{ $department->id }}" placeholder="Enter the number" />
                    <flux:select label="School" name="school_id">
                        @foreach ($schools as $school)
                            <option value="{{ $school->id }}" @selected($school->id === $department->school_id)>{{ $school->name }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:button variant="primary" type="submit" class="w-full p-2">Save Changes</flux:button>
            </div>
        </flux:fieldset>
    </form>
</x-layouts.app>
