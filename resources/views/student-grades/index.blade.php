<x-layouts.app>
    <div class="mx-auto w-full max-w-6xl space-y-6 p-6">
        <div>
            <flux:heading size="xl">Results</flux:heading>
            <flux:text class="mt-1">Students with academic results and report cards.</flux:text>
        </div>
        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-zinc-50 text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
                        <tr><th class="px-5 py-3">Student</th><th class="px-5 py-3">Number</th><th class="px-5 py-3">Classroom</th><th class="px-5 py-3 text-right">Actions</th></tr>
                    </thead>
                    <tbody class="divide-y dark:divide-zinc-700">
                        @foreach($students as $student)
                            <tr>
                                <td class="px-5 py-4">{{ $student->last_name }} {{ $student->first_name }}</td>
                                <td class="px-5 py-4">{{ $student->student_number }}</td>
                                <td class="px-5 py-4">{{ $student->classroom->name }}</td>
                                <td class="px-5 py-4 text-right"><a class="text-teal-600" href="{{ route('student-grades.results', $student) }}">Open results</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $students->links() }}</div>
        </div>
    </div>
</x-layouts.app>
