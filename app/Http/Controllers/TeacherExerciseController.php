<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Exercise;
use App\Models\ExerciseAttempt;
use App\Models\Sign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Teacher tools: create & manage custom practice (Latihan) for linked students.
 */
class TeacherExerciseController extends Controller
{
    public function index(Request $request): View
    {
        $exercises = Exercise::where('user_id', $request->user()->id)
            ->withCount('attempts')
            ->withAvg('attempts', 'stars')
            ->latest()
            ->get();

        return view('guardian.exercises.index', ['exercises' => $exercises]);
    }

    public function create(): View
    {
        return view('guardian.exercises.create', [
            'categories' => Category::with('signs:id,category_id,label,word')->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'type' => ['required', 'in:choice,spelling'],
            'sign_ids' => ['nullable', 'array'],
            'sign_ids.*' => ['integer', 'exists:signs,id'],
            'words' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['type'] === Exercise::TYPE_CHOICE) {
            $items = array_values(array_unique(array_map('intval', $data['sign_ids'] ?? [])));
            if (count($items) < 2) {
                return back()->withInput()->withErrors(['sign_ids' => 'Pilih sekurang-kurangnya 2 isyarat.']);
            }
        } else {
            $items = collect(preg_split('/[\r\n,]+/', (string) ($data['words'] ?? '')))
                ->map(fn ($w) => Str::upper(trim(preg_replace('/[^A-Za-z ]/', '', Str::ascii($w)))))
                ->filter(fn ($w) => strlen(str_replace(' ', '', $w)) >= 2 && strlen(str_replace(' ', '', $w)) <= 12)
                ->unique()
                ->values()
                ->all();
            if (count($items) < 1) {
                return back()->withInput()->withErrors(['words' => 'Masukkan sekurang-kurangnya 1 perkataan (2-12 huruf).']);
            }
        }

        Exercise::create([
            'user_id' => $request->user()->id,
            'title' => $data['title'],
            'type' => $data['type'],
            'items' => $items,
            'is_active' => true,
        ]);

        return redirect()->route('guardian.exercises.index')->with('success', 'Latihan "'.$data['title'].'" berjaya dicipta. Murid dalam kelas anda boleh mencubanya sekarang.');
    }

    public function show(Request $request, Exercise $exercise): View
    {
        $this->authorizeOwner($request, $exercise);

        // Only show attempts by students linked to this teacher
        $studentIds = $request->user()->students()->pluck('students.id');

        $attempts = ExerciseAttempt::with('student')
            ->where('exercise_id', $exercise->id)
            ->whereIn('student_id', $studentIds)
            ->latest()
            ->take(50)
            ->get();

        $words = $exercise->type === Exercise::TYPE_CHOICE
            ? Sign::whereIn('id', $exercise->items ?? [])->pluck('word')->all()
            : ($exercise->items ?? []);

        return view('guardian.exercises.show', [
            'exercise' => $exercise,
            'attempts' => $attempts,
            'words' => $words,
        ]);
    }

    public function toggle(Request $request, Exercise $exercise): RedirectResponse
    {
        $this->authorizeOwner($request, $exercise);
        $exercise->update(['is_active' => ! $exercise->is_active]);

        return back()->with('success', $exercise->is_active ? 'Latihan diaktifkan semula.' : 'Latihan disembunyikan daripada murid.');
    }

    public function destroy(Request $request, Exercise $exercise): RedirectResponse
    {
        $this->authorizeOwner($request, $exercise);
        $exercise->delete();

        return redirect()->route('guardian.exercises.index')->with('success', 'Latihan telah dipadam.');
    }

    private function authorizeOwner(Request $request, Exercise $exercise): void
    {
        abort_unless($exercise->user_id === $request->user()->id, 403);
    }
}

