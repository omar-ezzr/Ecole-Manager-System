<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SchoolController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', School::class);

        return view('schools.index', ['schools' => School::all()]);
    }

    public function create()
    {
        $this->authorize('create', School::class);

        return view('schools.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', School::class);

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
        $school = School::findOrFail($id);
        $this->authorize('view', $school);

        return view('schools.show', ['school' => $school]);
    }

    public function edit(string $id)
    {
        $school = School::findOrFail($id);
        $this->authorize('update', $school);

        return view('schools.edite', ['school' => $school]);
    }

    public function update(Request $request, string $id)
    {
        $school = School::findOrFail($id);
        $this->authorize('update', $school);

        $request->validate([
            'name' => ['required'],
            'country' => ['required'],
            'region' => ['required'],
            'city' => ['required'],
            'address' => ['required'],
        ]);

        $school->update($this->validatedPayload($request));

        return redirect()->back()->with('success', 'School updated successfully!');
    }

    public function destroy(string $id)
    {
        $school = School::findOrFail($id);
        $this->authorize('delete', $school);

        if ($school->departments()->exists()) {
            return $this->referencedParentResponse(request(), 'This school cannot be deleted because it still has departments.');
        }

        try {
            $school->delete();
        } catch (QueryException) {
            return $this->referencedParentResponse(request(), 'This school cannot be deleted because it is still referenced.');
        }

        return redirect('schools');
    }

    private function referencedParentResponse(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], Response::HTTP_CONFLICT);
        }

        return back()->withErrors(['delete' => $message]);
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
