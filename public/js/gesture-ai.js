/**
 * GestureSpeak shared AI helpers (ES module).
 *
 * - normaliseLandmarks(): converts 21 MediaPipe landmarks into 63 numbers.
 *   Same formula as train_model.py and GestureSample::normalise() in PHP:
 *   wrist (lm0) becomes origin, then everything is divided by the max abs value.
 *   => position / distance from camera no longer matter.
 *
 * - KnnClassifier: tiny k-nearest-neighbour classifier that runs in the browser.
 *   It is mirror-invariant (left & right hand both work).
 */

export function normaliseLandmarks(lm) {
    const ox = lm[0].x, oy = lm[0].y, oz = lm[0].z;
    const rel = new Array(63);
    let maxAbs = 0;
    for (let i = 0; i < 21; i++) {
        const x = lm[i].x - ox, y = lm[i].y - oy, z = lm[i].z - oz;
        rel[i * 3] = x; rel[i * 3 + 1] = y; rel[i * 3 + 2] = z;
        maxAbs = Math.max(maxAbs, Math.abs(x), Math.abs(y), Math.abs(z));
    }
    const s = maxAbs > 1e-6 ? maxAbs : 1;
    return rel.map(v => Math.round((v / s) * 1e5) / 1e5);
}

export function isPinkyUpShape(lm) {
    if (!lm || lm.length < 21) return false;

    const dist = (a, b) => Math.hypot(a.x - b.x, a.y - b.y);

    const wrist = lm[0];
    const dPinky = dist(wrist, lm[20]);
    const dPinkyBase = dist(wrist, lm[17]);
    const dIndex = dist(wrist, lm[8]);
    const dMiddle = dist(wrist, lm[12]);
    const dRing = dist(wrist, lm[16]);

    // Pinky must be extended outwards/upwards
    const pinkyExtended = dPinky > dPinkyBase * 1.25 && lm[20].y < lm[18].y;

    // Index, Middle, Ring must be curled into the fist
    const othersCurled = (dIndex < dPinky * 0.85) &&
                         (dMiddle < dPinky * 0.85) &&
                         (dRing < dPinky * 0.85);

    // Pinky tip must also be higher (smaller Y in screen coords) than other 3 fingertips
    const pinkyHighest = (lm[20].y < lm[8].y - 0.02) &&
                         (lm[20].y < lm[12].y - 0.02) &&
                         (lm[20].y < lm[16].y - 0.02);

    return pinkyExtended && (othersCurled || pinkyHighest);
}

function sqDist(a, b, mirror) {
    let d = 0;
    for (let i = 0; i < 63; i++) {
        const av = (mirror && i % 3 === 0) ? -a[i] : a[i];
        const diff = av - b[i];
        d += diff * diff;
    }
    return d;
}

export class KnnClassifier {
    constructor(k = 5) {
        this.k = k;
        this.samples = []; // [{ label, f: Float32Array(63) }]
    }

    load(samples) {
        this.samples = samples
            .filter(s => Array.isArray(s.f) && s.f.length === 63)
            .map(s => ({ label: s.label, f: Float32Array.from(s.f) }));
    }

    get size() { return this.samples.length; }
    get labels() { return [...new Set(this.samples.map(s => s.label))]; }

    /** @returns {{label:string|null, confidence:number, distance:number}} */
    predict(features, motionTracker = null) {
        if (this.samples.length === 0) return { label: null, confidence: 0, distance: Infinity };

        const best = []; // sorted ascending by distance, length <= k
        for (const s of this.samples) {
            const d = Math.min(sqDist(features, s.f, false), sqDist(features, s.f, true));
            if (best.length < this.k || d < best[best.length - 1].d) {
                best.push({ d, label: s.label });
                best.sort((a, b) => a.d - b.d);
                if (best.length > this.k) best.pop();
            }
        }

        const votes = {};
        best.forEach(b => { votes[b.label] = (votes[b.label] || 0) + 1 / (b.d + 1e-3); });

        // Disambiguation for signs sharing identical hand shape (e.g. static 'I' vs dynamic 'J')
        const iScore = votes['I'] || 0;
        const jScore = votes['J'] || 0;
        if (iScore > 0 || jScore > 0) {
            let isJMotion = false;
            if (motionTracker) {
                const jCheck = motionTracker.detectJMotion();
                if (jCheck && jCheck.matched) {
                    isJMotion = true;
                }
            }
            if (isJMotion) {
                // Dynamic J hook motion active => this is 'J'!
                votes['J'] = jScore + iScore;
                delete votes['I'];
            } else {
                // Not performing J hook motion => static pinky-up is ALWAYS 'I', NEVER 'J'!
                votes['I'] = iScore + jScore;
                delete votes['J'];
            }
        }

        let top = null, topScore = 0, total = 0;
        for (const [label, score] of Object.entries(votes)) {
            total += score;
            if (score > topScore) { top = label; topScore = score; }
        }

        // Penalise if the nearest sample is still far away (unknown gesture)
        const nearest = Math.sqrt(best[0].d);
        const distanceFactor = Math.max(0, Math.min(1, 1.6 - nearest)); // ~1 when close, 0 when far
        const confidence = Math.round((topScore / total) * distanceFactor * 100);

        return { label: top, confidence, distance: nearest };
    }
}

