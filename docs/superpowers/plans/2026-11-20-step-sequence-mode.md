# Step-by-Step Sequence Mode (`sample_type = 'steps'`) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a third recognition mode — **Urutan Langkah (`sample_type = 'steps'`)** — allowing admins to record 2–6 ordered keyframe poses per dynamic sign in AI Studio and automatically activating step-by-step live guidance on `learn/sign.blade.php` for signs that have recorded `steps`, without altering existing `static` (KNN) or `sequence` (DTW) modes.

**Architecture:** Reuse `gesture_samples` (`sample_type = 'steps'`, JSON `features` array of `{ name, f }`) with zero DB migrations. Add `stepFrameDistance()` and `StepSequenceClassifier` to `public/js/gesture-ai.js` using the existing `146`-feature multimodal vectors (2 hands + face/pose/position context). Wire minimal, functional UI first in `admin/ai-studio.blade.php` and `learn/sign.blade.php` (the user explicitly asked to defer theme/visual polish until the core feature works).

**Tech Stack:** Laravel 12 (PHPUnit), Vanilla ES Modules (`public/js/gesture-ai.js`, Node `node:test`), Blade + MediaPipe Tasks Vision `@0.10.14`.

**Spec:** `docs/superpowers/specs/2026-11-20-step-sequence-mode-design.md`

## Global Constraints

- Zero DB schema migrations: store in existing `gesture_samples` with `sample_type = 'steps'`.
- Backward compatibility: existing `991` static samples, `21` DTW `sequence` clips, `GET /api/ai/dataset` keys (`samples`, `sequences`), and `GET /admin/ai-studio/export` (`static` only) must remain intact.
- 100% client-side browser inference; keep interleaved `HandLandmarker` (`numHands: 2`) + `PoseLandmarker` / `FaceLandmarker` loop (`>= 20 FPS`).
- Step bounds: `2` to `6` steps per `steps` sample; each step vector `63..146` floats, step `name` max `40` chars defaulting to `"Langkah N"`.
- Functional UI first: keep Blade/CSS changes clean and minimal — theme styling polish is deferred to a later pass per user request.

## Review Focus

1. **Same handshape, different spatial position** (e.g. `TERIMA_KASIH` Step 1 at chin vs Step 2 forward): if `stepFrameDistance` ignores `63..66` when `f[77] >= 0.45`, Step 1 and Step 2 collapse into the same pose and auto-pass instantly. *(Pinned in Task 2 tests.)*
2. **Clearing `steps` accidentally wiping DTW `sequence` clips:** calling `clearSamples` from AI Studio in `steps` mode without `sample_type` filter would delete the sign's DTW clips. *(Pinned in Task 1 tests.)*
3. **Multiple templates with different step counts for the same label:** e.g. one 2-step sample and one 3-step sample recorded for the same sign must not crash or index out-of-bounds in `StepSequenceClassifier`. *(Pinned in Task 2 tests.)*
4. **Brief hand occlusion between steps:** when a child lowers or shifts their hand for `< 1200ms` between Step 1 and Step 2, progress must not reset prematurely; absence `> 1200ms` must reset cleanly. *(Pinned in Task 2 tests.)*
5. **Fallback safety on `learn/sign.blade.php`:** dynamic signs with `0` `steps` samples (and all static signs) must remain 100% on the existing `DynamicGestureEngine` / `KnnClassifier` path even when `data.steps` is empty or missing. *(Pinned in Task 4 tests.)*

---

### Task 1: Backend Support for `sample_type = 'steps'`

**Files:**
- Modify: `app/Models/GestureSample.php:18-120`
- Modify: `app/Http/Controllers/AiTrainingController.php:25-153,270-380`
- Create: `tests/Feature/StepSequenceSampleTest.php`

