<x-layouts.app>
         @vite('resources/css/app.css')
    <div class="p-6">
        <flux:heading level="3" size="xl" > 
        Create New Student <flux:badge icon="user-circle"></flux:badge>
    </flux:heading>
<flux:text class="mt-2">Use this page to create a new student record.</flux:text>
</div>
<form  action ="{{route('students.store')}}" method ="POST">
    @csrf
<flux:fieldset>
    <flux:menu.separator />

    <div class="space-y-6">
        <div class="grid grid-cols-3 gap-4">
            <flux:input label="Last Name" name="last_name"  placeholder="Enter the last name" />
            @error('title')
            <div class="alert alert-danger">{{ $message }}</div>
        @enderror
            <flux:input label="First Name"   name="first_name"  placeholder="Enter the first name" />
            <flux:input label="Student ID"  name="student_number"  placeholder="Enter the student ID" />
            <flux:select label="Class" name="classroom_id">
                @foreach($classrooms as $classroom)
                    <option value="{{ $classroom->id }}" @selected(old('classroom_id') == $classroom->id)>{{ $classroom->name }}</option>
                @endforeach
            </flux:select>
            <flux:input label="Phone" name="phone" placeholder="Enter the phone number" />
            <flux:input label="Email" name="email" placeholder="Enter the email address" />
            <flux:input label="Diploma" name="diploma" placeholder="Enter the diploma" />
            <flux:input label="City" name="city" placeholder="Enter the city" />
            <flux:input label="Address" name="address" placeholder="Enter the address" />
            {{-- <flux:input label="Adress" name="" placeholder="River" />
            <flux:input label="Adress" name="" placeholder="River" /> --}}
            <flux:select label="Academic Level" name="education_level">
                <option >Bac</option>
                <option >Bac +2</option>
                <option >Bac +3</option>
                <option >Bac +4</option>
                <option >Bac +5</option>
                <!-- ... -->
            </flux:select>
            <flux:input label="Height" name="height" placeholder="Enter height" />
            <flux:input label="Weight" name="weight" placeholder="Enter weight in kg" />
        </div>

        <div class="grid grid-cols-2 gap-x-6 gap-y-6">
            <flux:input label="Semester 1" name="semester_1" placeholder="Optional" />
            <flux:input label="Semester 2" name="semester_2" placeholder="Optional" />
            <flux:input label="Semester 3" name="semester_3" placeholder="Optional" />
            <flux:input label="Semester 4" name="semester_4" placeholder="Optional" />
            <flux:input label="Semester 5" name="semester_5" placeholder="Optional" />
            <flux:input label="Semester 6" name="semester_6" placeholder="Optional" />
            
        </div>
        <div class="grid grid-cols-2 gap-x-6 gap-y-6">
            <flux:input label="Absences" name="absences_count" placeholder="Optional" />
            <flux:input label="Evaluation Note" name="appreciation_score" placeholder="Optional" />
            <flux:textarea label="Evaluation Comment" name="appreciation" placeholder="Add an evaluation comment" />
           
            
        </div>


    </div>
    
    <div class="p-3">
        <flux:modal name="confirm">
            <!-- ... -->
        </flux:modal>
    <flux:button variant="primary" type="submit" class="w-full p-2">Add Student</flux:button>
    </div>
</flux:fieldset>
</form>
<!-- resources/views/import.blade.php -->
<flux:menu.separator />

<div class="p-6 mt-5">
    <flux:heading level="3" size="xl" > 
    Create New Students with Excel <flux:badge icon="document"></flux:badge>
</flux:heading>
</div>
<div class="grid grid-cols-2 gap-x-6 gap-y-6 flex items-center justify-center ">

<!-- resources/views/import.blade.php -->
<form action="{{ route('excel.import') }}" method="POST" enctype="multipart/form-data">
@csrf
<flux:input type="file" name="excel_file" wire:model="logo" label="Importer"  accept=".xlsx,.xls,.csv"/>

<flux:button variant="primary" type="submit" class="w-full p-1 mt-2">Import</flux:button></form>
<div class="block  p-6 bg-white border border-zinc-900 rounded-lg shadow-sm hover:bg-gray-200 dark:bg-zinc-700 dark:border-gray-700 dark:hover:bg-gray-700">

