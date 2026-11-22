import test from "node:test";
import assert from "node:assert/strict";
import {
    FULL_V2_FEATURE_DIM,
    normaliseLandmarks,
    stepFrameDistance,
    StepSequenceClassifier,
} from "../../public/js/gesture-ai.js";

function makePoseHand(variant = "open") {
    const lm = Array.from({ length: 21 }, (_, i) => ({
        x: 0.5 + ((i % 4) - 1.5) * 0.02,
        y: 0.5 + (0.10 - Math.floor(i / 4) * 0.04),
        z: 0,
    }));
    lm[0] = { x: 0.5, y: 0.62, z: 0 };

    if (variant === "fist") {
        // Curled fingertips close to palm
        for (const tip of [4, 8, 12, 16, 20]) {
            lm[tip] = { x: 0.5, y: 0.56, z: 0.02 };
        }
    } else if (variant === "point") {
        for (const tip of [4, 12, 16, 20]) {
            lm[tip] = { x: 0.5, y: 0.56, z: 0.02 };
        }
        lm[8] = { x: 0.5, y: 0.35, z: -0.02 };
    }
    return normaliseLandmarks(lm);
}

function makeVec146({
    hand1 = "open",
    pos = [0, 0, 0, 0],
    anchorFlag = 0.5,
    hand2 = null,
    hand2Offset = [-0.4, 0.0, 0.4],
} = {}) {
    const h1 = makePoseHand(hand1);
    const face15 = [
        pos[0], pos[1], pos[2], pos[3],
        0, 0, 0, 0, 0,
        0, 0, 0, 0, 0,
        anchorFlag,
    ];
    const h2 = hand2 ? makePoseHand(hand2) : new Array(63).fill(0);
    const dwi5 = hand2
        ? [1, hand2Offset[0], hand2Offset[1], hand2Offset[2], 1]
        : [0, 0, 0, 0, 1];
    const out = [...h1, ...face15, ...h2, ...dwi5];
    assert.equal(out.length, FULL_V2_FEATURE_DIM);
    return out;
}

test("2-step & 3-step ordered progression requires hold frames and completes in order", () => {
    const s1 = makeVec146({ hand1: "fist", pos: [0.0, -0.3, 0.0, -0.2] });
    const s2 = makeVec146({ hand1: "open", pos: [0.0, 0.2, 0.0, 0.25] });
    const s3 = makeVec146({ hand1: "point", pos: [0.5, 0.1, 0.45, 0.1] });

    const clf = new StepSequenceClassifier({ holdFrames: 3 });
    clf.load([
        { label: "TERIMA_KASIH", steps: [{ name: "Dagu", f: s1 }, { name: "Depan", f: s2 }] },
        { label: "TIGA_LANGKAH", steps: [{ name: "L1", f: s1 }, { name: "L2", f: s2 }, { name: "L3", f: s3 }] },
    ]);

    assert.equal(clf.hasTemplates("TERIMA_KASIH"), true);
    assert.equal(clf.hasTemplates("TIADA"), false);
    assert.deepEqual(clf.getStepMeta("TERIMA_KASIH"), {
        totalSteps: 2,
        stepNames: ["Dagu", "Depan"],
    });

    // Feed Step 1 for 2 frames -> still tracking step 0
    let r = clf.update(s1, { targetLabel: "TERIMA_KASIH", nowMs: 100 });
    assert.equal(r.currentStep, 0);
    assert.equal(r.completed, false);
    r = clf.update(s1, { targetLabel: "TERIMA_KASIH", nowMs: 150 });
    assert.equal(r.currentStep, 0);

    // 3rd consecutive frame -> advances to step 1
    r = clf.update(s1, { targetLabel: "TERIMA_KASIH", nowMs: 200 });
    assert.equal(r.status, "step_advanced");
    assert.equal(r.currentStep, 1);
    assert.equal(r.completed, false);

    // Feed Step 2 for 3 frames -> completes!
    clf.update(s2, { targetLabel: "TERIMA_KASIH", nowMs: 300 });
    clf.update(s2, { targetLabel: "TERIMA_KASIH", nowMs: 350 });
    r = clf.update(s2, { targetLabel: "TERIMA_KASIH", nowMs: 400 });
    assert.equal(r.matched, true);
    assert.equal(r.completed, true);
    assert.equal(r.status, "completed");
    assert.equal(r.label, "TERIMA_KASIH");
    assert.ok(r.confidence >= 60);
});

test("out-of-order and skipped steps are rejected", () => {
    const s1 = makeVec146({ hand1: "fist", pos: [0.0, -0.4, 0.0, -0.3] });
    const s2 = makeVec146({ hand1: "open", pos: [0.0, 0.4, 0.0, 0.4] });
    const s3 = makeVec146({ hand1: "point", pos: [0.8, 0.0, 0.7, 0.0] });

    const clf = new StepSequenceClassifier({ holdFrames: 3 });
    clf.load([{ label: "TEST3", steps: [{ name: "1", f: s1 }, { name: "2", f: s2 }, { name: "3", f: s3 }] }]);

    // Feed Step 2 first -> must NOT advance
    for (let i = 0; i < 5; i++) {
        const r = clf.update(s2, { targetLabel: "TEST3", nowMs: 100 + i * 40 });
        assert.equal(r.currentStep, 0);
        assert.equal(r.completed, false);
    }

    // Complete Step 1
    for (let i = 0; i < 3; i++) clf.update(s1, { targetLabel: "TEST3", nowMs: 400 + i * 40 });

    // Skip Step 2 and feed Step 3 -> must stay at step 1
    for (let i = 0; i < 5; i++) {
        const r = clf.update(s3, { targetLabel: "TEST3", nowMs: 600 + i * 40 });
        assert.equal(r.currentStep, 1);
        assert.equal(r.completed, false);
    }
});

