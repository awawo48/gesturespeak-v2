/**
 * GestureSpeak shared Multimodal AI Engine (ES module).
 *
 * Combines:
 * 1. MediaPipe HandLandmarker (21 3D hand joints -> 63 wrist-normalised numbers
 *    + 15 on-the-fly anatomical finger-curl/pinch geometry features)
 * 2. MediaPipe FaceLandmarker (Hand-to-Face spatial anchor zones + 52 Blendshape
 *    facial expressions -> 15 normalised context numbers, total = 78 features)
 * 3. Camera-Space Trajectory Fallback:
 *    - Even when Face AI is off or a sign is hand-only (e.g. J, Z, 10), slots
 *      63..66 encode palm-scaled hand trajectory with f[77] = 0.25 so Derivative
 *      DTW always tracks 2D/3D movement paths accurately.
 * 4. Derivative DTW with Sakoe-Chiba Warping Band, Left/Right Mirror Invariance,
 *    Multi-Scale Sliding Window Alignment, and Distance-Weighted k-NN Voting.
 */

export const HAND_FEATURE_DIM = 63;
export const FACE_FEATURE_DIM = 15;
export const FULL_FEATURE_DIM = 78;
export const FULL_V2_FEATURE_DIM = 146;

const HAND_ONLY_LABELS = new Set([
    ..."ABCDEFGHIJKLMNOPQRSTUVWXYZ".split(""),
    "0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "10",
    "KOSONG", "SATU", "DUA", "TIGA", "EMPAT", "LIMA", "ENAM", "TUJUH", "LAPAN", "SEMBILAN", "SEPULUH",
]);

export function isHandOnlyLabel(label) {
    if (!label) return false;
    return HAND_ONLY_LABELS.has(String(label).trim().toUpperCase());
}

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

const clamp = (v, min, max) => Math.max(min, Math.min(max, v));
const round5 = v => Math.round(v * 1e5) / 1e5;

/**
 * Computes 15 scale- and rotation-invariant anatomical hand geometry features
 * directly from any 63-number wrist-normalised hand vector:
 *   [0..4]   5 fingertip-to-wrist Euclidean distances normalised by palm size
 *   [5..9]   5 finger extension ratios: dist(tip, mcp) / dist(pip, mcp)
 *   [10..13] 4 thumb-to-fingertip pinch distances (index, middle, ring, pinky)
 *   [14]     1 index-to-middle fingertip spread (separates U vs V vs R)
 *
 * Works on both existing database samples and live frames without schema changes.
 */
export function extractHandGeometry(vec63, offset = 0) {
    const geom = new Float32Array(15);
    if (!vec63 || vec63.length < offset + 63) return geom;

    const jDist = (a, b) => {
        const ia = offset + a * 3, ib = offset + b * 3;
        const dx = vec63[ia] - vec63[ib];
        const dy = vec63[ia + 1] - vec63[ib + 1];
        const dz = vec63[ia + 2] - vec63[ib + 2];
        return Math.hypot(dx, dy, dz);
    };

    // Anatomical palm reference: average of wrist->index_mcp(5), wrist->middle_mcp(9), wrist->pinky_mcp(17)
    const palmLen = Math.max(
        0.12,
        (jDist(0, 5) + jDist(0, 9) + jDist(0, 17)) / 3
    );

    const tips = [4, 8, 12, 16, 20];
    const mcps = [2, 5, 9, 13, 17];
    const pips = [3, 6, 10, 14, 18];

    for (let f = 0; f < 5; f++) {
        // [0..4] Fingertip distance from wrist (distinguishes closed fist A/S/E from open B/5)
        geom[f] = clamp(jDist(0, tips[f]) / (palmLen * 2.1), 0, 1.5);

        // [5..9] Finger extension ratio (curled ~0.6..1.1 vs straight ~1.6..2.2)
        const baseSeg = Math.max(0.05, jDist(mcps[f], pips[f]));
        geom[5 + f] = clamp(jDist(mcps[f], tips[f]) / (baseSeg * 2.2), 0, 1.5);
    }

    // [10..13] Thumb-tip (4) to other fingertips (8, 12, 16, 20) pinch distances
    geom[10] = clamp(jDist(4, 8) / (palmLen * 1.8), 0, 1.5);
    geom[11] = clamp(jDist(4, 12) / (palmLen * 1.8), 0, 1.5);
    geom[12] = clamp(jDist(4, 16) / (palmLen * 1.8), 0, 1.5);
    geom[13] = clamp(jDist(4, 20) / (palmLen * 1.8), 0, 1.5);

    // [14] Index-to-middle tip spread (distinguishes U/R from V/2)
    geom[14] = clamp(jDist(8, 12) / (palmLen * 1.2), 0, 1.5);

    return geom;
}

/**
 * Extracts palm-normalised camera-space coordinates for the index tip and palm
 * center when face landmarks are unavailable, enabling accurate 2D trajectory
 * tracking for hand-only dynamic gestures.
 */
function extractCameraTrajectory(handLm) {
    if (!handLm || handLm.length < 21) {
        return [0, 0, 0, 0];
    }
    const wrist = handLm[0];
    const indexTip = handLm[8];
    const palm = handLm[9];
    const pinkyMcp = handLm[17];

    const palmSpan = Math.max(
        0.06,
        Math.hypot(palm.x - wrist.x, palm.y - wrist.y) +
        Math.hypot(pinkyMcp.x - wrist.x, pinkyMcp.y - wrist.y)
    );
    const scale = Math.max(0.12, palmSpan * 1.35);

    const ix = clamp((indexTip.x - 0.5) / scale, -2.2, 2.2);
    const iy = clamp((indexTip.y - 0.5) / scale, -2.2, 2.2);
    const px = clamp((palm.x - 0.5) / scale, -2.2, 2.2);
    const py = clamp((palm.y - 0.5) / scale, -2.2, 2.2);
    return [round5(ix), round5(iy), round5(px), round5(py)];
}

/**
 * Extracts 15 scale-invariant Hand-to-Face spatial + facial expression features
 * plus human-readable zone and expression metadata for UI & BIM coaching.
 *
 * @param {Array|null} handLm  21 MediaPipe hand landmarks (optional if only inspecting face)
 * @param {Array|null} faceLm  478 MediaPipe face landmarks
 * @param {Array|null} blendshapes  MediaPipe faceBlendshapes[0]?.categories
 */