/** Majority vote over the last N predictions to stop flickering. */
export class PredictionSmoother {
    constructor(size = 7) { this.size = size; this.buf = []; }
    push(label) {
        this.buf.push(label);
        if (this.buf.length > this.size) this.buf.shift();
        const counts = {};
        this.buf.forEach(l => { if (l) counts[l] = (counts[l] || 0) + 1; });
        let best = null, n = 0;
        for (const [l, c] of Object.entries(counts)) if (c > n) { best = l; n = c; }
        return n >= Math.ceil(this.size / 2) ? best : null;
    }
    reset() { this.buf = []; }
}

/**
 * HandMotionTracker: Tracks trajectory of key landmarks over time
 * to differentiate static signs (like 'I') from dynamic motion signs (like 'J').
 */
export class HandMotionTracker {
    constructor(bufferSize = 45) {
        this.bufferSize = bufferSize;
        this.history = []; // [{ time, pinky: {x,y}, wrist: {x,y}, index: {x,y} }]
        this.lastJDetection = 0;
    }

    push(landmarks) {
        if (!landmarks || landmarks.length < 21) return;
        const now = performance.now();
        // landmarks[0] = wrist, landmarks[8] = index tip, landmarks[20] = pinky tip
        this.history.push({
            time: now,
            pinky: { x: landmarks[20].x, y: landmarks[20].y },
            wrist: { x: landmarks[0].x, y: landmarks[0].y },
            index: { x: landmarks[8].x, y: landmarks[8].y }
        });

        // Keep buffer trimmed to ~1.5s of history
        while (this.history.length > this.bufferSize || (this.history.length > 2 && now - this.history[0].time > 1500)) {
            this.history.shift();
        }
    }

    reset() {
        this.history = [];
        this.lastJDetection = 0;
    }

    getMovementMagnitude(frames = 15) {
        if (this.history.length < 3) return 0;
        const recent = this.history.slice(-Math.min(frames, this.history.length));
        let minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity;
        for (const p of recent) {
            const px = p.pinky.x, py = p.pinky.y;
            if (px < minX) minX = px;
            if (px > maxX) maxX = px;
            if (py < minY) minY = py;
            if (py > maxY) maxY = py;
        }
        return Math.hypot(maxX - minX, maxY - minY);
    }

    isStill(threshold = 0.06) {
        if (this.history.length < 4) return true;
        return this.getMovementMagnitude(15) < threshold;
    }

    detectJMotion() {
        const now = performance.now();
        // Latch detection for 1500ms so UI and student view register smoothly
        if (now - this.lastJDetection < 1500) {
            return { matched: true, confidence: 95 };
        }
        if (this.history.length < 6) return { matched: false, confidence: 0 };

        const pts = this.history.map(h => h.pinky);
        const n = pts.length;

        // Find max Y (lowest point on screen in canvas coordinate space where Y=1 is bottom)
        let maxY = -Infinity;
        let bottomIdx = -1;
        for (let i = 0; i < n; i++) {
            if (pts[i].y > maxY) {
                maxY = pts[i].y;
                bottomIdx = i;
            }
        }

        // Bottom point shouldn't be at the very first frames
        if (bottomIdx < 2) {
            return { matched: false, confidence: 0 };
        }

        // Find highest point (minimum Y) BEFORE the bottom
        let minYBefore = Infinity;
        for (let i = 0; i < bottomIdx; i++) {
            if (pts[i].y < minYBefore) {
                minYBefore = pts[i].y;
            }
        }

        const downwardDrop = maxY - minYBefore; // How far hand moved downwards
        if (downwardDrop < 0.03) { // At least ~15 pixels drop in camera
            return { matched: false, confidence: 0 };
        }

        // Check curve after bottom:
        // Case A: Hand hooked back upwards
        let minYAfter = Infinity;
        for (let i = bottomIdx + 1; i < n; i++) {
            if (pts[i].y < minYAfter) {
                minYAfter = pts[i].y;
            }
        }
        const upwardHook = minYAfter !== Infinity ? (maxY - minYAfter) : 0;

        // Case B: Horizontal curve near bottom
        let maxHorizontalSweep = 0;
        const bottomX = pts[bottomIdx].x;
        for (let i = Math.max(0, bottomIdx - 3); i < n; i++) {
            const dx = Math.abs(pts[i].x - bottomX);
            if (dx > maxHorizontalSweep) maxHorizontalSweep = dx;
        }

        const totalMovement = this.getMovementMagnitude(20);

        // A J hook stroke has:
        // 1. Clear downward drop >= 0.03
        // 2. AND (upward hook >= 0.012 OR lateral sweep >= 0.02 OR total curve >= 0.02)
        const hasHook = (upwardHook >= 0.012) ||
                        (maxHorizontalSweep >= 0.02) ||
                        (downwardDrop >= 0.05 && (upwardHook >= 0.008 || maxHorizontalSweep >= 0.012));

        if (hasHook && totalMovement >= 0.045) {
            this.lastJDetection = now;
            const score = Math.min(98, Math.round(78 + (downwardDrop + upwardHook + maxHorizontalSweep) * 130));
            return { matched: true, confidence: score };
        }

        return { matched: false, confidence: 0 };
    }

