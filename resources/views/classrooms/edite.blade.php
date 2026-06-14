<x-layouts.app>
    @vite('resources/css/app.css')
    <div class="p-6">
        <flux:heading level="3" size="xl">Edit Classroom <flux:badge variant="solid" size="lg" color="zinc">{{ $classroom->name }}</flux:badge></flux:heading>
        <flux:text class="mt-2">Use this page to update classroom information.</flux:text>
    </div>

    <form action="{{ route('classrooms.update', $classroom->id) }}" method="POST">
        @csrf
        @method('PUT')
        <flux:fieldset>
            <div class="space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <flux:input label="Name" name="name" value="{{ $classroom->name }}" placeholder="Enter the classroom name" />
                    <flux:input label="Address" name="address" value="{{ $classroom->address }}" placeholder="Enter the classroom address" />
                    <flux:input label="Number" name="id" type="number" value="{{ $classroom->id }}" placeholder="Enter the number" />
                    <flux:select label="Department" name="department_id">
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected($department->id === $classroom->department_id)>{{ $department->name }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:button variant="primary" type="submit" class="w-full p-2">Save Changes</flux:button>
            </div>
        </flux:fieldset>
    </form>
</x-layouts.app>
