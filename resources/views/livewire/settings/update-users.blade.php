<div class="flex flex-col items-start">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Account Administration')" :subheading="__('Accounts and roles are managed in the secured administration area')">
        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:text>User creation, role assignment, and account status are centralized in User Administration.</flux:text>
            <flux:button href="{{ route('administration.users.index') }}" class="mt-4" variant="primary" wire:navigate>
                Open User Administration
            </flux:button>
        </div>
    </x-settings.layout>
</div>