    getTrail() {
        return this.history.map(h => h.pinky);
    }
}

/**
 * Resamples a sequence of landmark frames to an exact target length (default: 30)
 * using linear interpolation.
 */
export function resampleSequence(frames, targetLength = 30) {
    if (!frames || frames.length === 0) return [];
    if (frames.length === 1) {
        return Array(targetLength).fill(frames[0]);
    }
    const n = frames.length;
    const resampled = [];
    for (let k = 0; k < targetLength; k++) {
        const t = (k * (n - 1)) / (targetLength - 1);
        const i0 = Math.floor(t);
        const i1 = Math.min(i0 + 1, n - 1);
        const frac = t - i0;

        const f0 = frames[i0];
        const f1 = frames[i1];
        const interpolated = new Array(f0.length);
        for (let d = 0; d < f0.length; d++) {
            interpolated[d] = f0[d] * (1 - frac) + f1[d] * frac;
        }
        resampled.push(interpolated);
    }
    return resampled;
}

/**
 * Distance between two 63-number landmark frames.
 */
function sequenceFrameDist(a, b) {
    let d = 0;
    for (let i = 0; i < 63; i++) {
        const diff = a[i] - b[i];
        d += diff * diff;
    }
    return Math.sqrt(d);
}

/**
 * Dynamic Time Warping (DTW) distance between two sequences of frames.
 */
export function dtwDistance(seqA, seqB) {
    const n = seqA.length;
    const m = seqB.length;
    if (n === 0 || m === 0) return Infinity;

    // 2-row DP buffer for memory efficiency
    let prev = new Float32Array(m + 1).fill(Infinity);
    let curr = new Float32Array(m + 1).fill(Infinity);
    prev[0] = 0;

    for (let i = 1; i <= n; i++) {
        curr.fill(Infinity);
        for (let j = 1; j <= m; j++) {
            const cost = sequenceFrameDist(seqA[i - 1], seqB[j - 1]);
            const minPrev = Math.min(prev[j], curr[j - 1], prev[j - 1]);
            curr[j] = cost + minPrev;
        }
        prev.set(curr);
    }

    return prev[m] / (n + m);
}

/**
 * SequenceClassifier: Classifies dynamic sign language gestures
 * using temporal sequence matching with Dynamic Time Warping (DTW).
 */
export class SequenceClassifier {
    constructor(targetLength = 30) {
        this.targetLength = targetLength;
        this.templates = []; // [{ label, frames: Float32Array[] }]
    }

    load(sequences) {
        if (!Array.isArray(sequences)) return;
        this.templates = sequences
            .filter(s => Array.isArray(s.frames) && s.frames.length >= 8)
            .map(s => ({
                label: s.label,
                frames: resampleSequence(s.frames, this.targetLength)
            }));
    }

    get size() { return this.templates.length; }
    get labels() { return [...new Set(this.templates.map(t => t.label))]; }

    /**
     * Match an input sequence of frames against stored templates.
     * @param {Array<Array<number>>} testFrames
     * @returns {{label: string|null, confidence: number, distance: number}}
     */
    predict(testFrames) {
        if (this.templates.length === 0 || !testFrames || testFrames.length < 8) {
            return { label: null, confidence: 0, distance: Infinity };
        }

        const resampled = resampleSequence(testFrames, this.targetLength);
        let bestLabel = null;
        let minDistance = Infinity;

        for (const t of this.templates) {
            const dist = dtwDistance(resampled, t.frames);
            if (dist < minDistance) {
                minDistance = dist;
                bestLabel = t.label;
            }
        }

        // Distance factor: a distance of 0.05 is an extremely close match (~95%)
        // A distance > 0.35 is far/unknown
        const confidence = Math.max(0, Math.min(99, Math.round((1 - minDistance / 0.35) * 100)));

        return { label: bestLabel, confidence, distance: minDistance };
    }
}

/**
 * SequenceBuffer: Sliding window FIFO buffer to collect normalized frames
 * for real-time dynamic gesture recognition.
 */
export class SequenceBuffer {
    constructor(maxFrames = 40) {
        this.maxFrames = maxFrames;
        this.buffer = [];
    }

    push(frame) {
        if (!frame || frame.length !== 63) return;
        this.buffer.push(frame);
        if (this.buffer.length > this.maxFrames) {
            this.buffer.shift();
        }
    }

    getFrames() {
        return [...this.buffer];
    }

    get length() {
        return this.buffer.length;
    }

    reset() {
        this.buffer = [];
    }
}