**Interfaces:**
- Consumes: `GestureSample::normalise(array $features): array`
- Produces:
  - `GestureSample::scopeSteps($query)`
  - `GestureSample::normaliseSteps(array $steps): array` returning `[['name' => string, 'f' => float[]], ...]`
  - `POST /admin/ai-studio/sample` accepting `{ sample_type: 'steps', label: string, steps: [{ name?: string, f: number[] }] }` (2–6 items) and returning `{ success, sample_type: 'steps', label, saved: 1, steps_count, counts, sequence_counts, step_counts, total_steps, total_all }`
  - `GET /api/ai/dataset` and `GET /admin/ai-studio/dataset` returning `{ samples, sequences, steps: [{ label, steps }] }`
  - `POST /admin/ai-studio/clear` accepting optional `sample_type` (`static|sequence|steps`) and returning `step_counts`
  - `AiTrainingController::studio()` passing `stepCounts` and `totalSteps` to `admin.ai-studio`

- [ ] **Step 1: Write failing PHPUnit feature tests in `tests/Feature/StepSequenceSampleTest.php`**

Cover:
1. `test_normalise_steps_normalises_146_vectors_and_fills_default_names`: passes 2 steps (one with blank name, one with `"  Buka  "`) and asserts default `"Langkah 1"`, trimmed `"Buka"`, and idempotent `146`-float normalized `f`.
2. `test_admin_can_store_steps_sample_and_rejects_out_of_range_step_counts`: `POST route('admin.ai.sample.store')` with 2 steps returns `200` with `sample_type: 'steps'`, `steps_count: 2`, `step_counts: ['BUKU' => 1]`; 1 step and 7 steps return `422`.
3. `test_dataset_endpoint_includes_steps_without_breaking_samples_or_sequences`: seeds 1 `static`, 1 `sequence`, 1 `steps`, calls `GET route('ai.public_dataset')`, asserts all 3 arrays have count `1` and `steps[0]` has `label` and `steps`.
4. `test_clear_samples_with_sample_type_steps_preserves_sequence_and_static`: seeds `static`, `sequence`, and `steps` for `'BUKU'`, posts `route('admin.ai.clear')` with `['label' => 'BUKU', 'sample_type' => 'steps']`, asserts only `steps` deleted while `static` and `sequence` remain; also asserts `exportCsv` (`route('admin.ai.export')`) outputs only the `static` row.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=StepSequenceSampleTest`
Expected: FAIL (`Call to undefined method App\Models\GestureSample::normaliseSteps()` / validation failure).

- [ ] **Step 3: Implement `scopeSteps` and `normaliseSteps` in `app/Models/GestureSample.php`**

Add `scopeSteps($query)` filtering `sample_type = 'steps'` and `public static function normaliseSteps(array $steps): array` mapping each step index `$i` to `['name' => $cleanName !== '' ? mb_substr($cleanName, 0, 40) : 'Langkah '.($i + 1), 'f' => self::normalise($step['f'])]`.

- [ ] **Step 4: Implement `steps` handling in `app/Http/Controllers/AiTrainingController.php`**

- Add `private function stepCounts(): array` mirroring `sequenceCounts()` for `sample_type = 'steps'`.
- In `studio()`: pass `'stepCounts' => $this->stepCounts()` and `'totalSteps' => GestureSample::where('sample_type', 'steps')->count()`.
- In `storeSample()`: handle `$sampleType === 'steps'` (validate `label`, `steps` `min:2|max:6`, `steps.*.name` `nullable|string|max:40`, `steps.*.f` `required|array|min:63|max:146`, `steps.*.f.*` `numeric`; persist with `GestureSample::normaliseSteps($data['steps'])`; return `step_counts` and `total_steps` on all 3 branches).
- In `dataset()`: collect `sample_type === 'steps'` into `$stepSamples[] = ['label' => $s->label, 'steps' => $s->features]` and include `'steps' => $stepSamples` in the JSON response (ensure `else` only catches `static`, not `steps`).
- In `clearSamples()`: validate `'sample_type' => ['nullable', 'in:static,sequence,steps']`, apply `->when($request->filled('sample_type'), fn ($q) => $q->where('sample_type', $request->input('sample_type')))` when deleting by `label` (or `all`), and include `'step_counts' => $this->stepCounts()` in the JSON response.

- [ ] **Step 5: Run PHPUnit tests to verify pass and no regressions**

Run: `php artisan test --filter="StepSequenceSampleTest|DynamicGestureSampleTest"`
Expected: PASS (all tests green).

- [ ] **Step 6: Commit**

```bash
git add app/Models/GestureSample.php app/Http/Controllers/AiTrainingController.php tests/Feature/StepSequenceSampleTest.php
git commit -m "feat: add backend support for step-by-step gesture samples"
```

---

### Task 2: JS Recognition Engine (`stepFrameDistance` & `StepSequenceClassifier`)

**Files:**
- Modify: `public/js/gesture-ai.js` (add exports near end of file)
- Create: `tests/js/step-sequence.test.mjs`

**Interfaces:**
- Consumes: `multimodalSqDist`, `extractHandGeometry`, `clamp` in `public/js/gesture-ai.js`
- Produces:
  - `export function stepFrameDistance(frameA, frameB, options = {})` → `number` (squared distance, minimum across `mirror = false | true`)
  - `export class StepSequenceClassifier` with:
    - `load(stepSamples)` (`[{ label, steps: [{ name, f }] }]`)
    - `hasTemplates(label)` → `boolean`
    - `getStepMeta(label)` → `{ totalSteps: number, stepNames: string[] } | null`
    - `reset(label = null)`
    - `update(features, options = { targetLabel: null, mode: 'target-locked', nowMs: Date.now() })` → `{ matched: boolean, completed: boolean, label: string|null, confidence: number, currentStep: number, totalSteps: number, stepNames: string[], status: 'idle'|'tracking'|'step_advanced'|'completed'|'timeout_reset'|'cooldown'|'no_templates' }`

- [ ] **Step 1: Write failing Node ES module tests in `tests/js/step-sequence.test.mjs`**

Cover:
1. `2-step & 3-step ordered progression`: advances only after `HOLD_FRAMES` (3 consecutive matching frames per step), returns `status: 'step_advanced'` on intermediate steps and `matched: true, completed: true` on final step.
2. `Out-of-order & skipped-step rejection`: feeding Step 2 before Step 1 (or Step 3 before Step 2) stays at `currentStep: 0` / `1` and never completes.
3. `Same handshape, different spatial position (shoulder/face context 63..66)`: two steps with identical hand landmarks (`0..62`) but different normalized positions in `63..66` (`f[77] = 0.5` or `1.0`) must be clearly separated so holding Step 1 does not trigger Step 2.
4. `Two-hand vs one-hand distinction (78..145)`: a 2-hand step template (`f[141] = 1.0`) penalizes a 1-hand live frame (`f[141] = 0.0`) so it does not falsely match.
5. `Timeout reset & brief hand-loss grace`: pausing `> 5000ms` after Step 1 returns `status: 'timeout_reset'` and resets `currentStep` to `0`; passing `features = null` for `< 1200ms` preserves `currentStep: 1`, while `> 1200ms` resets to `0`.
6. `Mixed template lengths & open-set mode`: loading both a 2-step and a 3-step template for the same label does not throw; `mode: 'open-set'` across two labels (`BUKU` and `TERIMA_KASIH`) completes whichever label finishes its sequence first.

- [ ] **Step 2: Run test to verify it fails**

Run: `node --test tests/js/step-sequence.test.mjs`
Expected: FAIL (`SyntaxError` / missing export `StepSequenceClassifier`).

- [ ] **Step 3: Implement `stepFrameDistance` and `StepSequenceClassifier` in `public/js/gesture-ai.js`**

- `stepFrameDistance(frameA, frameB, { geomA, geomB, geom2A, geom2B } = {})`:
  - Computes distance for `mirror = false` and `mirror = true` and returns `Math.min(dNormal, dMirror)`.
  - Uses `multimodalSqDist` for primary hand (`0..62`) + face (`63..76` when both have `f[77] > 0.55`).
  - When face is absent on either frame but both have shoulder/pose or camera trajectory (`f[77] >= 0.45`), adds position distance over `63..66` (flipping X at `63, 65` when `mirror = true`) weighted at `0.85` so spatial transitions with the same handshape are clearly distinguished.
  - When both vectors have length `>= 145`: compares `hasHand2` (`f[141]`), penalizes mismatch (`+0.85`), and when both have `hasHand2 > 0.5`, adds second-hand shape (`78..140` + `extractHandGeometry(vec, 78)`) and relative wrist offset (`142..144`).
- `StepSequenceClassifier`:
  - Defaults: `matchThreshold = 56`, `holdFrames = 3`, `timeoutMs = 5000`, `handLostGraceMs = 1200`, `cooldownMs = 700`.
  - Distance-to-confidence: `Math.round(clamp(1 - Math.sqrt(bestDist) / 1.45, 0, 1) * 99)` (tunable via constructor options).
  - Tracks per-template state (`currentStep`, `holdCount`, `stepScores`, `lastAdvanceTime`, `handLostSince`) so mixed step-count templates for the same label work safely without index errors.
  - Supports both `target-locked` and `open-set` modes.

- [ ] **Step 4: Run all JS tests to verify pass and no regressions**

Run: `node --test tests/js/step-sequence.test.mjs tests/js/dtw-and-rules.test.mjs tests/js/dynamic-engine.test.mjs tests/js/gesture-features.test.mjs`
Expected: PASS (all tests green).

- [ ] **Step 5: Commit**

```bash
git add public/js/gesture-ai.js tests/js/step-sequence.test.mjs
git commit -m "feat: add StepSequenceClassifier and stepFrameDistance to gesture-ai.js"
```

---

### Task 3: AI Studio 3rd Mode Tab ("Urutan Langkah")

**Files:**
- Modify: `resources/views/admin/ai-studio.blade.php`
- Modify: `tests/Feature/DynamicViewsSmokeTest.php`

**Interfaces:**
- Consumes:
  - Blade vars `$stepCounts`, `$totalSteps` from `AiTrainingController::studio()`
  - `POST route('admin.ai.sample.store')` with `sample_type: 'steps'`
  - `POST route('admin.ai.clear')` with `sample_type: 'steps'`
  - `GET route('admin.ai.dataset')` returning `steps`
  - `StepSequenceClassifier` from `public/js/gesture-ai.js`
- Produces:
  - Functional 3rd tab `#btn-cam-mode-steps` (`data-cam-mode="steps"`), draft step recorder panel `#steps-recorder-panel`, and live step progress readout in the Studio Live Tester.

