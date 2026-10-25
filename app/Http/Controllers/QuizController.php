<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\QuizAttempt;
use App\Models\Sign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Gamified quizzes:
 * - List quizzes with star rating history
 * - Interactive quiz session with real-time feedback
 * - Score submission & star reward engine
 */
class QuizController extends Controller
{
    public function index(): View
    {
        $student = Auth::guard('student')->user();
        $categories = Category::withCount('signs')
            ->having('signs_count', '>=', 4)
            ->orderBy('sort_order')
            ->get();

        // Best stars per category
        $attempts = $student->quizAttempts()
            ->selectRaw('category_id, MAX(stars) as best_stars, MAX(score) as best_score, COUNT(*) as attempt_count')
            ->groupBy('category_id')
            ->get()
            ->keyBy('category_id');

        $categories->each(function ($cat) use ($attempts) {
            $record = $attempts->get($cat->id);
            $cat->best_stars = $record ? (int) $record->best_stars : 0;
            $cat->best_score = $record ? (int) $record->best_score : 0;
            $cat->attempt_count = $record ? (int) $record->attempt_count : 0;
        });

        return view('quiz.index', [
            'student' => $student,
            'categories' => $categories,
        ]);
    }

    public function play(Category $category): View
    {
        $student = Auth::guard('student')->user();

        // Pick 5 random signs from category as quiz questions
        $signs = $category->signs()->inRandomOrder()->take(5)->get();
        if ($signs->count() < 3) {
            $signs = $category->signs()->take(5)->get();
        }

        // Generate multiple choice options for each question
        $allSigns = $category->signs()->get();
        $questions = $signs->map(function ($sign) use ($allSigns) {
            $wrongOptions = $allSigns->where('id', '!=', $sign->id)
                ->shuffle()
                ->take(3)
                ->pluck('word')
                ->all();

            $options = array_merge([$sign->word], $wrongOptions);
            shuffle($options);

            return [
                'id' => $sign->id,
                'label' => $sign->label,
                'word' => $sign->word,
                'meaning_en' => $sign->meaning_en,
                'video' => $sign->video,
                'image' => $sign->image,
                'is_static' => $sign->is_static,
                'options' => $options,
            ];
        });

        return view('quiz.play', [
            'student' => $student,
            'category' => $category,
            'questions' => $questions,
        ]);
    }

    public function submit(Request $request, Category $category): JsonResponse
    {
        $student = Auth::guard('student')->user();

        $data = $request->validate([
            'score' => ['required', 'integer', 'min:0'],
            'total' => ['required', 'integer', 'min:1'],
        ]);

        $score = $data['score'];
        $total = $data['total'];
        $ratio = $total > 0 ? $score / $total : 0;

        // Calculate stars
        if ($ratio >= 0.8) {
            $stars = 3;
        } elseif ($ratio >= 0.5) {
            $stars = 2;
        } elseif ($ratio > 0) {
            $stars = 1;
        } else {
            $stars = 0;
        }

        $attempt = QuizAttempt::create([
            'student_id' => $student->id,
            'category_id' => $category->id,
            'score' => $score,
            'total' => $total,
            'stars' => $stars,
        ]);

        // Add stars to student's total balance
        if ($stars > 0) {
            $student->increment('stars', $stars);
        }

        return response()->json([
            'success' => true,
            'stars' => $stars,
            'score' => $score,
            'total' => $total,
            'total_stars' => $student->fresh()->stars,
            'message' => $stars === 3
                ? 'Luar biasa! 🌟 3 Bintang penuh!'
                : ($stars === 2 ? 'Hebat! 🌟 2 Bintang!' : 'Bagus! Teruskan mencuba!'),
        ]);
    }
}
