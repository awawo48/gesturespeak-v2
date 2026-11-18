<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Exercise;
use App\Models\ExerciseAttempt;
use App\Models\QuizAttempt;
use App\Models\Sign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Gamified quizzes:
 * - Pilihan Jawapan : watch the sign, pick the right word
 * - Mengeja         : watch the sign, build the word from letter tiles
 * - Latihan Guru    : teacher-made exercises (choice / spelling)
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

        // Best stars per category (choice + spelling combined)
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
            $cat->can_spell = $this->spellableSigns($cat->signs)->count() >= 3;
        });

        $exercises = $student->teacherExercises()->get();
        $exerciseBest = $student->exerciseAttempts()
            ->selectRaw('exercise_id, MAX(stars) as best_stars, MAX(score) as best_score, MAX(total) as total')
            ->groupBy('exercise_id')
            ->get()
            ->keyBy('exercise_id');

        return view('quiz.index', [
            'student' => $student,
            'categories' => $categories,
            'exercises' => $exercises,
            'exerciseBest' => $exerciseBest,
        ]);
    }

    public function play(Request $request, Category $category): View
    {
        $student = Auth::guard('student')->user();
        $mode = $request->query('mode') === 'spelling' ? 'spelling' : 'choice';
        $allSigns = $category->signs()->get();

        if ($mode === 'spelling') {
            $pool = $this->spellableSigns($allSigns);
            abort_if($pool->count() < 1, 404);
            $questions = $pool->shuffle()->take(5)->map(fn (Sign $s) => $this->spellQuestion($s->word, $s))->values();
        } else {
            $signs = $allSigns->shuffle()->take(5);
            $questions = $signs->map(fn (Sign $s) => $this->choiceQuestion($s, $allSigns))->values();
        }

        return view('quiz.play', [
            'student' => $student,
            'title' => ($mode === 'spelling' ? 'Mengeja: ' : 'Kuiz: ').$category->name,
            'questions' => $questions,
            'mode' => $mode,
            'submitUrl' => route('quiz.submit', $category),
            'replayUrl' => route('quiz.play', ['category' => $category, 'mode' => $mode]),
        ]);
    }

    public function playExercise(Exercise $exercise): View
    {
        $student = Auth::guard('student')->user();
        $this->authorizeExercise($student, $exercise);

        $items = $exercise->items ?? [];

        if ($exercise->type === Exercise::TYPE_SPELLING) {
            $questions = collect($items)->shuffle()->map(function ($word) {
                $sign = Sign::whereRaw('UPPER(word) = ?', [Str::upper($word)])->first();

                return $this->spellQuestion($word, $sign);
            })->values();
        } else {
            $signs = Sign::whereIn('id', $items)->get()->shuffle();
            $questions = $signs->map(function (Sign $s) {
                $siblings = Sign::where('category_id', $s->category_id)->get();

                return $this->choiceQuestion($s, $siblings);
            })->values();
        }

        abort_if($questions->isEmpty(), 404);

        return view('quiz.play', [
            'student' => $student,
            'title' => 'Latihan: '.$exercise->title,
            'questions' => $questions,
            'mode' => $exercise->type === Exercise::TYPE_SPELLING ? 'spelling' : 'choice',
            'submitUrl' => route('quiz.exercise.submit', $exercise),
            'replayUrl' => route('quiz.exercise', $exercise),
        ]);
    }

    public function submit(Request $request, Category $category): JsonResponse
    {
        $student = Auth::guard('student')->user();

        $data = $request->validate([
            'score' => ['required', 'integer', 'min:0'],
            'total' => ['required', 'integer', 'min:1'],
            'mode' => ['nullable', 'in:choice,spelling'],
        ]);

        $stars = $this->starsFor($data['score'], $data['total']);

        QuizAttempt::create([
            'student_id' => $student->id,
            'category_id' => $category->id,
            'score' => $data['score'],
            'total' => $data['total'],
            'stars' => $stars,
            'mode' => $data['mode'] ?? 'choice',
        ]);

        return $this->rewardResponse($student, $stars, $data['score'], $data['total']);
    }

    public function submitExercise(Request $request, Exercise $exercise): JsonResponse
    {
        $student = Auth::guard('student')->user();
        $this->authorizeExercise($student, $exercise);

        $data = $request->validate([
            'score' => ['required', 'integer', 'min:0'],
            'total' => ['required', 'integer', 'min:1'],
        ]);

        $stars = $this->starsFor($data['score'], $data['total']);

        ExerciseAttempt::create([
            'student_id' => $student->id,
            'exercise_id' => $exercise->id,
            'score' => $data['score'],
            'total' => $data['total'],
            'stars' => $stars,
        ]);

        return $this->rewardResponse($student, $stars, $data['score'], $data['total']);
    }

    // ------------------------------------------------------------------

    /** Signs whose word can be spelled with letter tiles (2-10 letters). */
    private function spellableSigns(Collection $signs): Collection
    {
        return $signs->filter(function (Sign $s) {
            $len = strlen($this->letters($s->word));

            return $len >= 2 && $len <= 10;
        })->values();
    }

    private function letters(string $word): string
    {
        return Str::upper(preg_replace('/[^A-Za-z]/', '', Str::ascii($word)));
    }

    private function choiceQuestion(Sign $sign, Collection $pool): array
    {
        $wrong = $pool->where('id', '!=', $sign->id)->shuffle()->take(3)->pluck('word')->all();
        $options = array_merge([$sign->word], $wrong);
        shuffle($options);

        return [
            'type' => 'choice',
            'id' => $sign->id,
            'label' => $sign->label,
            'word' => $sign->word,
            'meaning_en' => $sign->meaning_en,
            'video' => $sign->video,
            'image' => $sign->image,
            'is_static' => $sign->is_static,
            'options' => $options,
        ];
    }

    private function spellQuestion(string $word, ?Sign $sign): array
    {
        $letters = $this->letters($word);
        $tiles = str_split($letters);

        // Add up to 2 distractor letters so tiles are not trivially the answer
        $alphabet = array_values(array_diff(range('A', 'Z'), $tiles));
        shuffle($alphabet);
        $tiles = array_merge($tiles, array_slice($alphabet, 0, 2));
        shuffle($tiles);

        return [
            'type' => 'spell',
            'id' => $sign?->id,
            'word' => $letters,
            'display' => $word,
            'meaning_en' => $sign?->meaning_en,
            'video' => $sign?->video,
            'image' => $sign?->image,
            'tiles' => $tiles,
        ];
    }

    private function starsFor(int $score, int $total): int
    {
        $ratio = $total > 0 ? $score / $total : 0;

        return match (true) {
            $ratio >= 0.8 => 3,
            $ratio >= 0.5 => 2,
            $ratio > 0 => 1,
            default => 0,
        };
    }

    private function rewardResponse($student, int $stars, int $score, int $total): JsonResponse
    {
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

    private function authorizeExercise($student, Exercise $exercise): void
    {
        $allowed = $exercise->is_active
            && $student->guardians()->whereKey($exercise->user_id)->exists();

        abort_unless($allowed, 403, 'Latihan ini tidak tersedia untuk anda.');
    }
}
