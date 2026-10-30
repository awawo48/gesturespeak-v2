{{--
    Kiko - Watak Kartun Penceria Kanak-kanak (Rakan Ceria BIM)
    Interactive animated SVG mascot with dynamic expressions, particle bursts, & Malay TTS voice!
--}}
@php
    $kikoStudentName = Auth::guard('student')->check() ? Auth::guard('student')->user()->name : 'Kawan';
    $kikoInitialMood = 'happy';

    if (request()->routeIs('student.login')) {
        $kikoInitialMsg = 'Hai kawan! Saya Kiko 🐾 Masukkan Kod Murid & PIN untuk kita mula belajar sambil bermain!';
    } elseif (request()->routeIs('learn.hub')) {
        $kikoInitialMsg = "Hai {$kikoStudentName}! Pilih mana-mana modul warna-warni di bawah. Kiko akan temankan awak belajar! 🌟";
    } elseif (request()->routeIs('learn.category')) {
        $catName = isset($category) ? $category->name : 'ini';
        $kikoInitialMsg = "Wah, Modul {$catName}! Klik mana-mana kad isyarat untuk tonton video & kumpul bintang bersama Kiko! ⭐";
    } elseif (request()->routeIs('learn.sign')) {
        $sWord = isset($sign) ? $sign->word : 'isyarat ini';
        $kikoInitialMsg = "Jom tiru isyarat \"{$sWord}\"! Buka Kamera AI dan tunjukkan gaya tangan awak pada Kiko ya! 📷🤟";
        $kikoInitialMood = 'coach';
    } elseif (request()->routeIs('quiz.index')) {
        $kikoInitialMsg = "Masa untuk Cabaran Kuiz, {$kikoStudentName}! Jom uji ingatan dan kumpul bintang emas! 🏆✨";
        $kikoInitialMood = 'celebrate';
    } elseif (request()->routeIs('quiz.play')) {
        $kikoInitialMsg = 'Perhatikan video atau gambar dengan teliti... Kiko yakin awak tahu jawapannya! 💡🐾';
        $kikoInitialMood = 'coach';
    } else {
        $kikoInitialMsg = "Hai {$kikoStudentName}! Klik Kiko bila-bila masa untuk dapatkan kata semangat! 🌈";
    }
@endphp