- [ ] **Step 1: Add smoke test assertions in `tests/Feature/DynamicViewsSmokeTest.php`**

Assert `GET route('admin.ai.studio')` as admin renders `btn-cam-mode-steps`, `steps-recorder-panel`, `StepSequenceClassifier`, and `data.steps`.

- [ ] **Step 2: Run smoke test to verify it fails**

Run: `php artisan test --filter=DynamicViewsSmokeTest`
Expected: FAIL (missing `btn-cam-mode-steps`).

- [ ] **Step 3: Wire the 3rd mode tab, step recorder, and Live Tester in `resources/views/admin/ai-studio.blade.php`**

Keep styling clean and minimal (reusing existing `.cam-mode-tab`, `.btn`, `.hud-pill` classes — visual polish deferred per user request):
- Update `.cam-mode-switcher` to 3 columns and add `#btn-cam-mode-steps` (`data-cam-mode="steps"`, label **"Urutan Langkah"**).
- When `camMode === 'steps'`, activate `numHands: 2` and `PoseLandmarker` (same as `dynamic`), show `#steps-recorder-panel`, and hide static/DTW-only buttons.
- `#steps-recorder-panel` provides:
  - Draft steps pill list (`#steps-draft-list`) showing `1. Langkah 1 ✓`, etc. (max `6`).
  - Optional step name input (`#input-step-name`, placeholder `"Nama langkah (cth: Rapat)"`).
  - Buttons: **Tangkap Langkah** (`#btn-capture-step`, with optional 2s countdown checkbox + `Space` shortcut when in `steps` mode), **Buang Terakhir** (`#btn-undo-step`), **Set Semula** (`#btn-reset-steps`), **Simpan Urutan** (`#btn-save-steps`, enabled at `>= 2` steps), and **Padam Langkah** (`#btn-clear-steps` calling `clearSamples` with `sample_type: 'steps'`).
