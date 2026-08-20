<x-layouts.app>     @vite('resources/css/app.css')
    <div class="p-3">
        <flux:heading level="3" size="xl" > 
        Update student: <flux:badge variant="solid" size="lg"  color="zinc">{{$student->last_name}} {{$student->first_name}}</flux:badge>

    </flux:heading>
<flux:text class="mt-2">Use this page to update the student record.</flux:text>
</div>
<form  action ="{{route('students.update',[$student->id])}}" method ="POST">
    @csrf
    @method ('PUT')

<flux:fieldset>

    <div class="space-y-6">
        <div class="grid grid-cols-3 gap-4">
            @php($semesterGrades = $student->semesterAverages->mapWithKeys(fn ($grade) => ['semester_'.$grade->semester?->sequence => $grade->grade]))
            <flux:input label="Last Name" name="last_name" value="{{$student->last_name}}" placeholder="Enter the last name" />
            @error('title')
            <div class="alert alert-danger">{{ $message }}</div>
        @enderror
            <flux:input label="First Name"   name="first_name" value="{{$student->first_name}}"  placeholder="Enter the first name" />
            <flux:input label="Student ID"  name="student_number" value="{{$student->student_number}}"  placeholder="Enter the student ID" />
            <flux:select label="Class" name="classroom_id">
                <option  value="1">1</option>
                <option  value="2">2</option>
                <option  value="3">3</option>
                <option  value="4">4</option>
                <option  value="5">5</option>
                <option  value="6">6</option>
                <option  value="7">7</option>
                <option  value="8">8</option>
                <option  value="9">9</option>
                <option  value="10">10</option>
                <option  value="11">11</option>
                <option  value="12">12</option>
                <option  value="13">13</option>
                <option  value="14">14</option>
                <option  value="15">15</option>
                <option  value="16">16</option>
                <option  value="17">17</option>
                <option  value="18">18</option>
                <option  value="19">19</option>
                <option  value="20">20</option>
                <!-- ... -->
            </flux:select>
            <flux:input label="Phone" name="phone" value="{{$student->phone}}" placeholder="Enter the phone number" />
            <flux:input label="Email" name="email" value="{{$student->email}}" placeholder="Enter the email address" />
            <flux:input label="Diploma" name="diploma" value="{{$student->diploma}}" placeholder="Enter the diploma" />
            <flux:input label="City" name="city" value="{{$student->city}}" placeholder="Enter the city" />
            <flux:input label="Address" name="address" value="{{$student->address}}" placeholder="Enter the address" />
           
            <flux:select label="Academic Level" name="education_level">
                <option selected>Bac</option>
                <option >Bac +2</option>
                <option >Bac +3</option>
                <option >Bac +4</option>
                <option >Bac +5</option>
                <!-- ... -->
            </flux:select>
            <flux:input label="Height" name="height" value="{{$student->height}}" placeholder="Enter height" />
            <flux:input label="Weight" name="weight" value="{{$student->weight}}" placeholder="Enter weight in kg" />
        </div>

        <div class="grid grid-cols-2 gap-x-6 gap-y-6">
            <flux:input label="Semester 1" name="semester_1" value="{{$semesterGrades['semester_1'] ?? ''}}" placeholder="Optional" />
            <flux:input label="Semester 2" name="semester_2" value="{{$semesterGrades['semester_2'] ?? ''}}" placeholder="Optional" />
            <flux:input label="Semester 3" name="semester_3" value="{{$semesterGrades['semester_3'] ?? ''}}" placeholder="Optional" />
            <flux:input label="Semester 4" name="semester_4" value="{{$semesterGrades['semester_4'] ?? ''}}" placeholder="Optional" />
            <flux:input label="Semester 5" name="semester_5" value="{{$semesterGrades['semester_5'] ?? ''}}" placeholder="Optional" />
            <flux:input label="Semester 6" name="semester_6" value="{{$semesterGrades['semester_6'] ?? ''}}" placeholder="Optional" />
            
        </div>


    </div>
    <div class="p-3">
        <flux:modal name="confirm">
            <!-- ... -->
        </flux:modal>
    <flux:button variant="primary" type="submit" class="w-full p-2">Save Changes</flux:button>
    </div>
</flux:fieldset>
</form>

</x-layouts.app>    
