<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\School;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        return view('departments.index', ['departments' => Department::all()]);
    }

    public function create()
    {
        return view('departments.create', ['schools' => School::all()]);
    }

    public function store(Request $request)
    {
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
        return view('departments.show', ['department' => Department::findOrFail($id)]);
    }

    public function edit(string $id)
    {
        return view('departments.edite', [
                'department' => Department::findOrFail($id),
                'schools' => School::all(),
            ]);
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => ['required'],
            'address' => ['required'],
            'school_id' => ['required', 'integer', 'exists:schools,id'],
        ]);

        Department::findOrFail($id)->update([
            'name' => strip_tags($request->input('name')),
            'address' => strip_tags($request->input('address')),
            'school_id' => strip_tags($request->input('school_id')),
        ]);

        return redirect()->back()->with('success', 'Department updated successfully!');
    }

    public function destroy(string $id)
    {
        Department::findOrFail($id)->delete();

        return redirect('departments');
    }
}
