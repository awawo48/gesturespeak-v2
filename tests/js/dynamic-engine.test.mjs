import test from "node:test";
import assert from "node:assert/strict";
import {
    FULL_V2_FEATURE_DIM,
    buildMultimodalVector,
    MotionSegmenter,
    DynamicGestureEngine,
    analyzeJTrajectory,
    analyzeZTrajectory,
} from "../../public/js/gesture-ai.js";

function makeHand({ cx = 0.5, cy = 0.5, shape = "open" } = {}) {
    const lm = Array.from({ length: 21 }, (_, i) => ({
        x: cx + ((i % 4) - 1.5) * 0.02,
        y: cy + (0.10 - Math.floor(i / 4) * 0.04),
        z: 0,
    }));
    // Wrist at 0
    lm[0] = { x: cx, y: cy + 0.12, z: 0 };

    if (shape === "pinky") {
        // Curled thumb, index, middle, ring
        lm[2] = { x: cx - 0.03, y: cy + 0.08, z: 0 };
        lm[4] = { x: cx - 0.01, y: cy + 0.06, z: 0 };
        lm[5] = { x: cx - 0.03, y: cy + 0.04, z: 0 };
        lm[8] = { x: cx - 0.03, y: cy + 0.06, z: 0 };
        lm[9] = { x: cx, y: cy + 0.04, z: 0 };
        lm[12] = { x: cx, y: cy + 0.06, z: 0 };
        lm[13] = { x: cx + 0.02, y: cy + 0.04, z: 0 };
        lm[16] = { x: cx + 0.02, y: cy + 0.06, z: 0 };
        // Extended pinky
        lm[17] = { x: cx + 0.04, y: cy + 0.04, z: 0 };
        lm[18] = { x: cx + 0.04, y: cy + 0.01, z: 0 };
        lm[20] = { x: cx + 0.05, y: cy - 0.09, z: 0 };
    } else if (shape === "index") {
        lm[5] = { x: cx - 0.03, y: cy + 0.04, z: 0 };
        lm[6] = { x: cx - 0.03, y: cy + 0.01, z: 0 };
        lm[8] = { x: cx - 0.03, y: cy - 0.09, z: 0 };
        lm[9] = { x: cx, y: cy + 0.04, z: 0 };
        lm[12] = { x: cx, y: cy + 0.07, z: 0 };
        lm[13] = { x: cx + 0.02, y: cy + 0.04, z: 0 };
        lm[16] = { x: cx + 0.02, y: cy + 0.07, z: 0 };
        lm[17] = { x: cx + 0.04, y: cy + 0.04, z: 0 };
        lm[20] = { x: cx + 0.04, y: cy + 0.07, z: 0 };
    } else {
        lm[9] = { x: cx, y: cy, z: 0 };
    }
    return lm;
}

function makePose(sw = 0.30) {
    const pose = new Array(33).fill(null).map(() => ({ x: 0.5, y: 0.5, z: 0, visibility: 0 }));
    pose[0] = { x: 0.5, y: 0.25, z: 0, visibility: 0.99 };
    pose[11] = { x: 0.5 + sw / 2, y: 0.55, z: 0, visibility: 0.99 };
    pose[12] = { x: 0.5 - sw / 2, y: 0.55, z: 0, visibility: 0.99 };
    return pose;
}

function pushSyntheticFrame(seg, cx, cy, ts, shape = "open", faceContext = null) {
    const hand = makeHand({ cx, cy, shape });
    const pose = makePose(0.30);
    const frameVec = buildMultimodalVector([hand], null, faceContext, {
        hasPose: true,
        shoulderCenter: { x: 0.5, y: 0.55, z: 0 },
        shoulderWidth: 0.30,
        nose: pose[0],
        anchors: null,
    });
    return seg.pushFrame({
        frameVec,
        handsLm: [hand],
        primaryHand: hand,
        faceContext,
        timestampMs: ts,
    });
}