export function extractFaceContext(handLm, faceLm, blendshapes = null) {
    if (!faceLm || faceLm.length < 400) {
        const [camIx, camIy, camPx, camPy] = extractCameraTrajectory(handLm);
        const hasHandTraj = Boolean(handLm && handLm.length >= 21);
        return {
            hasFace: false,
            vector: [
                camIx, camIy, camPx, camPy,
                0, 0, 0, 0, 0,
                0, 0, 0, 0, 0,
                hasHandTraj ? 0.25 : 0, // 0.25 = camera-space hand trajectory present, face absent
            ],
            zone: "tiada",
            zoneLabel: "Tiada Wajah",
            zoneProximity: 0,
            expression: "neutral",
            expressionLabel: "Neutral",
            expressionScore: 0,
            scores: { happy: 0, sad: 0, angry: 0, surprised: 0, question: 0 },
            anchors: null,
        };
    }

    // Key canonical MediaPipe Face Mesh landmarks
    const forehead = {
        x: (faceLm[10].x + faceLm[168].x) * 0.5,
        y: (faceLm[10].y + faceLm[168].y) * 0.5,
    };
    const leftEye = faceLm[159];
    const rightEye = faceLm[386];
    const nose = faceLm[1];
    const leftCheek = faceLm[50];
    const rightCheek = faceLm[280];
    const mouth = {
        x: (faceLm[13].x + faceLm[14].x) * 0.5,
        y: (faceLm[13].y + faceLm[14].y) * 0.5,
    };
    const chin = faceLm[152];

    const faceHeight = Math.hypot(faceLm[10].x - chin.x, faceLm[10].y - chin.y);
    const faceWidth = Math.hypot(faceLm[234].x - faceLm[454].x, faceLm[234].y - faceLm[454].y);
    const faceScale = Math.max(0.06, Math.max(faceHeight, faceWidth));

    // Blendshape dictionary lookup
    const bs = {};
    if (Array.isArray(blendshapes)) {
        for (const c of blendshapes) {
            if (c && c.categoryName) bs[c.categoryName] = c.score || 0;
        }
    }

    const avg = (a, b) => ((bs[a] || 0) + (bs[b] || 0)) * 0.5;
    const happy = clamp(avg("mouthSmileLeft", "mouthSmileRight") * 1.25, 0, 1);
    const frown = avg("mouthFrownLeft", "mouthFrownRight");
    const browInner = bs["browInnerUp"] || 0;
    const browDown = avg("browDownLeft", "browDownRight");
    const browOuter = avg("browOuterUpLeft", "browOuterUpRight");
    const jawOpen = bs["jawOpen"] || 0;
    const eyeWide = avg("eyeWideLeft", "eyeWideRight");

    const sad = clamp(Math.max(frown * 1.4, browInner * 0.75 * (1 - happy)), 0, 1);
    const angry = clamp(browDown * 1.35, 0, 1);
    const surprised = clamp(Math.max(jawOpen * 1.15, eyeWide * 1.3), 0, 1);
    const question = clamp(Math.max(browInner, browOuter) * 1.2, 0, 1);

    // Dominant expression classification
    let expression = "neutral";
    let expressionLabel = "Neutral 🙂";
    let expressionScore = 0;

    const exprCandidates = [
        { key: "gembira", label: "Ceria 😊", val: happy, min: 0.30 },
        { key: "terkejut", label: "Terkejut 😲", val: surprised, min: 0.30 },
        { key: "marah", label: "Tegas/Marah 😠", val: angry, min: 0.26 },
        { key: "sedih", label: "Sedih 😢", val: sad, min: 0.25 },
        { key: "soalan", label: "Kening Soalan 🤔", val: question, min: 0.28 },
    ];
    for (const c of exprCandidates) {
        if (c.val >= c.min && c.val * 100 > expressionScore) {
            expression = c.key;
            expressionLabel = c.label;
            expressionScore = Math.round(c.val * 100);
        }
    }

    // Hand-to-Face spatial features
    let ix = 0, iy = 0, px = 0, py = 0;
    let proxForehead = 0, proxEye = 0, proxCheek = 0, proxMouth = 0, proxChin = 0;
    let zone = "bebas";
    let zoneLabel = "Bebas";
    let zoneProximity = 0;
    let activeContactPt = null;

    if (handLm && handLm.length >= 21) {
        const indexTip = handLm[8];
        const thumbTip = handLm[4];
        const middleTip = handLm[12];
        const palm = handLm[9];
        const fingerPts = [thumbTip, indexTip, middleTip, palm];

        ix = clamp((indexTip.x - nose.x) / faceScale, -2.0, 2.0);
        iy = clamp((indexTip.y - nose.y) / faceScale, -2.0, 2.0);
        px = clamp((palm.x - nose.x) / faceScale, -2.0, 2.0);
        py = clamp((palm.y - nose.y) / faceScale, -2.0, 2.0);

        const minDistTo = targetPt => {
            let best = Infinity, bestPt = indexTip;
            for (const fp of fingerPts) {
                const d = Math.hypot(fp.x - targetPt.x, fp.y - targetPt.y);
                if (d < best) { best = d, bestPt = fp; }
            }
            return { dist: best, pt: bestPt };
        };

        const toProx = d => clamp(1 - d / (faceScale * 0.95), 0, 1);

        const dForehead = minDistTo(forehead);
        const dEyeL = minDistTo(leftEye);
        const dEyeR = minDistTo(rightEye);
        const dEye = dEyeL.dist <= dEyeR.dist ? dEyeL : dEyeR;
        const dCheekL = minDistTo(leftCheek);
        const dCheekR = minDistTo(rightCheek);
        const dCheek = dCheekL.dist <= dCheekR.dist ? dCheekL : dCheekR;
        const dMouth = minDistTo(mouth);
        const dChin = minDistTo(chin);

        proxForehead = toProx(dForehead.dist);
        proxEye = toProx(dEye.dist);
        proxCheek = toProx(dCheek.dist);
        proxMouth = toProx(dMouth.dist);
        proxChin = toProx(dChin.dist);

        const zoneChecks = [
            { key: "dahi", label: "Dahi", prox: proxForehead, pt: dForehead.pt },
            { key: "mata", label: "Mata", prox: proxEye, pt: dEye.pt },
            { key: "pipi", label: "Pipi", prox: proxCheek, pt: dCheek.pt },
            { key: "mulut", label: "Mulut", prox: proxMouth, pt: dMouth.pt },
            { key: "dagu", label: "Dagu", prox: proxChin, pt: dChin.pt },
        ];
        zoneChecks.sort((a, b) => b.prox - a.prox);

        if (zoneChecks[0].prox >= 0.42) {
            zone = zoneChecks[0].key;
            zoneLabel = zoneChecks[0].label;
            zoneProximity = Math.round(zoneChecks[0].prox * 100);
            activeContactPt = zoneChecks[0].pt;
        } else if (py > 0.65 && Math.abs(px) < 1.1) {
            zone = "dada";
            zoneLabel = "Dada / Badan";
            zoneProximity = 75;
        }
    }

    const vector = [
        round5(ix), round5(iy), round5(px), round5(py),
        round5(proxForehead), round5(proxEye), round5(proxCheek), round5(proxMouth), round5(proxChin),
        round5(happy), round5(sad), round5(angry), round5(surprised), round5(question),
        1, // f[14] = hasFace flag
    ];

    return {
        hasFace: true,
        vector,
        zone,
        zoneLabel,
        zoneProximity,
        expression,
        expressionLabel,
        expressionScore,
        activeContactPt,
        scores: { happy, sad, angry, surprised, question },
        anchors: { forehead, leftEye, rightEye, nose, leftCheek, rightCheek, mouth, chin },
    };
}

/**
 * Builds the 78-number multimodal feature vector (63 hand + 15 face/trajectory).
 * Even when face landmarks are not present, slots 63..66 carry camera-space
 * palm/index trajectory with f[77] = 0.25 so DTW sequence matching tracks motion.
 */
export function buildFeatureVector(handLm, faceContext = null) {
    const handVec = normaliseLandmarks(handLm);
    if (faceContext && Array.isArray(faceContext.vector) && faceContext.vector.length === FACE_FEATURE_DIM) {
        return [...handVec, ...faceContext.vector];
    }
    const [camIx, camIy, camPx, camPy] = extractCameraTrajectory(handLm);
    return [
        ...handVec,
        camIx, camIy, camPx, camPy,
        0, 0, 0, 0, 0,
        0, 0, 0, 0, 0,
        0.25,
    ];
}

/**
 * Extracts validated upper-body shoulder context from 33 MediaPipe Pose landmarks.
 * Returns null if shoulders are missing, low-visibility (<0.35), or degenerate (<0.08).
 */
export function extractPoseContext(poseLm) {
    if (!Array.isArray(poseLm) || poseLm.length <= 12) return null;
    const leftShoulder = poseLm[11];
    const rightShoulder = poseLm[12];
    const nose = poseLm[0] || null;
    if (!leftShoulder || !rightShoulder) return null;

    const visL = leftShoulder.visibility ?? 1;
    const visR = rightShoulder.visibility ?? 1;
    if (visL < 0.35 || visR < 0.35) return null;

    const dx = leftShoulder.x - rightShoulder.x;
    const dy = leftShoulder.y - rightShoulder.y;
    const shoulderWidth = Math.hypot(dx, dy);
    if (!Number.isFinite(shoulderWidth) || shoulderWidth < 0.08) return null;

    const shoulderCenter = {
        x: (leftShoulder.x + rightShoulder.x) * 0.5,
        y: (leftShoulder.y + rightShoulder.y) * 0.5,
        z: ((leftShoulder.z ?? 0) + (rightShoulder.z ?? 0)) * 0.5,
    };

    return {
        hasPose: true,
        shoulderCenter,
        shoulderWidth,
        nose,
        anchors: { leftShoulder, rightShoulder, shoulderCenter, nose },
    };
}

