
<x-layouts.app.sidebar>
                  <link rel="icon" href="{!! asset('favicon.ico') !!}"/>

     

        <flux:header class="block! bg-white lg:bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
            <flux:navbar class="lg:hidden w-full">
                <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
                <flux:spacer />
                
            </flux:navbar>

            <form action="{{ route('students.search') }}" method="GET">
@csrf
                <flux:navbar class="w-100 ">
                    <flux:input icon="magnifying-glass" name='find' placeholder="Search student by ID, first name, or last name" />
                    <flux:button type="submit" variant="primary">Search</flux:button>
    
                </flux:navbar></form>

        </flux:header>
    <flux:main>
            @if(session('error'))

            <flux:callout  icon="bell">
            <flux:callout.heading>Student not found</flux:callout.heading>
                {{ session('error') }}
        </flux:callout>
            @endif
            @if(session('warning'))
                <flux:callout icon="bell">
                    <flux:callout.heading>Action needed</flux:callout.heading>
                    {{ session('warning') }}
                </flux:callout>
            @endif
        {{ $slot }}
    </flux:main>
</x-layouts.app.sidebar>
