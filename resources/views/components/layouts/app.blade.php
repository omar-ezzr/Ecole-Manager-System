
<x-layouts.app.sidebar>
    @canany(['students.view_all', 'students.view_assigned', 'students.view_own'])
        <flux:header class="border-b border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <form action="{{ route('students.search') }}" method="GET" class="mx-auto flex w-full max-w-3xl items-end gap-2">
                <label for="global-student-search" class="sr-only">Search students</label>
                <flux:input
                    id="global-student-search"
                    icon="magnifying-glass"
                    name="find"
                    value="{{ request('find') }}"
                    placeholder="Search by student ID, first name, or last name"
                    class="min-w-0 flex-1"
                />
                <flux:button type="submit" variant="primary">Search</flux:button>
            </form>
        </flux:header>
    @endcanany

    <flux:main>
        @if(session('success') || session('error') || session('warning') || $errors->any())
            <div class="mx-auto grid w-full max-w-7xl gap-3 px-4 pt-4 sm:px-6 lg:px-8" aria-live="polite">
                @if(session('success'))
                    <flux:callout icon="check-circle" variant="success">
                        <flux:callout.text>{{ session('success') }}</flux:callout.text>
                    </flux:callout>
                @endif

                @if(session('error'))
                    <flux:callout icon="exclamation-circle" variant="danger">
                        <flux:callout.heading>Unable to complete the request</flux:callout.heading>
                        <flux:callout.text>{{ session('error') }}</flux:callout.text>
                    </flux:callout>
                @endif

                @if(session('warning'))
                    <flux:callout icon="exclamation-triangle" variant="warning">
                        <flux:callout.heading>Action needed</flux:callout.heading>
                        <flux:callout.text>{{ session('warning') }}</flux:callout.text>
                    </flux:callout>
                @endif

                @if($errors->any())
                    <flux:callout icon="exclamation-triangle" variant="danger">
                        <flux:callout.heading>Please review the form</flux:callout.heading>
                        <flux:callout.text>{{ $errors->first() }}</flux:callout.text>
                    </flux:callout>
                @endif
            </div>
        @endif

        {{ $slot }}
    </flux:main>
</x-layouts.app.sidebar>