/**
 * Consistently assigns primary vs secondary hand using MediaPipe handedness labels,
 * preventing random slot swaps between frames when two hands are visible.
 */
export function selectHandsOrder(handsLm, handedness = null, preferredPrimary = "Right") {
    if (!Array.isArray(handsLm) || handsLm.length === 0) {
        return { primary: null, secondary: null, primaryIdx: -1 };
    }
    if (handsLm.length === 1) {
        return { primary: handsLm[0], secondary: null, primaryIdx: 0 };
    }

    const pref = String(preferredPrimary || "Right").toLowerCase();
    let primaryIdx = 0;
    if (Array.isArray(handedness)) {
        const foundIdx = handedness.findIndex(
            h => String(h?.[0]?.categoryName || "").toLowerCase() === pref
        );
        if (foundIdx >= 0 && handsLm[foundIdx]) {
            primaryIdx = foundIdx;
        }
    }
    const secondaryIdx = primaryIdx === 0 ? 1 : 0;
    return {
        primary: handsLm[primaryIdx] || null,
        secondary: handsLm[secondaryIdx] || null,
        primaryIdx,
    };
}

function estimateHandSpan(handLm) {
    if (!handLm || handLm.length < 21) return 0.12;
    const w = handLm[0], m = handLm[9], p = handLm[17];
    const span = Math.hypot(m.x - w.x, m.y - w.y) + Math.hypot(p.x - w.x, p.y - w.y);
    return Math.max(0.08, span * 1.35);
}

/**
 * Builds the 146-number v2 multimodal feature vector:
 *   [0..62]    63 primary hand coordinates (wrist-normalised)
 *   [63..77]   15 face context (f[77]=1), shoulder-normalised trajectory (f[77]=0.5),
 *              or camera trajectory fallback (f[77]=0.25)
 *   [78..140]  63 secondary hand coordinates (or zeros if single hand)
 *   [141..145] hasHand2, secWristDx, secWristDy, wristDist, hasPose
 */
export function buildMultimodalVector(handsLm, handedness = null, faceContext = null, poseContext = null) {
    const { primary, secondary } = selectHandsOrder(handsLm, handedness);
    if (!primary || primary.length < 21) {
        return new Array(FULL_V2_FEATURE_DIM).fill(0);
    }

    const hand1Vec = normaliseLandmarks(primary);

    let context15;
    if (faceContext?.hasFace && Array.isArray(faceContext.vector) && faceContext.vector.length === FACE_FEATURE_DIM) {
        context15 = faceContext.vector;
    } else if (poseContext?.hasPose) {
        const sc = poseContext.shoulderCenter;
        const sw = poseContext.shoulderWidth;
        const idxTip = primary[8];
        const palm = primary[9];
        context15 = [
            round5(clamp((idxTip.x - sc.x) / sw, -2.2, 2.2)),
            round5(clamp((idxTip.y - sc.y) / sw, -2.2, 2.2)),
            round5(clamp((palm.x - sc.x) / sw, -2.2, 2.2)),
            round5(clamp((palm.y - sc.y) / sw, -2.2, 2.2)),
            0, 0, 0, 0, 0,
            0, 0, 0, 0, 0,
            0.5,
        ];
    } else {
        const [camIx, camIy, camPx, camPy] = extractCameraTrajectory(primary);
        context15 = [
            camIx, camIy, camPx, camPy,
            0, 0, 0, 0, 0,
            0, 0, 0, 0, 0,
            0.25,
        ];
    }

    const hasSecond = Boolean(secondary && secondary.length >= 21);
    const hand2Vec = hasSecond ? normaliseLandmarks(secondary) : new Array(HAND_FEATURE_DIM).fill(0);

    const hasPose = poseContext?.hasPose ? 1 : 0;
    let dx = 0, dy = 0, dist = 0;
    if (hasSecond) {
        const refScale = poseContext?.hasPose ? poseContext.shoulderWidth : estimateHandSpan(primary);
        dx = round5(clamp((secondary[0].x - primary[0].x) / refScale, -2.5, 2.5));
        dy = round5(clamp((secondary[0].y - primary[0].y) / refScale, -2.5, 2.5));
        dist = round5(clamp(Math.hypot(dx, dy), 0, 3.0));
    }

    return [
        ...hand1Vec,
        ...context15,
        ...hand2Vec,
        hasSecond ? 1 : 0,
        dx,
        dy,
        dist,
        hasPose,
    ];
}

/**
 * Draws lightweight upper-body shoulder anchor guide for dynamic signing.
 */
export function drawPoseAnchors(ctx, poseLm, w, h, poseContext = null) {
    const anchors = poseContext?.anchors || extractPoseContext(poseLm)?.anchors;
    if (!anchors || !ctx) return;
    const { leftShoulder, rightShoulder, shoulderCenter } = anchors;

    ctx.save();
    ctx.beginPath();
    ctx.setLineDash([6, 5]);
    ctx.strokeStyle = "rgba(56, 189, 248, 0.55)";
    ctx.lineWidth = 2;
    ctx.moveTo(leftShoulder.x * w, leftShoulder.y * h);
    ctx.lineTo(rightShoulder.x * w, rightShoulder.y * h);
    ctx.stroke();

    [leftShoulder, shoulderCenter, rightShoulder].forEach((pt, idx) => {
        ctx.beginPath();
        ctx.arc(pt.x * w, pt.y * h, idx === 1 ? 5 : 4, 0, 2 * Math.PI);
        ctx.fillStyle = idx === 1 ? "#38bdf8" : "rgba(255, 234, 167, 0.85)";
        ctx.fill();
    });
    ctx.restore();
}

/**
 * Draws clean, non-intrusive facial anchor points & active hand-to-face connection.
 */
export function drawFaceAnchors(ctx, faceLm, w, h, faceContext = null) {
    if (!faceLm || !faceContext?.anchors) return;
    const { forehead, leftEye, rightEye, nose, leftCheek, rightCheek, mouth, chin } = faceContext.anchors;

    ctx.save();

    // Subtle face contour oval (canonical face oval indices)
    const ovalIndices = [10, 338, 297, 332, 284, 251, 389, 356, 454, 323, 361, 288, 397, 365, 379, 378, 400, 377, 152, 148, 176, 149, 150, 136, 172, 58, 132, 93, 234, 127, 162, 21, 54, 103, 67, 109, 10];
    ctx.beginPath();
    ovalIndices.forEach((idx, i) => {
        const p = faceLm[idx];
        if (!p) return;
        if (i === 0) ctx.moveTo(p.x * w, p.y * h);
        else ctx.lineTo(p.x * w, p.y * h);
    });
    ctx.strokeStyle = "rgba(129, 236, 236, 0.35)";
    ctx.lineWidth = 1.8;
    ctx.stroke();

    // Key BIM spatial anchor dots
    const anchorMap = [
        { key: "dahi", pt: forehead },
        { key: "mata", pt: leftEye },
        { key: "mata", pt: rightEye },
        { key: "hidung", pt: nose },
        { key: "pipi", pt: leftCheek },
        { key: "pipi", pt: rightCheek },
        { key: "mulut", pt: mouth },
        { key: "dagu", pt: chin },
    ];

    for (const a of anchorMap) {
        const isActive = faceContext.zone === a.key;
        const ax = a.pt.x * w, ay = a.pt.y * h;
        ctx.beginPath();
        ctx.arc(ax, ay, isActive ? 6.5 : 3, 0, 2 * Math.PI);
        ctx.fillStyle = isActive ? "#2ed573" : "rgba(255, 234, 167, 0.75)";
        if (isActive) {
            ctx.shadowColor = "#2ed573";
            ctx.shadowBlur = 12;
        } else {
            ctx.shadowBlur = 0;
        }
        ctx.fill();
    }

    // Draw dashed proximity link between fingertip & active face zone
    if (faceContext.activeContactPt && faceContext.zone !== "bebas" && faceContext.zone !== "dada") {
        const targetAnchor = anchorMap.find(a => a.key === faceContext.zone)?.pt;
        if (targetAnchor) {
            ctx.beginPath();
            ctx.setLineDash([5, 4]);
            ctx.strokeStyle = "#2ed573";
            ctx.lineWidth = 2.2;
            ctx.moveTo(faceContext.activeContactPt.x * w, faceContext.activeContactPt.y * h);
            ctx.lineTo(targetAnchor.x * w, targetAnchor.y * h);
            ctx.stroke();
        }
    }

    ctx.restore();
}

