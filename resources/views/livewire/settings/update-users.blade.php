<div class="flex flex-col items-start">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Acounts')" :subheading=" __('Update users autorisations')">
       <flux:fieldset>
 <form action="{{ route('students.index') }}" method="GET">
    <div class="space-y-5">
       
            <flux:select label="Classroom" name="classroom_id">
                @for($i = 1; $i <= 13; $i++)
                    <option value="{{ $i }}">{{ $i }}</option>
                @endfor
            </flux:select>
            
        </div>
        <flux:button variant="primary" type="submit" class="w-full p-2">View Students</flux:button>
    </div>
</form>
    <div class="m-3">
    @if(session('success'))
    <flux:callout  icon="bell">
    <flux:callout.heading>Message à venir</flux:callout.heading>
    <flux:callout.text>
        {{ session('success') }}
        <flux:callout.link href="{{route('students.index')}}">Explorer</flux:callout.link>
    </flux:callout.text>
</flux:callout>
    @endif
</div>
</flux:fieldset>
    </x-settings.layout>
</div>
