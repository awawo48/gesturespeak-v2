<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;

/**
 * Child learner (Option 1 login: Student Code + PIN).
 */
#[Fillable(['student_code', 'name', 'age', 'pin', 'avatar', 'stars', 'created_by'])]
#[Hidden(['pin', 'remember_token'])]
class Student extends Authenticatable
{
    /** Emoji avatars children can choose from. */
    public const AVATARS = [
        'lion' => '🦁', 'rabbit' => '🐰', 'panda' => '🐼', 'tiger' => '🐯',
        'frog' => '🐸', 'monkey' => '🐵', 'fox' => '🦊', 'koala' => '🐨',
        'cat' => '🐱', 'dog' => '🐶', 'owl' => '🦉', 'unicorn' => '🦄',
    ];

    protected function casts(): array
    {
        return [
            'pin' => 'hashed',
        ];
    }

    /** Laravel auth uses this column as the "password". */
    public function getAuthPasswordName(): string
    {
        return 'pin';
    }

    public function getAuthPassword(): string
    {
        return $this->pin;
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'student_guardians')
            ->withPivot('relationship')
            ->withTimestamps();
    }

    public function progress(): HasMany
    {
        return $this->hasMany(SignProgress::class);
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function avatarEmoji(): string
    {
        return self::AVATARS[$this->avatar] ?? '🙂';
    }

    /** Generates a unique, easy-to-read code such as ADAM-4821. */
    public static function generateCode(string $name): string
    {
        $prefix = Str::upper(Str::limit(preg_replace('/[^A-Za-z]/', '', Str::ascii($name)) ?: 'KID', 4, ''));

        do {
            $code = $prefix.'-'.random_int(1000, 9999);
        } while (self::where('student_code', $code)->exists());

        return $code;
    }

    /**
     * Overall and per-category completion percentages.
     * A sign counts as "done" when it is mastered (static) or viewed (dynamic).
     *
     * @return array{overall:int, categories:array<int, array{name:string, icon:?string, color:string, done:int, total:int, percent:int}>}
     */
    public function progressSummary(): array
    {
        $categories = Category::withCount('signs')->with('signs:id,category_id,is_static')->orderBy('sort_order')->get();
        $progress = $this->progress()->get()->keyBy('sign_id');

        $rows = [];
        $doneAll = 0;
        $totalAll = 0;

        foreach ($categories as $category) {
            $done = $category->signs->filter(function ($sign) use ($progress) {
                $p = $progress->get($sign->id);

                return $p && ($p->mastered || (! $sign->is_static && $p->viewed_at));
            })->count();

            $total = $category->signs_count;
            $doneAll += $done;
            $totalAll += $total;

            $rows[] = [
                'name' => $category->name,
                'icon' => $category->icon,
                'color' => $category->color,
                'done' => $done,
                'total' => $total,
                'percent' => $total ? (int) round($done / $total * 100) : 0,
            ];
        }

        return [
            'overall' => $totalAll ? (int) round($doneAll / $totalAll * 100) : 0,
            'categories' => $rows,
        ];
    }
}