/**
 * BIM Sign Face-Zone & Facial-Expression Rules:
 * Evaluates whether the user's hand placement on the face & facial expression
 * match the linguistic rules of Bahasa Isyarat Malaysia (BIM).
 */
const BIM_FACE_RULES = {
    // Spatial Face-Zone Anchored Signs (Keluarga, Salam & Anggota Badan)
    BAPA: { zones: ["dahi"], hintNeed: "Letakkan tangan berhampiran dahi untuk isyarat Bapa 👨‍👧", hintOk: "Tepat! Tangan di zon dahi (Bapa) ⭐" },
    EMAK: { zones: ["pipi", "dagu"], hintNeed: "Sentuh bahagian pipi/dagu untuk isyarat Emak 👩‍👧", hintOk: "Tepat! Tangan di zon pipi/dagu (Emak) ⭐" },
    ABANG: { zones: ["dahi", "pipi", "dagu", "bebas"], hintOk: "Isyarat Abang dikesan dengan baik! ⭐" },
    KAKAK: { zones: ["pipi", "dagu", "bebas"], hintOk: "Isyarat Kakak dikesan dengan baik! ⭐" },
    ADIK: { zones: ["dagu", "mulut", "pipi", "bebas"], hintOk: "Isyarat Adik dikesan dengan baik! ⭐" },
    DATUK: { zones: ["dahi", "dagu"], hintNeed: "Dekatkan tangan ke bahagian dahi/dagu mengikut video Datuk", hintOk: "Zon wajah Datuk tepat! ⭐" },
    NENEK: { zones: ["pipi", "dagu"], hintNeed: "Dekatkan tangan ke bahagian dagu/pipi untuk Nenek", hintOk: "Zon wajah Nenek tepat! ⭐" },
    MATA: { zones: ["mata"], strictZone: true, hintNeed: "Tunjukkan jari ke arah mata anda 👁️", hintOk: "Tepat! Jari menunjuk ke arah Mata! ⭐" },
    PIPI: { zones: ["pipi"], strictZone: true, hintNeed: "Sentuh atau tunjuk kawasan pipi anda 😊", hintOk: "Tepat! Tangan pada kawasan Pipi! ⭐" },
    DADA: { zones: ["dada"], strictZone: true, hintNeed: "Letakkan tangan di bahagian dada", hintOk: "Tepat! Tangan di bahagian Dada! ⭐" },
    SAYA: { zones: ["dada"], hintNeed: "Arahkan tangan ke dada untuk isyarat Saya", hintOk: "Tepat! Tangan di dada (Saya) ⭐" },
    TERIMA_KASIH: { zones: ["mulut", "dagu", "dada"], hintNeed: "Mulakan tangan dari dagu/mulut ke hadapan (Terima Kasih)", hintOk: "Gerakan Terima Kasih dari wajah dikesan! ⭐" },
    GULA: { zones: ["mulut", "pipi", "dagu"], hintNeed: "Sentuh jari berhampiran bibir/pipi untuk Gula", hintOk: "Zon wajah Gula tepat! ⭐" },
    RUSA: { zones: ["dahi", "mata"], hintNeed: "Angkat tangan di sisi dahi seperti tanduk Rusa 🦌", hintOk: "Tepat! Kedudukan tanduk Rusa di dahi dikesan! ⭐" },
    KUDA: { zones: ["dahi", "mata", "pipi"], hintNeed: "Letakkan tangan di sisi dahi/kepala seperti telinga Kuda 🐴", hintOk: "Kedudukan isyarat Kuda tepat! ⭐" },
    TOLONG: { zones: ["dada", "bebas"], hintOk: "Gerakan Tolong dikesan dengan baik! ⭐" },
    ASSALAMUALAIKUM: { zones: ["dahi", "pipi", "mulut", "bebas"], exprs: ["gembira", "neutral"], hintOk: "Isyarat Assalamualaikum dikesan! ⭐" },
    SELAMAT_PAGI: { zones: ["dada", "bebas"], exprs: ["gembira", "neutral"], hintOk: "Isyarat Selamat Pagi dikesan! ☀️⭐" },
    APA_KHABAR: { zones: ["dada", "bebas"], exprs: ["gembira", "soalan", "neutral"], hintOk: "Isyarat Apa Khabar dikesan! 👋⭐" },
    SELAMAT_TINGGAL: { zones: ["bebas"], exprs: ["gembira", "neutral"], hintOk: "Lambaian Selamat Tinggal dikesan! 👋⭐" },

    // Emotion / Expression Signs (Modul Perasaan)
    GEMBIRA: { exprs: ["gembira"], zones: ["dada", "bebas"], hintExpr: "Cuba senyum ceria sambil buat isyarat Gembira! 😊", hintOk: "Senyuman Gembira + Isyarat Tangan dikesan! 😊⭐" },
    SUKA: { exprs: ["gembira"], zones: ["dada", "bebas"], hintExpr: "Senyum sambil buat gerakan Suka di dada 😊", hintOk: "Ekspresi Suka yang sangat baik! 😊⭐" },
    SEDIH: { exprs: ["sedih"], hintExpr: "Tunjukkan mimik muka sedih untuk tambah ketepatan 😢", hintOk: "Mimik muka Sedih + Isyarat dikesan! ⭐" },
    MARAH: { exprs: ["marah"], hintExpr: "Kerutkan sedikit kening (mimik tegas/marah) 😠", hintOk: "Mimik muka Marah + Isyarat dikesan! ⭐" },
    TERKEJUT: { exprs: ["terkejut"], hintExpr: "Buka mata/mulut sedikit seperti Terkejut! 😲", hintOk: "Mimik Terkejut + Isyarat dikesan! 😲⭐" },
    TAKUT: { exprs: ["terkejut", "sedih"], hintExpr: "Tunjukkan mimik muka Takut sambil gerakkan tangan", hintOk: "Mimik Takut + Isyarat dikesan! ⭐" },
    SAKIT: { exprs: ["sedih", "marah"], hintOk: "Mimik Sakit + Isyarat dikesan! ⭐" },
    MAAF: { zones: ["dada", "bebas"], exprs: ["sedih", "neutral"], hintOk: "Isyarat Maaf dikesan dengan baik! ⭐" },

    // Question Signs (Modul Soalan - Non-manual brow markers)
    APA: { exprs: ["soalan", "terkejut"], hintOk: "Mimik bertanya + Isyarat Apa dikesan! 🤔⭐" },
    SIAPA: { exprs: ["soalan"], zones: ["mulut", "dagu", "bebas"], hintOk: "Mimik bertanya + Isyarat Siapa dikesan! ⭐" },
    DI_MANA: { exprs: ["soalan"], hintOk: "Mimik bertanya + Isyarat Di Mana dikesan! ⭐" },
    BILA: { exprs: ["soalan"], hintOk: "Mimik bertanya + Isyarat Bila dikesan! ⭐" },
    KENAPA: { exprs: ["soalan", "marah"], zones: ["dahi", "bebas"], hintOk: "Mimik bertanya + Isyarat Kenapa dikesan! ⭐" },
    BAGAIMANA: { exprs: ["soalan"], hintOk: "Mimik bertanya + Isyarat Bagaimana dikesan! ⭐" },
    BERAPA: { exprs: ["soalan"], hintOk: "Mimik bertanya + Isyarat Berapa dikesan! ⭐" },
};

