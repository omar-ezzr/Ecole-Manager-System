<?php

namespace App\Http\Controllers;

use App\Models\HealthRecord;
use App\Models\Student;
use App\Support\SchoolPermissions as P;
use Illuminate\Http\Request;

class HealthRecordController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', HealthRecord::class);

        $query = HealthRecord::with('student')
            ->whereIn('student_id', Student::visibleTo($request->user())->select('students.id'));

        if ($request->filled('student_number')) {
            $query->whereHas('student', fn ($student) => $student->where('student_number', 'like', '%'.$request->student_number.'%'));
        }

        if ($request->filled('last_name')) {
            $query->whereHas('student', fn ($student) => $student->where('last_name', 'like', '%'.$request->last_name.'%'));
        }

        if ($request->filled('first_name')) {
            $query->whereHas('student', fn ($student) => $student->where('first_name', 'like', '%'.$request->first_name.'%'));
        }

        $healthRecords = $query->orderBy('id')->paginate(30)->withQueryString();

        return view('health-records.index', compact('healthRecords'));
    }

    public function create()
    {
        $this->authorize('create', HealthRecord::class);

        return view('health-records.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', HealthRecord::class);

        $validated = $this->validatedPayload($request);
        HealthRecord::create($validated);

        return redirect()->back()->with('success', 'Health record created successfully!');
    }

    public function show(string $id)
    {
        $healthRecord = HealthRecord::with('student')->findOrFail($id);
        $this->authorize('view', $healthRecord);

        return view('health-records.show', ['healthRecord' => $healthRecord]);
    }

    public function edit(string $id)
    {
        $healthRecord = HealthRecord::with('student')->findOrFail($id);
        $this->authorize('update', $healthRecord);

        return view('health-records.edite', ['healthRecord' => $healthRecord]);
    }

    public function update(Request $request, string $id)
    {
        $healthRecord = HealthRecord::findOrFail($id);
        $this->authorize('update', $healthRecord);
        $healthRecord->update($this->validatedPayload($request));

        return redirect()->route('health-records.index');
    }

    public function destroy(string $id)
    {
        $healthRecord = HealthRecord::findOrFail($id);
        $this->authorize('delete', $healthRecord);
        $healthRecord->delete();

        return redirect()->route('health-records.index');
    }

    private function validatedPayload(Request $request): array
    {
        $request->validate([
            'student_number' => ['required', 'string', 'exists:students,student_number'],
            'date' => ['required', 'date'],
            'type' => ['required'],
            'medical_prescription' => ['required', 'string'],
        ]);

        $student = Student::where('student_number', $request->input('student_number'))->firstOrFail();

        if (! $request->user()->can(P::STUDENTS_ALL)) {
            abort_unless(Student::visibleTo($request->user())->whereKey($student->id)->exists(), 403);
        }

        return [
            'student_id' => $student->id,
            'date' => strip_tags($request->input('date')),
            'type' => strip_tags($request->input('type')),
            'medical_prescription' => strip_tags($request->input('medical_prescription')),
        ];
    }
}
