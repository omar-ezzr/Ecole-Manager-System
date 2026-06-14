<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClassroomController extends Controller
{
    public function index()
    {
        return Auth::user()
            ? view('classrooms.index', ['classrooms' => Classroom::all()])
            : view('/');
    }

    public function create()
    {
        if (Auth::user()->email === env('EMAIL_AUTH')) {
            return view('classrooms.create', ['departments' => Department::all()]);
        }

        return view('/');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required'],
            'address' => ['required'],
            'id' => ['required', 'unique:classrooms'],
            'department_id' => ['required'],
        ]);

        Classroom::create([
            'id' => strip_tags($request->input('id')),
            'name' => strip_tags($request->input('name')),
            'address' => strip_tags($request->input('address')),
            'department_id' => strip_tags($request->input('department_id')),
        ]);

        return redirect()->back()->with('success', 'Classroom created successfully!');
    }

    public function show(string $id)
    {
        return Auth::user()
            ? view('classrooms.show', ['classroom' => Classroom::findOrFail($id)])
            : view('/');
    }

    public function edit(string $id)
    {
        if (Auth::user()->email === env('EMAIL_AUTH')) {
            return view('classrooms.edite', [
                'classroom' => Classroom::findOrFail($id),
                'departments' => Department::all(),
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
            'department_id' => ['required'],
        ]);

        $classroom = Classroom::findOrFail($id);
        $classroom->update([
            'id' => strip_tags($request->input('id')),
            'name' => strip_tags($request->input('name')),
            'address' => strip_tags($request->input('address')),
            'department_id' => strip_tags($request->input('department_id')),
        ]);

        return redirect()->back()->with('success', 'Classroom updated successfully!');
    }

    public function destroy(string $id)
    {
        Classroom::findOrFail($id)->delete();

        return redirect('classrooms');
    }
}
