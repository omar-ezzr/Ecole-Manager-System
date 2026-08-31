<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentGrade;
use App\Support\SchoolPermissions as P;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $visibleStudents = Student::visibleTo($user);
        $visibleClassrooms = Classroom::query();

        if (! $user->can(P::CLASSROOMS_ALL)) {
            if ($user->can(P::CLASSROOMS_ASSIGNED)) {
                $visibleClassrooms->whereHas('professors', fn ($professors) => $professors
                    ->whereKey($user->id));
            } else {
                $visibleClassrooms->whereIn('id', (clone $visibleStudents)->select('classroom_id'));
            }
        }

        return view('dashboard', array_merge([
            'totalStudents' => (clone $visibleStudents)->count(),
            'totalClassrooms' => (clone $visibleClassrooms)->count(),
            'totalDepartments' => $user->can(P::DEPARTMENTS_VIEW)
                ? Department::count()
                : Department::whereHas('classrooms', fn ($classrooms) => $classrooms
                    ->whereIn('classrooms.id', (clone $visibleClassrooms)->select('classrooms.id')))->count(),
            'totalSchools' => $user->can(P::SCHOOLS_VIEW)
                ? School::count()
                : School::whereHas('departments.classrooms', fn ($classrooms) => $classrooms
                    ->whereIn('classrooms.id', (clone $visibleClassrooms)->select('classrooms.id')))->count(),
            'semesterAverageCharts' => $this->semesterAverageCharts(),
        ], $this->attendanceReportContext($request)));
    }

    /**
     * @return array{
     *     canViewAttendanceReporting: bool,
     *     attendanceAcademicYears: Collection,
     *     attendanceClassrooms: Collection,
     *     selectedAttendanceYear: ?AcademicYear,
     *     selectedAttendanceClassroom: ?Classroom,
     *     attendanceSummary: ?array
     * }
     */
    private function attendanceReportContext(Request $request): array
    {
        $user = $request->user();
        $canViewAttendanceReporting = $user->canAny([
            P::ATTENDANCE_VIEW_ALL,
            P::ATTENDANCE_VIEW_ASSIGNED,
        ]);

        if (! $canViewAttendanceReporting) {
            abort_if(
                $request->filled('attendance_academic_year_id') || $request->filled('attendance_classroom_id'),
                403
            );

            return [
                'canViewAttendanceReporting' => false,
                'attendanceAcademicYears' => collect(),
                'attendanceClassrooms' => collect(),
                'selectedAttendanceYear' => null,
                'selectedAttendanceClassroom' => null,
                'attendanceSummary' => null,
            ];
        }

        $academicYears = AcademicYear::query()
            ->reportableForAttendance($user)
            ->orderByDesc('starts_at')
            ->get();
        $selectedYear = $request->filled('attendance_academic_year_id')
            ? $academicYears->firstWhere('id', $request->integer('attendance_academic_year_id'))
            : ($academicYears->firstWhere('is_active', true) ?? $academicYears->first());

        abort_if($request->filled('attendance_academic_year_id') && ! $selectedYear, 403);

        $classrooms = collect();
        $selectedClassroom = null;
        $summary = AttendanceRecord::summarize(AttendanceRecord::query()->whereRaw('1 = 0'));

        if ($selectedYear) {
            $classrooms = Classroom::query()
                ->whereHas('studentEnrollments', fn ($enrollments) => $enrollments
                    ->where('academic_year_id', $selectedYear->id))
                ->when(! $user->can(P::ATTENDANCE_VIEW_ALL), fn ($query) => $query
                    ->whereHas('teachingAssignments', fn ($assignments) => $assignments
                        ->where('professor_id', $user->id)
                        ->where('academic_year_id', $selectedYear->id)))
                ->orderBy('name')
                ->get();
            $selectedClassroom = $request->filled('attendance_classroom_id')
                ? $classrooms->firstWhere('id', $request->integer('attendance_classroom_id'))
                : null;

            abort_if($request->filled('attendance_classroom_id') && ! $selectedClassroom, 403);

            $attendanceQuery = AttendanceRecord::query()
                ->visibleTo($user)
                ->forAcademicYear($selectedYear)
                ->when($selectedClassroom, fn ($query) => $query->forClassroom($selectedClassroom));
            $summary = AttendanceRecord::summarize($attendanceQuery);
        }

        return [
            'canViewAttendanceReporting' => true,
            'attendanceAcademicYears' => $academicYears,
            'attendanceClassrooms' => $classrooms,
            'selectedAttendanceYear' => $selectedYear,
            'selectedAttendanceClassroom' => $selectedClassroom,
            'attendanceSummary' => $summary,
        ];
    }

    private function semesterAverageCharts(): array
    {
        return collect([1, 2])
            ->mapWithKeys(fn (int $position) => [$position => $this->semesterAverageChart($position)])
            ->all();
    }

    private function semesterAverageChart(int $position): array
    {
        $user = auth()->user();
        $activeAcademicYearId = AcademicYear::active()->value('id');
        $query = StudentGrade::query()
            ->join('semesters', 'student_grades.semester_id', '=', 'semesters.id')
            ->join('students', 'student_grades.student_id', '=', 'students.id')
            ->join('classrooms', 'students.classroom_id', '=', 'classrooms.id')
            ->where('semesters.position', $position)
            ->where('semesters.academic_year_id', $activeAcademicYearId ?? 0)
            ->whereIn('students.id', Student::visibleTo($user)->select('students.id'))
            ->whereNotNull('student_grades.grade')
            ->select([
                'student_grades.*',
                'classrooms.id as result_classroom_id',
                'classrooms.name as result_classroom_name',
            ]);

        if ($user->can(P::GRADES_ALL) || $user->can(P::GRADES_OWN)) {
            $query->whereNull('student_grades.teaching_assignment_id')
                ->whereNull('student_grades.subject_id');
        } elseif ($user->can(P::GRADES_ASSIGNED)) {
            $query->join('teaching_assignments', 'student_grades.teaching_assignment_id', '=', 'teaching_assignments.id')
                ->where('teaching_assignments.professor_id', $user->id)
                ->whereColumn('teaching_assignments.classroom_id', 'students.classroom_id')
                ->whereColumn('teaching_assignments.academic_year_id', 'semesters.academic_year_id')
                ->whereColumn('teaching_assignments.subject_id', 'student_grades.subject_id');
        } else {
            $query->whereRaw('1 = 0');
        }

        $classroomAverages = $query->get()
            ->groupBy('result_classroom_id')
            ->map(function (Collection $grades): array {
                return [
                    'label' => $grades->first()->result_classroom_name,
                    'average_grade' => StudentGrade::weightedAverage($grades),
                ];
            })
            ->filter(fn (array $result) => $result['average_grade'] !== null)
            ->sortBy('label')
            ->values();

        return [
            'labels' => $classroomAverages->pluck('label')->all(),
            'data' => $classroomAverages->pluck('average_grade')->map(fn ($grade) => (float) $grade)->all(),
            'summary' => $classroomAverages->isEmpty() ? null : round((float) $classroomAverages->avg('average_grade'), 2),
            'groupedBy' => 'Classroom',
        ];
    }
}
