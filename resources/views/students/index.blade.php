<x-layouts.app>
    <div class="mx-auto flex min-w-0 w-full max-w-7xl flex-col gap-5 p-4 sm:p-6 lg:p-8">
        <section class="grid overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900 lg:grid-cols-[1fr_320px]">
            <div class="p-6">
                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                    <div>
                        <flux:heading level="1" size="xl">Students</flux:heading>
                        <flux:text class="mt-2">Student records, contact details, class placement, and semester grades.</flux:text>
                    </div>
                    @can('students.create')
                        <div class="flex flex-wrap gap-2">
                            <flux:button href="{{ route('students.create') }}" icon="plus" variant="primary" wire:navigate>Add student</flux:button>
                            <flux:button href="{{ route('templates.students') }}" icon="arrow-down-tray">Template</flux:button>
                        </div>
                    @endcan
                </div>

                <form method="GET" action="{{ route('students.index') }}" class="mt-6">
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                        <input type="text" name="last_name" placeholder="Last name" value="{{ request('last_name') }}" class="rounded-xl border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 dark:border-white/10 dark:bg-zinc-950" />
                        <input type="text" name="first_name" placeholder="First name" value="{{ request('first_name') }}" class="rounded-xl border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 dark:border-white/10 dark:bg-zinc-950" />
                        <input type="text" name="student_number" placeholder="Student ID" value="{{ request('student_number') }}" class="rounded-xl border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 dark:border-white/10 dark:bg-zinc-950" />
                        <input type="text" name="classroom_id" placeholder="Class" value="{{ request('classroom_id') }}" class="rounded-xl border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 dark:border-white/10 dark:bg-zinc-950" />
                        <flux:button type="submit" icon="magnifying-glass" variant="primary">Search</flux:button>
                    </div>
                </form>
            </div>
            <div class="relative hidden min-h-56 lg:block">
                <img src="{{ asset('images/school-campus.svg') }}" alt="School campus" class="absolute inset-0 h-full w-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-zinc-950/70 to-transparent"></div>
                <div class="absolute bottom-4 left-4 right-4 rounded-xl bg-white/90 p-4 shadow-sm backdrop-blur dark:bg-zinc-950/80">
                    <p class="text-sm font-medium text-zinc-950 dark:text-white">{{ $students->total() }} records found</p>
                    <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-300">Use filters to find students quickly.</p>
                </div>
            </div>
        </section>

        <div class="min-w-0 max-w-full overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900">
            <div class="max-w-full overflow-x-auto overscroll-x-contain">
                <table class="w-full min-w-[1200px] text-left text-sm text-zinc-600 dark:text-zinc-300">
                    <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-800/70 dark:text-zinc-400">
                        <tr>
                            <th scope="col" class="px-5 py-4">Student</th>
                            <th scope="col" class="px-5 py-4">Student ID</th>
                            <th scope="col" class="px-5 py-4">Class</th>
                            <th scope="col" class="px-5 py-4">City</th>
                            <th scope="col" class="px-5 py-4">Phone</th>
                            <th scope="col" class="px-5 py-4">Email</th>
                            <th scope="col" class="px-5 py-4">Level</th>
                            @foreach ([1, 2, 3, 4, 5, 6] as $position)
                                <th scope="col" class="px-5 py-4 text-center">S{{ $position }}</th>
                            @endforeach
                            <th scope="col" class="px-5 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-white/10">
                        @forelse ($students as $student)
                            <tr class="transition hover:bg-teal-50/50 dark:hover:bg-white/5">
                                <th scope="row" class="px-5 py-4">
                                    <a href="{{ route('students.show', $student->id) }}" class="flex items-center gap-3 font-medium text-zinc-950 dark:text-white">
                                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-teal-100 text-sm font-semibold text-teal-700 dark:bg-teal-400/10 dark:text-teal-200">
                                            {{ strtoupper(substr($student['last_name'] ?? 'S', 0, 1).substr($student['first_name'] ?? 'M', 0, 1)) }}
                                        </span>
                                        <span>
                                            <span class="block">{{ $student['last_name'] }} {{ $student['first_name'] }}</span>
                                            <span class="block text-xs font-normal text-zinc-500 dark:text-zinc-400">Student profile</span>
                                        </span>
                                    </a>
                                </th>
                                <td class="px-5 py-4">{{ $student['student_number'] }}</td>
                                <td class="px-5 py-4"><span class="rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 dark:bg-blue-400/10 dark:text-blue-200">{{ $student['classroom_id'] }}</span></td>
                                <td class="px-5 py-4">{{ $student['city'] }}</td>
                                <td class="px-5 py-4">{{ $student['phone'] }}</td>
                                <td class="px-5 py-4">{{ $student['email'] }}</td>
                                <td class="px-5 py-4">{{ $student['education_level'] }}</td>
                                @foreach ([1, 2, 3, 4, 5, 6] as $position)
                                    <td class="px-5 py-4 text-center font-medium text-zinc-950 dark:text-white">
                                        {{ $canViewSemesterAverages ? (optional($student->semesterAverages->first(fn ($grade) => $grade->semester?->sequence === $position))->grade ?? 0) : '—' }}
                                    </td>
                                @endforeach
                                <td class="px-5 py-4 text-right">
                                    <flux:dropdown>
                                        <flux:button icon:trailing="chevron-down">Options</flux:button>
                                        <flux:menu>
                                            @can('students.update')
                                                <flux:menu.item href="{{ route('students.edit', $student->id) }}" icon="bars-2">Edit</flux:menu.item>
                                            @endcan
                                            <flux:menu.item href="{{ route('students.show', $student->id) }}" icon="eye">Show</flux:menu.item>
                                            @can('students.delete')
                                                <flux:menu.separator />
                                                <flux:modal.trigger name="delete-student-{{ $student->id }}">
                                                    <flux:menu.item variant="danger" icon="trash">Delete</flux:menu.item>
                                                </flux:modal.trigger>
                                            @endcan
                                        </flux:menu>
                                    </flux:dropdown>
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="14" class="px-5 py-16 text-center">
                                    <p class="font-medium text-zinc-950 dark:text-white">No students found</p>
                                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Try changing the search filters.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-zinc-100 p-4 dark:border-white/10">
                {{ $students->links() }}
            </div>
        </div>

        @can('students.delete')
            @foreach ($students as $student)
                <flux:modal name="delete-student-{{ $student->id }}" class="min-w-[22rem]">
                    <form action="{{ route('students.destroy', $student->id) }}" method="POST" class="space-y-6">
                        @csrf
                        @method('DELETE')

                        <div>
                            <flux:heading size="lg">Delete student</flux:heading>
                            <flux:text class="mt-2">
                                <p>You are about to delete {{ $student['last_name'] }} {{ $student['first_name'] }}.</p>
                                <p>This action cannot be undone.</p>
                            </flux:text>
                        </div>
                        <div class="flex gap-2">
                            <flux:spacer />
                            <flux:modal.close>
                                <flux:button type="button" variant="ghost">Cancel</flux:button>
                            </flux:modal.close>
                            <flux:button type="submit" variant="danger">Delete Student</flux:button>
                        </div>
                    </form>
                </flux:modal>
            @endforeach
        @endcan
    </div>
</x-layouts.app>
