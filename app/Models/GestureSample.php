<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['label', 'features', 'user_id'])]
class GestureSample extends Model
{
    protected function casts(): array
    {
        return [
            'features' => 'array',
        ];
    }
}
