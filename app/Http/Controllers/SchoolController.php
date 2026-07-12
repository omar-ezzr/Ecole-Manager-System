<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Http\Request;

class SchoolController extends Controller
{
    public function index()
    {
        return view('schools.index', ['schools' => School::all()]);
    }

    public function create()
    {
        return view('schools.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required'],
            'country' => ['required'],
            'region' => ['required'],
            'city' => ['required'],
            'address' => ['required'],
        ]);

        School::create($this->validatedPayload($request));

        return redirect()->back()->with('success', 'School created successfully!');
    }

    public function show(string $id)
    {
        return view('schools.show', ['school' => School::findOrFail($id)]);
    }

    public function edit(string $id)
    {
        return view('schools.edite', ['school' => School::findOrFail($id)]);
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => ['required'],
            'country' => ['required'],
            'region' => ['required'],
            'city' => ['required'],
            'address' => ['required'],
        ]);

        School::findOrFail($id)->update($this->validatedPayload($request));

        return redirect()->back()->with('success', 'School updated successfully!');
    }

    public function destroy(string $id)
    {
        School::findOrFail($id)->delete();

        return redirect('schools');
    }

    private function validatedPayload(Request $request): array
    {
        return [
            'name' => strip_tags($request->input('name')),
            'country' => strip_tags($request->input('country')),
            'region' => strip_tags($request->input('region')),
            'city' => strip_tags($request->input('city')),
            'address' => strip_tags($request->input('address')),
        ];
    }
}
