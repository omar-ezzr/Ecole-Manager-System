<x-layouts.app>     @vite('resources/css/app.css')
    <div class="p-3">
        <flux:heading level="3" size="xl" >
        Student information: <flux:badge variant="solid" size="lg"   color="zinc">{{$student->last_name}} {{$student->first_name}}</flux:badge>

    </flux:heading>
</div>
<div class="grid grid-cols-2 gap-4">
<dl class="max-w-md text-gray-900 divide-y divide-gray-200 dark:text-white dark:divide-gray-700">
    <div class="flex flex-col pb-3"> <flux:text class="text-base">Academic Information:</flux:text>
        <dt class="mb-1 text-gray-500 md:text-lg dark:text-gray-400">Student ID:</dt>
        <dd class="text-lg font-semibold">{{$student->student_number}} </dd>
    </div>
    <div class="flex flex-col py-3">
        <dt class="mb-1 text-gray-500 md:text-lg dark:text-gray-400">Class:</dt>
        <dd class="text-lg font-semibold">{{$student->classroom_id}}</dd>
    </div>

</dl>
 <div class="flex flex-col py-3">
        {!! $qrcode !!}
    </div>


</div>

<div class="grid grid-cols-1 gap-4">
<dl class="max-w-md text-gray-900 divide-y divide-gray-200 dark:text-white dark:divide-gray-700">

    <div class="flex flex-col pt-3"><flux:text class="text-base">Personal Information:</flux:text>
        <dt class="mb-1 text-gray-500 md:text-lg dark:text-gray-400">Phone:</dt>
        <dd class="text-lg font-semibold">{{$student->phone}}</dd>
    </div>
    <div class="flex flex-col pt-3">
        <dt class="mb-1 text-gray-500 md:text-lg dark:text-gray-400">Email:</dt>
        <dd class="text-lg font-semibold">{{$student->email}}</dd>
    </div>
    <div class="flex flex-col pt-3">
        <dt class="mb-1 text-gray-500 md:text-lg dark:text-gray-400">City:</dt>
        <dd class="text-lg font-semibold">{{$student->city}}</dd>
    </div>
    <div class="flex flex-col pt-3">
        <dt class="mb-1 text-gray-500 md:text-lg dark:text-gray-400">Address: </dt>
        <dd class="text-lg font-semibold">{{$student->address}}</dd>
    </div>
    <div class="flex flex-col pt-3">
        <dt class="mb-1 text-gray-500 md:text-lg dark:text-gray-400">Height: </dt>
        <dd class="text-lg font-semibold">{{$student->height}} Cm</dd>
    </div>
    <div class="flex flex-col pt-3">
        <dt class="mb-1 text-gray-500 md:text-lg dark:text-gray-400">Weight </dt>
        <dd class="text-lg font-semibold">{{$student->weight}} KG</dd>
    </div>
</dl>

</div>


<div class="mt-20 grid grid-cols-1 gap-4">
<dl class="max-w-md text-gray-900 divide-y divide-gray-200 dark:text-white dark:divide-gray-700">

    <div class="flex flex-col "><flux:text class="text-base">Attendance Information:</flux:text>
        <dt class="mb-1 text-gray-500 md:text-lg dark:text-gray-400">Absences:</dt>
        <dd class="text-lg font-semibold">{{$student->absences_count}}</dd>
    </div>
 <div class="flex flex-col ">
        <dt class="mb-1 text-gray-500 md:text-lg dark:text-gray-400">Evaluation Comment </dt>
        <dd class="text-lg font-semibold">{{$student->appreciation}} </dd>
    </div>
</dl>

</div>

@if($canViewHealthRecords)
<div class="mt-20 grid grid-cols-1 gap-1"> <flux:text class="text-base">Health Records:</flux:text>
   @foreach($healthRecords as $healthRecord)

<dl class=" text-gray-900 divide-y divide-gray-200 dark:text-white dark:divide-gray-700">
  <div class="flex flex-col">
        <dt class="mb-1 text-gray-500 md:text-lg dark:text-gray-400">La date:</dt>
        <dd class="text-lg font-semibold">{{$healthRecord->date}}</dd>
    </div> <div class="flex flex-col pt-3">

        <dt class="mb-1 text-gray-500 md:text-lg dark:text-gray-400">Consultation type:</dt>
        <dd class="text-lg font-semibold">{{$healthRecord->type}}</dd>
    </div>
 <div class="flex flex-col pt-3 mb-10">
        <dt class="mb-1 text-gray-500 md:text-lg dark:text-gray-400">Medical prescription </dt>
        <dd class="text-lg font-semibold">{{$healthRecord->medical_prescription}} </dd>
    </div>
</dl>
    @endforeach

</div>
@endif




<script src="{{ asset('js/chart.js') }}"></script>

@if($canViewSemesterAverages)
<div class="relative overflow-hidden rounded-xl border-neutral-200 dark:border-neutral-700">
    <flux:heading class="text-center mt-2">Semester Grades</flux:heading>
    <x-student-semester-grades :id="$student->id" />
</div>
@endif
</x-layouts.app>
