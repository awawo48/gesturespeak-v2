/**
 * GestureSpeak shared Multimodal AI Engine (ES module).
 *
 * Combines:
 * 1. MediaPipe HandLandmarker (21 3D hand joints -> 63 wrist-normalised numbers)
 * 2. MediaPipe FaceLandmarker (Hand-to-Face spatial anchor zones + 52 Blendshape
 *    facial expressions -> 15 normalised context numbers, total = 78 features)
 * 3. Smart Modular Feature Fusion:
 *    - Alphabet (A-Z) & Numbers (0-10) use Hand-only features (63) so head/smile
 *      variations never reduce finger-spelling accuracy.
 *    - Contextual BIM signs (Keluarga, Salam, Perasaan, Soalan, Anggota Badan)
 *      fuse Hand (63) + Face Spatial Anchor & Expression (15) features!
 */

export const HAND_FEATURE_DIM = 63;
export const FACE_FEATURE_DIM = 15;
export const FULL_FEATURE_DIM = 78;

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
 * Extracts 15 scale-invariant Hand-to-Face spatial + facial expression features
 * plus human-readable zone and expression metadata for UI & BIM coaching.
 *
 * @param {Array|null} handLm  21 MediaPipe hand landmarks (optional if only inspecting face)
 * @param {Array|null} faceLm  478 MediaPipe face landmarks
 * @param {Array|null} blendshapes  MediaPipe faceBlendshapes[0]?.categories
 */
