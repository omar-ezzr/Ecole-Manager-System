<?php

namespace App\Http\Controllers;

use App\Models\HealthRecord;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentGrade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::query();

        if ($request->filled('last_name')) {
            $query->where('last_name', 'like', '%'.$request->last_name.'%');
        }

        if ($request->filled('first_name')) {
            $query->where('first_name', 'like', '%'.$request->first_name.'%');
        }

        if ($request->filled('student_number')) {
            $query->where('student_number', 'like', '%'.$request->student_number.'%');
        }

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', 'like', '%'.$request->classroom_id.'%');
        }

        $students = $query->with('grades.semester')->orderBy('student_number')->paginate(30)->withQueryString();
        $semesterIds = Semester::orderBy('position')->pluck('id', 'position');

        return view('students.index', compact('students', 'semesterIds'));
    }

    public function create()
    {
        if (Auth::user()->email === env('EMAIL_AUTH')) {
            return view('students.create');
        }

        return view('/');
    }

    public function store(Request $request)
    {
        $validated = $this->validatedStudent($request);
        $student = Student::create($validated);
        $this->syncSemesterGrades($student, $request);

        return redirect()->route('students.index');
    }

    public function show(string $id)
    {
        if (! Auth::user()) {
            return view('/');
        }

        $student = Student::with(['grades.semester', 'healthRecords'])->findOrFail($id);
        $healthRecords = $student->healthRecords()->orderByDesc('date')->get();
        $data = [
            'name' => $student->last_name,
            'first_name' => $student->first_name,
            'student_number' => $student->student_number,
            'classroom' => $student->classroom_id,
            'address' => $student->address,
            'email' => $student->email,
            'phone' => $student->phone,
            'height' => $student->height,
            'weight' => $student->weight,
            'absences' => $student->absences_count,
        ];

        return view('students.show', [
            'student' => $student,
            'qrcode' => QrCode::size(300)->generate(json_encode($data)),
            'healthRecords' => $healthRecords,
        ]);
    }

    public function edit(string $id)
    {
        if (Auth::user()->email === env('EMAIL_AUTH')) {
            return view('students.edite', ['student' => Student::with('grades.semester')->findOrFail($id)]);
        }

        return view('/');
    }

    public function update(Request $request, string $id)
    {
        $student = Student::findOrFail($id);
        $validated = $this->validatedStudent($request, $student->id);
        $student->update($validated);
        $this->syncSemesterGrades($student, $request);

        return redirect()->route('students.index');
    }

    public function destroy(string $id)
    {
        Student::findOrFail($id)->delete();

        return redirect()->route('students.index');
    }

    private function validatedStudent(Request $request, ?int $ignoreId = null): array
    {
        $studentNumberRule = Rule::unique('students', 'student_number');
        if ($ignoreId !== null) {
            $studentNumberRule->ignore($ignoreId);
        }

        $request->validate([
            'last_name' => ['required', 'string'],
            'first_name' => ['required', 'string'],
            'student_number' => ['required', $studentNumberRule, 'bail'],
            'classroom_id' => ['required'],
            'phone' => ['required'],
            'email' => ['required', 'email'],
            'diploma' => ['required', 'string'],
            'city' => ['required', 'string'],
            'address' => ['required', 'string'],
            'education_level' => ['required', 'string'],
            'height' => ['required'],
            'weight' => ['required'],
        ]);

        return [
            'last_name' => strip_tags($request->input('last_name')),
            'first_name' => strip_tags($request->input('first_name')),
            'student_number' => strip_tags($request->input('student_number')),
            'classroom_id' => strip_tags($request->input('classroom_id')),
            'phone' => strip_tags($request->input('phone')),
            'email' => strip_tags($request->input('email')),
            'diploma' => strip_tags($request->input('diploma')),
            'city' => strip_tags($request->input('city')),
            'address' => strip_tags($request->input('address')),
            'education_level' => strip_tags($request->input('education_level')),
            'height' => strip_tags($request->input('height')),
            'weight' => strip_tags($request->input('weight')),
            'appreciation_score' => strip_tags($request->input('appreciation_score', 0)),
            'absences_count' => strip_tags($request->input('absences_count', 0)),
            'appreciation' => strip_tags($request->input('appreciation')),
        ];
    }

    private function syncSemesterGrades(Student $student, Request $request): void
    {
        $semesters = Semester::orderBy('position')->get();

        foreach ($semesters as $semester) {
            $value = $request->input($semester->code);
            if ($value === null || $value === '') {
                continue;
            }

            StudentGrade::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'semester_id' => $semester->id,
                    'subject_id' => null,
                ],
                ['grade' => $value]
            );
        }
    }
}
