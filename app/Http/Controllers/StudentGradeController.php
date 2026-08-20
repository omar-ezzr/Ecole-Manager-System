<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentGrade;
use App\Models\TeachingAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StudentGradeController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', StudentGrade::class);

        if ($request->user()->isStudentUser() && $request->user()->student_id) {
            return redirect()->route('student-grades.results', $request->user()->student_id);
        }

        if ($request->user()->isProfessor()) {
            return redirect()->route('teaching-assignments.index');
        }

        $students = Student::query()
            ->with('classroom.department.school')
            ->whereHas('grades', fn ($grades) => $grades->whereNotNull('teaching_assignment_id'))
            ->orderBy('student_number')
            ->paginate(20);

        return view('student-grades.index', compact('students'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'teaching_assignment_id' => ['required', 'integer', 'exists:teaching_assignments,id'],
            'semester_id' => ['required', 'integer', 'exists:semesters,id'],
            'grades' => ['required', 'array'],
            'grades.*.student_id' => ['required', 'integer', 'exists:students,id'],
            'grades.*.grade' => ['nullable', 'numeric', 'min:0'],
            'grades.*.type' => ['nullable', 'string', 'max:255'],
            'grades.*.coefficient' => ['nullable', 'numeric', 'gt:0'],
        ]);

        $assignment = TeachingAssignment::with('subject')->findOrFail($request->integer('teaching_assignment_id'));
        $semester = Semester::findOrFail($request->integer('semester_id'));
        $rows = collect($request->input('grades'));
        $students = Student::whereIn('id', $rows->pluck('student_id')->all())->get()->keyBy('id');

        abort_if($rows->isEmpty(), 422, 'At least one grade row is required.');

        foreach ($rows as $row) {
            $student = $students->get((int) $row['student_id']);
            abort_if(! $student, 422, 'A selected student does not exist.');

            $this->authorize('createForAssignment', [StudentGrade::class, $assignment, $student, $semester]);
        }

        DB::transaction(function () use ($rows, $students, $assignment, $semester): void {
            foreach ($rows as $row) {
                $grade = $row['grade'] ?? null;

                if ($grade === null || $grade === '') {
                    continue;
                }

                $student = $students->get((int) $row['student_id']);

                StudentGrade::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'teaching_assignment_id' => $assignment->id,
                        'semester_id' => $semester->id,
                    ],
                    [
                        'subject_id' => $assignment->subject_id,
                        'grade' => $grade,
                        'type' => filled($row['type'] ?? null) ? strip_tags((string) $row['type']) : 'Exam',
                        'coefficient' => $row['coefficient'] ?? 1,
                    ]
                );
            }
        });

        return redirect()
            ->route('teaching-assignments.show', ['teaching_assignment' => $assignment->id, 'semester_id' => $semester->id])
            ->with('success', 'Grades saved successfully.');
    }

    public function results(Request $request, Student $student)
    {
        $this->authorize('view', $student);

        $visibleGrades = StudentGrade::query()
            ->with(['student', 'semester', 'subject', 'teachingAssignment.professor'])
            ->where('student_id', $student->id)
            ->whereNotNull('teaching_assignment_id')
            ->orderBy('semester_id')
            ->get()
            ->filter(fn (StudentGrade $grade) => $request->user()->can('view', $grade))
            ->values();

        $availableYears = AcademicYear::query()
            ->whereIn('id', $visibleGrades->pluck('semester.academic_year_id')->filter()->unique())
            ->orderByDesc('starts_at')
            ->get();

        $selectedYear = $request->filled('academic_year_id')
            ? $availableYears->firstWhere('id', $request->integer('academic_year_id'))
            : ($availableYears->first() ?? AcademicYear::active()->first());

        $grades = $selectedYear
            ? $visibleGrades->where('semester.academic_year_id', $selectedYear->id)->values()
            : collect();

        $semesterResults = $grades
            ->groupBy('semester_id')
            ->map(function (Collection $semesterGrades) {
                $totalWeight = $semesterGrades->sum('coefficient');
                $weighted = $semesterGrades->sum(fn (StudentGrade $grade) => (float) $grade->grade * (float) $grade->coefficient);

                return [
                    'semester' => $semesterGrades->first()->semester,
                    'grades' => $semesterGrades,
                    'average' => $totalWeight > 0 ? round($weighted / $totalWeight, 2) : null,
                ];
            })
            ->sortBy(fn (array $entry) => $entry['semester']->sequence)
            ->values();

        return view('student-grades.results', [
            'student' => $student->load('classroom.department.school'),
            'availableYears' => $availableYears,
            'selectedYear' => $selectedYear,
            'semesterResults' => $semesterResults,
        ]);
    }

    public function reportCard(Student $student, Semester $semester)
    {
        $this->authorize('view', $student);
        $this->authorize('view', $semester);

        $grades = StudentGrade::query()
            ->with(['student', 'semester', 'subject', 'teachingAssignment.professor'])
            ->where('student_id', $student->id)
            ->where('semester_id', $semester->id)
            ->whereNotNull('teaching_assignment_id')
            ->get()
            ->filter(fn (StudentGrade $grade) => request()->user()->can('view', $grade))
            ->values();

        $totalWeight = $grades->sum('coefficient');
        $weightedAverage = $totalWeight > 0
            ? round($grades->sum(fn (StudentGrade $grade) => (float) $grade->grade * (float) $grade->coefficient) / $totalWeight, 2)
            : null;

        return view('student-grades.report-card', [
            'student' => $student->load('classroom.department.school'),
            'semester' => $semester->load('academicYear'),
            'grades' => $grades,
            'weightedAverage' => $weightedAverage,
        ]);
    }
}
