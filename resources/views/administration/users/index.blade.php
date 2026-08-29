<x-layouts.app>
    <div class="mx-auto w-full max-w-6xl space-y-6 p-4 sm:p-6 lg:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <flux:heading size="xl">User administration</flux:heading>
                <flux:text class="mt-1">Manage accounts, roles, and access links.</flux:text>
            </div>
            @can('create', \App\Models\User::class)
                <flux:button href="{{ route('administration.users.create') }}" variant="primary" icon="plus">Add User</flux:button>
            @endcan
        </div>

        <form method="GET" action="{{ route('administration.users.index') }}" class="flex max-w-xl flex-col gap-3 sm:flex-row sm:items-end">
            <flux:input name="search" label="Search Users" value="{{ request('search') }}" placeholder="Name or email" icon="magnifying-glass" class="min-w-0 flex-1" />
            <div class="flex gap-2">
                <flux:button type="submit" variant="primary">Search</flux:button>
                @if(request()->filled('search'))
                    <flux:button href="{{ route('administration.users.index') }}" variant="ghost">Clear</flux:button>
                @endif
            </div>
        </form>

        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="border-b bg-zinc-50 text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
                        <tr><th scope="col" class="px-5 py-3">Name / Email</th><th scope="col" class="px-5 py-3">Role</th><th scope="col" class="px-5 py-3">Student Link</th><th scope="col" class="px-5 py-3">Status</th><th scope="col" class="px-5 py-3 text-right">Actions</th></tr>
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
                                        <a class="mr-3 font-medium text-teal-700 hover:underline dark:text-teal-300" href="{{ route('administration.users.edit', $user) }}">Edit</a>
                                    @endcan
                                    @can('delete', $user)
                                        <form class="inline" method="POST" action="{{ route('administration.users.destroy', $user) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-medium text-rose-700 hover:underline dark:text-rose-300" onclick="return confirm('Delete this user?')">Delete</button>
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
            @if($users->hasPages())
                <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">{{ $users->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.app>
