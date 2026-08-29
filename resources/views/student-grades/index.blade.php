<x-layouts.app>
    <div class="mx-auto w-full max-w-6xl space-y-6 p-4 sm:p-6 lg:p-8">
        <div>
            <flux:heading level="1" size="xl">Grades / Results</flux:heading>
            <flux:text class="mt-1">Open authorized student results and report cards.</flux:text>
        </div>
        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-zinc-50 text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
                        <tr><th scope="col" class="px-5 py-3">Student</th><th scope="col" class="px-5 py-3">Student ID</th><th scope="col" class="px-5 py-3">Classroom</th><th scope="col" class="px-5 py-3 text-right">Actions</th></tr>
                    </thead>
                    <tbody class="divide-y dark:divide-zinc-700">
                        @forelse($students as $student)
                            <tr>
                                <td class="px-5 py-4">{{ $student->last_name }} {{ $student->first_name }}</td>
                                <td class="px-5 py-4">{{ $student->student_number }}</td>
                                <td class="px-5 py-4">{{ $student->classroom->name }}</td>
                                <td class="px-5 py-4 text-right"><a class="font-medium text-teal-700 hover:underline dark:text-teal-300" href="{{ route('student-grades.results', $student) }}">View Results</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-10 text-center text-zinc-500">No assignment-based results found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($students->hasPages())
                <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">{{ $students->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.app>
