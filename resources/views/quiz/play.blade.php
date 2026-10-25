@extends('layouts.app', ['title' => 'Main Kuiz: '.$category->name])

@push('styles')
<style>
    .quiz-card {
        max-width: 650px;
        margin: 1rem auto;
        background: #fff;
        border-radius: var(--radius-lg);
        padding: 2rem;
        box-shadow: var(--shadow-lg);
        border: 2px solid var(--border-light);
        text-align: center;
    }
    .options-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-top: 1.5rem;
    }
    @media (max-width: 600px) {
        .options-grid {
            grid-template-columns: 1fr;
        }
    }
    .option-btn {
        padding: 1.2rem;
        font-size: 1.3rem;
        font-weight: 800;
        font-family: var(--font-display);
        background: #f8fafc;
        border: 3px solid var(--border-light);
        border-radius: var(--radius-md);
        cursor: pointer;
        transition: all 0.2s ease;
        color: var(--text-dark);
    }
    .option-btn:hover:not(:disabled) {
        transform: translateY(-3px);
        border-color: var(--primary);
        background: #f0edff;
    }
    .option-correct {
        background: #d1fae5 !important;
        border-color: #00b894 !important;
        color: #065f46 !important;
        animation: pulse 0.5s;
    }
    .option-wrong {
        background: #fee2e2 !important;
        border-color: #ff7675 !important;
        color: #991b1b !important;
    }
</style>
@endpush

@section('content')
<div style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
    <a href="{{ route('quiz.index') }}" class="btn btn-outline btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Berhenti Kuiz
    </a>

    <div style="display: flex; gap: 1rem; align-items: center;">
        <span id="question-counter" style="font-weight: 800; color: var(--text-muted); font-size: 1rem;">
            Soalan 1 / {{ count($questions) }}
        </span>
        <span class="star-pill" id="score-counter">
            ⭐ 0 Mata
        </span>
    </div>
</div>

<div class="quiz-card" id="quiz-area">
    {{-- QUESTIONS CONTAINER --}}
    <div id="question-card">
        <h2 style="font-size: 1.4rem; color: var(--text-muted); margin-bottom: 1rem;">
            Apakah isyarat yang ditunjukkan di bawah?
        </h2>

        {{-- MEDIA DISPLAY --}}
        <div id="media-container" style="max-height: 260px; min-height: 200px; display: flex; align-items: center; justify-content: center; background: #f8fafc; border-radius: var(--radius-md); margin-bottom: 1.5rem; overflow: hidden;">
            {{-- Injected dynamically by JS --}}
        </div>

        <div class="options-grid" id="options-container">
            {{-- Option buttons injected dynamically by JS --}}
        </div>
    </div>

    {{-- RESULTS MODAL (HIDDEN AT START) --}}
    <div id="results-card" style="display: none; padding: 1.5rem 0;">
        <div style="font-size: 4rem; margin-bottom: 0.5rem;" id="results-emoji">🎉</div>
        <h2 style="font-size: 2rem; margin-bottom: 0.3rem;" id="results-title">Tahniah!</h2>
        <div id="results-stars" style="font-size: 2.5rem; margin-bottom: 1rem;">⭐⭐⭐</div>
        <p style="font-size: 1.1rem; color: var(--text-muted); margin-bottom: 2rem;" id="results-score-text">
            Anda mendapat 5 daripada 5 soalan betul!
        </p>

        <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
            <a href="{{ route('quiz.play', $category) }}" class="btn btn-primary btn-lg">
                <i class="fa-solid fa-rotate-right"></i> Main Lagi
            </a>
            <a href="{{ route('quiz.index') }}" class="btn btn-outline btn-lg">
                <i class="fa-solid fa-trophy"></i> Senarai Kuiz
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
    const mediaContainer = document.getElementById("media-container");
    const optionsContainer = document.getElementById("options-container");
    const questionCard = document.getElementById("question-card");
    const resultsCard = document.getElementById("results-card");

    function renderQuestion() {
        if (currentIndex >= totalQuestions) {
            finishQuiz();
            return;
        }

        const q = questions[currentIndex];
        questionCounter.innerText = "Soalan " + (currentIndex + 1) + " / " + totalQuestions;

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

        // Render 4 Options
        optionsContainer.innerHTML = "";
        q.options.forEach(opt => {
            const btn = document.createElement("button");
            btn.type = "button";
            btn.className = "option-btn";
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
            score++;
            scoreCounter.innerText = "⭐ " + score + " Mata";
            speak("Betul!");
        } else {
            btn.classList.add("option-wrong");
            speak("Jawapan yang betul ialah " + correct);
            // Highlight correct option
            allBtns.forEach(b => {
                if (b.innerText.toUpperCase() === correct.toUpperCase()) {
                    b.classList.add("option-correct");
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

        // Submit score to database via AJAX
        try {
            const resp = await fetch("{{ route('quiz.submit', $category) }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    "Accept": "application/json"
                },
                body: JSON.stringify({ score: score, total: totalQuestions })
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
        if (!('speechSynthesis' in window)) return;
        window.speechSynthesis.cancel();
        const utter = new SpeechSynthesisUtterance(text);
        utter.rate = 0.95;
        window.speechSynthesis.speak(utter);
    }

    // Start Question 1
    renderQuestion();
</script>
@endpush
@endsection
