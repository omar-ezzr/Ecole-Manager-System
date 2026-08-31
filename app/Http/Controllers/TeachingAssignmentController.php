<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ClassroomSubject;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentGrade;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Support\SchoolPermissions as P;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TeachingAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', TeachingAssignment::class);

        $query = TeachingAssignment::with([
            'professor',
            'classroom' => fn ($classrooms) => $classrooms->with(['department.school'])->withCount('students'),
            'subject',
            'academicYear',
        ])->latest();

        $viewOwnOnly = $request->user()->can(P::TEACHING_ASSIGNMENTS_VIEW_OWN)
            && ! $request->user()->can(P::TEACHING_ASSIGNMENTS_VIEW_ALL);

        if ($viewOwnOnly) {
            $query->where('professor_id', $request->user()->id);
        }

        foreach (['academic_year_id', 'professor_id', 'classroom_id', 'subject_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->integer($filter));
            }
        }

        $assignments = $query->paginate(20)->withQueryString();

        $professors = User::role(Role::ROLE_PROFESSOR)->orderBy('name');
        $classrooms = Classroom::active()->orderBy('name');
        $subjects = Subject::active()->orderBy('code');
        $academicYears = AcademicYear::orderByDesc('starts_at');

        if ($viewOwnOnly) {
            $ownAssignment = fn ($assignments) => $assignments
                ->where('professor_id', $request->user()->id);
            $professors->whereKey($request->user()->id);
            $classrooms->whereHas('teachingAssignments', $ownAssignment);
            $subjects->whereHas('teachingAssignments', $ownAssignment);
            $academicYears->whereHas('teachingAssignments', $ownAssignment);
        }

        return view('teaching-assignments.index', [
            'assignments' => $assignments,
            'academicYears' => $academicYears->get(),
            'professors' => $professors->get(),
            'classrooms' => $classrooms->get(),
            'subjects' => $subjects->get(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', TeachingAssignment::class);

        return view('teaching-assignments.create', $this->formData());
    }

    public function store(Request $request)
    {
        $this->authorize('create', TeachingAssignment::class);

        TeachingAssignment::create($this->validatedPayload($request));

        return redirect()->route('teaching-assignments.index')->with('success', 'Teaching assignment created successfully.');
    }

    public function show(Request $request, TeachingAssignment $teachingAssignment)
    {
        $this->authorize('view', $teachingAssignment);

        $teachingAssignment->load([
            'professor',
            'subject',
            'academicYear',
            'classroom.department.school',
        ]);

        $students = Student::query()
            ->whereHas('enrollments', fn ($enrollments) => $enrollments
                ->where('classroom_id', $teachingAssignment->classroom_id)
                ->where('academic_year_id', $teachingAssignment->academic_year_id))
            ->with(['enrollments' => fn ($enrollments) => $enrollments
                ->where('classroom_id', $teachingAssignment->classroom_id)
                ->where('academic_year_id', $teachingAssignment->academic_year_id)])
            ->orderBy('student_number')
            ->get();

        $semesters = Semester::query()
            ->where('academic_year_id', $teachingAssignment->academic_year_id)
            ->orderBy('sequence')
            ->get();

        $selectedSemester = $request->filled('semester_id')
            ? $semesters->firstWhere('id', $request->integer('semester_id'))
            : $semesters->first();

        $existingGrades = collect();

        if ($selectedSemester) {
            $existingGrades = StudentGrade::query()
                ->where('teaching_assignment_id', $teachingAssignment->id)
                ->where('semester_id', $selectedSemester->id)
                ->whereIn('student_id', $students->modelKeys())
                ->get()
                ->keyBy('student_id');
        }

        $firstStudent = $students->first();
        $canManageGrades = $selectedSemester
            && $firstStudent
            && $request->user()->can('createForAssignment', [
                StudentGrade::class,
                $teachingAssignment,
                $firstStudent,
                $selectedSemester,
            ]);

        return view('teaching-assignments.show', [
            'assignment' => $teachingAssignment,
            'semesters' => $semesters,
            'selectedSemester' => $selectedSemester,
            'students' => $students,
            'existingGrades' => $existingGrades,
            'canManageGrades' => $canManageGrades,
        ]);
    }

    public function edit(TeachingAssignment $teachingAssignment)
    {
        $this->authorize('update', $teachingAssignment);

        return view('teaching-assignments.edit', array_merge(
            $this->formData(),
            ['assignment' => $teachingAssignment]
        ));
    }

    public function update(Request $request, TeachingAssignment $teachingAssignment)
    {
        $this->authorize('update', $teachingAssignment);

        $teachingAssignment->update($this->validatedPayload($request, $teachingAssignment));

        return redirect()->route('teaching-assignments.index')->with('success', 'Teaching assignment updated successfully.');
    }

    public function destroy(Request $request, TeachingAssignment $teachingAssignment)
    {
        $this->authorize('delete', $teachingAssignment);

        if ($teachingAssignment->grades()->exists()) {
            return $this->referencedParentResponse($request, 'This teaching assignment cannot be deleted because grades already exist for it.');
        }

        try {
            $teachingAssignment->delete();
        } catch (QueryException) {
            return $this->referencedParentResponse($request, 'This teaching assignment cannot be deleted because it is still referenced.');
        }

        return redirect()->route('teaching-assignments.index');
    }

    private function validatedPayload(Request $request, ?TeachingAssignment $teachingAssignment = null): array
    {
        $data = $request->validate([
            'professor_id' => ['required', 'integer', 'exists:users,id'],
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
        ]);

        $professor = User::findOrFail($data['professor_id']);

        if (! $professor->hasRole(Role::ROLE_PROFESSOR)) {
            throw ValidationException::withMessages([
                'professor_id' => 'The selected user is not a Professor.',
            ]);
        }

        validator($data, [
            'professor_id' => [
                Rule::unique('teaching_assignments')
                    ->where(fn ($query) => $query
                        ->where('classroom_id', $data['classroom_id'])
                        ->where('subject_id', $data['subject_id'])
                        ->where('academic_year_id', $data['academic_year_id']))
                    ->ignore($teachingAssignment?->id),
            ],
        ])->validate();

        if (! ClassroomSubject::query()
            ->where('classroom_id', $data['classroom_id'])
            ->where('subject_id', $data['subject_id'])
            ->where('academic_year_id', $data['academic_year_id'])
            ->exists()) {
            throw ValidationException::withMessages([
                'subject_id' => 'Assign this subject to the classroom for the selected academic year before creating a teaching assignment.',
            ]);
        }

        return $data;
    }

    private function formData(): array
    {
        return [
            'professors' => User::role(Role::ROLE_PROFESSOR)->orderBy('name')->get(),
            'classrooms' => Classroom::with('department.school')->orderBy('name')->get(),
            'subjects' => Subject::active()->orderBy('code')->get(),
            'academicYears' => AcademicYear::orderByDesc('starts_at')->get(),
        ];
    }

    private function referencedParentResponse(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], Response::HTTP_CONFLICT);
        }

        return back()->withErrors(['delete' => $message]);
    }
}