<flux:heading>Import Help</flux:heading>
<flux:text class="mt-2">Use the provided template for a correct student import.<flux:text>Download the <flux:link href="{{ route('templates.students') }}">template</flux:link> before uploading your file.</flux:text>
</flux:text>
<div >
<video class="w-full" autoplay muted controls>
    <source src="../data/tutorial.mp4" type="video/mp4">
    Your browser does not support the video tag.
  </video>
</div>


</div>


</div>

<div class="m-3">

    @if(session('success'))

    <flux:callout  icon="bell">
    <flux:callout.heading>Message à venir</flux:callout.heading>
    <flux:callout.text>
        {{ session('success') }}
        <flux:callout.link href="#">Explorer</flux:callout.link>
    </flux:callout.text>
</flux:callout>
    @endif

    @if(session('import_errors') && count(session('import_errors')) > 0)
        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-100">
            <p class="font-semibold">Import warnings</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach(array_slice(session('import_errors'), 0, 20) as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
            @if(count(session('import_errors')) > 20)
                <p class="mt-2 text-xs">Showing first 20 of {{ count(session('import_errors')) }} warnings.</p>
            @endif
        </div>
    @endif
</div>
<div class="grid grid-cols-3 gap-x-6 gap-y-6 ">

<form action="{{ route('excel.importSemester1') }}" class="" method="POST" enctype="multipart/form-data">
    <h1 class="text-2xl mt-5 mb-5 font-bold leading-tight md:text-2xl">Import Semester 1 data <flux:text>Download the <flux:link href="{{ route('templates.semester-1') }}">template</flux:link> before uploading your file.</flux:text></h1>
    
    @csrf
    <flux:input type="file" name="excel_file_semester_1" wire:model="logo"   accept=".xlsx,.xls,.csv"/>
    
    <flux:button variant="filled" type="submit" class="w-50 p-1 mt-2">Import</flux:button></form>
<form action="{{ route('excel.importSemester2') }}" method="POST" enctype="multipart/form-data">
    <h1 class="text-2xl mt-5 mb-5 font-bold leading-tight md:text-2xl">Import Semester 2 data<flux:text>Download the <flux:link href="{{ route('templates.semester-2') }}">template</flux:link> before uploading your file.</flux:text></h1>

    @csrf
    <flux:input type="file" name="excel_file_semester_2" wire:model="logo"   accept=".xlsx,.xls,.csv"/>
    
    <flux:button variant="filled" type="submit" class="w-50 p-1 mt-2">Import</flux:button></form>
<form action="{{ route('excel.importSemester3') }}" method="POST" enctype="multipart/form-data">
    <h1 class="text-2xl mt-5 mb-5 font-bold leading-tight md:text-2xl">Import Semester 3 data<flux:text>Download the <flux:link href="{{ route('templates.semester-3') }}">template</flux:link> before uploading your file.</flux:text></h1>

    @csrf
    <flux:input type="file" name="excel_file_semester_3" wire:model="logo"   accept=".xlsx,.xls,.csv"/>
    
    <flux:button variant="filled" type="submit" class="w-50 p-1 mt-2">Import</flux:button></form>
<form action="{{ route('excel.importSemester4') }}" method="POST" enctype="multipart/form-data">
    <h1 class="text-2xl mt-5 mb-5 font-bold leading-tight md:text-2xl">Import Semester 4 data<flux:text>Download the <flux:link href="{{ route('templates.semester-4') }}">template</flux:link> before uploading your file.</flux:text></h1>

    @csrf
    <flux:input type="file" name="excel_file_semester_4" wire:model="logo"   accept=".xlsx,.xls,.csv"/>
    
    <flux:button variant="filled" type="submit" class="w-50 p-1 mt-2">Import</flux:button></form>
<form action="{{ route('excel.importSemesters5And6') }}" method="POST" enctype="multipart/form-data">
    <h1 class="text-2xl mt-5 mb-5 font-bold leading-tight md:text-2xl">Import Semesters 5 and 6 data<flux:text>Download the <flux:link href="{{ route('templates.semesters-5-6') }}">template</flux:link> before uploading your file.</flux:text></h1>

    @csrf
    <flux:input type="file" name="excel_file_semesters_5_6" wire:model="logo"   accept=".xlsx,.xls,.csv"/>
    
    <flux:button variant="filled" type="submit" class="w-50 p-1 mt-2">Import</flux:button></form>

</div>


</x-layouts.app>    