- Import `StepSequenceClassifier` from `gesture-ai.js`, load `data.steps` on dataset fetch and after save/clear, and run `stepClassifier.update(vec146, { targetLabel, mode: liveTestMode, nowMs })` in the Live Tester loop when `camMode === 'steps'`, displaying live step progress pills (`Langkah 1 ✓ → Langkah 2 …`).
- Display `stepCounts[label]` badge in the sign selector/list and `totalSteps` in the summary stats.

- [ ] **Step 4: Run smoke test to verify pass**

Run: `php artisan test --filter=DynamicViewsSmokeTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/views/admin/ai-studio.blade.php tests/Feature/DynamicViewsSmokeTest.php
git commit -m "feat: add Urutan Langkah recording and testing mode to AI Studio"
```

---

### Task 4: Student Learning Page Auto-Activation (`learn/sign.blade.php`) & Full Verification

**Files:**
- Modify: `resources/views/learn/sign.blade.php`
- Modify: `tests/Feature/DynamicViewsSmokeTest.php`

**Interfaces:**
- Consumes:
  - `StepSequenceClassifier` from `public/js/gesture-ai.js`
  - `data.steps` from `GET route('ai.public_dataset')`
  - Existing `onGestureSuccess(conf)` callback in `resources/views/learn/sign.blade.php`
