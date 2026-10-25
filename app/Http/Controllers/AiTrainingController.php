<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\GestureSample;
use App\Models\Sign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * AI Studio for Team Member C / Admin:
 * - Collect 21 hand landmarks directly via webcam
 * - View collected sample counts per sign
 * - Export dataset to CSV / JSON for Google Colab / TensorFlow.js training
 */
class AiTrainingController extends Controller
{
    public function studio(): View
    {
        $categories = Category::with(['signs' => fn ($q) => $q->where('is_static', true)])
            ->whereHas('signs', fn ($q) => $q->where('is_static', true))
            ->get();

        $sampleCounts = GestureSample::selectRaw('label, COUNT(*) as total')
            ->groupBy('label')
            ->pluck('total', 'label');

        return view('admin.ai-studio', [
            'categories' => $categories,
            'sampleCounts' => $sampleCounts,
            'totalSamples' => GestureSample::count(),
        ]);
    }

    public function storeSample(Request $request): JsonResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:50'],
            'features' => ['required', 'array', 'size:63'], // 21 landmarks * (x,y,z)
            'features.*' => ['numeric'],
        ]);

        $sample = GestureSample::create([
            'label' => strtoupper(trim($data['label'])),
            'features' => $data['features'],
            'user_id' => Auth::id(),
        ]);

        $totalForLabel = GestureSample::where('label', $sample->label)->count();

        return response()->json([
            'success' => true,
            'id' => $sample->id,
            'label' => $sample->label,
            'total_for_label' => $totalForLabel,
            'total_all' => GestureSample::count(),
        ]);
    }

    /**
     * Export collected samples as CSV for model training in Google Colab / Python.
     */
    public function exportCsv(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="gesturespeak_landmarks_'.date('Ymd_His').'.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');

            // Header row: label, x0, y0, z0, ... x20, y20, z20
            $colNames = ['label'];
            for ($i = 0; $i < 21; $i++) {
                $colNames[] = "x{$i}";
                $colNames[] = "y{$i}";
                $colNames[] = "z{$i}";
            }
            fputcsv($handle, $colNames);

            GestureSample::chunk(500, function ($samples) use ($handle) {
                foreach ($samples as $sample) {
                    $row = array_merge([$sample->label], $sample->features);
                    fputcsv($handle, $row);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    public function clearSamples(Request $request): JsonResponse
    {
        $label = $request->input('label');
        if ($label) {
            GestureSample::where('label', $label)->delete();
        }

        return response()->json([
            'success' => true,
            'total_all' => GestureSample::count(),
        ]);
    }
}