/**
 * Resolves the effective Option-A face/pose constraint rule for a BIM sign:
 * - Ignores catch-all "bebas" zones.
 * - Treats "neutral"-containing expression lists as optional mood markers rather than mandatory checks.
 */
export function getEffectiveFaceRule(label) {
    const key = String(label || "").trim().toUpperCase();
    const raw = BIM_FACE_RULES[key];
    if (!raw) {
        return {
            key,
            zones: null,
            exprs: null,
            strictZone: false,
            hasConstraint: false,
            hintNeed: null,
            hintExpr: null,
            hintOk: null,
        };
    }

    const rawZones = Array.isArray(raw.zones) ? raw.zones.filter(z => z && z !== "bebas") : [];
    const zones = rawZones.length > 0 ? rawZones : null;

    const rawExprs = Array.isArray(raw.exprs) ? raw.exprs : [];
    const hasNeutral = rawExprs.includes("neutral");
    const nonNeutralExprs = rawExprs.filter(e => e && e !== "neutral");
    const exprs = (!hasNeutral && nonNeutralExprs.length > 0) ? nonNeutralExprs : null;

    const strictZone = Boolean(raw.strictZone && zones && zones.length > 0);
    const hasConstraint = Boolean((zones && zones.length > 0) || (exprs && exprs.length > 0));

    return {
        key,
        zones,
        exprs,
        strictZone,
        hasConstraint,
        hintNeed: raw.hintNeed || null,
        hintExpr: raw.hintExpr || null,
        hintOk: raw.hintOk || null,
    };
}

