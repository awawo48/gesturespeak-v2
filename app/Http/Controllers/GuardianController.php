<?php

namespace App\Http\Controllers;

use App\Models\Category;
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
 *
 * Role differences:
 * - Teacher: class analytics, search/sort, writes feedback notes & assigns homework modules.
 * - Parent : sees teacher notes/homework, manages child profile & resets PIN.
 * - Only the creator of a child profile (or Admin) may delete it permanently.
 */
class GuardianController extends Controller
{
    /** Waiting page for teachers not yet approved by Admin. */
    public function pending(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->isApproved()) {
            return redirect()->route('guardian.dashboard');
        }

        return view('guardian.pending', ['user' => $user]);
    }

    public function dashboard(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->input('search', ''));
        $sort = $request->input('sort', 'name');

        $children = $user->students()
            ->with(['guardians:id,name,role', 'assignedCategory:id,name,icon,color', 'teacherReviewer:id,name'])
            ->withCount(['progress as mastered_count' => fn ($q) => $q->where('mastered', true)])
            ->withCount('quizAttempts')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('name', 'like', "%{$search}%")
                        ->orWhere('student_code', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->get()
            ->map(function (Student $child) {
                $child->summary = $child->progressSummary();
                $child->last_active = $child->progress()->max('updated_at');
                $child->has_parent = $child->guardians->contains(fn ($g) => $g->role === User::ROLE_PARENT);
                $child->has_teacher = $child->guardians->contains(fn ($g) => $g->role === User::ROLE_TEACHER);

                return $child;
            });

        $children = match ($sort) {
            'stars' => $children->sortByDesc('stars')->values(),
            'progress' => $children->sortByDesc(fn ($c) => $c->summary['overall'])->values(),
            'progress_low' => $children->sortBy(fn ($c) => $c->summary['overall'])->values(),
            default => $children,
        };

        // Class analytics (teacher view)
        $classStats = null;
        if ($user->isTeacher()) {
            $count = $children->count();
            $top = $children->sortByDesc(fn ($c) => $c->summary['overall'])->first();
            $classStats = [
                'total' => $count,
                'average' => $count ? (int) round($children->avg(fn ($c) => $c->summary['overall'])) : 0,
                'top' => $top,
                'needs_help' => $children->filter(fn ($c) => $c->summary['overall'] < 30)->count(),
                'without_parent' => $children->where('has_parent', false)->count(),
            ];
        }

        return view('guardian.dashboard', [
            'children' => $children,
            'avatars' => Student::AVATARS,
            'classStats' => $classStats,
            'search' => $search,
            'sort' => $sort,
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

        $shareWith = $user->isTeacher() ? 'ibu bapa' : 'guru';

        return redirect()->route('guardian.dashboard')
            ->with('success', "{$child->name} berjaya ditambah! Kod Murid: {$child->student_code}. Kongsi kod ini dengan {$shareWith} supaya mereka boleh pantau kemajuan bersama.");
    }

    public function linkChild(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_code' => ['required', 'string', 'max:12'],
        ]);

        $code = Str::upper(trim($data['student_code']));
        $child = Student::where('student_code', $code)->first();

        if (! $child) {
            return back()->withErrors(['student_code' => "Tiada murid dijumpai dengan kod {$code}."])->withInput();
        }

        $user = $request->user();

        if ($child->guardians()->whereKey($user->id)->exists()) {
            return back()->with('success', "{$child->name} sudah dipautkan dengan akaun anda.");
        }

        $child->guardians()->attach($user->id, ['relationship' => $user->role]);

        return redirect()->route('guardian.dashboard')
            ->with('success', "{$child->name} kini dipautkan dengan akaun anda. Anda akan melihat kemajuan yang sama seperti penjaga lain.");
    }

    public function showChild(Request $request, Student $student): View
    {
        $this->authorizeGuardian($request->user(), $student);

        $student->load(['guardians', 'assignedCategory', 'teacherReviewer', 'creator']);

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
        $activity = collect(range(13, 0))
            ->mapWithKeys(fn ($d) => [now()->subDays($d)->format('Y-m-d') => 0])
            ->all();

        $student->progress()
            ->where('updated_at', '>=', now()->subDays(14)->startOfDay())
            ->get(['updated_at'])
            ->each(function ($p) use (&$activity) {
                $key = $p->updated_at->format('Y-m-d');
                if (array_key_exists($key, $activity)) {
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
            'categories' => Category::orderBy('sort_order')->get(['id', 'name', 'icon']),
            'avatars' => Student::AVATARS,
            'isCreator' => $student->isCreatedBy($request->user()),
        ]);
    }

    /** Parent/teacher hands the device to the child without typing the PIN. */
    public function startLearning(Request $request, Student $student): RedirectResponse
    {
        $this->authorizeGuardian($request->user(), $student);

        Auth::guard('student')->login($student);

        return redirect()->route('learn.hub');
    }

    /** Update profile / reset PIN: creator of the profile or any linked parent. */
    public function updateChild(Request $request, Student $student): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeGuardian($user, $student);

        abort_unless(
            $student->isCreatedBy($user) || $user->isParent(),
            403,
            'Hanya pencipta profil atau ibu bapa boleh mengemas kini profil murid.'
        );

        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'age' => ['nullable', 'integer', 'min:2', 'max:18'],
            'avatar' => ['required', Rule::in(array_keys(Student::AVATARS))],
            'pin' => ['nullable', 'digits:4'],
        ]);

        if (empty($data['pin'])) {
            unset($data['pin']);
        }

        $student->update($data);

        return back()->with('success', "Profil {$student->name} berjaya dikemas kini".(isset($data['pin']) ? ' dan PIN baharu telah ditetapkan.' : '.'));
    }

    /** Teacher-only: write feedback note and assign weekly homework module. */
    public function saveTeacherNote(Request $request, Student $student): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeGuardian($user, $student);

        $data = $request->validate([
            'teacher_note' => ['nullable', 'string', 'max:1000'],
            'assigned_category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $student->update([
            'teacher_note' => $data['teacher_note'] ?: null,
            'assigned_category_id' => $data['assigned_category_id'] ?? null,
            'teacher_note_by' => $user->id,
            'teacher_note_updated_at' => now(),
        ]);

        return back()->with('success', "Ulasan dan tugasan untuk {$student->name} telah disimpan. Ibu bapa boleh melihatnya sekarang.");
    }

    /** Remove the link between this adult and the child (profile is kept). */
    public function unlinkChild(Request $request, Student $student): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeGuardian($user, $student);

        $student->guardians()->detach($user->id);

        // Orphaned profiles can no longer be reached by anyone, so clean them up
        if (! $student->guardians()->exists()) {
            $student->delete();
        }

        return redirect()->route('guardian.dashboard')->with('success', "{$student->name} telah dinyahpaut daripada akaun anda.");
    }

    /** Permanently delete a child profile: creator only. */
    public function destroyChild(Request $request, Student $student): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeGuardian($user, $student);

        abort_unless($student->isCreatedBy($user), 403, 'Hanya pencipta profil murid boleh memadam profil ini.');

        $name = $student->name;
        $student->delete();

        return redirect()->route('guardian.dashboard')->with('success', "Profil {$name} telah dipadam sepenuhnya.");
    }

    private function authorizeGuardian(User $user, Student $student): void
    {
        abort_unless($student->guardians()->whereKey($user->id)->exists(), 403, 'Murid ini tidak dipautkan dengan akaun anda.');
    }
}
