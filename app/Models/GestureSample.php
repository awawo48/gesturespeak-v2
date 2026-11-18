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
     * Supports:
     * - 63 numbers: 21 primary hand landmarks * (x, y, z)
     * - 78 numbers: 63 primary hand + 15 normalised face/trajectory context
     * - 146 numbers: 78 v1 features + 63 second hand + 5 two-hand/pose context flags
     *
     * @param  array<int, float>  $features  63, 78 or 146 numbers
     * @return array<int, float>
     */
    public static function normalise(array $features): array
    {
        $features = array_map('floatval', array_values($features));
        $handNorm = self::normaliseHandSlice(array_slice($features, 0, 63));

        $count = count($features);
        if ($count < 78) {
            return $handNorm;
        }

        $faceNorm = self::clampSlice(array_slice($features, 63, 15), -2.5, 2.5);
        $base78 = array_merge($handNorm, $faceNorm);

        if ($count < 146) {
            return $base78;
        }

        $hand2Norm = self::normaliseHandSlice(array_slice($features, 78, 63));
        $dwiNorm = self::clampSlice(array_slice($features, 141, 5), -3.0, 3.0);

        return array_merge($base78, $hand2Norm, $dwiNorm);
    }

    /**
     * Wrist-relative + scale-invariant normalisation for one 21-landmark hand slice (63 floats).
     * Returns exact zeros when all landmarks are absent (e.g. inactive second hand).
     *
     * @param  array<int, float>  $slice
     * @return array<int, float>
     */
    private static function normaliseHandSlice(array $slice): array
    {
        if (count($slice) < 63) {
            $slice = array_pad($slice, 63, 0.0);
        }

        [$ox, $oy, $oz] = [$slice[0], $slice[1], $slice[2]];
        $rel = [];
        $maxAbs = 0.0;

        for ($i = 0; $i < 21; $i++) {
            foreach ([$ox, $oy, $oz] as $axis => $origin) {
                $v = $slice[$i * 3 + $axis] - $origin;
                $rel[] = $v;
                $maxAbs = max($maxAbs, abs($v));
            }
        }

        if ($maxAbs <= 1e-6) {
            return array_fill(0, 63, 0.0);
        }

        return array_map(fn ($v) => round($v / $maxAbs, 5), $rel);
    }

    /**
     * Clamp and round a context feature slice safely.
     *
     * @param  array<int, float>  $slice
     * @return array<int, float>
     */
    private static function clampSlice(array $slice, float $min, float $max): array
    {
        return array_map(
            fn ($v) => round(max($min, min($max, (float) $v)), 5),
            $slice
        );
    }

    /**
     * Normalise an array of sequence frames (each frame is 63, 78 or 146 numbers).
     *
     * @param  array<int, array<int, float>>  $frames
     * @return array<int, array<int, float>>
     */
    public static function normaliseSequence(array $frames): array
    {
        return array_map(fn ($frame) => self::normalise($frame), $frames);
    }
}
