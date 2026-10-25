<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_id', 'sign_id', 'viewed_at', 'practice_count', 'best_confidence', 'mastered', 'mastered_at'])]
class SignProgress extends Model
{
    protected $table = 'sign_progress';

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
            'mastered_at' => 'datetime',
            'mastered' => 'boolean',
            'best_confidence' => 'float',
        ];
    }

    public function sign(): BelongsTo
    {
        return $this->belongsTo(Sign::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
