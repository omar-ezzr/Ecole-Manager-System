<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Support\SchoolPermissions as P;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AcademicYearController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', AcademicYear::class);

        $query = AcademicYear::query()->orderByDesc('starts_at');

        if ($request->user()->can(P::TEACHING_ASSIGNMENTS_VIEW_OWN)
            && ! $request->user()->can(P::TEACHING_ASSIGNMENTS_VIEW_ALL)) {
            $query->whereHas('teachingAssignments', fn ($assignments) => $assignments
                ->where('professor_id', $request->user()->id));
        } elseif ($request->user()->can(P::GRADES_OWN)) {
            $query->whereHas('semesters.grades', fn ($grades) => $grades
                ->where('student_id', $request->user()->student_id ?? 0));
        }

        $academicYears = $query->paginate(20);

        return view('academic-years.index', compact('academicYears'));
    }

    public function create()
    {
        $this->authorize('create', AcademicYear::class);

        return view('academic-years.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', AcademicYear::class);

        $data = $this->validatedPayload($request);

        DB::transaction(function () use ($data): void {
            if ($data['is_active']) {
                AcademicYear::query()->update(['is_active' => false]);
            }

            AcademicYear::create($data);
        });

        return redirect()->route('academic-years.index')->with('success', 'Academic year created successfully.');
    }

    public function show(AcademicYear $academicYear)
    {
        $this->authorize('view', $academicYear);

        $academicYear->load('semesters');

        return view('academic-years.show', compact('academicYear'));
    }

    public function edit(AcademicYear $academicYear)
    {
        $this->authorize('update', $academicYear);

        return view('academic-years.edit', compact('academicYear'));
    }

    public function update(Request $request, AcademicYear $academicYear)
    {
        $this->authorize('update', $academicYear);

        $data = $this->validatedPayload($request, $academicYear);

        DB::transaction(function () use ($academicYear, $data): void {
            if ($data['is_active']) {
                AcademicYear::whereKeyNot($academicYear->id)->update(['is_active' => false]);
            }

            $academicYear->update($data);
        });

        return redirect()->route('academic-years.index')->with('success', 'Academic year updated successfully.');
    }

    public function destroy(Request $request, AcademicYear $academicYear)
    {
        $this->authorize('delete', $academicYear);

        if ($academicYear->semesters()->exists() || $academicYear->teachingAssignments()->exists()) {
            return $this->referencedParentResponse($request, 'This academic year cannot be deleted because it is still referenced.');
        }

        try {
            $academicYear->delete();
        } catch (QueryException) {
            return $this->referencedParentResponse($request, 'This academic year cannot be deleted because it is still referenced.');
        }

        return redirect()->route('academic-years.index');
    }

    public function activate(AcademicYear $academicYear)
    {
        $this->authorize('update', $academicYear);

        DB::transaction(function () use ($academicYear): void {
            AcademicYear::query()->update(['is_active' => false]);
            $academicYear->update(['is_active' => true]);
        });

        return redirect()->route('academic-years.index')->with('success', 'Academic year activated successfully.');
    }

    private function validatedPayload(Request $request, ?AcademicYear $academicYear = null): array
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('academic_years', 'name')->ignore($academicYear?->id),
            ],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return [
            'name' => strip_tags($data['name']),
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'is_active' => (bool) ($data['is_active'] ?? false),
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
