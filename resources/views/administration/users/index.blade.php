<x-layouts.app>
    <div class="mx-auto w-full max-w-6xl space-y-6 p-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <flux:heading size="xl">User administration</flux:heading>
                <flux:text class="mt-1">Manage accounts, roles, and access links.</flux:text>
            </div>
            @can('create', \App\Models\User::class)
                <flux:button href="{{ route('administration.users.create') }}" variant="primary" icon="plus">Create user</flux:button>
            @endcan
        </div>

        @if(session('success'))
            <flux:callout variant="success">{{ session('success') }}</flux:callout>
        @endif

        <form method="GET">
            <flux:input name="search" value="{{ request('search') }}" placeholder="Search by name or email" icon="magnifying-glass" />
        </form>

        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-zinc-50 text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
                        <tr><th class="px-5 py-3">User</th><th class="px-5 py-3">Role</th><th class="px-5 py-3">Student</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Actions</th></tr>
                    </thead>
                    <tbody class="divide-y dark:divide-zinc-700">
                        @forelse($users as $user)
                            <tr>
                                <td class="px-5 py-4"><div class="font-medium">{{ $user->name }}</div><div class="text-zinc-500">{{ $user->email }}</div></td>
                                <td class="px-5 py-4">{{ $user->roles->first()?->name ?? 'No role' }}</td>
                                <td class="px-5 py-4 text-zinc-500">
                                    @if($user->student)
                                        <div>{{ $user->student->student_number }}</div>
                                        <div>{{ $user->student->last_name }} {{ $user->student->first_name }}</div>
                                    @else
                                        &mdash;
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <flux:badge color="{{ $user->is_active ? 'emerald' : 'zinc' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</flux:badge>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    @can('update', $user)
                                        <a class="mr-3 text-teal-600" href="{{ route('administration.users.edit', $user) }}">Edit</a>
                                    @endcan
                                    @can('delete', $user)
                                        <form class="inline" method="POST" action="{{ route('administration.users.destroy', $user) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-rose-600" onclick="return confirm('Delete this user?')">Delete</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-10 text-center text-zinc-500">No users found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $users->links() }}</div>
        </div>
    </div>
</x-layouts.app>
