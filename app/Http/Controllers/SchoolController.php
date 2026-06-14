<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SchoolController extends Controller
{
    public function index()
    {
        return Auth::user()
            ? view('schools.index', ['schools' => School::all()])
            : view('/');
    }

    public function create()
    {
        return Auth::user()->email === env('EMAIL_AUTH') ? view('schools.create') : view('/');
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
        return Auth::user()->email === env('EMAIL_AUTH')
            ? view('schools.edite', ['school' => School::findOrFail($id)])
            : view('/');
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
