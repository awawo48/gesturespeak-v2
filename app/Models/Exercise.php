<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Teacher-made practice (Latihan): multiple-choice sign quiz or word spelling.
 * - choice   : `items` = list of sign IDs
 * - spelling : `items` = list of words (A-Z only)
 */
#[Fillable(['user_id', 'title', 'type', 'items', 'is_active'])]
class Exercise extends Model
{
    public const TYPE_CHOICE = 'choice';
    public const TYPE_SPELLING = 'spelling';

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExerciseAttempt::class);
    }

    public function typeLabel(): string
    {
        return $this->type === self::TYPE_SPELLING ? 'Mengeja Perkataan' : 'Pilihan Jawapan';
    }
}

