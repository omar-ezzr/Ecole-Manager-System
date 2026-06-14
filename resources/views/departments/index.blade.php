<x-layouts.app>
    <div class="p-3">
        <flux:heading level="3" size="xl">Departments</flux:heading>
        <flux:text class="mt-2">Department records and school assignments.</flux:text>
    </div>

    <div class="relative overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-200">
            <thead class="text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-200">
                <tr>
                    <th class="px-6 py-3">Name</th>
                    <th class="px-6 py-3">Address</th>
                    <th class="px-6 py-3">School</th>
                    <th class="px-6 py-3">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($departments as $department)
                    <tr class="border-b dark:border-gray-700 border-gray-200">
                        <td class="px-6 py-4"><a href="{{ route('departments.show', $department->id) }}">{{ $department->name }}</a></td>
                        <td class="px-6 py-4">{{ $department->address }}</td>
                        <td class="px-6 py-4">{{ $department->school_id }}</td>
                        <td class="px-6 py-4"><a href="{{ route('departments.edit', $department->id) }}">Edit</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.app>
