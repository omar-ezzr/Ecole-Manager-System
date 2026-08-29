<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-full bg-[#FDFDFC] text-[#1b1b18] antialiased dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
        <div class="flex min-h-screen flex-col px-6 py-6 lg:px-10 lg:py-8">
            <header class="mx-auto flex w-full max-w-5xl items-center justify-between text-sm">
                <a href="{{ route('home') }}" class="flex items-center gap-2 font-medium" wire:navigate>
                    <x-app-logo-icon class="size-9 fill-current text-black dark:text-white" />
                    <span class="hidden sm:inline">{{ config('app.name', 'School Management System') }}</span>
                </a>
                @if (Route::has('login'))
                    <nav class="flex items-center gap-2 sm:gap-4">
                        @auth
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-md border border-[#19140035] px-4 py-2 text-sm transition hover:border-black dark:border-[#3E3E3A] dark:hover:border-white" wire:navigate>Dashboard</a>
                       @else
    <a
        href="{{ route('login') }}"
        class="rounded-md px-3 py-2 text-sm transition hover:bg-black/5 dark:hover:bg-white/10"
        wire:navigate
    >
        Log in
    </a>

    @if (Route::has('register'))
        <a
            href="{{ route('register') }}"
            class="inline-flex items-center rounded-md border border-[#19140035] px-4 py-2 text-sm transition hover:border-black dark:border-[#3E3E3A] dark:hover:border-white"
            wire:navigate
        >
            Register
        </a>
    @endif
@endauth
                    </nav>
                @endif
            </header>

            <main class="mx-auto flex w-full max-w-5xl flex-1 items-center justify-center py-10 lg:py-16">
                <div class="grid w-full overflow-hidden rounded-xl border border-[#19140020] bg-white shadow-sm dark:border-[#3E3E3A] dark:bg-[#161615] lg:grid-cols-[1.1fr_.9fr]">
                    <section class="flex min-h-[360px] flex-col justify-center p-8 sm:p-12 lg:p-16">
                        <p class="mb-5 text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">School Management System</p>
                        <h1 class="max-w-xl text-3xl font-semibold leading-tight tracking-tight sm:text-5xl">Student records, attendance, health records, and grades in one place.</h1>
                        <p class="mt-5 max-w-lg text-base leading-7 text-[#706f6c] dark:text-[#A1A09A]">A focused workspace for managing your school community with clarity and confidence.</p>
                        <div class="mt-8 flex flex-wrap gap-3">
                            @auth
                                <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-md bg-[#1b1b18] px-5 py-2.5 text-sm font-medium text-white transition hover:bg-black dark:bg-[#EDEDEC] dark:text-[#1b1b18] dark:hover:bg-white" wire:navigate>Open Dashboard</a>
                            @else
                                <a href="{{ route('login') }}" class="inline-flex items-center rounded-md bg-[#1b1b18] px-5 py-2.5 text-sm font-medium text-white transition hover:bg-black dark:bg-[#EDEDEC] dark:text-[#1b1b18] dark:hover:bg-white" wire:navigate>Sign in to continue</a>
                            @endauth
                        </div>
                    </section>

                    <section class="relative flex min-h-[300px] items-center justify-center overflow-hidden border-t border-[#19140020] bg-[#f3f4f6] p-8 dark:border-[#3E3E3A] dark:bg-[#20201e] lg:min-h-full lg:border-l lg:border-t-0">
                        <x-placeholder-pattern class="absolute inset-0 size-full stroke-[#19140012] dark:stroke-[#eeeeec12]" />
                        <div class="relative w-full max-w-sm rounded-lg border border-[#19140020] bg-white/90 p-6 shadow-sm backdrop-blur-sm dark:border-[#3E3E3A] dark:bg-[#161615]/90">
                            <div class="flex items-center justify-between border-b border-[#19140015] pb-4 dark:border-[#3E3E3A]">
                                <span class="text-sm font-medium">Your workspace</span>
                                <span class="size-2 rounded-full bg-emerald-500" aria-label="Available"></span>
                            </div>
                            <div class="mt-5 space-y-3">
                                <div class="flex items-center justify-between rounded-md bg-[#f3f4f6] px-4 py-3 dark:bg-[#252523]"><span class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Student records</span><span class="font-semibold">Ready</span></div>
                                <div class="flex items-center justify-between rounded-md bg-[#f3f4f6] px-4 py-3 dark:bg-[#252523]"><span class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Academic tracking</span><span class="font-semibold">Ready</span></div>
                                <div class="flex items-center justify-between rounded-md bg-[#f3f4f6] px-4 py-3 dark:bg-[#252523]"><span class="text-sm text-[#706f6c] dark:text-[#A1A09A]">School operations</span><span class="font-semibold">Ready</span></div>
                            </div>
                        </div>
                    </section>
                </div>
            </main>
        </div>
    </body>
</html>
