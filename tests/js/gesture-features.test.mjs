import test from "node:test";
import assert from "node:assert/strict";
import {
    FULL_V2_FEATURE_DIM,
    extractPoseContext,
    selectHandsOrder,
    buildMultimodalVector,
    SequenceBuffer,
} from "../../public/js/gesture-ai.js";

function makeHand(baseX = 0.5, baseY = 0.5, scale = 0.1) {
    const lm = [];
    for (let i = 0; i < 21; i++) {
        lm.push({
            x: baseX + (i % 5) * scale * 0.25,
            y: baseY - Math.floor(i / 4) * scale * 0.25,
            z: -0.01 * i,
        });
    }
    return lm;
}

function makePose(lx = 0.6, ly = 0.5, rx = 0.4, ry = 0.5, vis = 0.95) {
    const pose = Array.from({ length: 33 }, () => ({ x: 0.5, y: 0.5, z: 0, visibility: vis }));
    pose[0] = { x: (lx + rx) * 0.5, y: ly - 0.25, z: 0, visibility: vis };
    pose[11] = { x: lx, y: ly, z: 0, visibility: vis };
    pose[12] = { x: rx, y: ry, z: 0, visibility: vis };
    return pose;
}

test("extractPoseContext validates visibility and shoulderWidth bounds safely", () => {
    assert.equal(extractPoseContext(null), null);
    assert.equal(extractPoseContext([]), null);

    // Degenerate overlapping shoulders (width < 0.08)
    assert.equal(extractPoseContext(makePose(0.51, 0.5, 0.49, 0.5, 0.95)), null);

    // Low visibility shoulders
    assert.equal(extractPoseContext(makePose(0.65, 0.5, 0.35, 0.5, 0.15)), null);

    const ctx = extractPoseContext(makePose(0.65, 0.52, 0.35, 0.48, 0.92));
    assert.equal(ctx.hasPose, true);
    assert.ok(Math.abs(ctx.shoulderWidth - Math.hypot(0.3, 0.04)) < 1e-4);
    assert.ok(Math.abs(ctx.shoulderCenter.x - 0.5) < 1e-5);
    assert.ok(Math.abs(ctx.shoulderCenter.y - 0.5) < 1e-5);
});

test("selectHandsOrder consistently orders Right hand as primary when 2 hands exist", () => {
    const leftHand = makeHand(0.3, 0.5);
    const rightHand = makeHand(0.7, 0.5);

    const res = selectHandsOrder(
        [leftHand, rightHand],
        [[{ categoryName: "Left" }], [{ categoryName: "Right" }]]
    );
    assert.equal(res.primaryIdx, 1);
    assert.strictEqual(res.primary, rightHand);
    assert.strictEqual(res.secondary, leftHand);

    // Single hand falls back cleanly
    const single = selectHandsOrder([leftHand], [[{ categoryName: "Left" }]]);
    assert.equal(single.primaryIdx, 0);
    assert.strictEqual(single.primary, leftHand);
    assert.equal(single.secondary, null);
});

test("buildMultimodalVector outputs clean 146-dim vector for pose-only, 2-hand, and fallback modes", () => {
    assert.equal(FULL_V2_FEATURE_DIM, 146);

    const h1 = makeHand(0.6, 0.45);
    const h2 = makeHand(0.35, 0.48);
    const poseCtx = extractPoseContext(makePose(0.65, 0.5, 0.35, 0.5, 0.95));

    // Mode A: 1 hand + Pose + no Face
    const vecPoseOnly = buildMultimodalVector([h1], null, null, poseCtx);
    assert.equal(vecPoseOnly.length, 146);
    assert.equal(vecPoseOnly[77], 0.5);
    assert.equal(vecPoseOnly[141], 0);
    assert.equal(vecPoseOnly[145], 1);
    assert.ok(vecPoseOnly.every(v => Number.isFinite(v)));
    for (let i = 78; i < 141; i++) {
        assert.equal(vecPoseOnly[i], 0);
    }

    // Mode B: 2 hands + Pose + Face
    const faceCtx = {
        hasFace: true,
        vector: [0.1, 0.2, 0.05, 0.15, 0.8, 0, 0, 0, 0, 0.5, 0, 0, 0, 0, 1],
    };
    const vecTwoHands = buildMultimodalVector(
        [h2, h1],
        [[{ categoryName: "Left" }], [{ categoryName: "Right" }]],
        faceCtx,
        poseCtx
    );
    assert.equal(vecTwoHands.length, 146);
    assert.equal(vecTwoHands[77], 1);
    assert.equal(vecTwoHands[141], 1);
    assert.equal(vecTwoHands[145], 1);
    assert.ok(vecTwoHands[144] > 0.2); // wrist distance normalized by shoulderWidth
    assert.ok(vecTwoHands.slice(78, 141).some(v => Math.abs(v) > 0));
    assert.ok(vecTwoHands.every(v => Number.isFinite(v)));

    // Mode C: 1 hand + no Pose + no Face (camera fallback)
    const vecFallback = buildMultimodalVector([h1], null, null, null);
    assert.equal(vecFallback.length, 146);
    assert.equal(vecFallback[77], 0.25);
    assert.equal(vecFallback[145], 0);
    assert.ok(vecFallback.every(v => Number.isFinite(v)));
});

test("SequenceBuffer accepts 63, 78, and 146 dimensions and rejects malformed lengths", () => {
    const buf = new SequenceBuffer(10);
    buf.push(new Array(63).fill(0.1));
    buf.push(new Array(78).fill(0.2));
    buf.push(new Array(146).fill(0.3));
    buf.push(new Array(100).fill(0.4));
    assert.equal(buf.length, 3);
});
