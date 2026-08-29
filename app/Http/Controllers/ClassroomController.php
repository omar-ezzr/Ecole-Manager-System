<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Department;
use App\Support\SchoolPermissions as P;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ClassroomController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Classroom::class);

        $classrooms = $request->user()->can(P::CLASSROOMS_ALL)
            ? Classroom::with('department')->orderBy('name')->get()
            : $request->user()->assignedClassrooms()->orderBy('name')->get();

        return view('classrooms.index', compact('classrooms'));
    }

    public function create()
    {
        $this->authorize('create', Classroom::class);

        return view('classrooms.create', ['departments' => Department::all()]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Classroom::class);

        $request->validate([
            'name' => ['required', 'string', 'max:255'], 'address' => ['nullable', 'string', 'max:255'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
        ]);

        Classroom::create([
            'name' => strip_tags($request->input('name')),
            'address' => strip_tags($request->input('address')),
            'department_id' => strip_tags($request->input('department_id')),
        ]);

        return redirect()->back()->with('success', 'Classroom created successfully!');
    }

    public function show(string $id)
    {
        $classroom = Classroom::with('department')->findOrFail($id);
        $this->authorize('view', $classroom);

        return view('classrooms.show', compact('classroom'));
    }

    public function edit(string $id)
    {
        $classroom = Classroom::findOrFail($id);
        $this->authorize('update', $classroom);

        return view('classrooms.edite', [
            'classroom' => $classroom,
            'departments' => Department::all(),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $classroom = Classroom::findOrFail($id);
        $this->authorize('update', $classroom);

        $request->validate([
            'name' => ['required'],
            'address' => ['required'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
        ]);

        $classroom->update([
            'name' => strip_tags($request->input('name')),
            'address' => strip_tags($request->input('address')),
            'department_id' => strip_tags($request->input('department_id')),
        ]);

        return redirect()->back()->with('success', 'Classroom updated successfully!');
    }

    public function destroy(string $id)
    {
        $classroom = Classroom::findOrFail($id);
        $this->authorize('delete', $classroom);

        if ($classroom->students()->exists()) {
            return $this->referencedParentResponse(request(), 'This classroom cannot be deleted because it still has students.');
        }

        try {
            $classroom->delete();
        } catch (QueryException) {
            return $this->referencedParentResponse(request(), 'This classroom cannot be deleted because it is still referenced.');
        }

        return redirect('classrooms');
    }

    private function referencedParentResponse(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], Response::HTTP_CONFLICT);
        }

        return back()->withErrors(['delete' => $message]);
    }
}
