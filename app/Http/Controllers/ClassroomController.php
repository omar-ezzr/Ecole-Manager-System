<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ClassroomSubject;
use App\Models\Department;
use App\Models\Subject;
use App\Support\SchoolPermissions as P;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ClassroomController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Classroom::class);

        $classrooms = $request->user()->can(P::CLASSROOMS_ALL)
            ? Classroom::active()->with('department')->orderBy('name')->get()
            : $request->user()->assignedClassrooms()->where('is_active', true)->orderBy('name')->get();

        return view('classrooms.index', compact('classrooms'));
    }

    public function create()
    {
        $this->authorize('create', Classroom::class);

        return view('classrooms.create', ['departments' => Department::all()]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Classroom::class);

        $request->validate([
            'name' => ['required', 'string', 'max:255'], 'address' => ['nullable', 'string', 'max:255'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
        ]);

        Classroom::create([
            'name' => strip_tags($request->input('name')),
            'address' => strip_tags($request->input('address')),
            'department_id' => strip_tags($request->input('department_id')),
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Classroom created successfully!');
    }

    public function show(string $id)
    {
        $classroom = Classroom::with(['department', 'students' => fn ($students) => $students->orderBy('student_number')])->findOrFail($id);
        $this->authorize('view', $classroom);
        $activeAcademicYear = AcademicYear::active()->first();
        $selectedAcademicYear = request()->filled('academic_year_id')
            ? AcademicYear::findOrFail(request()->integer('academic_year_id'))
            : $activeAcademicYear;
        $academicYears = AcademicYear::orderByDesc('starts_at')->get();
        $assignedSubjects = $selectedAcademicYear
            ? $classroom->classroomSubjects()->with(['subject', 'academicYear'])->where('academic_year_id', $selectedAcademicYear->id)->orderBy('subject_id')->get()
            : collect();
        $availableSubjects = $selectedAcademicYear
            ? Subject::active()->whereDoesntHave('classroomSubjects', fn ($subjects) => $subjects->where('classroom_id', $classroom->id)->where('academic_year_id', $selectedAcademicYear->id))->orderBy('code')->get()
            : collect();
        $teachingAssignments = $selectedAcademicYear
            ? $classroom->teachingAssignments()->with(['professor', 'subject', 'academicYear'])->where('academic_year_id', $selectedAcademicYear->id)->get()
            : collect();

        return view('classrooms.show', compact('classroom', 'activeAcademicYear', 'selectedAcademicYear', 'academicYears', 'assignedSubjects', 'availableSubjects', 'teachingAssignments'));
    }

    public function edit(string $id)
    {
        $classroom = Classroom::findOrFail($id);
        $this->authorize('update', $classroom);

        return view('classrooms.edite', [
            'classroom' => $classroom,
            'departments' => Department::all(),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $classroom = Classroom::findOrFail($id);
        $this->authorize('update', $classroom);

        $request->validate([
            'name' => ['required'],
            'address' => ['required'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
        ]);

        $classroom->update([
            'name' => strip_tags($request->input('name')),
            'address' => strip_tags($request->input('address')),
            'department_id' => strip_tags($request->input('department_id')),
        ]);

        return redirect()->back()->with('success', 'Classroom updated successfully!');
    }

    public function destroy(string $id)
    {
        $classroom = Classroom::findOrFail($id);
        $this->authorize('delete', $classroom);

        if ($classroom->students()->exists()
            || $classroom->studentEnrollments()->exists()
            || $classroom->teachingAssignments()->exists()
            || $classroom->classroomSubjects()->exists()) {
            $classroom->update(['is_active' => false]);

            return redirect('classrooms')->with('warning', 'This classroom has academic history and cannot be deleted. It has been archived instead.');
        }

        try {
            $classroom->delete();
        } catch (QueryException) {
            return $this->referencedParentResponse(request(), 'This classroom cannot be deleted because it is still referenced.');
        }

        return redirect('classrooms');
    }

    public function assignSubject(Request $request, Classroom $classroom)
    {
        $this->authorize('update', $classroom);

        $data = $request->validate([
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
        ]);

        $subject = Subject::active()->findOrFail($data['subject_id']);

        ClassroomSubject::firstOrCreate([
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'academic_year_id' => (int) $data['academic_year_id'],
        ]);

        return redirect()
            ->route('classrooms.show', ['classroom' => $classroom, 'academic_year_id' => $data['academic_year_id']])
            ->with('success', 'Subject assigned to classroom successfully.');
    }

    public function removeSubject(Request $request, Classroom $classroom, ClassroomSubject $classroomSubject)
    {
        $this->authorize('update', $classroom);
        abort_unless($classroomSubject->classroom_id === $classroom->id, 404);

        $academicYearId = $classroomSubject->academic_year_id;
        $classroomSubject->delete();

        return redirect()
            ->route('classrooms.show', ['classroom' => $classroom, 'academic_year_id' => $academicYearId])
            ->with('success', 'Subject assignment removed. Historical grades remain available.');
    }

    private function referencedParentResponse(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], Response::HTTP_CONFLICT);
        }

        return back()->withErrors(['delete' => $message]);
    }
}