function makeMovingClip(numFrames, opts = {}) {
    const { dirX = 1, twoHands = false, faceFlag = 0.5 } = opts;
    const frames = [];
    for (let t = 0; t < numFrames; t++) {
        const prog = numFrames > 1 ? t / (numFrames - 1) : 0;
        const vec = new Array(FULL_V2_FEATURE_DIM).fill(0);
        for (let i = 0; i < 21; i++) {
            vec[i * 3] = (i % 4) * 0.08;
            vec[i * 3 + 1] = -Math.floor(i / 4) * 0.08 * (1 - 0.15 * prog);
        }
        vec[63] = prog * 0.8 * dirX;
        vec[64] = -prog * 0.4;
        vec[65] = prog * 0.7 * dirX;
        vec[66] = -prog * 0.35;
        vec[77] = faceFlag;
        if (twoHands) {
            for (let i = 0; i < 21; i++) {
                vec[78 + i * 3] = -(i % 4) * 0.08;
                vec[78 + i * 3 + 1] = -Math.floor(i / 4) * 0.08;
            }
            vec[141] = 1;
            vec[142] = -0.35 * (1 - prog);
            vec[143] = 0.05;
            vec[144] = 0.36 * (1 - prog);
        }
        vec[145] = 1;
        frames.push(vec);
    }
    return frames;
}

test("1. MotionSegmenter full lifecycle: IDLE -> CAPTURING -> completed -> COOLDOWN -> IDLE", () => {
    const seg = new MotionSegmenter();
    let ts = 0;
    const dt = 40;
    const events = [];
    let completedRes = null;

    // 6 still frames
    for (let i = 0; i < 6; i++) {
        const res = pushSyntheticFrame(seg, 0.5, 0.5, ts);
        assert.equal(res.state, "IDLE");
        ts += dt;
    }

    // 16 moving frames
    for (let i = 0; i < 16; i++) {
        const res = pushSyntheticFrame(seg, 0.5 + (i + 1) * 0.015, 0.5 - (i + 1) * 0.008, ts);
        if (res.event) events.push(res.event);
        assert.equal(res.state, "CAPTURING");
        ts += dt;
    }
    assert.deepEqual(events, ["started"]);

    // 10 still frames (pause reaches >= 280ms)
    for (let i = 0; i < 10; i++) {
        const res = pushSyntheticFrame(seg, 0.74, 0.372, ts);
        if (res.event === "completed") {
            assert.equal(completedRes, null, "Must emit 'completed' only once per gesture");
            completedRes = res;
            assert.equal(res.state, "COOLDOWN");
        }
        ts += dt;
    }

    assert.ok(completedRes, "Expected a 'completed' event");
    assert.ok(completedRes.clip.length >= 18, `Expected clip with preroll >= 18 frames, got ${completedRes.clip.length}`);

    // Advance past COOLDOWN_MS (450ms) and ensure return to IDLE
    ts += 500;
    const afterCooldown = pushSyntheticFrame(seg, 0.74, 0.372, ts);
    assert.equal(afterCooldown.state, "IDLE");
});

test("2. MotionSegmenter discards <8 active frames as too_short", () => {
    const seg = new MotionSegmenter();
    let ts = 0;
    const dt = 45;

    for (let i = 0; i < 6; i++) {
        pushSyntheticFrame(seg, 0.5, 0.5, ts);
        ts += dt;
    }
    // Only 3 moving frames
    for (let i = 0; i < 3; i++) {
        pushSyntheticFrame(seg, 0.5 + (i + 1) * 0.02, 0.5, ts);
        ts += dt;
    }

    let gotTooShort = false;
    let gotCompleted = false;
    for (let i = 0; i < 10; i++) {
        const res = pushSyntheticFrame(seg, 0.56, 0.5, ts);
        if (res.event === "too_short") gotTooShort = true;
        if (res.event === "completed") gotCompleted = true;
        ts += dt;
    }
    assert.equal(gotTooShort, true);
    assert.equal(gotCompleted, false);
});

