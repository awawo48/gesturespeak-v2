<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\GestureSample;
use App\Models\QuizAttempt;
use App\Models\Sign;
use App\Models\SignProgress;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Administrator Control Panel:
 * - System overview & analytics
 * - User management (Parents & Teachers)
 * - Student management & guardian links
 * - BIM sign content management
 */
class AdminController extends Controller
{
    public function dashboard(): View
    {
        $stats = [
            'total_users' => User::count(),
            'total_parents' => User::where('role', User::ROLE_PARENT)->count(),
            'total_teachers' => User::where('role', User::ROLE_TEACHER)->count(),
            'pending_teachers' => User::where('role', User::ROLE_TEACHER)->where('status', User::STATUS_PENDING)->count(),
            'total_students' => Student::count(),
            'total_signs' => Sign::count(),
            'total_practices' => SignProgress::sum('practice_count'),
            'total_mastered' => SignProgress::where('mastered', true)->count(),
            'total_quizzes' => QuizAttempt::count(),
        ];

        $recentStudents = Student::with('guardians')->latest()->take(6)->get();
        $recentQuizzes = QuizAttempt::with(['student', 'category'])->latest()->take(6)->get();

        // Real AI dataset readiness across BIM curriculum modules
        $samplesByLabel = GestureSample::selectRaw('UPPER(label) as lbl, sample_type, COUNT(*) as total')
            ->groupBy('lbl', 'sample_type')
            ->get()
            ->groupBy('lbl');

        $moduleReadiness = Category::with('signs')
            ->orderBy('sort_order')
            ->get()
            ->map(function ($cat) use ($samplesByLabel) {
                $total = $cat->signs->count();
                $steps = 0;
                $dtw = 0;
                $static = 0;
                foreach ($cat->signs as $sign) {
                    $types = $samplesByLabel->get(strtoupper($sign->label), collect())->pluck('total', 'sample_type');
                    if (($types['steps'] ?? 0) > 0) {
                        $steps++;
                    } elseif (($types['sequence'] ?? 0) > 0) {
                        $dtw++;
                    } elseif (($types['static'] ?? 0) > 0) {
                        $static++;
                    }
                }
                $ready = $steps + $dtw + $static;

                return [
                    'name' => $cat->name,
                    'total' => $total,
                    'ready' => $ready,
                    'steps' => $steps,
                    'dtw' => $dtw,
                    'static' => $static,
                    'missing' => max(0, $total - $ready),
                ];
            });

        // 7-day daily activity trend for line-chart analysis (SQLite & MySQL compatible)
        $since = now()->subDays(6)->startOfDay();
        $quizzesByDay = QuizAttempt::where('created_at', '>=', $since)
            ->get(['score', 'total', 'created_at'])
            ->groupBy(fn ($q) => $q->created_at->format('Y-m-d'));
        $samplesByDay = GestureSample::where('created_at', '>=', $since)
            ->get(['created_at'])
            ->groupBy(fn ($s) => $s->created_at->format('Y-m-d'));

        $dailyActivity = collect(range(6, 0))->map(function (int $daysAgo) use ($quizzesByDay, $samplesByDay) {
            $date = now()->subDays($daysAgo);
            $key = $date->format('Y-m-d');
            $dayQuizzes = $quizzesByDay->get($key, collect());
            $daySamples = $samplesByDay->get($key, collect());
            $avgPct = $dayQuizzes->isNotEmpty()
                ? (int) round($dayQuizzes->avg(fn ($q) => $q->total > 0 ? ($q->score / $q->total) * 100 : 0))
                : null;

            return [
                'date' => $key,
                'label' => $daysAgo === 0 ? 'Hari ini' : $date->format('d/m'),
                'quizzes' => $dayQuizzes->count(),
                'samples' => $daySamples->count(),
                'avg_pct' => $avgPct,
            ];
        })->values();

        $sampleTotals = GestureSample::selectRaw('sample_type, COUNT(*) as total')
            ->groupBy('sample_type')
            ->pluck('total', 'sample_type');

        return view('admin.dashboard', [
            'stats' => $stats,
            'recentStudents' => $recentStudents,
            'recentQuizzes' => $recentQuizzes,
            'moduleReadiness' => $moduleReadiness,
            'dailyActivity' => $dailyActivity,
            'sampleTotals' => [
                'steps' => (int) ($sampleTotals['steps'] ?? 0),
                'sequence' => (int) ($sampleTotals['sequence'] ?? 0),
                'static' => (int) ($sampleTotals['static'] ?? 0),
            ],
        ]);
    }