export function evaluateFaceSignBoost(targetLabel, faceContext) {
    if (!faceContext || !faceContext.hasFace || !targetLabel) {
        return { bonus: 0, zoneMatched: null, exprMatched: null, hintOk: null, hintNeed: null, strictBlocked: false };
    }
    const key = String(targetLabel).trim().toUpperCase();
    const raw = BIM_FACE_RULES[key];
    if (!raw) {
        return { bonus: 0, zoneMatched: null, exprMatched: null, hintOk: null, hintNeed: null, strictBlocked: false };
    }

    const effZones = Array.isArray(raw.zones) ? raw.zones.filter(z => z && z !== "bebas") : [];
    const effExprs = Array.isArray(raw.exprs) ? raw.exprs.filter(e => e && e !== "neutral") : [];

    let bonus = 0;
    let zoneMatched = null;
    let exprMatched = null;
    let strictBlocked = false;

    if (effZones.length > 0) {
        zoneMatched = effZones.includes(faceContext.zone);
        if (zoneMatched) {
            bonus += 9;
        } else if (raw.strictZone) {
            strictBlocked = true;
            bonus -= 18;
        }
    }

    if (effExprs.length > 0) {
        exprMatched = effExprs.includes(faceContext.expression);
        if (exprMatched) {
            bonus += 8;
        }
    }

    return {
        bonus,
        zoneMatched,
        exprMatched,
        strictBlocked,
        hintOk: (zoneMatched || exprMatched) ? (raw.hintOk || null) : null,
        hintNeed: (effZones.length > 0 && !zoneMatched && raw.hintNeed)
            ? raw.hintNeed
            : (effExprs.length > 0 && !exprMatched && raw.hintExpr ? raw.hintExpr : null),
    };
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

/**
 * Checks if the hand is in an index-finger-pointing shape (used for dynamic 'Z' or static 'D'/'1').
 */
export function isIndexPointingShape(lm) {
    if (!lm || lm.length < 21) return false;
    const dist = (a, b) => Math.hypot(a.x - b.x, a.y - b.y);
    const wrist = lm[0];
    const dIndex = dist(wrist, lm[8]);
    const dIndexBase = dist(wrist, lm[5]);
    const dMiddle = dist(wrist, lm[12]);
    const dRing = dist(wrist, lm[16]);
    const dPinky = dist(wrist, lm[20]);

    const indexExtended = dIndex > dIndexBase * 1.22 && lm[8].y < lm[6].y + 0.03;
    const othersCurled = (dMiddle < dIndex * 0.86) &&
                         (dRing < dIndex * 0.86) &&
                         (dPinky < dIndex * 0.88);
    return indexExtended && othersCurled;
}

/**
 * Multimodal Squared Distance:
 * - Compares 63 wrist-normalised hand features (indices 0..62)
 * - Fuses 15 rotation/scale-invariant anatomical finger curl & pinch features
 *   (if precomputed on sample objects or computed on the fly)
 * - If useFace === true and BOTH vectors include valid face context (length >= 78 and f[77] > 0.5),
 *   fuses hand distance with face-anchor + expression distance!
 */
function multimodalSqDist(a, b, mirror, useFace, geomA = null, geomB = null) {
    let handD = 0;
    for (let i = 0; i < 63; i++) {
        const av = (mirror && i % 3 === 0) ? -a[i] : a[i];
        const diff = av - b[i];
        // Give extra weight to fingertips (indices 4, 8, 12, 16, 20 -> base 12, 24, 36, 48, 60)
        const jointIdx = (i / 3) | 0;
        const w = (jointIdx > 0 && jointIdx % 4 === 0) ? 1.35 : 0.9;
        handD += diff * diff * w;
    }

    // Fuse rotation-invariant anatomical finger curl & pinch geometry
    const gA = geomA || extractHandGeometry(a);
    const gB = geomB || extractHandGeometry(b);
    let geomD = 0;
    for (let k = 0; k < 15; k++) {
        const gd = gA[k] - gB[k];
        geomD += gd * gd;
    }
    handD = handD * 0.78 + geomD * 0.65;

    if (!useFace || a.length < 78 || b.length < 78 || a[77] <= 0.55 || b[77] <= 0.55) {
        return handD;
    }

    let faceD = 0;
    // Indices 63..66: relative hand-to-nose offsets (63 & 65 are horizontal X, so mirror flips sign)
    for (let j = 63; j < 67; j++) {
        const av = (mirror && (j === 63 || j === 65)) ? -a[j] : a[j];
        const diff = av - b[j];
        faceD += diff * diff * 0.45;
    }
    // Indices 67..71: 5 facial zone proximities (mirror-invariant, high discriminative value for Bapa/Emak/Mata/Pipi)
    for (let j = 67; j < 72; j++) {
        const diff = a[j] - b[j];
        faceD += diff * diff * 0.85;
    }
    // Indices 72..76: 5 facial expression scores (mirror-invariant, discriminative for Perasaan/Soalan)
    for (let j = 72; j < 77; j++) {
        const diff = a[j] - b[j];
        faceD += diff * diff * 0.45;
    }

    // Blend so total distance scale stays compatible with confidence thresholds
    return handD * 0.72 + faceD * 0.55;
}

export class KnnClassifier {
    constructor(k = 5) {
        this.k = k;
        this.samples = []; // [{ label, f: Float32Array(63 | 78), geom: Float32Array(15) }]
    }

    load(samples) {
        this.samples = samples
            .filter(s => Array.isArray(s.f) && (s.f.length === 63 || s.f.length === 78))
            .map(s => {
                const f = Float32Array.from(s.f);
                return { label: s.label, f, geom: extractHandGeometry(f) };
            });
    }

    get size() { return this.samples.length; }
    get labels() { return [...new Set(this.samples.map(s => s.label))]; }

    /** @returns {{label:string|null, confidence:number, distance:number, usedFace:boolean}} */
    predict(features, motionTracker = null, faceContext = null) {
        if (this.samples.length === 0) return { label: null, confidence: 0, distance: Infinity, usedFace: false };

        const queryHasFace = features.length >= 78 && features[77] > 0.5;
        const queryGeom = extractHandGeometry(features);
        const best = [];
        for (const s of this.samples) {
            const useFace = queryHasFace && !isHandOnlyLabel(s.label) && s.f.length >= 78 && s.f[77] > 0.5;
            const d = Math.min(
                multimodalSqDist(features, s.f, false, useFace, queryGeom, s.geom),
                multimodalSqDist(features, s.f, true, useFace, queryGeom, s.geom)
            );
            if (best.length < this.k || d < best[best.length - 1].d) {
                best.push({ d, label: s.label, usedFace: useFace });
                best.sort((a, b) => a.d - b.d);
                if (best.length > this.k) best.pop();
            }
        }

        const votes = {};
        best.forEach(b => { votes[b.label] = (votes[b.label] || 0) + 1 / (b.d + 1e-3); });

        // Disambiguation for signs sharing identical hand shape (e.g. static 'I' vs dynamic 'J', static 'D'/'1' vs dynamic 'Z')
        const iScore = votes["I"] || 0;
        const jScore = votes["J"] || 0;
        if (iScore > 0 || jScore > 0) {
            let isJMotion = false;
            if (motionTracker) {
                const jCheck = motionTracker.detectJMotion();
                if (jCheck && jCheck.matched) isJMotion = true;
            }
            if (isJMotion) {
                votes["J"] = jScore + iScore;
                delete votes["I"];
            } else {
                votes["I"] = iScore + jScore;
                delete votes["J"];
            }
        }

        let top = null, topScore = 0, total = 0;
        for (const [label, score] of Object.entries(votes)) {
            total += score;
            if (score > topScore) { top = label; topScore = score; }
        }

        const nearest = Math.sqrt(best[0].d);
        const distanceFactor = Math.max(0, Math.min(1, 1.65 - nearest));
        let confidence = Math.round((topScore / total) * distanceFactor * 100);

        if (top && faceContext?.hasFace && !isHandOnlyLabel(top)) {
            const boost = evaluateFaceSignBoost(top, faceContext);
            confidence = clamp(confidence + boost.bonus, 0, 99);
        }

        return {
            label: top,
            confidence,
            distance: nearest,
            usedFace: Boolean(best[0]?.usedFace),
        };
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
 * to differentiate static signs (like 'I', 'D') from dynamic motion signs (like 'J', 'Z', and BIM dynamic signs).
 */
export class HandMotionTracker {
    constructor(bufferSize = 45) {
        this.bufferSize = bufferSize;
        this.history = [];
        this.lastJDetection = 0;
        this.lastZDetection = 0;
    }

    push(landmarks) {
        if (!landmarks || landmarks.length < 21) return;
        const now = performance.now();
        this.history.push({
            time: now,
            pinky: { x: landmarks[20].x, y: landmarks[20].y },
            wrist: { x: landmarks[0].x, y: landmarks[0].y },
            index: { x: landmarks[8].x, y: landmarks[8].y },
            palm: { x: landmarks[9].x, y: landmarks[9].y },
        });

        while (this.history.length > this.bufferSize || (this.history.length > 2 && now - this.history[0].time > 1500)) {
            this.history.shift();
        }
    }

    reset() {
        this.history = [];
        this.lastJDetection = 0;
        this.lastZDetection = 0;
    }

    /**
     * Computes maximum spatial movement across wrist, palm, index, and pinky
     * so motion detection works regardless of which fingers are curled.
     */
    getMovementMagnitude(frames = 15) {
        if (this.history.length < 3) return 0;
        const recent = this.history.slice(-Math.min(frames, this.history.length));
        const keys = ["pinky", "index", "palm", "wrist"];
        let maxSpan = 0;

        for (const k of keys) {
            let minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity;
            for (const p of recent) {
                const pt = p[k];
                if (!pt) continue;
                if (pt.x < minX) minX = pt.x;
                if (pt.x > maxX) maxX = pt.x;
                if (pt.y < minY) minY = pt.y;
                if (pt.y > maxY) maxY = pt.y;
            }
            const span = Math.hypot(maxX - minX, maxY - minY);
            if (span > maxSpan) maxSpan = span;
        }
        return maxSpan;
    }

    isStill(threshold = 0.05) {
        if (this.history.length < 4) return true;
        return this.getMovementMagnitude(14) < threshold;
    }

    detectJMotion() {
        const now = performance.now();
        if (now - this.lastJDetection < 1500) {
            return { matched: true, confidence: 95 };
        }
        if (this.history.length < 6) return { matched: false, confidence: 0 };

        const pts = this.history.map(h => h.pinky);
        const n = pts.length;

        let maxY = -Infinity;
        let bottomIdx = -1;
        for (let i = 0; i < n; i++) {
            if (pts[i].y > maxY) {
                maxY = pts[i].y;
                bottomIdx = i;
            }
        }

        if (bottomIdx < 2) return { matched: false, confidence: 0 };

        let minYBefore = Infinity;
        for (let i = 0; i < bottomIdx; i++) {
            if (pts[i].y < minYBefore) minYBefore = pts[i].y;
        }

        const downwardDrop = maxY - minYBefore;
        if (downwardDrop < 0.03) return { matched: false, confidence: 0 };

        let minYAfter = Infinity;
        for (let i = bottomIdx + 1; i < n; i++) {
            if (pts[i].y < minYAfter) minYAfter = pts[i].y;
        }
        const upwardHook = minYAfter !== Infinity ? (maxY - minYAfter) : 0;

        let maxHorizontalSweep = 0;
        const bottomX = pts[bottomIdx].x;
        for (let i = Math.max(0, bottomIdx - 3); i < n; i++) {
            const dx = Math.abs(pts[i].x - bottomX);
            if (dx > maxHorizontalSweep) maxHorizontalSweep = dx;
        }

        const totalMovement = this.getMovementMagnitude(20);
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

    /**
     * Detects the dynamic zig-zag trajectory of letter 'Z' traced by the index finger.
     */
    detectZMotion() {
        const now = performance.now();
        if (now - this.lastZDetection < 1500) {
            return { matched: true, confidence: 95 };
        }
        if (this.history.length < 8) return { matched: false, confidence: 0 };

        const pts = this.history.map(h => h.index);
        const n = pts.length;

        let minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity;
        for (const p of pts) {
            if (p.x < minX) minX = p.x;
            if (p.x > maxX) maxX = p.x;
            if (p.y < minY) minY = p.y;
            if (p.y > maxY) maxY = p.y;
        }

        const width = maxX - minX;
        const height = maxY - minY;
        if (width < 0.055 || height < 0.04) {
            return { matched: false, confidence: 0 };
        }

        // Count horizontal direction reversals (Z has at least 1 reversal: horizontal -> diagonal back -> horizontal)
        let dirChanges = 0;
        let lastDir = 0;
        for (let i = 2; i < n; i++) {
            const dx = pts[i].x - pts[i - 2].x;
            if (Math.abs(dx) > 0.014) {
                const dir = dx > 0 ? 1 : -1;
                if (lastDir !== 0 && dir !== lastDir) {
                    dirChanges++;
                }
                lastDir = dir;
            }
        }

        const netDownward = pts[n - 1].y - pts[0].y;
        if (dirChanges >= 1 && netDownward >= 0.025) {
            this.lastZDetection = now;
            const score = Math.min(98, Math.round(80 + (width + height) * 85));
            return { matched: true, confidence: score };
        }

        return { matched: false, confidence: 0 };
    }

    getTrail(joint = "auto") {
        if (joint === "index") return this.history.map(h => h.index);
        if (joint === "palm") return this.history.map(h => h.palm);
        return this.history.map(h => h.pinky);
    }
}

/**
 * Trims leading and trailing stationary frames from a sequence so DTW templates
 * and live sliding windows align tightly on the active gesture stroke.
 */
export function trimIdleSequenceFrames(frames, minFrames = 10) {
    if (!frames || frames.length <= minFrames) return frames || [];
    const n = frames.length;
    const motion = new Float32Array(n);

    for (let i = 1; i < n; i++) {
        const a = frames[i - 1], b = frames[i];
        let sumSq = 0;
        const minLen = Math.min(a.length, b.length);
        const handLim = Math.min(minLen, 63);
        for (let d = 0; d < handLim; d++) {
            const diff = b[d] - a[d];
            sumSq += diff * diff;
        }
        if (minLen >= 78 && a[77] >= 0.2 && b[77] >= 0.2) {
            for (let d = 63; d < 67; d++) {
                const diff = (b[d] - a[d]) * 2.5;
                sumSq += diff * diff;
            }
        }
        if (minLen >= 146 && a[141] >= 0.5 && b[141] >= 0.5) {
            for (let d = 78; d < 141; d++) {
                const diff = b[d] - a[d];
                sumSq += diff * diff * 0.7;
            }
        }
        motion[i] = Math.sqrt(sumSq);
    }

    let maxM = 0;
    for (let i = 1; i < n; i++) if (motion[i] > maxM) maxM = motion[i];
    if (maxM < 0.04) return frames;

    const thresh = maxM * 0.14;
    let start = 0;
    while (start < n - minFrames && motion[start + 1] < thresh) start++;

    let end = n - 1;
    while (end > start + minFrames - 1 && motion[end] < thresh) end--;

    // Keep 2 context frames on each side
    start = Math.max(0, start - 2);
    end = Math.min(n - 1, end + 2);
    return frames.slice(start, end + 1);
}

/**
 * Resamples a sequence of landmark frames (each 63, 78, or 146 numbers) to an exact target length.
 */
export function resampleSequence(frames, targetLength = 30) {
    if (!frames || frames.length === 0) return [];
    const dim = frames.some(f => f && f.length >= 146)
        ? 146
        : (frames.some(f => f && f.length >= 78) ? 78 : 63);

    const normFrame = f => {
        if (f.length === dim) return f;
        if (f.length < dim) return [...f, ...new Array(dim - f.length).fill(0)];
        return f.slice(0, dim);
    };

    if (frames.length === 1) {
        return Array(targetLength).fill(normFrame(frames[0]));
    }
    const n = frames.length;
    const resampled = [];
    for (let k = 0; k < targetLength; k++) {
        const t = (k * (n - 1)) / (targetLength - 1);
        const i0 = Math.floor(t);
        const i1 = Math.min(i0 + 1, n - 1);
        const frac = t - i0;

        const f0 = normFrame(frames[i0]);
        const f1 = normFrame(frames[i1]);
        const interpolated = new Array(dim);
        for (let d = 0; d < dim; d++) {
            interpolated[d] = f0[d] * (1 - frac) + f1[d] * frac;
        }
        // Preserve trajectory/face flag cleanly without fractional decay at boundaries
        if (dim >= 78) {
            interpolated[77] = Math.max(f0[77] || 0, f1[77] || 0);
        }
        // Preserve 2nd-hand & pose presence flags cleanly; snap 2nd-hand block if only one endpoint has hand 2
        if (dim >= 146) {
            const h2a = (f0[141] || 0) >= 0.5;
            const h2b = (f1[141] || 0) >= 0.5;
            if (h2a && h2b) {
                interpolated[141] = 1;
            } else if (h2a || h2b) {
                const src = frac < 0.5 ? f0 : f1;
                for (let d = 78; d <= 144; d++) {
                    interpolated[d] = src[d];
                }
                interpolated[141] = (src[141] || 0) >= 0.5 ? 1 : 0;
            } else {
                interpolated[141] = 0;
            }
            interpolated[145] = Math.max(f0[145] || 0, f1[145] || 0) >= 0.5 ? 1 : 0;
        }
        resampled.push(interpolated);
    }
    return resampled;
}

/**
 * Precomputes per-frame anatomical geometry, start-anchored trajectory, and
 * first-order velocity (Derivative DTW) for fast sequence comparison.
 */
function prepareSequenceFeatures(resampledFrames) {
    const n = resampledFrames.length;
    const dim = n > 0 ? resampledFrames[0].length : 0;
    const geom = new Array(n);
    const geom2 = dim >= 145 ? new Array(n) : null;
    const vel = new Array(n);
    const traj = new Array(n);
    const trajVel = new Array(n);

    const hasTraj = n > 0 && dim >= 78 && resampledFrames.some(f => f[77] >= 0.2);
    const hasPose = n > 0 && dim >= 78 && resampledFrames.some(f => f[77] >= 0.45 || (f.length >= 146 && f[145] >= 0.5));
    const hasFace = n > 0 && dim >= 78 && resampledFrames.some(f => f[77] > 0.55);

    const baseIx = hasTraj ? resampledFrames[0][63] : 0;
    const baseIy = hasTraj ? resampledFrames[0][64] : 0;
    const basePx = hasTraj ? resampledFrames[0][65] : 0;
    const basePy = hasTraj ? resampledFrames[0][66] : 0;

    for (let t = 0; t < n; t++) {
        const curr = resampledFrames[t];
        const prev = t > 0 ? resampledFrames[t - 1] : curr;

        geom[t] = extractHandGeometry(curr, 0);
        if (geom2) {
            geom2[t] = curr[141] >= 0.5 ? extractHandGeometry(curr, 78) : null;
        }

        const v = new Float32Array(63);
        for (let d = 0; d < 63; d++) {
            v[d] = curr[d] - prev[d];
        }
        vel[t] = v;

        if (hasTraj) {
            traj[t] = [
                curr[63] - baseIx,
                curr[64] - baseIy,
                curr[65] - basePx,
                curr[66] - basePy,
            ];
            trajVel[t] = [
                curr[63] - prev[63],
                curr[64] - prev[64],
                curr[65] - prev[65],
                curr[66] - prev[66],
            ];
        } else {
            traj[t] = [0, 0, 0, 0];
            trajVel[t] = [0, 0, 0, 0];
        }
    }

    return { frames: resampledFrames, geom, geom2, vel, traj, trajVel, hasTraj, hasPose, hasFace };
}

/**
 * Derivative Dynamic Time Warping (DDTW) distance between two sequences with:
 * - Sakoe-Chiba warping band constraint (prevents pathological time stretching)
 * - Left/Right hand mirror invariance (mirror = false | true)
 * - Handshape + anatomical geometry + 2-hand coordination + start-anchored trajectory + speed-tolerant velocity matching
 */
export function dtwDistance(seqA, seqB, useFace = false, mirror = false) {
    const prepA = seqA.frames ? seqA : prepareSequenceFeatures(seqA);
    const prepB = seqB.frames ? seqB : prepareSequenceFeatures(seqB);

    const A = prepA.frames;
    const B = prepB.frames;
    const n = A.length;
    const m = B.length;
    if (n === 0 || m === 0) return Infinity;

    const useTraj = prepA.hasTraj && prepB.hasTraj;
    const useTwoHand = A[0].length >= 145 && B[0].length >= 145;
    const band = Math.max(5, Math.round(Math.max(n, m) * 0.25));

    let prev = new Float32Array(m + 1).fill(Infinity);
    let curr = new Float32Array(m + 1).fill(Infinity);
    prev[0] = 0;

    for (let i = 1; i <= n; i++) {
        curr.fill(Infinity);
        const jStart = Math.max(1, i - band);
        const jEnd = Math.min(m, i + band);
        const idxA = i - 1;

        for (let j = jStart; j <= jEnd; j++) {
            const idxB = j - 1;
            const fA = A[idxA];
            const fB = B[idxB];

            // 1. Static handshape + anatomical geometry + optional face context distance
            const poseSq = multimodalSqDist(
                fA,
                fB,
                mirror,
                useFace,
                prepA.geom[idxA],
                prepB.geom[idxB]
            );

            // 2. Joint articulation velocity distance (Derivative DTW, speed-tolerant)
            const vA = prepA.vel[idxA];
            const vB = prepB.vel[idxB];
            let velSq = 0;
            for (let d = 0; d < 63; d++) {
                const av = (mirror && d % 3 === 0) ? -vA[d] : vA[d];
                const dv = av - vB[d];
                velSq += dv * dv;
            }

            // 3. Start-anchored 2D spatial trajectory + trajectory velocity distance
            let trajSq = 0;
            if (useTraj) {
                const tA = prepA.traj[idxA];
                const tB = prepB.traj[idxB];
                const tvA = prepA.trajVel[idxA];
                const tvB = prepB.trajVel[idxB];
                for (let k = 0; k < 4; k++) {
                    const isX = (k === 0 || k === 2);
                    const ak = (mirror && isX) ? -tA[k] : tA[k];
                    const avk = (mirror && isX) ? -tvA[k] : tvA[k];
                    const dt = ak - tB[k];
                    const dtv = avk - tvB[k];
                    trajSq += dt * dt * 0.85 + dtv * dtv * 1.8;
                }
            }

            // 4. Secondary hand coordination & mismatch penalty (v2 146-dim vs 146-dim)
            let secSq = 0;
            if (useTwoHand) {
                const hasH2A = fA[141] >= 0.5;
                const hasH2B = fB[141] >= 0.5;
                if (hasH2A && hasH2B) {
                    let hand2D = 0;
                    for (let d = 0; d < 63; d++) {
                        const av = (mirror && d % 3 === 0) ? -fA[78 + d] : fA[78 + d];
                        const diff = av - fB[78 + d];
                        const jointIdx = (d / 3) | 0;
                        const w = (jointIdx > 0 && jointIdx % 4 === 0) ? 1.35 : 0.9;
                        hand2D += diff * diff * w;
                    }
                    const g2A = (prepA.geom2 && prepA.geom2[idxA]) || extractHandGeometry(fA, 78);
                    const g2B = (prepB.geom2 && prepB.geom2[idxB]) || extractHandGeometry(fB, 78);
                    let geom2D = 0;
                    for (let k = 0; k < 15; k++) {
                        const gd = g2A[k] - g2B[k];
                        geom2D += gd * gd;
                    }
                    const dxA = mirror ? -fA[142] : fA[142];
                    const ddx = dxA - fB[142];
                    const ddy = fA[143] - fB[143];
                    const ddist = fA[144] - fB[144];
                    const relSq = ddx * ddx + ddy * ddy + ddist * ddist;
                    secSq = hand2D * 0.25 + geom2D * 0.20 + relSq * 0.50;
                } else if (hasH2A !== hasH2B) {
                    secSq = 0.36;
                }
            }

            const cost = Math.sqrt(poseSq * 0.68 + velSq * 0.9 + trajSq + secSq);
            const minPrev = Math.min(prev[j], curr[j - 1], prev[j - 1]);
            curr[j] = cost + minPrev;
        }
        prev.set(curr);
    }

    return prev[m] / (n + m);
}

/**
 * SequenceClassifier: Classifies dynamic sign language gestures
 * using Derivative DTW + Left/Right Mirror Invariance + Multi-Scale Windowing + k-NN Voting.
 */
export class SequenceClassifier {
    constructor(targetLength = 30) {
        this.targetLength = targetLength;
        this.templates = [];
    }

    load(sequences) {
        if (!Array.isArray(sequences)) return;
        this.templates = sequences
            .filter(s => Array.isArray(s.frames) && s.frames.length >= 8)
            .map(s => {
                const trimmed = trimIdleSequenceFrames(s.frames, 10);
                const resampled = resampleSequence(trimmed, this.targetLength);
                return {
                    label: s.label,
                    frames: resampled,
                    prep: prepareSequenceFeatures(resampled),
                };
            });
    }

    get size() { return this.templates.length; }
    get labels() { return [...new Set(this.templates.map(t => t.label))]; }

    predict(testFrames, faceContext = null, targetLabel = null) {
        if (this.templates.length === 0 || !testFrames || testFrames.length < 8) {
            return { label: null, confidence: 0, distance: Infinity, usedFace: false };
        }

        const normTarget = targetLabel ? String(targetLabel).trim().toUpperCase() : null;
        const pool = normTarget
            ? this.templates.filter(t => String(t.label).trim().toUpperCase() === normTarget)
            : this.templates;

        if (pool.length === 0) {
            return { label: null, confidence: 0, distance: Infinity, usedFace: false };
        }

        // Build candidate windows: trimmed full buffer + recent 24-frame tail (handles live streaming lag)
        const candidates = [];
        const trimmedFull = trimIdleSequenceFrames(testFrames, 10);
        const resampledFull = resampleSequence(trimmedFull, this.targetLength);
        candidates.push(prepareSequenceFeatures(resampledFull));

        if (testFrames.length >= 26) {
            const tailFrames = trimIdleSequenceFrames(testFrames.slice(-24), 10);
            const resampledTail = resampleSequence(tailFrames, this.targetLength);
            candidates.push(prepareSequenceFeatures(resampledTail));
        }

        const matches = [];

        for (const t of pool) {
            let bestTplDist = Infinity;
            let bestTplUsedFace = false;

            for (const cand of candidates) {
                const useFace = cand.hasFace && t.prep.hasFace && !isHandOnlyLabel(t.label);
                const dNormal = dtwDistance(cand, t.prep, useFace, false);
                const dMirror = dtwDistance(cand, t.prep, useFace, true);
                const d = Math.min(dNormal, dMirror);
                if (d < bestTplDist) {
                    bestTplDist = d;
                    bestTplUsedFace = useFace;
                }
            }

            matches.push({
                label: t.label,
                d: bestTplDist,
                usedFace: bestTplUsedFace,
            });
        }

        matches.sort((a, b) => a.d - b.d);
        const topK = matches.slice(0, Math.min(3, matches.length));
        const minDistance = topK[0].d;

        // Distance-weighted voting over top-K closest DTW templates
        const votes = {};
        let totalVote = 0;
        for (const m of topK) {
            const w = 1 / (m.d * m.d + 1e-3);
            votes[m.label] = (votes[m.label] || 0) + w;
            totalVote += w;
        }

        let bestLabel = topK[0].label;
        let bestVote = 0;
        for (const [lbl, v] of Object.entries(votes)) {
            if (v > bestVote) {
                bestVote = v;
                bestLabel = lbl;
            }
        }

        const voteRatio = totalVote > 0 ? (bestVote / totalVote) : 1;
        const distScore = Math.max(0, Math.min(1, 1 - minDistance / 0.58));
        let confidence = Math.round((distScore * 0.82 + voteRatio * distScore * 0.18) * 100);
        confidence = clamp(confidence, 0, 99);

        if (bestLabel && faceContext?.hasFace && !isHandOnlyLabel(bestLabel)) {
            const boost = evaluateFaceSignBoost(bestLabel, faceContext);
            confidence = clamp(confidence + boost.bonus, 0, 99);
        }

        return {
            label: bestLabel,
            confidence,
            distance: minDistance,
            usedFace: Boolean(topK[0]?.usedFace),
        };
    }
}

/**
 * SequenceBuffer: Sliding window FIFO buffer to collect normalized frames (63, 78, or 146 numbers).
 */
export class SequenceBuffer {
    constructor(maxFrames = 40) {
        this.maxFrames = maxFrames;
        this.buffer = [];
    }

    push(frame) {
        if (!frame || (frame.length !== 63 && frame.length !== 78 && frame.length !== 146)) return;
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