- Produces:
  - Automatic switch to `StepSequenceClassifier` for any dynamic sign (`!isStaticSign`) that has `>= 1` `steps` template, with minimal functional step progress pills `#step-progress-bar`, and zero behavior change for signs with `0` `steps` templates.

- [ ] **Step 1: Add smoke test assertions in `tests/Feature/DynamicViewsSmokeTest.php`**

Assert `GET route('learn.sign', $dynamicSign)` renders `StepSequenceClassifier`, `step-progress-bar`, and `data.steps`.

- [ ] **Step 2: Run smoke test to verify it fails**

Run: `php artisan test --filter=DynamicViewsSmokeTest`
Expected: FAIL (missing `step-progress-bar` / `StepSequenceClassifier` on learn sign view).

- [ ] **Step 3: Wire `StepSequenceClassifier` auto-activation in `resources/views/learn/sign.blade.php`**

- Import `StepSequenceClassifier` alongside `DynamicGestureEngine` from `gesture-ai.js`.
- Instantiate `const stepClassifier = new StepSequenceClassifier();` and load `if (data.steps) stepClassifier.load(data.steps);` inside `loadDataset()`.
- Add helper `isStepModeActive()` returning `!isStaticSign && stepClassifier.hasTemplates(targetLabel)`.
- Add compact functional `#step-progress-bar` (hidden by default; shown when `isStepModeActive()`) rendering numbered step pills (`Langkah 1 ✓ → Langkah 2 …`) and updating the camera header badge to `"Kamera AI Urutan Langkah (N Langkah)"`.
- In the camera prediction loop for `!isStaticSign`:
  - If `isStepModeActive()`: feed `vec146` (or `null` when hand absent) into `stepClassifier.update(vec146, { targetLabel, mode: 'target-locked', nowMs })`, update `#step-progress-bar` and status text (*"Buat Langkah 1..."* → *"Bagus! Langkah 1 betul. Sekarang buat Langkah 2..."*), and call `onGestureSuccess(Math.max(60, res.confidence || 85))` when `res.completed === true`.
  - Else: execute existing `DynamicGestureEngine` path completely untouched.

- [ ] **Step 4: Run full automated test suite (PHPUnit + Node JS tests)**

Run:
```bash
php artisan test
node --test tests/js/step-sequence.test.mjs tests/js/dtw-and-rules.test.mjs tests/js/dynamic-engine.test.mjs tests/js/gesture-features.test.mjs
```
Expected: ALL PASS with zero regressions.

- [ ] **Step 5: Commit**

```bash
git add resources/views/learn/sign.blade.php tests/Feature/DynamicViewsSmokeTest.php
git commit -m "feat: auto-activate step-by-step recognition on student sign page"
```
