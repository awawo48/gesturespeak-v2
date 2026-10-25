<?php

namespace App\Http\Controllers;

use App\Models\Category;
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
            'total_students' => Student::count(),
            'total_signs' => Sign::count(),
            'total_practices' => SignProgress::sum('practice_count'),
            'total_mastered' => SignProgress::where('mastered', true)->count(),
            'total_quizzes' => QuizAttempt::count(),
        ];

        $recentStudents = Student::with('guardians')->latest()->take(6)->get();
        $recentQuizzes = QuizAttempt::with(['student', 'category'])->latest()->take(6)->get();

        return view('admin.dashboard', [
            'stats' => $stats,
            'recentStudents' => $recentStudents,
            'recentQuizzes' => $recentQuizzes,
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

        $users = $query->latest()->paginate(15)->withQueryString();

        return view('admin.users', [
            'users' => $users,
            'search' => $search,
            'role' => $role,
        ]);
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
