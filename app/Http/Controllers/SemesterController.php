<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Semester;
use App\Support\SchoolPermissions as P;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SemesterController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Semester::class);

        $query = Semester::with('academicYear')->orderBy('academic_year_id')->orderBy('sequence');
        $academicYearsQuery = AcademicYear::query()->orderByDesc('starts_at');

        if ($request->user()->can(P::TEACHING_ASSIGNMENTS_VIEW_OWN)
            && ! $request->user()->can(P::TEACHING_ASSIGNMENTS_VIEW_ALL)) {
            $scopeAssignments = fn ($assignments) => $assignments
                ->where('professor_id', $request->user()->id);
            $query->whereHas('academicYear.teachingAssignments', $scopeAssignments);
            $academicYearsQuery->whereHas('teachingAssignments', $scopeAssignments);
        } elseif ($request->user()->can(P::GRADES_OWN)) {
            $studentId = $request->user()->student_id ?? 0;
            $query->whereHas('grades', fn ($grades) => $grades->where('student_id', $studentId));
            $academicYearsQuery->whereHas('semesters.grades', fn ($grades) => $grades->where('student_id', $studentId));
        }

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->integer('academic_year_id'));
        }

        $semesters = $query->paginate(20)->withQueryString();
        $academicYears = $academicYearsQuery->get();

        return view('semesters.index', compact('semesters', 'academicYears'));
    }

    public function create()
    {
        $this->authorize('create', Semester::class);

        return view('semesters.create', [
            'academicYears' => AcademicYear::orderByDesc('starts_at')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Semester::class);

        Semester::create($this->validatedPayload($request));

        return redirect()->route('semesters.index')->with('success', 'Semester created successfully.');
    }

    public function show(Semester $semester)
    {
        $this->authorize('view', $semester);

        $semester->load('academicYear');

        return view('semesters.show', compact('semester'));
    }

    public function edit(Semester $semester)
    {
        $this->authorize('update', $semester);

        return view('semesters.edit', [
            'semester' => $semester,
            'academicYears' => AcademicYear::orderByDesc('starts_at')->get(),
        ]);
    }

    public function update(Request $request, Semester $semester)
    {
        $this->authorize('update', $semester);

        $semester->update($this->validatedPayload($request, $semester));

        return redirect()->route('semesters.index')->with('success', 'Semester updated successfully.');
    }

    public function destroy(Request $request, Semester $semester)
    {
        $this->authorize('delete', $semester);

        if ($semester->grades()->exists()) {
            return $this->referencedParentResponse($request, 'This semester cannot be deleted because it is still referenced.');
        }

        try {
            $semester->delete();
        } catch (QueryException) {
            return $this->referencedParentResponse($request, 'This semester cannot be deleted because it is still referenced.');
        }

        return redirect()->route('semesters.index');
    }

    private function validatedPayload(Request $request, ?Semester $semester = null): array
    {
        $data = $request->validate([
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('semesters', 'name')
                    ->where(fn ($query) => $query->where('academic_year_id', $request->input('academic_year_id')))
                    ->ignore($semester?->id),
            ],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'sequence' => ['required', 'integer', 'min:1'],
        ]);

        $academicYear = AcademicYear::findOrFail($data['academic_year_id']);

        if ($data['starts_at'] < $academicYear->starts_at->toDateString() || $data['ends_at'] > $academicYear->ends_at->toDateString()) {
            throw ValidationException::withMessages([
                'starts_at' => 'Semester dates must stay within the academic year range.',
            ]);
        }

        return [
            'academic_year_id' => $academicYear->id,
            'name' => strip_tags($data['name']),
            'code' => 'semester_'.(int) $data['sequence'],
            'position' => (int) $data['sequence'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'sequence' => (int) $data['sequence'],
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
