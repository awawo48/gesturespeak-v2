@extends('layouts.app', ['title' => 'Main '.$title])

@section('content')
<div class="top-action-bar" style="margin-bottom: var(--space-4);">
    <a href="{{ route('quiz.index') }}" class="btn btn-outline">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Berhenti Kuiz
    </a>

    <div style="display: flex; gap: var(--space-4); align-items: center; flex-wrap: wrap;">
        <span id="question-counter" class="pixel-code" style="color: var(--text-dark);">
            Soalan 1 / {{ count($questions) }}
        </span>
        <span class="star-pill" id="score-counter">
            ⭐ 0 Mata
        </span>
    </div>
</div>

<div class="quiz-card" id="quiz-area">
    {{-- VISUAL PROGRESS BAR (Immediate Feedback) --}}
    <div style="margin-bottom: var(--space-5);" aria-label="Kemajuan kuiz">
        <div class="progress-track" style="height: 10px;">
            <div id="quiz-progress-bar" class="progress-fill" style="width: {{ count($questions) > 0 ? round(100 / count($questions)) : 100 }}%;"></div>
        </div>
    </div>

    {{-- QUESTIONS CONTAINER --}}
    <div id="question-card">
        <h2 id="question-title" style="font-size: 1.4rem; color: var(--text-muted); margin-bottom: var(--space-4);">
            Apakah isyarat yang ditunjukkan di bawah?
        </h2>

        {{-- MEDIA DISPLAY --}}
        <div class="pixel-stage" style="margin-bottom: var(--space-5);">
            <span class="pixel-corner pixel-corner--tl" aria-hidden="true"></span>
            <span class="pixel-corner pixel-corner--tr" aria-hidden="true"></span>
            <span class="pixel-corner pixel-corner--bl" aria-hidden="true"></span>
            <span class="pixel-corner pixel-corner--br" aria-hidden="true"></span>
            <div id="media-container" style="max-height: 260px; min-height: 200px; display: flex; align-items: center; justify-content: center; background: #f8fafc; border-radius: var(--radius-md); overflow: hidden; border: 2px solid var(--border-light);">
                {{-- Injected dynamically by JS --}}
            </div>
        </div>

        <div class="options-grid" id="options-container">
            {{-- Option buttons injected dynamically by JS --}}
        </div>

        {{-- SPELLING AREA --}}
        <div id="spell-area" style="display: none;">
            <div id="spell-slots" class="spell-slots"></div>
            <div id="spell-tiles" class="spell-tiles"></div>
            <div style="display: flex; gap: var(--space-3); justify-content: center; margin-top: var(--space-5); flex-wrap: wrap;">
                <button type="button" class="btn btn-outline" id="spell-speak">
                    <i class="fa-solid fa-volume-high" aria-hidden="true"></i> Dengar Sebutan
                </button>
                <button type="button" class="btn btn-outline" id="spell-undo">
                    <i class="fa-solid fa-delete-left" aria-hidden="true"></i> Padam
                </button>
                <button type="button" class="btn btn-primary" id="spell-check">
                    <i class="fa-solid fa-check" aria-hidden="true"></i> Semak Ejaan
                </button>
            </div>
        </div>
    </div>

    {{-- RESULTS CARD (HIDDEN AT START) --}}
    <div id="results-card" style="display: none; padding: var(--space-4) 0;">
        <div style="font-size: 4rem; margin-bottom: var(--space-2);" id="results-emoji" aria-hidden="true">🎉</div>
        <h2 style="font-size: 2rem; margin-bottom: var(--space-2);" id="results-title">Tahniah!</h2>
        <div id="results-stars" style="font-size: 2.5rem; margin-bottom: var(--space-3);">⭐⭐⭐</div>
        <p style="font-size: 1.1rem; color: var(--text-muted); margin-bottom: var(--space-6);" id="results-score-text">
            Anda mendapat 5 daripada 5 soalan betul!
        </p>

        <div style="display: flex; justify-content: center; gap: var(--space-4); flex-wrap: wrap;">
            <a href="{{ $replayUrl }}" class="btn btn-primary btn-kid">
                <i class="fa-solid fa-rotate-right" aria-hidden="true"></i> Main Lagi
            </a>
            <a href="{{ route('quiz.index') }}" class="btn btn-outline btn-kid">
                <i class="fa-solid fa-trophy" aria-hidden="true"></i> Senarai Kuiz
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const questions = @json($questions);
    let currentIndex = 0;
    let score = 0;
    const totalQuestions = questions.length;

    const questionCounter = document.getElementById("question-counter");
    const scoreCounter = document.getElementById("score-counter");
    const quizProgressBar = document.getElementById("quiz-progress-bar");
    const mediaContainer = document.getElementById("media-container");
    const optionsContainer = document.getElementById("options-container");
    const questionCard = document.getElementById("question-card");
    const resultsCard = document.getElementById("results-card");

    const spellArea = document.getElementById("spell-area");
    const spellSlots = document.getElementById("spell-slots");
    const spellTiles = document.getElementById("spell-tiles");
    let spellPicked = [];
    let spellLocked = false;

    function renderSpelling(q) {
        optionsContainer.style.display = "none";
        spellArea.style.display = "block";
        spellPicked = [];
        spellLocked = false;
        const hasMedia = q.video || q.image;
        document.getElementById("question-title").innerText = hasMedia
            ? "Eja perkataan bagi isyarat ini!" + (q.meaning_en ? " (" + q.meaning_en + ")" : "")
            : "Eja perkataan ini: " + q.display;
        speak(hasMedia ? "Eja perkataan ini" : q.display);

        spellTiles.innerHTML = "";
        q.tiles.forEach((ch, i) => {
            const b = document.createElement("button");
            b.type = "button";
            b.className = "spell-tile";
            b.innerText = ch;
            b.dataset.idx = i;
            b.onclick = () => {
                if (spellLocked || spellPicked.length >= q.word.length) return;
                spellPicked.push({ ch, idx: i });
                b.disabled = true;
                drawSlots(q);
            };
            spellTiles.appendChild(b);
        });

        document.getElementById("spell-speak").onclick = () => speak(q.display || q.word);
        document.getElementById("spell-undo").onclick = () => {
            if (spellLocked || !spellPicked.length) return;
            const last = spellPicked.pop();
            spellTiles.querySelector('[data-idx="' + last.idx + '"]').disabled = false;
            drawSlots(q);
        };
        document.getElementById("spell-check").onclick = () => checkSpelling(q);
        drawSlots(q);
    }

    function drawSlots(q, state) {
        spellSlots.innerHTML = "";
        for (let i = 0; i < q.word.length; i++) {
            const s = document.createElement("div");
            s.className = "spell-slot" + (state ? " " + state : "");
            s.innerText = spellPicked[i] ? spellPicked[i].ch : "";
            spellSlots.appendChild(s);
        }
    }

    function checkSpelling(q) {
        if (spellLocked) return;
        if (spellPicked.length < q.word.length) {
            window.Kiko?.encourage("Lengkapkan semua huruf dahulu ya! ✍️", { duration: 1200 });
            return;
        }
        spellLocked = true;
        spellTiles.querySelectorAll(".spell-tile").forEach(b => b.disabled = true);
        const answer = spellPicked.map(p => p.ch).join("");
        if (answer === q.word) {
            drawSlots(q, "ok");
            score++;
            scoreCounter.innerText = "⭐ " + score + " Mata";
            speak("Betul! " + q.word.split("").join(" "));
            window.Kiko?.celebrate("Hebat! Ejaan \"" + q.word + "\" betul! 🎉⭐", { duration: 1500 });
        } else {
            drawSlots(q, "bad");
            speak("Ejaan yang betul ialah " + q.word.split("").join(" "));
            window.Kiko?.encourage("Hampir! Ejaan sebenar ialah \"" + q.word + "\". 💪", { duration: 1500 });
        }
        setTimeout(() => { currentIndex++; renderQuestion(); }, 1800);
    }

    function renderQuestion() {
        if (currentIndex >= totalQuestions) {
            finishQuiz();
            return;
        }

        const q = questions[currentIndex];
        questionCounter.innerText = "Soalan " + (currentIndex + 1) + " / " + totalQuestions;
        if (quizProgressBar && totalQuestions > 0) {
            quizProgressBar.style.width = Math.round(((currentIndex + 1) / totalQuestions) * 100) + "%";
        }
        if (currentIndex > 0) {
            window.Kiko?.say(
                "Soalan ke-" + (currentIndex + 1) + "! Perhatikan isyarat ini baik-baik ya! 👀🐾",
                "coach",
                { openBubble: false }
            );
        }

        // Render Media (Video demo or fallback image or big letter)
        mediaContainer.innerHTML = "";
        if (q.video) {
            const vid = document.createElement("video");
            vid.src = "{{ asset('') }}" + q.video;
            vid.autoplay = true;
            vid.loop = true;
            vid.muted = true;
            vid.playsInline = true;
            vid.style.maxHeight = "250px";
            vid.style.width = "auto";
            mediaContainer.appendChild(vid);
        } else if (q.image) {
            const img = document.createElement("img");
            img.src = "{{ asset('') }}" + q.image;
            img.alt = "Gambar isyarat";
            img.style.maxHeight = "200px";
            img.style.objectFit = "contain";
            mediaContainer.appendChild(img);
        } else {
            const txt = document.createElement("div");
            txt.innerText = q.word;
            txt.style.fontSize = "4.5rem";
            txt.style.fontFamily = "var(--font-display)";
            txt.style.color = "var(--primary)";
            mediaContainer.appendChild(txt);
        }

        if (q.type === "spell") {
            renderSpelling(q);
            return;
        }
        spellArea.style.display = "none";
        optionsContainer.style.display = "";
        document.getElementById("question-title").innerText = "Apakah isyarat yang ditunjukkan di bawah?";

        // Render 4 Options
        optionsContainer.innerHTML = "";
        q.options.forEach(opt => {
            const btn = document.createElement("button");
            btn.type = "button";
            btn.className = "option-btn";
            btn.dataset.value = opt;
            btn.innerText = opt;
            btn.onclick = () => selectAnswer(btn, opt, q.word);
            optionsContainer.appendChild(btn);
        });
    }

    function selectAnswer(btn, selected, correct) {
        // Disable all buttons
        const allBtns = optionsContainer.querySelectorAll(".option-btn");
        allBtns.forEach(b => b.disabled = true);

        if (selected.toUpperCase() === correct.toUpperCase()) {
            btn.classList.add("option-correct");
            btn.innerText = "✔ " + (btn.dataset.value || btn.innerText);
            score++;
            scoreCounter.innerText = "⭐ " + score + " Mata";
            speak("Betul!");
            window.Kiko?.celebrate(
                "Yeay!! Tepat sekali! Jawapan \"" + correct + "\" memang betul! 🎉⭐",
                { duration: 1500 }
            );
        } else {
            btn.classList.add("option-wrong");
            btn.innerText = "✖ " + (btn.dataset.value || btn.innerText);
            speak("Jawapan yang betul ialah " + correct);
            window.Kiko?.encourage(
                "Hampir tepat! Jawapan sebenar ialah \"" + correct + "\". Cuba soalan seterusnya ya, awak pasti boleh! 💪💖",
                { duration: 1500 }
            );
            // Highlight correct option with non-color ✔ indicator
            allBtns.forEach(b => {
                const raw = b.dataset.value || b.innerText;
                if (raw.toUpperCase() === correct.toUpperCase()) {
                    b.classList.add("option-correct");
                    b.innerText = "✔ " + raw;
                }
            });
        }

        setTimeout(() => {
            currentIndex++;
            renderQuestion();
        }, 1500);
    }

    async function finishQuiz() {
        questionCard.style.display = "none";
        resultsCard.style.display = "block";
        if (quizProgressBar) quizProgressBar.style.width = "100%";

        const ratio = score / totalQuestions;
        let starCount = 1;
        let starStr = "⭐";
        if (ratio >= 0.8) {
            starCount = 3;
            starStr = "⭐⭐⭐";
            document.getElementById("results-title").innerText = "Luar Biasa! 🌟";
        } else if (ratio >= 0.5) {
            starCount = 2;
            starStr = "⭐⭐";
            document.getElementById("results-title").innerText = "Hebat Sekali! 👏";
        } else {
            document.getElementById("results-title").innerText = "Bagus! Teruskan Berlatih! 💪";
        }

        document.getElementById("results-stars").innerText = starStr;
        document.getElementById("results-score-text").innerText = "Anda menjawab " + score + " daripada " + totalQuestions + " soalan dengan tepat!";

        speak("Tahniah! Anda dapat " + starCount + " bintang!");
        window.Kiko?.celebrate(
            "Tahniah tamatkan kuiz! Awak dapat " + score + "/" + totalQuestions + " betul! 🥳",
            { stars: starCount, duration: 7000 }
        );

        // Submit score to database via AJAX
        try {
            const resp = await fetch("{{ $submitUrl }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    "Accept": "application/json"
                },
                body: JSON.stringify({ score: score, total: totalQuestions, mode: @json($mode) })
            });
            const data = await resp.json();
            if (data.success && data.total_stars) {
                const navStar = document.getElementById("nav-star-count");
                if (navStar) navStar.innerText = data.total_stars;
            }
        } catch (e) {
            console.error("Gagal menyimpan skor kuiz:", e);
        }
    }

    function speak(text) {
        if (window.Kiko && typeof window.Kiko.speak === 'function') {
            window.Kiko.speak(text);
            return;
        }
        if (!('speechSynthesis' in window)) return;
        window.speechSynthesis.cancel();
        const utter = new SpeechSynthesisUtterance(text);
        utter.lang = 'ms-MY';
        utter.rate = 0.95;
        window.speechSynthesis.speak(utter);
    }

    // Start Question 1
    renderQuestion();
</script>
@endpush
@endsection