test("same handshape at different spatial positions (63..66) are separated", () => {
    const chin = makeVec146({ hand1: "open", pos: [0.0, -0.8, 0.0, -0.7], anchorFlag: 0.5 });
    const forward = makeVec146({ hand1: "open", pos: [0.0, 0.8, 0.0, 0.8], anchorFlag: 0.5 });

    const sameDist = stepFrameDistance(chin, chin);
    const diffDist = stepFrameDistance(chin, forward);
    assert.ok(sameDist < 0.05);
    assert.ok(diffDist > 1.2, `Expected spatial separation > 1.2, got ${diffDist}`);

    const clf = new StepSequenceClassifier({ holdFrames: 2 });
    clf.load([{ label: "POS_SIGN", steps: [{ name: "Dagu", f: chin }, { name: "Depan", f: forward }] }]);

    // Holding chin indefinitely must advance step 1->2, then NOT complete step 2
    for (let i = 0; i < 8; i++) {
        const r = clf.update(chin, { targetLabel: "POS_SIGN", nowMs: 100 + i * 40 });
        assert.equal(r.completed, false);
    }
});

test("two-hand step template penalizes one-hand frame", () => {
    const twoHand = makeVec146({ hand1: "open", hand2: "open", hand2Offset: [-0.2, 0.0, 0.2] });
    const oneHand = makeVec146({ hand1: "open", hand2: null });

    const dMatch = stepFrameDistance(twoHand, twoHand);
    const dMismatch = stepFrameDistance(oneHand, twoHand);
    assert.ok(dMatch < 0.05);
    assert.ok(dMismatch > 0.8, `Expected 2-hand vs 1-hand penalty > 0.8, got ${dMismatch}`);
});

test("timeout reset and brief hand-loss grace work as specified", () => {
    const s1 = makeVec146({ hand1: "fist", pos: [0, -0.5, 0, -0.5] });
    const s2 = makeVec146({ hand1: "open", pos: [0, 0.5, 0, 0.5] });

    const clf = new StepSequenceClassifier({ holdFrames: 2, timeoutMs: 5000, handLostGraceMs: 1200 });
    clf.load([{ label: "BUKU", steps: [{ name: "Rapat", f: s1 }, { name: "Buka", f: s2 }] }]);

    // Advance to step 1 at t=1000
    clf.update(s1, { targetLabel: "BUKU", nowMs: 960 });
    let r = clf.update(s1, { targetLabel: "BUKU", nowMs: 1000 });
    assert.equal(r.currentStep, 1);

    // Brief hand drop (600ms < 1200ms) keeps progress
    r = clf.update(null, { targetLabel: "BUKU", nowMs: 1400 });
    assert.equal(r.currentStep, 1);
    r = clf.update(null, { targetLabel: "BUKU", nowMs: 1600 });
    assert.equal(r.currentStep, 1);

    // Hand drop > 1200ms resets progress
    r = clf.update(null, { targetLabel: "BUKU", nowMs: 2700 });
    assert.equal(r.currentStep, 0);

    // Advance again and test 5000ms timeout
    clf.update(s1, { targetLabel: "BUKU", nowMs: 3000 });
    r = clf.update(s1, { targetLabel: "BUKU", nowMs: 3040 });
    assert.equal(r.currentStep, 1);

    r = clf.update(s1, { targetLabel: "BUKU", nowMs: 8500 });
    assert.equal(r.status, "timeout_reset");
    assert.equal(r.currentStep, 0);
});

test("mixed template lengths and open-set mode work cleanly", () => {
    const a1 = makeVec146({ hand1: "fist", pos: [-0.6, 0, -0.6, 0] });
    const a2 = makeVec146({ hand1: "open", pos: [0.6, 0, 0.6, 0] });
    const b1 = makeVec146({ hand1: "point", pos: [0, -0.7, 0, -0.7] });
    const b2 = makeVec146({ hand1: "fist", pos: [0, 0.7, 0, 0.7] });

    const clf = new StepSequenceClassifier({ holdFrames: 2 });
    clf.load([
        { label: "BUKU", steps: [{ name: "1", f: a1 }, { name: "2", f: a2 }] },
        { label: "BUKU", steps: [{ name: "1", f: a1 }, { name: "2", f: a2 }, { name: "3", f: b1 }] },
        { label: "TOLONG", steps: [{ name: "1", f: b1 }, { name: "2", f: b2 }] },
    ]);

    clf.update(b1, { mode: "open-set", nowMs: 100 });
    clf.update(b1, { mode: "open-set", nowMs: 140 });
    clf.update(b2, { mode: "open-set", nowMs: 200 });
    const res = clf.update(b2, { mode: "open-set", nowMs: 240 });

    assert.equal(res.completed, true);
    assert.equal(res.label, "TOLONG");
});
