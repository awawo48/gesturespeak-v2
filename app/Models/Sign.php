<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['category_id', 'label', 'word', 'meaning_en', 'video', 'image', 'instructions', 'is_static', 'sort_order'])]
class Sign extends Model
{
    protected function casts(): array
    {
        return [
            'is_static' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