export function extractFaceContext(handLm, faceLm, blendshapes = null) {
    if (!faceLm || faceLm.length < 400) {
        return {
            hasFace: false,
            vector: new Array(FACE_FEATURE_DIM).fill(0),
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
                if (d < best) { best = d; bestPt = fp; }
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
 * Builds the 78-number multimodal feature vector (63 hand + 15 face) when face
 * is present, or 63-number hand-only vector when face is not present.
 */
export function buildFeatureVector(handLm, faceContext = null) {
    const handVec = normaliseLandmarks(handLm);
    if (faceContext && faceContext.hasFace && Array.isArray(faceContext.vector) && faceContext.vector.length === FACE_FEATURE_DIM) {
        return [...handVec, ...faceContext.vector];
    }
    return handVec;
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
    // Spatial Face-Zone Anchored Signs
    BAPA: { zones: ["dahi"], hintNeed: "Letakkan tangan berhampiran dahi untuk isyarat Bapa 👨‍👧", hintOk: "Tepat! Tangan di zon dahi (Bapa) ⭐" },
    EMAK: { zones: ["pipi", "dagu"], hintNeed: "Sentuh bahagian pipi/dagu untuk isyarat Emak 👩‍👧", hintOk: "Tepat! Tangan di zon pipi/dagu (Emak) ⭐" },
    DATUK: { zones: ["dahi", "dagu"], hintNeed: "Dekatkan tangan ke bahagian dahi/dagu mengikut video Datuk", hintOk: "Zon wajah Datuk tepat! ⭐" },
    NENEK: { zones: ["pipi", "dagu"], hintNeed: "Dekatkan tangan ke bahagian dagu/pipi untuk Nenek", hintOk: "Zon wajah Nenek tepat! ⭐" },
    MATA: { zones: ["mata"], strictZone: true, hintNeed: "Tunjukkan jari ke arah mata anda 👁️", hintOk: "Tepat! Jari menunjuk ke arah Mata! ⭐" },
    PIPI: { zones: ["pipi"], strictZone: true, hintNeed: "Sentuh atau tunjuk kawasan pipi anda 😊", hintOk: "Tepat! Tangan pada kawasan Pipi! ⭐" },
    DADA: { zones: ["dada"], strictZone: true, hintNeed: "Letakkan tangan di bahagian dada", hintOk: "Tepat! Tangan di bahagian Dada! ⭐" },
    SAYA: { zones: ["dada"], hintNeed: "Arahkan tangan ke dada untuk isyarat Saya", hintOk: "Tepat! Tangan di dada (Saya) ⭐" },
    TERIMA_KASIH: { zones: ["mulut", "dagu", "dada"], hintNeed: "Mulakan tangan dari dagu/mulut ke hadapan (Terima Kasih)", hintOk: "Gerakan Terima Kasih dari wajah dikesan! ⭐" },
    GULA: { zones: ["mulut", "pipi", "dagu"], hintNeed: "Sentuh jari berhampiran bibir/pipi untuk Gula", hintOk: "Zon wajah Gula tepat! ⭐" },
    ASSALAMUALAIKUM: { zones: ["dahi", "pipi", "mulut", "bebas"], exprs: ["gembira", "neutral"] },

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

export function evaluateFaceSignBoost(targetLabel, faceContext) {
    if (!faceContext || !faceContext.hasFace || !targetLabel) {
        return { bonus: 0, zoneMatched: null, exprMatched: null, hintOk: null, hintNeed: null, strictBlocked: false };
    }
    const key = String(targetLabel).trim().toUpperCase();
    const rule = BIM_FACE_RULES[key];
    if (!rule) {
        return { bonus: 0, zoneMatched: null, exprMatched: null, hintOk: null, hintNeed: null, strictBlocked: false };
    }

    let bonus = 0;
    let zoneMatched = null;
    let exprMatched = null;
    let strictBlocked = false;

    if (rule.zones) {
        zoneMatched = rule.zones.includes(faceContext.zone);
        if (zoneMatched) {
            bonus += 9;
        } else if (rule.strictZone) {
            strictBlocked = true;
            bonus -= 18;
        }
    }

    if (rule.exprs) {
        exprMatched = rule.exprs.includes(faceContext.expression);
        if (exprMatched) {
            bonus += 8;
        }
    }

    return {
        bonus,
        zoneMatched,
        exprMatched,
        strictBlocked,
        hintOk: (zoneMatched || exprMatched) ? (rule.hintOk || null) : null,
        hintNeed: (!zoneMatched && rule.hintNeed) ? rule.hintNeed : (!exprMatched && rule.hintExpr ? rule.hintExpr : null),
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
 * Multimodal Squared Distance:
 * - Always compares 63 wrist-normalised hand features (indices 0..62).
 * - If useFace === true and BOTH vectors include valid face context (length >= 78 and f[77] > 0.5),
 *   fuses hand distance with face-anchor + expression distance!
 */
function multimodalSqDist(a, b, mirror, useFace) {
    let handD = 0;
    for (let i = 0; i < 63; i++) {
        const av = (mirror && i % 3 === 0) ? -a[i] : a[i];
        const diff = av - b[i];
        handD += diff * diff;
    }

    if (!useFace || a.length < 78 || b.length < 78 || a[77] < 0.5 || b[77] < 0.5) {
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
        this.samples = []; // [{ label, f: Float32Array(63 | 78) }]
    }

    load(samples) {
        this.samples = samples
            .filter(s => Array.isArray(s.f) && (s.f.length === 63 || s.f.length === 78))
            .map(s => ({ label: s.label, f: Float32Array.from(s.f) }));
    }

    get size() { return this.samples.length; }
    get labels() { return [...new Set(this.samples.map(s => s.label))]; }

    /** @returns {{label:string|null, confidence:number, distance:number, usedFace:boolean}} */
    predict(features, motionTracker = null, faceContext = null) {
        if (this.samples.length === 0) return { label: null, confidence: 0, distance: Infinity, usedFace: false };

        const queryHasFace = features.length >= 78 && features[77] > 0.5;
        const best = [];
        for (const s of this.samples) {
            const useFace = queryHasFace && !isHandOnlyLabel(s.label) && s.f.length >= 78 && s.f[77] > 0.5;
            const d = Math.min(
                multimodalSqDist(features, s.f, false, useFace),
                multimodalSqDist(features, s.f, true, useFace)
            );
            if (best.length < this.k || d < best[best.length - 1].d) {
                best.push({ d, label: s.label, usedFace: useFace });
                best.sort((a, b) => a.d - b.d);
                if (best.length > this.k) best.pop();
            }
        }

        const votes = {};
        best.forEach(b => { votes[b.label] = (votes[b.label] || 0) + 1 / (b.d + 1e-3); });

        // Disambiguation for signs sharing identical hand shape (e.g. static 'I' vs dynamic 'J')
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
 * to differentiate static signs (like 'I') from dynamic motion signs (like 'J').
 */
export class HandMotionTracker {
    constructor(bufferSize = 45) {
        this.bufferSize = bufferSize;
        this.history = [];
        this.lastJDetection = 0;
    }

    push(landmarks) {
        if (!landmarks || landmarks.length < 21) return;
        const now = performance.now();
        this.history.push({
            time: now,
            pinky: { x: landmarks[20].x, y: landmarks[20].y },
            wrist: { x: landmarks[0].x, y: landmarks[0].y },
            index: { x: landmarks[8].x, y: landmarks[8].y }
        });

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

    getTrail() {
        return this.history.map(h => h.pinky);
    }
}

/**
 * Resamples a sequence of landmark frames (each 63 or 78 numbers) to an exact target length.
 */
export function resampleSequence(frames, targetLength = 30) {
    if (!frames || frames.length === 0) return [];
    const dim = frames.some(f => f && f.length >= 78) ? 78 : 63;
    const normFrame = f => {
        if (f.length === dim) return f;
        if (dim === 78 && f.length === 63) return [...f, ...new Array(15).fill(0)];
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
        resampled.push(interpolated);
    }
    return resampled;
}

function sequenceFrameDist(a, b, useFace = false) {
    return Math.sqrt(multimodalSqDist(a, b, false, useFace));
}

/**
 * Dynamic Time Warping (DTW) distance between two sequences of frames.
 */
export function dtwDistance(seqA, seqB, useFace = false) {
    const n = seqA.length;
    const m = seqB.length;
    if (n === 0 || m === 0) return Infinity;

    let prev = new Float32Array(m + 1).fill(Infinity);
    let curr = new Float32Array(m + 1).fill(Infinity);
    prev[0] = 0;

    for (let i = 1; i <= n; i++) {
        curr.fill(Infinity);
        for (let j = 1; j <= m; j++) {
            const cost = sequenceFrameDist(seqA[i - 1], seqB[j - 1], useFace);
            const minPrev = Math.min(prev[j], curr[j - 1], prev[j - 1]);
            curr[j] = cost + minPrev;
        }
        prev.set(curr);
    }

    return prev[m] / (n + m);
}

/**
 * SequenceClassifier: Classifies dynamic sign language gestures
 * using temporal sequence matching with Dynamic Time Warping (DTW) + optional Face fusion.
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
            .map(s => ({
                label: s.label,
                frames: resampleSequence(s.frames, this.targetLength),
            }));
    }

    get size() { return this.templates.length; }
    get labels() { return [...new Set(this.templates.map(t => t.label))]; }

    predict(testFrames, faceContext = null) {
        if (this.templates.length === 0 || !testFrames || testFrames.length < 8) {
            return { label: null, confidence: 0, distance: Infinity, usedFace: false };
        }

        const resampled = resampleSequence(testFrames, this.targetLength);
        const queryHasFace = resampled[0]?.length >= 78 && resampled.some(f => f[77] > 0.5);

        let bestLabel = null;
        let minDistance = Infinity;
        let bestUsedFace = false;

        for (const t of this.templates) {
            const tplHasFace = t.frames[0]?.length >= 78 && t.frames.some(f => f[77] > 0.5);
            const useFace = queryHasFace && tplHasFace && !isHandOnlyLabel(t.label);
            const dist = dtwDistance(resampled, t.frames, useFace);
            if (dist < minDistance) {
                minDistance = dist;
                bestLabel = t.label;
                bestUsedFace = useFace;
            }
        }

        let confidence = Math.max(0, Math.min(99, Math.round((1 - minDistance / 0.36) * 100)));
        if (bestLabel && faceContext?.hasFace && !isHandOnlyLabel(bestLabel)) {
            const boost = evaluateFaceSignBoost(bestLabel, faceContext);
            confidence = clamp(confidence + boost.bonus, 0, 99);
        }

        return { label: bestLabel, confidence, distance: minDistance, usedFace: bestUsedFace };
    }
}

/**
 * SequenceBuffer: Sliding window FIFO buffer to collect normalized frames (63 or 78 numbers).
 */
export class SequenceBuffer {
    constructor(maxFrames = 40) {
        this.maxFrames = maxFrames;
        this.buffer = [];
    }

    push(frame) {
        if (!frame || (frame.length !== 63 && frame.length !== 78)) return;
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
