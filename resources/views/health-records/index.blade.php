<x-layouts.app>
    <div class="relative overflow-hidden mb-5 border-neutral-200 dark:border-neutral-700">
        <flux:heading class="text-center" size="xl">Health Records</flux:heading>
    </div>

    <flux:modal.trigger name="health-statistics">
        <flux:button icon="chart-bar">View statistics</flux:button>
    </flux:modal.trigger>

    <flux:modal name="health-statistics" class="w-150">
        <div class="space-y-2">
            <x-health-record-statistics />
        </div>
    </flux:modal>

    <form method="GET" action="{{ route('health-records.index') }}">
        <div class="grid grid-cols-5 gap-2 m-3">
            <input type="text" name="last_name" placeholder="Last Name" value="{{ request('last_name') }}" class="form-input" />
            <input type="text" name="first_name" placeholder="First Name" value="{{ request('first_name') }}" class="form-input" />
            <input type="text" name="student_number" placeholder="Student ID" value="{{ request('student_number') }}" class="form-input" />
            <button type="submit" class="px-3 py-1 bg-gray-600 text-white rounded">Search</button>
        </div>
    </form>

    <div class="relative overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-200">
            <thead class="text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-200">
                <tr>
                    <th class="px-6 py-3">Last Name</th>
                    <th class="px-6 py-3">First Name</th>
                    <th class="px-6 py-3">Student ID</th>
                    <th class="px-6 py-3">Date</th>
                    <th class="px-6 py-3">Consultation category</th>
                    <th class="px-6 py-3">Medical Prescription</th>
                    <th class="px-6 py-3">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($healthRecords as $healthRecord)
                    <tr class="border-b dark:border-gray-700 border-gray-200">
                        <td class="px-6 py-4">{{ $healthRecord->student?->last_name }}</td>
                        <td class="px-6 py-4">{{ $healthRecord->student?->first_name }}</td>
                        <td class="px-6 py-4">{{ $healthRecord->student?->student_number }}</td>
                        <td class="px-6 py-4">{{ $healthRecord->date }}</td>
                        <td class="px-6 py-4">{{ $healthRecord->type }}</td>
                        <td class="px-6 py-4">{{ $healthRecord->medical_prescription }}</td>
                        <td class="px-6 py-4"><a href="{{ route('health-records.edit', $healthRecord->id) }}">Edit</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-2 pagination-wrapper opacity-50">
            {{ $healthRecords->links() }}
        </div>
    </div>
</x-layouts.app>
