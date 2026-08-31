<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Subject::class);

        $query = Subject::query()->orderBy('code');

        if ($request->user()->isProfessor()) {
            $query->whereHas('teachingAssignments', fn ($assignments) => $assignments->where('professor_id', $request->user()->id));
        }

        if ($request->filled('search')) {
            $query->where(function ($subjects) use ($request): void {
                $subjects
                    ->where('name', 'like', '%'.$request->string('search').'%')
                    ->orWhere('code', 'like', '%'.$request->string('search').'%');
            });
        }

        $subjects = $query->paginate(20)->withQueryString();

        return view('subjects.index', compact('subjects'));
    }

    public function create()
    {
        $this->authorize('create', Subject::class);

        return view('subjects.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Subject::class);

        Subject::create($this->validatedPayload($request));

        return redirect()->route('subjects.index')->with('success', 'Subject created successfully.');
    }

    public function show(Subject $subject)
    {
        $this->authorize('view', $subject);

        return view('subjects.show', compact('subject'));
    }

    public function edit(Subject $subject)
    {
        $this->authorize('update', $subject);

        return view('subjects.edit', compact('subject'));
    }

    public function update(Request $request, Subject $subject)
    {
        $this->authorize('update', $subject);

        $subject->update($this->validatedPayload($request, $subject));

        return redirect()->route('subjects.index')->with('success', 'Subject updated successfully.');
    }

    public function destroy(Request $request, Subject $subject)
    {
        $this->authorize('delete', $subject);

        if ($subject->teachingAssignments()->exists()
            || $subject->grades()->exists()
            || $subject->classroomSubjects()->exists()) {
            $subject->update(['is_active' => false]);

            return redirect()->route('subjects.index')->with('warning', 'This subject has academic history and cannot be deleted. It has been archived instead.');
        }

        try {
            $subject->delete();
        } catch (QueryException) {
            return $this->referencedParentResponse($request, 'This subject cannot be deleted because it is still referenced.');
        }

        return redirect()->route('subjects.index');
    }

    private function validatedPayload(Request $request, ?Subject $subject = null): array
    {
        $codeRule = Rule::unique('subjects', 'code');

        if ($subject) {
            $codeRule->ignore($subject->id);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', $codeRule],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return [
            'name' => strip_tags($validated['name']),
            'code' => strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', $validated['code'])),
            'description' => isset($validated['description']) ? strip_tags($validated['description']) : null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ];
    }

    private function referencedParentResponse(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], Response::HTTP_CONFLICT);
        }

        return back()->withErrors(['delete' => $message]);
    }
}