test("3. MotionSegmenter enforces MAX_CLIP_MS (2400ms) forced completion", () => {
    const seg = new MotionSegmenter();
    let ts = 100;
    pushSyntheticFrame(seg, 0.4, 0.5, ts);

    let forcedCompleted = null;
    for (let i = 1; i <= 70; i++) {
        ts += 45; // reaches > 2400ms without pausing
        const x = 0.4 + Math.sin(i * 0.3) * 0.15 + i * 0.002;
        const y = 0.5 + Math.cos(i * 0.3) * 0.08;
        const res = pushSyntheticFrame(seg, x, y, ts);
        if (res.event === "completed") {
            forcedCompleted = res;
            break;
        }
    }
    assert.ok(forcedCompleted, "Continuous motion >2400ms must force completion");
    assert.ok(forcedCompleted.durationMs >= 2400);
});

test("4. Option A strict rules on 0-clip signs (GEMBIRA, BAPA need_face, BUKU/SELAMAT_TINGGAL mirror)", () => {
    const engine = new DynamicGestureEngine();
    engine.setTemplates([]); // 0 clips!

    const clip = makeMovingClip(16);
    const handsHistory = Array.from({ length: 16 }, (_, i) => makeHand({ cx: 0.4 + i * 0.01, cy: 0.5 }));

    // GEMBIRA with zone=dada & expression=gembira -> matched
    const gembiraPass = engine.evaluateClip({
        mode: "target-locked",
        targetLabel: "GEMBIRA",
        clipFrames: clip,
        handsHistory,
        faceHistory: Array.from({ length: 4 }, () => ({ hasFace: true, zone: "dada", expression: "gembira" })),
        faceAiEnabled: true,
    });
    assert.equal(gembiraPass.status, "matched");
    assert.equal(gembiraPass.matched, true);

    // GEMBIRA with zone=dada & expression=neutral -> miss (expression constraint unmet)
    const gembiraMiss = engine.evaluateClip({
        mode: "target-locked",
        targetLabel: "GEMBIRA",
        clipFrames: clip,
        handsHistory,
        faceHistory: Array.from({ length: 4 }, () => ({ hasFace: true, zone: "dada", expression: "neutral" })),
        faceAiEnabled: true,
    });
    assert.equal(gembiraMiss.status, "miss");
    assert.equal(gembiraMiss.matched, false);

    // BAPA with faceAiEnabled=false or empty faceHistory -> need_face
    const bapaNeedFaceOff = engine.evaluateClip({
        mode: "target-locked",
        targetLabel: "BAPA",
        clipFrames: clip,
        handsHistory,
        faceHistory: [],
        faceAiEnabled: false,
    });
    assert.equal(bapaNeedFaceOff.status, "need_face");
    assert.equal(bapaNeedFaceOff.matched, false);

    const bapaNoFace = engine.evaluateClip({
        mode: "target-locked",
        targetLabel: "BAPA",
        clipFrames: clip,
        handsHistory,
        faceHistory: [{ hasFace: false }],
        faceAiEnabled: true,
    });
    assert.equal(bapaNoFace.status, "need_face");
    assert.equal(bapaNoFace.matched, false);

    // BUKU (no rule) & SELAMAT_TINGGAL (only bebas/neutral) -> mirror mode
    const bukuMirror = engine.evaluateClip({
        mode: "target-locked",
        targetLabel: "BUKU",
        clipFrames: clip,
        handsHistory,
        faceHistory: [],
        faceAiEnabled: false,
    });
    assert.equal(bukuMirror.status, "mirror");
    assert.equal(bukuMirror.matched, false);

    const byeMirror = engine.evaluateClip({
        mode: "target-locked",
        targetLabel: "SELAMAT_TINGGAL",
        clipFrames: clip,
        handsHistory,
        faceHistory: [{ hasFace: true, zone: "bebas", expression: "neutral" }, { hasFace: true, zone: "bebas", expression: "neutral" }],
        faceAiEnabled: true,
    });
    assert.equal(byeMirror.status, "mirror");
    assert.equal(byeMirror.matched, false);
});

