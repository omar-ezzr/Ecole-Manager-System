<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class StudentSearchController extends Controller
{
    public function search(Request $request)
    {
        $query = strip_tags($request->input('find'));

        $student = Student::visibleTo($request->user())
            ->where(fn ($students) => $students
                ->where('student_number', $query)
                ->orWhere('first_name', 'like', "%$query%")
                ->orWhere('last_name', 'like', "%$query%"))
            ->first();

        if ($student) {
            return redirect()->route('students.show', $student->id);
        }

        return redirect()->back()->with('error', 'Student not found');
    }
}
