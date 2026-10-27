<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['label', 'sample_type', 'features', 'user_id'])]
class GestureSample extends Model
{
    protected function casts(): array
    {
        return [
            'features' => 'array',
        ];
    }

    public function scopeStatic($query)
    {
        return $query->where('sample_type', 'static');
    }

    public function scopeSequence($query)
    {
        return $query->where('sample_type', 'sequence');
    }

    /**
     * Wrist-relative + scale-invariant normalisation (same as train_model.py and
     * public/js/gesture-ai.js). Idempotent: normalising already-normalised data
     * returns the same numbers, so raw and exported CSVs can both be imported.
     *
     * @param  array<int, float>  $features  63 numbers: x0,y0,z0 ... x20,y20,z20
     * @return array<int, float>
     */
    public static function normalise(array $features): array
    {
        $features = array_map('floatval', array_values($features));
        [$ox, $oy, $oz] = [$features[0], $features[1], $features[2]];

        $rel = [];
        $maxAbs = 0.0;
        for ($i = 0; $i < 21; $i++) {
            foreach ([$ox, $oy, $oz] as $axis => $origin) {
                $v = $features[$i * 3 + $axis] - $origin;
                $rel[] = $v;
                $maxAbs = max($maxAbs, abs($v));
            }
        }

        $scale = $maxAbs > 1e-6 ? $maxAbs : 1.0;

        return array_map(fn ($v) => round($v / $scale, 5), $rel);
    }

    /**
     * Normalise an array of sequence frames (each frame is 63 numbers).
     *
     * @param  array<int, array<int, float>>  $frames
     * @return array<int, array<int, float>>
     */
    public static function normaliseSequence(array $frames): array
    {
        return array_map(fn ($frame) => self::normalise($frame), $frames);
    }
}