    public function users(Request $request): View
    {
        $query = User::withCount('students')->where('role', '!=', User::ROLE_ADMIN);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $users = $query->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")->latest()->paginate(15)->withQueryString();

        return view('admin.users', [
            'users' => $users,
            'search' => $search,
            'role' => $role,
            'status' => $status,
            'pendingCount' => User::where('role', User::ROLE_TEACHER)->where('status', User::STATUS_PENDING)->count(),
        ]);
    }

    public function approveUser(User $user): RedirectResponse
    {
        $user->update(['status' => User::STATUS_APPROVED, 'approved_at' => now()]);

        return back()->with('success', "Akaun {$user->name} telah diluluskan.");
    }

    public function rejectUser(User $user): RedirectResponse
    {
        if ($user->isAdmin()) {
            return back()->with('error', 'Akaun admin tidak boleh ditolak.');
        }

        $user->update(['status' => User::STATUS_REJECTED, 'approved_at' => null]);

        return back()->with('success', "Akaun {$user->name} telah ditolak.");
    }

    public function deleteUser(User $user): RedirectResponse
    {
        if ($user->isAdmin()) {
            return back()->with('error', 'Cannot delete admin account.');
        }

        $user->delete();

        return back()->with('success', 'User account was deleted.');
    }

    public function students(Request $request): View
    {
        $query = Student::with(['guardians', 'progress'])->withCount('quizAttempts');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('student_code', 'like', "%{$search}%");
            });
        }

        $students = $query->latest()->paginate(15)->withQueryString();

        return view('admin.students', [
            'students' => $students,
            'search' => $search,
        ]);
    }

    public function showStudent(Student $student): View
    {
        $student->load(['guardians', 'assignedCategory', 'teacherReviewer', 'creator']);

        $progress = $student->progress()->with('sign.category')->latest('updated_at')->get();

        return view('admin.student-show', [
            'student' => $student,
            'summary' => $student->progressSummary(),
            'attempts' => $student->quizAttempts()->with('category')->latest()->take(30)->get(),
            'exerciseAttempts' => $student->exerciseAttempts()->with('exercise')->latest()->take(30)->get(),
            'recent' => $progress->take(15),
            'needsPractice' => $progress->where('mastered', false)->where('practice_count', '>', 0)->sortByDesc('practice_count')->take(8),
            'stats' => [
                'practices' => (int) $progress->sum('practice_count'),
                'mastered' => $progress->where('mastered', true)->count(),
                'quizzes' => $student->quizAttempts()->count() + $student->exerciseAttempts()->count(),
                'best_stars' => (int) $student->quizAttempts()->max('stars'),
            ],
        ]);
    }

    public function deleteStudent(Student $student): RedirectResponse
    {
        $student->delete();

        return back()->with('success', "Student {$student->name} was deleted.");
    }

    public function signs(Request $request): View
    {
        $categories = Category::orderBy('sort_order')->get();
        $query = Sign::with('category');

        if ($catId = $request->input('category')) {
            $query->where('category_id', $catId);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('word', 'like', "%{$search}%")
                  ->orWhere('label', 'like', "%{$search}%");
            });
        }

        $signs = $query->orderBy('category_id')->orderBy('sort_order')->paginate(20)->withQueryString();

        return view('admin.signs', [
            'signs' => $signs,
            'categories' => $categories,
            'selectedCategory' => $catId,
            'search' => $search,
        ]);
    }

    public function updateSign(Request $request, Sign $sign): RedirectResponse
    {
        $data = $request->validate([
            'word' => ['required', 'string', 'max:100'],
            'meaning_en' => ['nullable', 'string', 'max:100'],
            'instructions' => ['nullable', 'string'],
            'is_static' => ['required', 'boolean'],
        ]);

        $sign->update($data);

        return back()->with('success', "Sign {$sign->word} updated successfully.");
    }
}
