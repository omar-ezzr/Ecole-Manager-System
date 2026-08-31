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
use Illuminate\Validation\ValidationException;

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

        $academicYears = AcademicYear::orderByDesc('starts_at')->get();
        $selectedYear = $request->filled('academic_year_id')
            ? $academicYears->firstWhere('id', $request->integer('academic_year_id'))
            : ($academicYears->firstWhere('is_active', true) ?? $academicYears->first());
        $classrooms = $selectedYear
            ? \App\Models\Classroom::query()
                ->where(function ($query) use ($selectedYear): void {
                    $query->where('is_active', true)
                        ->orWhereHas('teachingAssignments', fn ($assignments) => $assignments->where('academic_year_id', $selectedYear->id))
                        ->orWhereHas('studentEnrollments', fn ($enrollments) => $enrollments->where('academic_year_id', $selectedYear->id));
                })
                ->orderBy('name')
                ->get()
            : collect();
        $selectedClassroom = $request->filled('classroom_id')
            ? $classrooms->firstWhere('id', $request->integer('classroom_id'))
            : null;

        $students = Student::query()
            ->with('classroom.department.school')
            ->whereHas('grades', function ($grades) use ($selectedYear, $selectedClassroom): void {
                $grades->whereNotNull('teaching_assignment_id')
                    ->when($selectedYear, fn ($query) => $query->forAcademicYear($selectedYear))
                    ->when($selectedClassroom, fn ($query) => $query->whereHas('teachingAssignment', fn ($assignments) => $assignments->where('classroom_id', $selectedClassroom->id)));
            })
            ->orderBy('student_number')
            ->paginate(20)
            ->withQueryString();

        return view('student-grades.index', compact('students', 'academicYears', 'selectedYear', 'classrooms', 'selectedClassroom'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'teaching_assignment_id' => ['required', 'integer', 'exists:teaching_assignments,id'],
            'semester_id' => ['required', 'integer', 'exists:semesters,id'],
            'grades' => ['required', 'array', 'min:1'],
            'grades.*.student_id' => ['required', 'integer', 'distinct', 'exists:students,id'],
            'grades.*.grade' => [
                'nullable',
                'numeric',
                'between:'.StudentGrade::MIN_GRADE.','.StudentGrade::MAX_GRADE,
            ],
            'grades.*.type' => ['nullable', 'string', 'max:255'],
            'grades.*.coefficient' => ['nullable', 'numeric', 'gt:0'],
        ]);

        $assignment = TeachingAssignment::with('subject')->findOrFail($data['teaching_assignment_id']);
        $semester = Semester::findOrFail($data['semester_id']);
        $rows = collect($data['grades']);
        $students = Student::query()
            ->whereIn('id', $rows->pluck('student_id')->all())
            ->with(['enrollments' => fn ($enrollments) => $enrollments
                ->where('classroom_id', $assignment->classroom_id)
                ->where('academic_year_id', $assignment->academic_year_id)])
            ->get()
            ->keyBy('id');
        $existingGrades = StudentGrade::query()
            ->where('teaching_assignment_id', $assignment->id)
            ->where('semester_id', $semester->id)
            ->whereIn('student_id', $students->keys())
            ->get()
            ->keyBy('student_id');

        foreach ($rows as $index => $row) {
            $student = $students->get((int) $row['student_id']);
            abort_if(! $student, 422, 'A selected student does not exist.');

            $existingGrade = $existingGrades->get($student->id);

            if ($existingGrade) {
                $this->authorize('update', $existingGrade);
            } else {
                $this->authorize('createForAssignment', [StudentGrade::class, $assignment, $student, $semester]);
            }

            $this->validateAcademicContext($assignment, $student, $semester, $index);
        }

        DB::transaction(function () use ($rows, $students, $existingGrades, $assignment, $semester): void {
            foreach ($rows as $row) {
                $grade = $row['grade'] ?? null;

                if ($grade === null || $grade === '') {
                    continue;
                }

                $student = $students->get((int) $row['student_id']);
                $values = [
                    'grade' => $grade,
                    'type' => filled($row['type'] ?? null) ? strip_tags((string) $row['type']) : 'Exam',
                    'coefficient' => $row['coefficient'] ?? 1,
                ];
                $existingGrade = $existingGrades->get($student->id);

                if ($existingGrade) {
                    $existingGrade->update($values);
                } else {
                    StudentGrade::create(array_merge(
                        $values,
                        [
                            'student_id' => $student->id,
                            'teaching_assignment_id' => $assignment->id,
                            'semester_id' => $semester->id,
                        ]
                    ));
                }
            }
        });

        return redirect()
            ->route('teaching-assignments.show', ['teaching_assignment' => $assignment->id, 'semester_id' => $semester->id])
            ->with('success', 'Grades saved successfully.');
    }

    public function results(Request $request, Student $student)
    {
        $this->authorize('view', $student);

        $visibleGrades = $this->visibleResultGrades($request, $student);

        $availableYears = AcademicYear::query()
            ->whereIn('id', $visibleGrades->pluck('semester.academic_year_id')->filter()->unique())
            ->orderByDesc('starts_at')
            ->get();

        $selectedYear = $request->filled('academic_year_id')
            ? $availableYears->firstWhere('id', $request->integer('academic_year_id'))
            : ($availableYears->first() ?? AcademicYear::active()->first());

        $grades = $selectedYear
            ? $this->visibleResultGrades($request, $student, $selectedYear)
            : collect();

        $semesterResults = $grades
            ->groupBy('semester_id')
            ->map(function (Collection $semesterGrades) {
                return [
                    'semester' => $semesterGrades->first()->semester,
                    'grades' => $semesterGrades,
                    'average' => StudentGrade::weightedAverage($semesterGrades),
                ];
            })
            ->sortBy(fn (array $entry) => $entry['semester']->sequence)
            ->values();

        return view('student-grades.results', [
            'student' => $student->load('classroom.department.school'),
            'historicalClassroom' => $this->historicalClassroom($student, $selectedYear, $grades),
            'availableYears' => $availableYears,
            'selectedYear' => $selectedYear,
            'semesterResults' => $semesterResults,
        ]);
    }

    public function reportCard(Request $request, Student $student, Semester $semester)
    {
        $this->authorize('view', $student);
        $this->authorize('view', $semester);

        $semester->load('academicYear');
        $grades = $this->visibleResultGrades($request, $student, $semester->academicYear, $semester);

        return view('student-grades.report-card', [
            'student' => $student->load('classroom.department.school'),
            'historicalClassroom' => $this->historicalClassroom($student, $semester->academicYear, $grades),
            'semester' => $semester,
            'grades' => $grades,
            'weightedAverage' => StudentGrade::weightedAverage($grades),
        ]);
    }

    private function validateAcademicContext(
        TeachingAssignment $assignment,
        Student $student,
        Semester $semester,
        int|string $rowIndex
    ): void {
        $errors = [];

        if (! $student->hasEnrollmentFor($assignment->classroom_id, $assignment->academic_year_id)) {
            $errors["grades.{$rowIndex}.student_id"] = 'The selected student was not enrolled in the assigned classroom for this academic year.';
        }

        if ($semester->academic_year_id !== $assignment->academic_year_id) {
            $errors['semester_id'] = 'The selected semester does not belong to the teaching assignment academic year.';
        }

        if ($assignment->subject?->is_active !== true) {
            $errors['teaching_assignment_id'] = 'The teaching assignment subject is inactive.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function historicalClassroom(Student $student, ?AcademicYear $academicYear, Collection $grades): ?\App\Models\Classroom
    {
        if (! $academicYear) {
            return null;
        }

        $enrollmentClassroom = $student->enrollments()
            ->with('classroom.department.school')
            ->where('academic_year_id', $academicYear->id)
            ->orderBy('enrolled_at')
            ->first()?->classroom;

        return $enrollmentClassroom
            ?? $grades->first()?->teachingAssignment?->classroom;
    }

    /**
     * @return Collection<int, StudentGrade>
     */
    private function visibleResultGrades(
        Request $request,
        Student $student,
        ?AcademicYear $academicYear = null,
        ?Semester $semester = null
    ): Collection {
        return StudentGrade::query()
            ->with(['student', 'semester.academicYear', 'subject', 'teachingAssignment.professor', 'teachingAssignment.classroom.department.school'])
            ->forAcademicResults($student)
            ->when($academicYear, fn ($grades) => $grades->forAcademicYear($academicYear))
            ->when($semester, fn ($grades) => $grades->forSemester($semester))
            ->orderBy('semester_id')
            ->orderBy('subject_id')
            ->get()
            ->filter(fn (StudentGrade $grade) => $request->user()->can('view', $grade))
            ->values();
    }
}
