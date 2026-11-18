import test from "node:test";
import assert from "node:assert/strict";
import {
    FULL_FEATURE_DIM,
    FULL_V2_FEATURE_DIM,
    getEffectiveFaceRule,
    evaluateFaceSignBoost,
    dtwDistance,
    SequenceClassifier,
} from "../../public/js/gesture-ai.js";

function makeMovingClip(numFrames, dim = FULL_V2_FEATURE_DIM, opts = {}) {
    const { dirX = 1, twoHands = false, curlScale = 0.08, faceFlag = 0.5 } = opts;
    const frames = [];
    for (let t = 0; t < numFrames; t++) {
        const prog = numFrames > 1 ? t / (numFrames - 1) : 0;
        const vec = new Array(dim).fill(0);
        // Primary hand shape shifts slightly along curlScale
        for (let i = 0; i < 21; i++) {
            vec[i * 3] = (i % 4) * curlScale;
            vec[i * 3 + 1] = -Math.floor(i / 4) * curlScale * (1 - 0.15 * prog);
            vec[i * 3 + 2] = 0;
        }
        // Spatial trajectory (slots 63..66)
        vec[63] = prog * 0.8 * dirX;
        vec[64] = -prog * 0.4;
        vec[65] = prog * 0.7 * dirX;
        vec[66] = -prog * 0.35;
        vec[77] = faceFlag;

        if (dim >= FULL_V2_FEATURE_DIM) {
            if (twoHands) {
                for (let i = 0; i < 21; i++) {
                    vec[78 + i * 3] = -(i % 4) * curlScale;
                    vec[78 + i * 3 + 1] = -Math.floor(i / 4) * curlScale;
                }
                vec[141] = 1;
                vec[142] = -0.35 * (1 - prog);
                vec[143] = 0.05;
                vec[144] = 0.36 * (1 - prog);
            }
            vec[145] = faceFlag === 0.5 ? 1 : 0;
        }
        frames.push(vec);
    }
    return frames;
}

test("getEffectiveFaceRule strips bebas and neutral and evaluateFaceSignBoost gives zero free bonus", () => {
    const tolongRule = getEffectiveFaceRule("TOLONG");
    assert.deepEqual(tolongRule.zones, ["dada"]);
    assert.equal(tolongRule.exprs, null);
    assert.equal(tolongRule.hasConstraint, true);

    const gembiraRule = getEffectiveFaceRule("GEMBIRA");
    assert.deepEqual(gembiraRule.zones, ["dada"]);
    assert.deepEqual(gembiraRule.exprs, ["gembira"]);
    assert.equal(gembiraRule.hasConstraint, true);

    const maafRule = getEffectiveFaceRule("MAAF");
    assert.deepEqual(maafRule.zones, ["dada"]);
    assert.equal(maafRule.exprs, null); // original included "neutral", so expr is optional
    assert.equal(maafRule.hasConstraint, true);

    const byeRule = getEffectiveFaceRule("SELAMAT_TINGGAL");
    assert.equal(byeRule.hasConstraint, false);

    // No free +17 boost for ASSALAMUALAIKUM when zone="bebas" and expression="neutral"
    const boost = evaluateFaceSignBoost("ASSALAMUALAIKUM", {
        hasFace: true,
        zone: "bebas",
        expression: "neutral",
    });
    assert.equal(boost.bonus, 0);
});

test("SequenceClassifier handles different signing speeds without velocity explosion", () => {
    const templateNormal = makeMovingClip(30, FULL_V2_FEATURE_DIM, { dirX: 1, twoHands: false });
    const testFast = makeMovingClip(18, FULL_V2_FEATURE_DIM, { dirX: 1, twoHands: false }); // 1.67x faster

    const seqCls = new SequenceClassifier(30);
    seqCls.load([{ label: "TERIMA_KASIH", frames: templateNormal }]);

    const pred = seqCls.predict(testFast, null, "TERIMA_KASIH");
    assert.equal(pred.label, "TERIMA_KASIH");
    assert.ok(pred.confidence >= 70, `Expected >=70% confidence across speed difference, got ${pred.confidence}%`);
});

test("SequenceClassifier maintains compatibility between 78-dim legacy clips and 146-dim live clips", () => {
    const legacyTemplate = makeMovingClip(24, FULL_FEATURE_DIM, { dirX: 1, faceFlag: 0.25 });
    const liveMatch = makeMovingClip(26, FULL_V2_FEATURE_DIM, { dirX: 1, faceFlag: 0.5 });
    const liveOpposite = makeMovingClip(26, FULL_V2_FEATURE_DIM, { dirX: -1, faceFlag: 0.5 });

    const seqCls = new SequenceClassifier(30);
    seqCls.load([
        { label: "ASSALAMUALAIKUM", frames: legacyTemplate },
    ]);

    // Check target-locked against opposite direction (distance should be substantially larger)
    const dMatch = dtwDistance(liveMatch, legacyTemplate, false, false);
    const dOpp = dtwDistance(liveOpposite, legacyTemplate, false, false);
    assert.ok(dMatch < dOpp * 0.65, `Matched clip distance (${dMatch}) should beat opposite (${dOpp})`);

    const pred = seqCls.predict(liveMatch, null, "ASSALAMUALAIKUM");
    assert.equal(pred.label, "ASSALAMUALAIKUM");
    assert.ok(pred.confidence >= 65, `Expected >=65% compat confidence, got ${pred.confidence}%`);
});

test("Two-hand 146-dim matching rewards genuine 2-hand clip over 1-hand clip and targetLabel filters candidates", () => {
    const tplTwoHand = makeMovingClip(24, FULL_V2_FEATURE_DIM, { dirX: 1, twoHands: true });
    const tplOther = makeMovingClip(24, FULL_V2_FEATURE_DIM, { dirX: -1, twoHands: false });

    const queryTwoHand = makeMovingClip(22, FULL_V2_FEATURE_DIM, { dirX: 1, twoHands: true });
    const queryOneHand = makeMovingClip(22, FULL_V2_FEATURE_DIM, { dirX: 1, twoHands: false });

    const dTwo = dtwDistance(queryTwoHand, tplTwoHand, false, false);
    const dOne = dtwDistance(queryOneHand, tplTwoHand, false, false);
    assert.ok(dTwo < dOne, `Two-hand distance (${dTwo}) must be smaller than missing-second-hand distance (${dOne})`);

    const seqCls = new SequenceClassifier(30);
    seqCls.load([
        { label: "TOLONG", frames: tplTwoHand },
        { label: "LAIN", frames: tplOther },
    ]);

    const openPred = seqCls.predict(queryTwoHand, null, null);
    assert.equal(openPred.label, "TOLONG");

    const lockedPred = seqCls.predict(queryTwoHand, null, "LAIN");
    assert.equal(lockedPred.label, "LAIN");
});
