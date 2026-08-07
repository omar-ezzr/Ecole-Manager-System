<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\School;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DepartmentController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Department::class);

        return view('departments.index', ['departments' => Department::with('school')->get()]);
    }

    public function create()
    {
        $this->authorize('create', Department::class);

        return view('departments.create', ['schools' => School::all()]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Department::class);

        $request->validate([
            'name' => ['required'],
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'address' => ['required'],
        ]);

        Department::create([
            'name' => strip_tags($request->input('name')),
            'school_id' => strip_tags($request->input('school_id')),
            'address' => strip_tags($request->input('address')),
        ]);

        return redirect()->back()->with('success', 'Department created successfully!');
    }

    public function show(string $id)
    {
        $department = Department::with('school')->findOrFail($id);
        $this->authorize('view', $department);

        return view('departments.show', ['department' => $department]);
    }

    public function edit(string $id)
    {
        $department = Department::findOrFail($id);
        $this->authorize('update', $department);

        return view('departments.edite', [
                'department' => $department,
                'schools' => School::all(),
            ]);
    }

    public function update(Request $request, string $id)
    {
        $department = Department::findOrFail($id);
        $this->authorize('update', $department);

        $request->validate([
            'name' => ['required'],
            'address' => ['required'],
            'school_id' => ['required', 'integer', 'exists:schools,id'],
        ]);

        $department->update([
            'name' => strip_tags($request->input('name')),
            'address' => strip_tags($request->input('address')),
            'school_id' => strip_tags($request->input('school_id')),
        ]);

        return redirect()->back()->with('success', 'Department updated successfully!');
    }

    public function destroy(string $id)
    {
        $department = Department::findOrFail($id);
        $this->authorize('delete', $department);

        if ($department->classrooms()->exists()) {
            return $this->referencedParentResponse(request(), 'This department cannot be deleted because it still has classrooms.');
        }

        try {
            $department->delete();
        } catch (QueryException) {
            return $this->referencedParentResponse(request(), 'This department cannot be deleted because it is still referenced.');
        }

        return redirect('departments');
    }

    private function referencedParentResponse(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], Response::HTTP_CONFLICT);
        }

        return back()->withErrors(['delete' => $message]);
    }
}