<style>
    .kiko-widget {
        position: fixed;
        right: 18px;
        bottom: 16px;
        z-index: 950;
        display: flex;
        align-items: flex-end;
        gap: 0.65rem;
        pointer-events: none;
        font-family: var(--font-main, 'Nunito', sans-serif);
    }

    /* Speech Bubble */
    .kiko-bubble {
        pointer-events: auto;
        position: relative;
        max-width: 285px;
        background: #ffffff;
        border: 3px solid #6c5ce7;
        border-radius: 22px 22px 6px 22px;
        padding: 0.85rem 1rem;
        box-shadow: 0 12px 28px rgba(108, 92, 231, 0.22);
        transform-origin: bottom right;
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.25s ease;
    }

    .kiko-widget.minimized .kiko-bubble {
        opacity: 0;
        transform: scale(0.4) translateY(20px);
        pointer-events: none;
        position: absolute;
        right: 90px;
        bottom: 10px;
    }

    .kiko-bubble.pop {
        animation: kikoBubblePop 0.42s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .kiko-widget[data-mood="celebrate"] .kiko-bubble {
        border-color: #f59e0b;
        background: linear-gradient(180deg, #fffbeb 0%, #ffffff 100%);
        box-shadow: 0 14px 32px rgba(245, 158, 11, 0.28);
    }

    .kiko-widget[data-mood="encourage"] .kiko-bubble {
        border-color: #ec4899;
        background: linear-gradient(180deg, #fdf2f8 0%, #ffffff 100%);
    }

    .kiko-bubble-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.4rem;
        margin-bottom: 0.35rem;
    }

    .kiko-name-tag {
        font-family: var(--font-display, 'Fredoka', sans-serif);
        font-size: 0.78rem;
        font-weight: 700;
        color: #6c5ce7;
        background: #f0edff;
        padding: 0.12rem 0.55rem;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }

    .kiko-bubble-actions {
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .kiko-mini-btn {
        border: none;
        background: #f1f5f9;
        color: #475569;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        font-size: 0.72rem;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.18s ease;
    }

    .kiko-mini-btn:hover {
        background: #6c5ce7;
        color: #fff;
        transform: scale(1.1);
    }

    .kiko-reward-pill {
        display: none;
        margin-bottom: 0.35rem;
        background: linear-gradient(135deg, #fde68a 0%, #f59e0b 100%);
        color: #78350f;
        font-weight: 900;
        font-size: 0.78rem;
        padding: 0.2rem 0.65rem;
        border-radius: 999px;
        box-shadow: 0 3px 10px rgba(245, 158, 11, 0.35);
    }

    .kiko-text {
        font-size: 0.88rem;
        font-weight: 800;
        color: #1e293b;
        line-height: 1.42;
        margin: 0;
    }

    /* Mascot Character Stage */
    .kiko-avatar-wrap {
        pointer-events: auto;
        position: relative;
        width: 108px;
        height: 116px;
        cursor: pointer;
        user-select: none;
        flex-shrink: 0;
        filter: drop-shadow(0 8px 16px rgba(108, 92, 231, 0.25));
        transition: transform 0.2s ease;
    }

    .kiko-avatar-wrap:hover {
        transform: scale(1.06);
    }

    .kiko-avatar-wrap:active {
        transform: scale(0.95);
    }

    .kiko-svg {
        width: 100%;
        height: 100%;
        overflow: visible;
        animation: kikoFloat 3.2s ease-in-out infinite;
    }

    .kiko-widget[data-mood="celebrate"] .kiko-svg {
        animation: kikoJump 0.65s ease-in-out infinite;
    }

    /* Tail, Ears, Eyes & Paws Animations */
    .kiko-tail {
        transform-origin: 78px 92px;
        animation: kikoTailWag 2.2s ease-in-out infinite;
    }

    .kiko-ear-left {
        transform-origin: 34px 34px;
        animation: kikoEarTwitch 4.5s ease-in-out infinite;
    }

    .kiko-ear-right {
        transform-origin: 76px 34px;
        animation: kikoEarTwitchR 4.5s ease-in-out infinite;
    }

    .kiko-eyes-normal {
        transform-origin: 55px 53px;
        animation: kikoBlink 4.2s infinite;
    }

    .kiko-paw-wave {
        transform-origin: 24px 76px;
        animation: kikoWave 1.8s ease-in-out infinite;
    }

    /* Expression State Visibility */
    .kiko-eyes-happy,
    .kiko-mouth-big,
    .kiko-mouth-encourage,
    .kiko-sparkles,
    .kiko-heart-badge,
    .kiko-paws-cheer {
        display: none;
    }

    .kiko-widget[data-mood="celebrate"] .kiko-eyes-normal,
    .kiko-widget[data-mood="celebrate"] .kiko-mouth-normal,
    .kiko-widget[data-mood="celebrate"] .kiko-paws-normal {
        display: none;
    }

    .kiko-widget[data-mood="celebrate"] .kiko-eyes-happy,
    .kiko-widget[data-mood="celebrate"] .kiko-mouth-big,
    .kiko-widget[data-mood="celebrate"] .kiko-sparkles,
    .kiko-widget[data-mood="celebrate"] .kiko-paws-cheer {
        display: block;
    }

    .kiko-widget[data-mood="encourage"] .kiko-mouth-normal {
        display: none;
    }

    .kiko-widget[data-mood="encourage"] .kiko-mouth-encourage,
    .kiko-widget[data-mood="encourage"] .kiko-heart-badge {
        display: block;
    }

    .kiko-widget[data-mood="coach"] .kiko-sparkles {
        display: block;
    }

    /* Minimized badge indicator */
    .kiko-peek-dot {
        display: none;
        position: absolute;
        top: 2px;
        right: 4px;
        background: #ff7675;
        color: #fff;
        font-size: 0.7rem;
        font-weight: 900;
        padding: 0.1rem 0.42rem;
        border-radius: 999px;
        border: 2px solid #fff;
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.18);
        animation: kikoPulseDot 1.8s infinite;
    }

    .kiko-widget.minimized .kiko-peek-dot {
        display: inline-block;
    }

    /* Particle Burst Layer */
    .kiko-particles {
        position: absolute;
        inset: -20px;
        pointer-events: none;
        overflow: visible;
    }

    .kiko-particle {
        position: absolute;
        left: 50%;
        top: 45%;
        font-size: 1.15rem;
        pointer-events: none;
        animation: kikoParticleFly 0.95s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    @keyframes kikoFloat {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-6px) rotate(1.2deg); }
    }

    @keyframes kikoJump {
        0%, 100% { transform: translateY(0) scale(1); }
        45% { transform: translateY(-14px) scale(1.05); }
    }

    @keyframes kikoWave {
        0%, 100% { transform: rotate(0deg); }
        50% { transform: rotate(-22deg); }
    }

    @keyframes kikoTailWag {
        0%, 100% { transform: rotate(0deg); }
        50% { transform: rotate(16deg); }
    }

    @keyframes kikoEarTwitch {
        0%, 86%, 100% { transform: rotate(0deg); }
        90% { transform: rotate(-8deg); }
        95% { transform: rotate(4deg); }
    }

    @keyframes kikoEarTwitchR {
        0%, 86%, 100% { transform: rotate(0deg); }
        92% { transform: rotate(8deg); }
        96% { transform: rotate(-3deg); }
    }

    @keyframes kikoBlink {
        0%, 93%, 100% { transform: scaleY(1); }
        96% { transform: scaleY(0.1); }
    }

    @keyframes kikoBubblePop {
        0% { transform: scale(0.75); }
        70% { transform: scale(1.04); }
        100% { transform: scale(1); }
    }

    @keyframes kikoPulseDot {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.15); }
    }

    @keyframes kikoParticleFly {
        0% { opacity: 1; transform: translate(-50%, -50%) scale(0.5); }
        100% { opacity: 0; transform: translate(var(--tx), var(--ty)) scale(1.25) rotate(var(--rot)); }
    }

    @media (max-width: 640px) {
        .kiko-widget {
            right: 10px;
            bottom: 10px;
            gap: 0.4rem;
        }
        .kiko-bubble {
            max-width: 215px;
            padding: 0.65rem 0.8rem;
        }
        .kiko-text {
            font-size: 0.8rem;
        }
        .kiko-avatar-wrap {
            width: 84px;
            height: 92px;
        }
    }
