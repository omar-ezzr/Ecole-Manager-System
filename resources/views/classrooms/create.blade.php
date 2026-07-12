<x-layouts.app>
    @vite('resources/css/app.css')
    <div class="p-6">
        <flux:heading level="3" size="xl">Create Classroom <flux:badge icon="document"></flux:badge></flux:heading>
        <flux:text class="mt-2">Use this page to create a new classroom.</flux:text>
    </div>

    <form action="{{ route('classrooms.store') }}" method="POST">
        @csrf
        <flux:fieldset>
            <div class="space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <flux:input label="Name" name="name" value="{{ old('name') }}" placeholder="Enter the classroom name" />
                    <flux:input label="Address" name="address" value="{{ old('address') }}" placeholder="Enter the classroom address" />
                    <flux:select label="Department" name="department_id">
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:button variant="primary" type="submit" class="w-full p-2">Add Classroom</flux:button>
            </div>
        </flux:fieldset>
    </form>
</x-layouts.app>
