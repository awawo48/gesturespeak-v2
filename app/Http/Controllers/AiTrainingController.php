<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\GestureSample;
use App\Models\Sign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * AI Studio for Team Member C / Admin:
 * - Collect 21 hand landmarks directly via webcam
 * - View collected sample counts per sign
 * - Export dataset to CSV / JSON for Google Colab / TensorFlow.js training
 * - Import an existing landmark CSV (e.g. sign_language_dataset.csv)
 * - Test the dataset live with an in-browser KNN classifier
 */
class AiTrainingController extends Controller
{
    public function studio(): View
    {
        $categories = Category::with(['signs' => fn ($q) => $q->orderBy('sort_order')])
            ->whereHas('signs')
            ->orderBy('sort_order')
            ->get();

        $allCategories = Category::orderBy('sort_order')->get();

        return view('admin.ai-studio', [
            'categories' => $categories,
            'allCategories' => $allCategories,
            'sampleCounts' => $this->counts(),
            'sequenceCounts' => $this->sequenceCounts(),
            'totalSamples' => GestureSample::count(),
            'totalStatic' => GestureSample::where('sample_type', 'static')->count(),
            'totalSequence' => GestureSample::where('sample_type', 'sequence')->count(),
        ]);
    }

    /**
     * Quick register a new sign into the signs database from AI Studio.
     */
    public function storeSign(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'label' => ['required', 'string', 'max:50'],
            'word' => ['required', 'string', 'max:100'],
            'meaning_en' => ['nullable', 'string', 'max:100'],
        ]);

        $label = strtoupper(trim($data['label']));
        $sign = Sign::firstOrCreate(
            ['category_id' => $data['category_id'], 'label' => $label],
            [
                'word' => trim($data['word']),
                'meaning_en' => $data['meaning_en'] ?? null,
                'is_static' => true,
                'instructions' => 'Salin bentuk tangan perlahan-lahan dan tahan di hadapan kamera.',
                'sort_order' => (Sign::where('category_id', $data['category_id'])->max('sort_order') ?? 0) + 1,
            ]
        );

        return response()->json([
            'success' => true,
            'sign' => [
                'id' => $sign->id,
                'label' => $sign->label,
                'word' => $sign->word,
                'category_id' => $sign->category_id,
            ],
            'message' => "Isyarat {$sign->word} ({$sign->label}) berjaya didaftarkan!",
        ]);
    }

    /**
     * Save one or many samples in a single request:
     * - static samples: array of 63 numbers per sample
     * - sequence samples: 1 sequence clip consisting of 10-60 frames (each 63 numbers)
     */
    public function storeSample(Request $request): JsonResponse
    {
        $sampleType = $request->input('sample_type', 'static');
        $label = strtoupper(trim($request->input('label', '')));

        if ($sampleType === 'sequence') {
            $data = $request->validate([
                'label' => ['required', 'string', 'max:50'],
                'frames' => ['required', 'array', 'min:8', 'max:120'], // Sequence of frames
                'frames.*' => ['required', 'array', 'size:63'],
                'frames.*.*' => ['numeric'],
            ]);

            $normFrames = GestureSample::normaliseSequence($data['frames']);

            GestureSample::create([
                'label' => $label,
                'sample_type' => 'sequence',
                'features' => $normFrames,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'sample_type' => 'sequence',
                'label' => $label,
                'saved' => 1,
                'frames_count' => count($normFrames),
                'counts' => $this->counts(),
                'sequence_counts' => $this->sequenceCounts(),
                'total_all' => GestureSample::count(),
            ]);
        }

        // Static burst or single samples
        $data = $request->validate([
            'label' => ['required', 'string', 'max:50'],
            'samples' => ['required', 'array', 'min:1', 'max:200'],
            'samples.*' => ['required', 'array', 'size:63'], // 21 landmarks * (x,y,z)
            'samples.*.*' => ['numeric'],
        ]);

        $now = now();
        $rows = array_map(fn ($features) => [
            'label' => $label,
            'sample_type' => 'static',
            'features' => json_encode(GestureSample::normalise($features)),
            'user_id' => Auth::id(),
            'created_at' => $now,
            'updated_at' => $now,
        ], $data['samples']);

        GestureSample::insert($rows);

        return response()->json([
            'success' => true,
            'sample_type' => 'static',
            'label' => $label,
            'saved' => count($rows),
            'counts' => $this->counts(),
            'sequence_counts' => $this->sequenceCounts(),
            'total_all' => GestureSample::count(),
        ]);
    }

    /**
     * Import a landmark CSV. Supported headers:
     *   label, [sample_id, frame_index, ...], lm0_x, lm0_y, lm0_z ... lm20_z   (MediaPipe recorder)
     *   label, x0, y0, z0 ... x20, y20, z20                                   (this studio's export)
     */
    public function importCsv(Request $request): RedirectResponse
    {
        $request->validate([
            'dataset' => ['required', 'file', 'mimes:csv,txt', 'max:20480'],
            'mode' => ['required', 'in:append,replace'],
        ]);

        $handle = fopen($request->file('dataset')->getRealPath(), 'r');
        $header = fgetcsv($handle);
        if (! $header) {
            return back()->with('error', 'Fail CSV kosong.');
        }
        $header = array_map(fn ($h) => strtolower(trim($h, " \t\n\r\0\x0B\xEF\xBB\xBF\"")), $header);

        $labelIdx = array_search('label', $header, true);
        $featureIdx = [];
        for ($i = 0; $i < 21; $i++) {
            foreach (['x', 'y', 'z'] as $axis) {
                $idx = array_search("lm{$i}_{$axis}", $header, true);
                if ($idx === false) {
                    $idx = array_search("{$axis}{$i}", $header, true);
                }
                if ($idx === false) {
                    fclose($handle);

                    return back()->with('error', "Format CSV tidak sah: kolum untuk titik {$i} ({$axis}) tidak dijumpai.");
                }
                $featureIdx[] = $idx;
            }
        }
        if ($labelIdx === false) {
            fclose($handle);

            return back()->with('error', "Format CSV tidak sah: kolum 'label' tidak dijumpai.");
        }

        $rows = [];
        $skipped = 0;
        $labelsInFile = [];
        $now = now();

        while (($line = fgetcsv($handle)) !== false) {
            $label = strtoupper(trim($line[$labelIdx] ?? ''));
            $features = array_map(fn ($idx) => $line[$idx] ?? null, $featureIdx);

            if ($label === '' || in_array(null, $features, true) || array_filter($features, fn ($v) => ! is_numeric($v))) {
                $skipped++;

                continue;
            }

            $labelsInFile[$label] = true;
            $rows[] = [
                'label' => $label,
                'features' => json_encode(GestureSample::normalise($features)),
                'user_id' => Auth::id(),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        fclose($handle);

        $imported = count($rows);

        if ($imported > 0) {
            if ($request->mode === 'replace') {
                GestureSample::whereIn('label', array_keys($labelsInFile))->delete();
            }
            foreach (array_chunk($rows, 500) as $chunk) {
                GestureSample::insert($chunk);
            }
        }

        if ($imported === 0) {
            return back()->with('error', "Tiada baris sah diimport ({$skipped} baris dilangkau).");
        }

        $msg = "Berjaya import {$imported} sampel untuk label: ".implode(', ', array_keys($labelsInFile)).'.';
        if ($skipped) {
            $msg .= " ({$skipped} baris tidak sah dilangkau.)";
        }

        return back()->with('success', $msg);
    }

    /**
     * All samples as compact JSON for the in-browser KNN tester / recognition page.
     */
    public function dataset(): JsonResponse
    {
        $staticSamples = [];
        $sequenceSamples = [];

        GestureSample::select(['id', 'label', 'sample_type', 'features'])->chunkById(1000, function ($chunk) use (&$staticSamples, &$sequenceSamples) {
            foreach ($chunk as $s) {
                if ($s->sample_type === 'sequence') {
                    $sequenceSamples[] = ['label' => $s->label, 'frames' => $s->features];
                } else {
                    $staticSamples[] = ['label' => $s->label, 'f' => $s->features];
                }
            }
        });

        return response()->json([
            'samples' => $staticSamples,
            'sequences' => $sequenceSamples,
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

            GestureSample::chunkById(500, function ($samples) use ($handle) {
                foreach ($samples as $sample) {
                    $row = array_merge([$sample->label], $sample->features);
                    fputcsv($handle, $row);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Delete samples for one label, or everything when all=1.
     */
    public function clearSamples(Request $request): JsonResponse
    {
        $request->validate([
            'label' => ['nullable', 'string', 'max:50'],
            'all' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('all')) {
            GestureSample::query()->delete();
        } elseif ($request->filled('label')) {
            GestureSample::where('label', strtoupper(trim($request->input('label'))))->delete();
        } else {
            return response()->json(['success' => false, 'message' => 'Tiada label dipilih.'], 422);
        }

        return response()->json([
            'success' => true,
            'counts' => $this->counts(),
            'total_all' => GestureSample::count(),
        ]);
    }

    private function counts(): array
    {
        return GestureSample::where('sample_type', 'static')
            ->selectRaw('label, COUNT(*) as total')
            ->groupBy('label')
            ->orderBy('label')
            ->pluck('total', 'label')
            ->map(fn ($n) => (int) $n)
            ->all();
    }

    private function sequenceCounts(): array
    {
        return GestureSample::where('sample_type', 'sequence')
            ->selectRaw('label, COUNT(*) as total')
            ->groupBy('label')
            ->orderBy('label')
            ->pluck('total', 'label')
            ->map(fn ($n) => (int) $n)
            ->all();
    }
}
