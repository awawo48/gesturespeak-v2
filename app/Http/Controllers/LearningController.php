<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Sign;
use App\Models\SignProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Child learning pipeline:
 * - Hub (pick category)
 * - Category listing (signs with mastered badges)
 * - Sign detail (video, instructions, camera AI practice with real-time feedback & TTS)
 */
class LearningController extends Controller
{
    public function hub(): View
    {
        $student = Auth::guard('student')->user();
        $summary = $student->progressSummary();

        $categories = Category::withCount('signs')
            ->orderBy('sort_order')
            ->get();

        // Attach category completion percentage for this child
        $categorySummary = collect($summary['categories'])->keyBy('name');
        $categories->each(function ($cat) use ($categorySummary) {
            $cat->percent = $categorySummary[$cat->name]['percent'] ?? 0;
            $cat->done = $categorySummary[$cat->name]['done'] ?? 0;
        });

        // Recent practice
        $recentProgress = $student->progress()
            ->with('sign.category')
            ->latest('updated_at')
            ->take(6)
            ->get();

        return view('learn.hub', [
            'student' => $student,
            'categories' => $categories,
            'summary' => $summary,
            'recentProgress' => $recentProgress,
        ]);
    }

    public function category(Category $category): View
    {
        $student = Auth::guard('student')->user();

        $signs = $category->signs()->get();
        $progress = $student->progress()
            ->whereIn('sign_id', $signs->pluck('id'))
            ->get()
            ->keyBy('sign_id');

        $signs->each(function ($sign) use ($progress) {
            $sign->user_progress = $progress->get($sign->id);
            $sign->is_done = $sign->user_progress && ($sign->user_progress->mastered || (! $sign->is_static && $sign->user_progress->viewed_at));
        });

        $doneCount = $signs->where('is_done', true)->count();
        $percent = $signs->count() > 0 ? (int) round(($doneCount / $signs->count()) * 100) : 0;

        return view('learn.category', [
            'student' => $student,
            'category' => $category,
            'signs' => $signs,
            'doneCount' => $doneCount,
            'percent' => $percent,
        ]);
    }

    public function sign(Category $category, Sign $sign): View
    {
        $student = Auth::guard('student')->user();

        // Mark as viewed on open
        $progress = SignProgress::firstOrCreate(
            ['student_id' => $student->id, 'sign_id' => $sign->id],
            ['viewed_at' => now()]
        );

        if (! $progress->viewed_at) {
            $progress->update(['viewed_at' => now()]);
        }

        // Navigation (previous / next sign)
        $allSigns = $category->signs()->orderBy('sort_order')->get();
        $currentIndex = $allSigns->search(fn ($s) => $s->id === $sign->id);
        $prevSign = $currentIndex > 0 ? $allSigns[$currentIndex - 1] : null;
        $nextSign = $currentIndex < $allSigns->count() - 1 ? $allSigns[$currentIndex + 1] : null;

        return view('learn.sign', [
            'student' => $student,
            'category' => $category,
            'sign' => $sign,
            'progress' => $progress,
            'prevSign' => $prevSign,
            'nextSign' => $nextSign,
            'currentIndex' => $currentIndex + 1,
            'totalSigns' => $allSigns->count(),
        ]);
    }

    /**
     * AJAX endpoint: Called when AI camera detects a match or when child completes practice.
     */
    public function recordProgress(Request $request, Sign $sign): JsonResponse
    {
        $student = Auth::guard('student')->user();

        $data = $request->validate([
            'confidence' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'mastered' => ['nullable', 'boolean'],
        ]);

        $progress = SignProgress::firstOrCreate(
            ['student_id' => $student->id, 'sign_id' => $sign->id],
            ['viewed_at' => now()]
        );

        $wasMastered = $progress->mastered;
        $isMastered = $data['mastered'] ?? false;
        $confidence = $data['confidence'] ?? 0;

        $progress->practice_count++;
        if ($confidence > $progress->best_confidence) {
            $progress->best_confidence = $confidence;
        }

        $starsAwarded = 0;
        if ($isMastered && ! $wasMastered) {
            $progress->mastered = true;
            $progress->mastered_at = now();
            // Reward: 3 stars for mastering a new sign!
            $starsAwarded = 3;
            $student->increment('stars', 3);
        }

        $progress->save();

        return response()->json([
            'success' => true,
            'mastered' => $progress->mastered,
            'best_confidence' => $progress->best_confidence,
            'practice_count' => $progress->practice_count,
            'stars_awarded' => $starsAwarded,
            'total_stars' => $student->fresh()->stars,
        ]);
    }
}