test("5. Dual-mode open-set vs target-locked routing", () => {
    const engine = new DynamicGestureEngine();
    const tplSalam = makeMovingClip(24, { dirX: 1, twoHands: false });
    const tplTolong = makeMovingClip(24, { dirX: -1, twoHands: true });
    engine.setTemplates([
        { label: "ASSALAMUALAIKUM", frames: tplSalam },
        { label: "TOLONG", frames: tplTolong },
    ]);

    const querySalam = makeMovingClip(20, { dirX: 1, twoHands: false });
    const handsHistory = Array.from({ length: 20 }, (_, i) => makeHand({ cx: 0.4 + i * 0.01, cy: 0.5 }));

    const openRes = engine.evaluateClip({
        mode: "open-set",
        targetLabel: "TOLONG",
        clipFrames: querySalam,
        handsHistory,
        faceHistory: [],
        faceAiEnabled: false,
    });
    assert.equal(openRes.status, "matched");
    assert.equal(openRes.label, "ASSALAMUALAIKUM");
    assert.equal(openRes.source, "dtw");

    const lockedRes = engine.evaluateClip({
        mode: "target-locked",
        targetLabel: "TOLONG",
        clipFrames: querySalam,
        handsHistory,
        faceHistory: [],
        faceAiEnabled: false,
    });
    assert.equal(lockedRes.status, "miss");
    assert.equal(lockedRes.matched, false);
});

test("6. J and Z clip-mode trajectory recognition with end-of-stroke pause settled frames", () => {
    // J hook trajectory: move down (+y) then sweep left & curve slightly up (-y), then settle 5 pause frames
    const jPts = [
        [0.50, 0.30], [0.50, 0.33], [0.50, 0.36], [0.50, 0.40], [0.50, 0.44],
        [0.50, 0.48], [0.49, 0.50], [0.47, 0.505], [0.45, 0.49], [0.43, 0.46],
        [0.42, 0.44], [0.42, 0.44], [0.42, 0.44], [0.42, 0.44], [0.42, 0.44], [0.42, 0.44],
    ];
    const jHands = jPts.map(([x, y]) => makeHand({ cx: x, cy: y, shape: "pinky" }));

    assert.equal(analyzeJTrajectory(jHands).matched, true);

    const zPts = [
        [0.40, 0.32], [0.45, 0.32], [0.50, 0.32], [0.55, 0.32],
        [0.51, 0.37], [0.47, 0.42], [0.42, 0.47],
        [0.47, 0.47], [0.52, 0.47], [0.57, 0.47],
        [0.57, 0.47], [0.57, 0.47], [0.57, 0.47],
    ];
    const zHands = zPts.map(([x, y]) => makeHand({ cx: x, cy: y, shape: "index" }));
    assert.equal(analyzeZTrajectory(zHands).matched, true);

    const engine = new DynamicGestureEngine();
    const dummyClip = makeMovingClip(jHands.length);

    const jTarget = engine.evaluateClip({
        mode: "target-locked",
        targetLabel: "J",
        clipFrames: dummyClip,
        handsHistory: jHands,
        faceHistory: [],
        faceAiEnabled: false,
    });
    assert.equal(jTarget.status, "matched");
    assert.equal(jTarget.label, "J");
    assert.equal(jTarget.source, "jz");

    const jOpen = engine.evaluateClip({
        mode: "open-set",
        targetLabel: null,
        clipFrames: dummyClip,
        handsHistory: jHands,
        faceHistory: [],
        faceAiEnabled: false,
    });
    assert.equal(jOpen.status, "matched");
    assert.equal(jOpen.label, "J");

    const zOpen = engine.evaluateClip({
        mode: "open-set",
        targetLabel: null,
        clipFrames: makeMovingClip(zHands.length),
        handsHistory: zHands,
        faceHistory: [],
        faceAiEnabled: false,
    });
    assert.equal(zOpen.status, "matched");
    assert.equal(zOpen.label, "Z");
});
