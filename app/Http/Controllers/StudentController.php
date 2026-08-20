<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentGrade;
use App\Models\User;
use App\Support\SchoolPermissions as P;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Student::class);

        if ($this->studentAccountIsWaitingForAssignment($request->user())) {
            return $this->redirectWaitingForStudentAssignment();
        }

        $query = Student::visibleTo($request->user());

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

        $canViewSemesterAverages = ! $request->user()->isProfessor()
            && $request->user()->canAny([P::GRADES_ALL, P::GRADES_OWN]);
        $students = $query
            ->when($canViewSemesterAverages, fn ($students) => $students->with('semesterAverages.semester'))
            ->orderBy('student_number')
            ->paginate(30)
            ->withQueryString();
        $semesterIds = Semester::where('academic_year_id', AcademicYear::active()->value('id'))->orderBy('sequence')->pluck('id', 'sequence');

        return view('students.index', compact('students', 'semesterIds', 'canViewSemesterAverages'));
    }

    public function create()
    {
        $this->authorize('create', Student::class);

        return view('students.create', ['classrooms' => \App\Models\Classroom::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Student::class);

        $validated = $this->validatedStudent($request);
        $student = Student::create($validated);
        $this->syncSemesterGrades($student, $request);

        return redirect()->route('students.index');
    }

    public function show(Request $request, string $id)
    {
        if ($this->studentAccountIsWaitingForAssignment($request->user())) {
            return $this->redirectWaitingForStudentAssignment();
        }

        $student = Student::findOrFail($id);
        $this->authorize('view', $student);
        $canViewHealthRecords = $request->user()->can(P::HEALTH_VIEW);
        $canViewSemesterAverages = ! $request->user()->isProfessor()
            && $request->user()->canAny([P::GRADES_ALL, P::GRADES_OWN]);
        $healthRecords = $canViewHealthRecords
            ? $student->healthRecords()->orderByDesc('date')->get()
            : collect();
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
            'canViewHealthRecords' => $canViewHealthRecords,
            'canViewSemesterAverages' => $canViewSemesterAverages,
        ]);
    }

    public function edit(string $id)
    {
        $student = Student::with('semesterAverages.semester')->findOrFail($id);
        $this->authorize('update', $student);

        return view('students.edite', ['student' => $student, 'classrooms' => \App\Models\Classroom::orderBy('name')->get()]);
    }

    public function update(Request $request, string $id)
    {
        $student = Student::findOrFail($id);
        $this->authorize('update', $student);
        $validated = $this->validatedStudent($request, $student->id);
        $student->update($validated);
        $this->syncSemesterGrades($student, $request);

        return redirect()->route('students.index');
    }

    public function destroy(string $id)
    {
        $student = Student::findOrFail($id);
        $this->authorize('delete', $student);
        $student->delete();

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
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'phone' => ['required'],
            'email' => ['required', 'email'],
            'diploma' => ['required', 'string'],
            'city' => ['required', 'string'],
            'address' => ['required', 'string'],
            'education_level' => ['required', 'string'],
            'height' => ['required', 'integer', 'min:0'],
            'weight' => ['required', 'integer', 'min:0'],
            'appreciation_score' => ['nullable', 'numeric'],
            'absences_count' => ['nullable', 'integer', 'min:0'],
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
        $activeAcademicYearId = AcademicYear::active()->value('id');
        $semesters = Semester::where('academic_year_id', $activeAcademicYearId)->orderBy('sequence')->get();

        foreach ($semesters as $semester) {
            $value = $request->input('semester_'.$semester->sequence);
            if ($value === null || $value === '') {
                continue;
            }

            $this->authorize('createForStudent', [StudentGrade::class, $student]);

            StudentGrade::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'teaching_assignment_id' => null,
                    'semester_id' => $semester->id,
                    'subject_id' => null,
                ],
                ['grade' => $value]
            );
        }
    }

    private function studentAccountIsWaitingForAssignment(User $user): bool
    {
        return $user->can(P::STUDENTS_OWN)
            && ! $user->canAny([P::STUDENTS_ALL, P::STUDENTS_ASSIGNED])
            && $user->student_id === null;
    }

    private function redirectWaitingForStudentAssignment()
    {
        return redirect()
            ->route('dashboard')
            ->with('warning', 'Your account is waiting for student record assignment.');
    }
}
