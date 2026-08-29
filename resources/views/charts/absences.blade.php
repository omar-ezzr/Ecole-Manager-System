<x-layouts.app>
    <div class="mx-auto w-full max-w-6xl space-y-6 p-4 sm:p-6 lg:p-8">
        <div>
            <flux:heading level="1" size="xl">Recorded Absences by Classroom</flux:heading>
            <flux:text class="mt-1">Daily absent records from the active academic year in your authorized classroom scope.</flux:text>
        </div>

        <div class="relative overflow-hidden rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 sm:p-6">
            <x-absences-chart />
        </div>
    </div>
</x-layouts.app>
