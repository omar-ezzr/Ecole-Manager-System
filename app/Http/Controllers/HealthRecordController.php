<?php

namespace App\Http\Controllers;

use App\Models\HealthRecord;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HealthRecordController extends Controller
{
    public function index(Request $request)
    {
        $query = HealthRecord::with('student');

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
        return Auth::user()->email === env('EMAIL_AUTH') ? view('health-records.create') : view('/');
    }

    public function store(Request $request)
    {
        $validated = $this->validatedPayload($request);
        HealthRecord::create($validated);

        return redirect()->back()->with('success', 'Health record created successfully!');
    }

    public function show(string $id)
    {
        return view('health-records.show', ['healthRecord' => HealthRecord::with('student')->findOrFail($id)]);
    }

    public function edit(string $id)
    {
        return Auth::user()->email === env('EMAIL_AUTH')
            ? view('health-records.edite', ['healthRecord' => HealthRecord::with('student')->findOrFail($id)])
            : view('/');
    }

    public function update(Request $request, string $id)
    {
        HealthRecord::findOrFail($id)->update($this->validatedPayload($request));

        return redirect()->route('health-records.index');
    }

    public function destroy(string $id)
    {
        HealthRecord::findOrFail($id)->delete();

        return redirect()->route('health-records.index');
    }

    private function validatedPayload(Request $request): array
    {
        $request->validate([
            'student_number' => ['required'],
            'date' => ['required', 'date'],
            'type' => ['required'],
            'medical_prescription' => ['required', 'string'],
        ]);

        $student = Student::where('student_number', $request->input('student_number'))->firstOrFail();

        return [
            'student_id' => $student->id,
            'date' => strip_tags($request->input('date')),
            'type' => strip_tags($request->input('type')),
            'medical_prescription' => strip_tags($request->input('medical_prescription')),
        ];
    }
}
