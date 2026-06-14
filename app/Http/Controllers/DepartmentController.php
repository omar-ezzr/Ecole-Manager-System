<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DepartmentController extends Controller
{
    public function index()
    {
        return Auth::user()
            ? view('departments.index', ['departments' => Department::all()])
            : view('/');
    }

    public function create()
    {
        if (Auth::user()->email === env('EMAIL_AUTH')) {
            return view('departments.create', ['schools' => School::all()]);
        }

        return view('/');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required'],
            'id' => ['required', 'unique:departments'],
            'school_id' => ['required'],
            'address' => ['required'],
        ]);

        Department::create([
            'id' => strip_tags($request->input('id')),
            'name' => strip_tags($request->input('name')),
            'school_id' => strip_tags($request->input('school_id')),
            'address' => strip_tags($request->input('address')),
        ]);

        return redirect()->back()->with('success', 'Department created successfully!');
    }

    public function show(string $id)
    {
        return Auth::user()
            ? view('departments.show', ['department' => Department::findOrFail($id)])
            : view('/');
    }

    public function edit(string $id)
    {
        if (Auth::user()->email === env('EMAIL_AUTH')) {
            return view('departments.edite', [
                'department' => Department::findOrFail($id),
                'schools' => School::all(),
            ]);
        }

        return view('/');
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => ['required'],
            'address' => ['required'],
            'id' => ['required'],
            'school_id' => ['required'],
        ]);

        Department::findOrFail($id)->update([
            'id' => strip_tags($request->input('id')),
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
