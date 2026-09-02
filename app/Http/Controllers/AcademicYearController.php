<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Semester;
use Carbon\CarbonImmutable;
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

        $user = $request->user();

        if ($user->isProfessor()) {
            $query->whereHas('teachingAssignments', fn ($assignments) => $assignments
                ->where('professor_id', $user->id));
        } elseif ($user->isStudentUser()) {
            $query->whereHas('semesters.grades', fn ($grades) => $grades
                ->where('student_id', $user->student_id ?? 0));
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

            $academicYear = AcademicYear::create($data);
            $this->ensureTwoSemesters($academicYear);
        });

        return redirect()->route('academic-years.index')->with('success', 'Academic year created successfully.');
    }

    public function show(AcademicYear $academicYear)
    {
        $this->authorize('view', $academicYear);

        $academicYear->load(['semesters' => fn ($semesters) => $semesters->orderBy('sequence')]);
        $classrooms = Classroom::query()
            ->with('department')
            ->withCount([
                'studentEnrollments as enrolled_students_count' => fn ($enrollments) => $enrollments->where('academic_year_id', $academicYear->id),
                'classroomSubjects as assigned_subjects_count' => fn ($subjects) => $subjects->where('academic_year_id', $academicYear->id),
            ])
            ->where(function ($query) use ($academicYear): void {
                $query->where('is_active', true)
                    ->orWhereHas('studentEnrollments', fn ($enrollments) => $enrollments->where('academic_year_id', $academicYear->id))
                    ->orWhereHas('classroomSubjects', fn ($subjects) => $subjects->where('academic_year_id', $academicYear->id))
                    ->orWhereHas('teachingAssignments', fn ($assignments) => $assignments->where('academic_year_id', $academicYear->id));
            })
            ->orderBy('name')
            ->get();

        return view('academic-years.show', compact('academicYear', 'classrooms'));
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
            $this->ensureTwoSemesters($academicYear->fresh());
        });

        return redirect()->route('academic-years.index')->with('success', 'Academic year updated successfully.');
    }

    public function destroy(Request $request, AcademicYear $academicYear)
    {
        $this->authorize('delete', $academicYear);

        if ($academicYear->teachingAssignments()->exists()
            || $academicYear->studentEnrollments()->exists()
            || $academicYear->classroomSubjects()->exists()
            || $academicYear->semesters()->whereHas('grades')->exists()) {
            return $this->referencedParentResponse($request, 'This academic year cannot be deleted because it contains academic history.');
        }

        try {
            $academicYear->semesters()->delete();
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

    private function ensureTwoSemesters(AcademicYear $academicYear): void
    {
        $start = CarbonImmutable::parse($academicYear->starts_at);
        $end = CarbonImmutable::parse($academicYear->ends_at);
        $midpoint = $start->addDays((int) floor($start->diffInDays($end) / 2));
        $ranges = [
            1 => [$start, $midpoint],
            2 => [$midpoint->addDay(), $end],
        ];

        foreach ([1, 2] as $sequence) {
            $semester = Semester::firstOrNew([
                'academic_year_id' => $academicYear->id,
                'sequence' => $sequence,
            ]);

            $semester->fill([
                'name' => 'Semester '.$sequence,
                'code' => 'year_'.$academicYear->id.'_semester_'.$sequence,
                'position' => $sequence,
                'starts_at' => $ranges[$sequence][0]->toDateString(),
                'ends_at' => $ranges[$sequence][1]->toDateString(),
            ])->save();
        }
    }

    private function referencedParentResponse(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], Response::HTTP_CONFLICT);
        }

        return back()->withErrors(['delete' => $message]);
    }
}
