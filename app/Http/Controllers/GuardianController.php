<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Parent / Teacher dashboard: create children, link a child by Student Code,
 * view progress reports and start Learning Mode.
 */
class GuardianController extends Controller
{
    public function dashboard(Request $request): View
    {
        $children = $request->user()->students()
            ->withCount(['progress as mastered_count' => fn ($q) => $q->where('mastered', true)])
            ->withCount('quizAttempts')
            ->orderBy('name')
            ->get()
            ->map(function (Student $child) {
                $child->summary = $child->progressSummary();
                $child->last_active = $child->progress()->max('updated_at');

                return $child;
            });

        return view('guardian.dashboard', [
            'children' => $children,
            'avatars' => Student::AVATARS,
        ]);
    }

    public function storeChild(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'age' => ['nullable', 'integer', 'min:2', 'max:18'],
            'pin' => ['required', 'digits:4'],
            'avatar' => ['required', Rule::in(array_keys(Student::AVATARS))],
        ]);

        $user = $request->user();

        $child = Student::create([
            ...$data,
            'student_code' => Student::generateCode($data['name']),
            'created_by' => $user->id,
        ]);

        $child->guardians()->attach($user->id, ['relationship' => $user->role]);

        return redirect()->route('guardian.dashboard')
            ->with('success', "{$child->name} was added! Student Code: {$child->student_code}. Share this code with the ".($user->isTeacher() ? 'parent' : 'teacher').' so they can follow progress too.');
    }

    public function linkChild(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_code' => ['required', 'string', 'max:12'],
        ]);

        $code = Str::upper(trim($data['student_code']));
        $child = Student::where('student_code', $code)->first();

        if (! $child) {
            return back()->withErrors(['student_code' => "No child found with code {$code}."])->withInput();
        }

        $user = $request->user();

        if ($child->guardians()->whereKey($user->id)->exists()) {
            return back()->with('success', "{$child->name} is already linked to your account.");
        }

        $child->guardians()->attach($user->id, ['relationship' => $user->role]);

        return redirect()->route('guardian.dashboard')
            ->with('success', "{$child->name} is now linked to your account. You will see the same progress as other guardians.");
    }

    public function showChild(Request $request, Student $student): View
    {
        $this->authorizeGuardian($request->user(), $student);

        $student->load(['guardians']);

        $attempts = $student->quizAttempts()->with('category')->latest()->take(20)->get();

        $recent = $student->progress()->with('sign.category')->latest('updated_at')->take(12)->get();

        $needsPractice = $student->progress()
            ->with('sign')
            ->where('mastered', false)
            ->where('practice_count', '>', 0)
            ->orderByDesc('practice_count')
            ->take(8)
            ->get();

        // Activity per day for the last 14 days (chart)
        $activity = collect(range(13, 0))->mapWithKeys(fn ($d) => [now()->subDays($d)->format('Y-m-d') => 0]);
        $student->progress()
            ->where('updated_at', '>=', now()->subDays(14)->startOfDay())
            ->get(['updated_at'])
            ->each(function ($p) use (&$activity) {
                $key = $p->updated_at->format('Y-m-d');
                if ($activity->has($key)) {
                    $activity[$key]++;
                }
            });

        return view('guardian.child', [
            'child' => $student,
            'summary' => $student->progressSummary(),
            'attempts' => $attempts,
            'recent' => $recent,
            'needsPractice' => $needsPractice,
            'activity' => $activity,
        ]);
    }

    /** Parent/teacher hands the device to the child without typing the PIN. */
    public function startLearning(Request $request, Student $student): RedirectResponse
    {
        $this->authorizeGuardian($request->user(), $student);

        Auth::guard('student')->login($student);

        return redirect()->route('learn.hub');
    }

    public function unlinkChild(Request $request, Student $student): RedirectResponse
    {
        $this->authorizeGuardian($request->user(), $student);

        $student->guardians()->detach($request->user()->id);

        // Remove the child completely if nobody is linked anymore
        if (! $student->guardians()->exists()) {
            $student->delete();
        }

        return redirect()->route('guardian.dashboard')->with('success', "{$student->name} was removed from your account.");
    }

    private function authorizeGuardian(User $user, Student $student): void
    {
        abort_unless($student->guardians()->whereKey($user->id)->exists(), 403, 'This child is not linked to your account.');
    }
}