</style>

<div class="kiko-widget" id="kiko-widget" data-mood="{{ $kikoInitialMood }}">
    {{-- Speech Bubble --}}
    <div class="kiko-bubble" id="kiko-bubble">
        <div class="kiko-bubble-header">
            <span class="kiko-name-tag">🐾 Kiko &bull; Penceria BIM</span>
            <div class="kiko-bubble-actions">
                <button type="button" class="kiko-mini-btn" id="kiko-btn-speak" title="Dengar suara Kiko">
                    <i class="fa-solid fa-volume-high"></i>
                </button>
                <button type="button" class="kiko-mini-btn" id="kiko-btn-cheer" title="Kata semangat baru!">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                </button>
                <button type="button" class="kiko-mini-btn" id="kiko-btn-min" title="Kecilkan mesej">
                    <i class="fa-solid fa-minus"></i>
                </button>
            </div>
        </div>
        <div class="kiko-reward-pill" id="kiko-reward-pill">⭐ +3 Bintang!</div>
        <p class="kiko-text" id="kiko-text">{{ $kikoInitialMsg }}</p>
    </div>

    {{-- Animated SVG Cartoon Character: Kiko the Cheerful BIM Cat --}}
    <div class="kiko-avatar-wrap" id="kiko-avatar" title="Klik Kiko untuk High-Five & Kata Semangat!">
        <span class="kiko-peek-dot">Hai! 💬</span>
        <div class="kiko-particles" id="kiko-particles"></div>

        <svg class="kiko-svg" viewBox="0 0 110 118" xmlns="http://www.w3.org/2000/svg" aria-label="Watak Kartun Kiko">
            {{-- Fluffy Wagging Tail --}}
            <g class="kiko-tail">
                <path d="M76 92 C96 88, 104 68, 94 58 C88 52, 82 62, 84 72 C85 78, 79 84, 74 86 Z" fill="#ffb142" stroke="#cc8e35" stroke-width="2.2" stroke-linejoin="round"/>
                <path d="M94 58 C90 54, 85 58, 85 65 C90 66, 95 63, 94 58 Z" fill="#fff7ed"/>
            </g>

            {{-- Chubby Body & GestureSpeak Cape --}}
            <ellipse cx="55" cy="88" rx="27" ry="23" fill="#ffbe76" stroke="#cc8e35" stroke-width="2.4"/>
            <ellipse cx="55" cy="91" rx="17" ry="16" fill="#fff7ed"/>

            {{-- Cute Purple-Turquoise BIM Scarf / Collar --}}
            <path d="M32 72 Q55 83 78 72 L74 80 Q55 89 36 80 Z" fill="#6c5ce7" stroke="#4834d4" stroke-width="1.8"/>
            <polygon points="55,77 57.5,82 63,82.6 58.8,86 60,91.3 55,88.4 50,91.3 51.2,86 47,82.6 52.5,82" fill="#fdcb6e" stroke="#d97706" stroke-width="1.2"/>

            {{-- Normal Paws (Left Paw Waving!) --}}
            <g class="kiko-paws-normal">
                <g class="kiko-paw-wave">
                    <ellipse cx="22" cy="70" rx="8.5" ry="11" transform="rotate(-25 22 70)" fill="#ffbe76" stroke="#cc8e35" stroke-width="2.2"/>
                    <circle cx="19" cy="66" r="3" fill="#ff9ff3" opacity="0.7"/>
                </g>
                <ellipse cx="83" cy="84" rx="8" ry="10" transform="rotate(15 83 84)" fill="#ffbe76" stroke="#cc8e35" stroke-width="2.2"/>
            </g>

            {{-- Cheering Paws Raised High (Celebrate Mood) --}}
            <g class="kiko-paws-cheer">
                <ellipse cx="20" cy="56" rx="8.5" ry="12" transform="rotate(-38 20 56)" fill="#ffbe76" stroke="#cc8e35" stroke-width="2.2"/>
                <ellipse cx="90" cy="56" rx="8.5" ry="12" transform="rotate(38 90 56)" fill="#ffbe76" stroke="#cc8e35" stroke-width="2.2"/>
            </g>

            {{-- Ears --}}
            <g class="kiko-ear-left">
                <path d="M24 40 L19 13 L43 26 Z" fill="#ffb142" stroke="#cc8e35" stroke-width="2.4" stroke-linejoin="round"/>
                <path d="M26 36 L23 19 L39 28 Z" fill="#ff9ff3"/>
            </g>
            <g class="kiko-ear-right">
                <path d="M86 40 L91 13 L67 26 Z" fill="#ffb142" stroke="#cc8e35" stroke-width="2.4" stroke-linejoin="round"/>
                <path d="M84 36 L87 19 L71 28 Z" fill="#ff9ff3"/>
            </g>

            {{-- Round Cute Head --}}
            <ellipse cx="55" cy="49" rx="33" ry="28" fill="#ffbe76" stroke="#cc8e35" stroke-width="2.5"/>

            {{-- Forehead Tabby Stripes --}}
            <path d="M55 22 L55 30 M47 24 L49 30 M63 24 L61 30" stroke="#e58e26" stroke-width="2.8" stroke-linecap="round"/>

            {{-- Cream Muzzle Patch --}}
            <ellipse cx="55" cy="57" rx="18" ry="12" fill="#fff7ed"/>

            {{-- Rosy Cheeks --}}
            <ellipse cx="31" cy="57" rx="6" ry="3.8" fill="#ff7675" opacity="0.55"/>
            <ellipse cx="79" cy="57" rx="6" ry="3.8" fill="#ff7675" opacity="0.55"/>

            {{-- Whiskers --}}
            <path d="M18 52 L28 54 M17 58 L28 58 M92 52 L82 54 M93 58 L82 58" stroke="#b37428" stroke-width="1.8" stroke-linecap="round"/>

            {{-- Eyes: Normal Glossy Anime Eyes --}}
            <g class="kiko-eyes-normal">
                <circle cx="41" cy="48" r="6.2" fill="#2d3436"/>
                <circle cx="39" cy="45.8" r="2.3" fill="#ffffff"/>
                <circle cx="43" cy="50.5" r="1.1" fill="#ffffff"/>

                <circle cx="69" cy="48" r="6.2" fill="#2d3436"/>
                <circle cx="67" cy="45.8" r="2.3" fill="#ffffff"/>
                <circle cx="71" cy="50.5" r="1.1" fill="#ffffff"/>
            </g>

            {{-- Eyes: Happy Crescent ^ ^ (Celebrate Mood) --}}
            <g class="kiko-eyes-happy">
                <path d="M34 49 Q41 41 48 49" fill="none" stroke="#2d3436" stroke-width="3.2" stroke-linecap="round"/>
                <path d="M62 49 Q69 41 76 49" fill="none" stroke="#2d3436" stroke-width="3.2" stroke-linecap="round"/>
            </g>

            {{-- Cute Pink Triangle Nose --}}
            <polygon points="55,54 51.5,50.5 58.5,50.5" fill="#ff6b81"/>

            {{-- Mouth: Normal Cute Cat Smile --}}
            <path class="kiko-mouth-normal" d="M48 58 Q51.5 62.5 55 58 Q58.5 62.5 62 58" fill="none" stroke="#2d3436" stroke-width="2.3" stroke-linecap="round"/>

            {{-- Mouth: Big Joyful Open Laugh (Celebrate Mood) --}}
            <g class="kiko-mouth-big">
                <path d="M46 57 Q55 70 64 57 Z" fill="#d63031" stroke="#2d3436" stroke-width="2"/>
                <ellipse cx="55" cy="62" rx="5" ry="2.8" fill="#ff7675"/>
            </g>

            {{-- Mouth: Encouraging Warm Smile --}}
            <path class="kiko-mouth-encourage" d="M47 58 Q55 65 63 58" fill="none" stroke="#2d3436" stroke-width="2.6" stroke-linecap="round"/>

            {{-- Floating Sparkles (Celebrate / Coach) --}}
            <g class="kiko-sparkles">
                <polygon points="14,22 16,27 21,29 16,31 14,36 12,31 7,29 12,27" fill="#fdcb6e"/>
                <polygon points="96,18 97.8,22 102,23.8 97.8,25.5 96,30 94.2,25.5 90,23.8 94.2,22" fill="#00cec9"/>
            </g>

            {{-- Floating Support Heart (Encourage Mood) --}}
            <g class="kiko-heart-badge">
                <path d="M93 30 C93 26, 87 26, 87 30 C87 26, 81 26, 81 30 C81 35, 87 39, 87 39 C87 39, 93 35, 93 30 Z" fill="#ff6b81"/>
            </g>
        </svg>
    </div>
