<x-layouts.app>
    <div class="p-3">
        <flux:heading level="3" size="xl">Schools</flux:heading>
        <flux:text class="mt-2">School records and locations.</flux:text>
    </div>

    <div class="relative overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-200">
            <thead class="text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-200">
                <tr>
                    <th class="px-6 py-3">Name</th>
                    <th class="px-6 py-3">Country</th>
                    <th class="px-6 py-3">Region</th>
                    <th class="px-6 py-3">City</th>
                    <th class="px-6 py-3">Address</th>
                    <th class="px-6 py-3">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($schools as $school)
                    <tr class="border-b dark:border-gray-700 border-gray-200">
                        <td class="px-6 py-4"><a href="{{ route('schools.show', $school->id) }}">{{ $school->name }}</a></td>
                        <td class="px-6 py-4">{{ $school->country }}</td>
                        <td class="px-6 py-4">{{ $school->region }}</td>
                        <td class="px-6 py-4">{{ $school->city }}</td>
                        <td class="px-6 py-4">{{ $school->address }}</td>
                        <td class="px-6 py-4">
                            @can('schools.manage')
                                <a href="{{ route('schools.edit', $school->id) }}">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.app>
