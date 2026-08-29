<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <link rel="icon" href="{{ asset('favicon.ico') }}">
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-950 dark:bg-zinc-950 dark:text-zinc-50">
        <flux:sidebar sticky stashable class="border-r border-teal-900/10 bg-white/95 shadow-sm dark:border-white/10 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('dashboard') }}" class="mr-5 flex items-center space-x-2 rounded-xl px-1 py-2" wire:navigate>
                <x-app-logo />
            </a>

            <flux:navlist variant="outline" class="space-y-3">
                <flux:navlist.group heading="Workspace">
                    <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:navlist.item>
                </flux:navlist.group>

                @canany(['students.view_all', 'students.view_assigned', 'students.view_own', 'health_records.view'])
                    <flux:navlist.group heading="Students">
                        @canany(['students.view_all', 'students.view_assigned', 'students.view_own'])
                            <flux:navlist.item icon="user" href="{{ route('students.index') }}" :current="request()->routeIs('students.*')" wire:navigate>
                                {{ __('Student records') }}
                            </flux:navlist.item>
                        @endcanany
                        @can('health_records.view')
                            <flux:navlist.item icon="heart" href="{{ route('health-records.index') }}" :current="request()->routeIs('health-records.*')" wire:navigate>
                                {{ __('Health records') }}
                            </flux:navlist.item>
                        @endcan
                    </flux:navlist.group>
                @endcanany

                @canany(['subjects.view', 'academic_years.view', 'semesters.view', 'teaching_assignments.view_all', 'teaching_assignments.view_own', 'grades.view_all', 'grades.view_assigned', 'grades.view_own'])
                    <flux:navlist.group heading="Academic">
                        @can('academic_years.view')
                            <flux:navlist.item icon="calendar-days" href="{{ route('academic-years.index') }}" :current="request()->routeIs('academic-years.*')" wire:navigate>{{ __('Academic Years') }}</flux:navlist.item>
                        @endcan
                        @can('semesters.view')
                            <flux:navlist.item icon="rectangle-group" href="{{ route('semesters.index') }}" :current="request()->routeIs('semesters.*')" wire:navigate>{{ __('Semesters') }}</flux:navlist.item>
                        @endcan
                        @can('subjects.view')
                            <flux:navlist.item icon="book-open-text" href="{{ route('subjects.index') }}" :current="request()->routeIs('subjects.*')" wire:navigate>{{ __('Subjects') }}</flux:navlist.item>
                        @endcan
                        @canany(['teaching_assignments.view_all', 'teaching_assignments.view_own'])
                            <flux:navlist.item icon="folder-git-2" href="{{ route('teaching-assignments.index') }}" :current="request()->routeIs('teaching-assignments.*')" wire:navigate>
                                {{ auth()->user()->isProfessor() ? __('My Teaching Assignments') : __('Teaching Assignments') }}
                            </flux:navlist.item>
                        @endcanany
                        @canany(['grades.view_all', 'grades.view_assigned', 'grades.view_own'])
                            <flux:navlist.item icon="chart-bar-square" href="{{ auth()->user()->isStudentUser() && auth()->user()->student_id ? route('student-grades.results', auth()->user()->student_id) : route('student-grades.index') }}" :current="request()->routeIs('student-grades.*')" wire:navigate>
                                {{ auth()->user()->isStudentUser() ? __('My Results') : __('Grades / Results') }}
                            </flux:navlist.item>
                        @endcanany
                    </flux:navlist.group>
                @endcanany

                @canany(['attendance.view_all', 'attendance.view_assigned'])
                    <flux:navlist.group heading="Attendance">
                        <flux:navlist.item icon="clipboard-document-check" href="{{ url('absences') }}" :current="request()->is('absences')">
                            {{ __('Attendance reports') }}
                        </flux:navlist.item>
                    </flux:navlist.group>
                @endcanany

                @canany(['schools.view', 'departments.view', 'classrooms.view_all', 'classrooms.view_assigned'])
                    <flux:navlist.group heading="School structure">
                        @can('schools.view')
                            <flux:navlist.item icon="academic-cap" href="{{ route('schools.index') }}" :current="request()->routeIs('schools.*')" wire:navigate>{{ __('Schools') }}</flux:navlist.item>
                        @endcan
                        @can('departments.view')
                            <flux:navlist.item icon="squares-2x2" href="{{ route('departments.index') }}" :current="request()->routeIs('departments.*')" wire:navigate>{{ __('Departments') }}</flux:navlist.item>
                        @endcan
                        @canany(['classrooms.view_all', 'classrooms.view_assigned'])
                            <flux:navlist.item icon="building-office" href="{{ route('classrooms.index') }}" :current="request()->routeIs('classrooms.*')" wire:navigate>{{ __('Classrooms') }}</flux:navlist.item>
                        @endcanany
                    </flux:navlist.group>
                @endcanany

                @can('viewAny', \App\Models\User::class)
                    <flux:navlist.group heading="Administration">
                        <flux:navlist.item icon="users" href="{{ route('administration.users.index') }}" :current="request()->routeIs('administration.users.*')" wire:navigate>
                            {{ __('User administration') }}
                        </flux:navlist.item>
                    </flux:navlist.group>
                @endcan
            </flux:navlist>

            <flux:spacer />

            <div class="mb-3 hidden overflow-hidden rounded-xl border border-teal-900/10 bg-teal-50 lg:block dark:border-white/10 dark:bg-zinc-800">
                <img src="{{ asset('images/school-campus.svg') }}" alt="School campus" class="h-24 w-full object-cover">
                <div class="p-3">
                    <p class="text-xs font-medium text-teal-900 dark:text-teal-100">Real-time academic records</p>
                    <p class="mt-1 text-xs text-teal-800/70 dark:text-zinc-300">Students, grades, attendance, and health data in one workspace.</p>
                </div>
            </div>

        
            <flux:dropdown position="bottom" align="start">
                <flux:profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevrons-up-down"
                    
                />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span 
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item href="{{ route('settings.profile') }}" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item href="{{ route('settings.profile') }}" icon="cog" wire:navigate>Settings</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