</div>

<script>
    (function () {
        const widget = document.getElementById('kiko-widget');
        const bubble = document.getElementById('kiko-bubble');
        const textEl = document.getElementById('kiko-text');
        const rewardPill = document.getElementById('kiko-reward-pill');
        const avatar = document.getElementById('kiko-avatar');
        const particlesBox = document.getElementById('kiko-particles');
        const btnSpeak = document.getElementById('kiko-btn-speak');
        const btnCheer = document.getElementById('kiko-btn-cheer');
        const btnMin = document.getElementById('kiko-btn-min');

        if (!widget || !textEl) return;

        const studentName = @json($kikoStudentName);
        const cheerLines = [
            { text: `High-five, ${studentName}! Awak memang rajin belajar Bahasa Isyarat hari ini! 🙌🌟`, mood: 'celebrate' },
            { text: 'Tip Kiko: Senyuman dan mimik muka buat isyarat BIM awak jauh lebih jelas! 😊✨', mood: 'coach' },
            { text: 'Jangan risau kalau tersilap! Praktis sikit-sikit, lama-lama jadi juara BIM! 💪💖', mood: 'encourage' },
            { text: 'Setiap isyarat baru yang awak kuasai beri 3 bintang emas! Jom kumpul banyak-banyak! ⭐', mood: 'happy' },
            { text: `Kiko bangga sangat dengan usaha ${studentName}! Teruskan semangat hebat awak! 🎉🐾`, mood: 'celebrate' },
            { text: 'Tahukah awak? Tangan yang terang di tengah kamera memudahkan AI membaca jari awak! 📷✋', mood: 'coach' },
            { text: 'Yay! Belajar Bahasa Isyarat membolehkan kita berkawan dengan semua orang! 🌈🤟', mood: 'happy' },
        ];
        let cheerIdx = 0;
        let moodResetTimer = null;

        try {
            if (sessionStorage.getItem('gs_kiko_min') === '1') {
                widget.classList.add('minimized');
            }
        } catch {}

        function setMinimized(min) {
            widget.classList.toggle('minimized', min);
            try { sessionStorage.setItem('gs_kiko_min', min ? '1' : '0'); } catch {}
        }

        function spawnParticles(emojis = ['⭐', '🌟', '✨', '🎉', '💖'], count = 12) {
            if (!particlesBox) return;
            for (let i = 0; i < count; i++) {
                const span = document.createElement('span');
                span.className = 'kiko-particle';
                span.textContent = emojis[i % emojis.length];
                const angle = (Math.PI * 2 * i) / count + (Math.random() * 0.4 - 0.2);
                const dist = 48 + Math.random() * 44;
                span.style.setProperty('--tx', `${Math.round(Math.cos(angle) * dist)}px`);
                span.style.setProperty('--ty', `${Math.round(Math.sin(angle) * dist - 18)}px`);
                span.style.setProperty('--rot', `${Math.round(Math.random() * 90 - 45)}deg`);
                particlesBox.appendChild(span);
                setTimeout(() => span.remove(), 1000);
            }
        }

        function speakKiko(text) {
            if (!('speechSynthesis' in window) || !text) return;
            const clean = String(text).replace(/[\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}]/gu, '').trim();
            if (!clean) return;
            window.speechSynthesis.cancel();
            const utter = new SpeechSynthesisUtterance(clean);
            utter.rate = 0.96;
            utter.pitch = 1.25;
            const voices = window.speechSynthesis.getVoices();
            const myVoice = voices.find(v => v.lang.startsWith('ms') || v.lang.startsWith('id') || v.lang.includes('Malay'));
            if (myVoice) utter.voice = myVoice;
            window.speechSynthesis.speak(utter);
        }

        function say(message, mood = 'happy', opts = {}) {
            if (moodResetTimer) clearTimeout(moodResetTimer);
            if (opts.openBubble !== false) {
                setMinimized(false);
            }
            widget.dataset.mood = mood;
            textEl.textContent = message;

            if (rewardPill) {
                if (opts.rewardText) {
                    rewardPill.textContent = opts.rewardText;
                    rewardPill.style.display = 'inline-block';
                } else {
                    rewardPill.style.display = 'none';
                }
            }

            bubble.classList.remove('pop');
            void bubble.offsetWidth;
            bubble.classList.add('pop');

            if (opts.autoSpeak) {
                speakKiko(message);
            }

            if (opts.duration && opts.duration > 0) {
                moodResetTimer = setTimeout(() => {
                    widget.dataset.mood = 'happy';
                    if (rewardPill) rewardPill.style.display = 'none';
                }, opts.duration);
            }
        }

        function celebrate(message, opts = {}) {
            const rewardText = opts.stars > 0 ? `🌟 +${opts.stars} Bintang Diperoleh!` : (opts.rewardText || null);
            say(message, 'celebrate', {
                openBubble: true,
                rewardText,
                autoSpeak: Boolean(opts.autoSpeak),
                duration: opts.duration || 5500,
            });
            spawnParticles(['🌟', '⭐', '🎉', '✨', '🥳', '💖'], 16);
        }

        function encourage(message, opts = {}) {
            say(message, 'encourage', {
                openBubble: true,
                autoSpeak: Boolean(opts.autoSpeak),
                duration: opts.duration || 4500,
            });
            spawnParticles(['💪', '💖', '✨', '🌈'], 7);
        }

        function randomCheer(autoSpeak = false) {
            const item = cheerLines[cheerIdx % cheerLines.length];
            cheerIdx++;
            say(item.text, item.mood, { openBubble: true, autoSpeak, duration: 5000 });
            spawnParticles(['⭐', '✨', '🐾', '💖'], 9);
        }

        avatar.addEventListener('click', () => {
            if (widget.classList.contains('minimized')) {
                setMinimized(false);
                spawnParticles(['🐾', '✨', '⭐'], 6);
            } else {
                randomCheer(false);
            }
        });

        btnCheer?.addEventListener('click', e => {
            e.stopPropagation();
            randomCheer(false);
        });

        btnSpeak?.addEventListener('click', e => {
            e.stopPropagation();
            speakKiko(textEl.textContent);
            spawnParticles(['🔊', '🎵', '✨'], 5);
        });

        btnMin?.addEventListener('click', e => {
            e.stopPropagation();
            setMinimized(true);
        });

        window.Kiko = { say, celebrate, encourage, randomCheer, speak: speakKiko };
    })();
</script>

