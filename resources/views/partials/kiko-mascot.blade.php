{{--
    Kiko v2 - Robot Kucing Holo-Pod Sign Cilik (rakan belajar BIM)
    Left-facing SVG robot-cat holding a pocket Holo-Pod that projects a glowing
    BIM hand-sign hologram toward the content area (ideal for bottom-right placement).
    Reactions: happy, celebrate, encourage, coach, speaking (TTS mouth + holo pulse), poke (surprised).
    Public JS API (used by learn/* and quiz/*): window.Kiko = { say, celebrate, encourage, randomCheer, speak }
--}}
@php
    $kikoStudentName = Auth::guard('student')->check() ? Auth::guard('student')->user()->name : 'Kawan';
    $kikoInitialMood = 'happy';

    if (request()->routeIs('learn.hub')) {
        $kikoInitialMsg = "Hai {$kikoStudentName}! Saya Kiko, robot kucing Sign Cilik. Pilih mana-mana modul di bawah, Kiko temankan awak belajar!";
    } elseif (request()->routeIs('learn.category')) {
        $catName = isset($category) ? $category->name : 'ini';
        $kikoInitialMsg = "Wah, Modul {$catName}! Klik mana-mana kad isyarat untuk tonton video dan kumpul bintang bersama Kiko.";
    } elseif (request()->routeIs('learn.sign')) {
        $sWord = isset($sign) ? $sign->word : 'isyarat ini';
        $kikoInitialMsg = "Jom tiru isyarat \"{$sWord}\"! Buka Kamera AI dan tunjukkan gaya tangan awak pada Kiko ya.";
        $kikoInitialMood = 'coach';
    } elseif (request()->routeIs('quiz.index')) {
        $kikoInitialMsg = "Masa untuk Cabaran Kuiz, {$kikoStudentName}! Jom uji ingatan dan kumpul bintang emas!";
        $kikoInitialMood = 'celebrate';
    } elseif (request()->routeIs('quiz.play')) {
        $kikoInitialMsg = 'Perhatikan video atau gambar dengan teliti... Kiko yakin awak tahu jawapannya!';
        $kikoInitialMood = 'coach';
    } else {
        $kikoInitialMsg = "Hai {$kikoStudentName}! Tekan Kiko bila-bila masa untuk dapatkan kata semangat.";
    }
@endphp

<style>
    .kiko-widget {
        position: fixed;
        right: 16px;
        bottom: 14px;
        z-index: 950;
        display: flex;
        align-items: flex-end;
        gap: 0.55rem;
        pointer-events: none;
        font-family: var(--font-main, 'Nunito', sans-serif);
    }

    /* ---------- Speech bubble ---------- */
    .kiko-bubble {
        pointer-events: auto;
        position: relative;
        max-width: 280px;
        background: #ffffff;
        border: 3px solid #16324f;
        border-radius: 22px 22px 6px 22px;
        padding: 0.85rem 1rem;
        box-shadow: 0 6px 0 #16324f1f, 0 14px 28px rgba(22, 50, 79, 0.16);
        transform-origin: bottom right;
        transition: transform 180ms var(--ease-out, cubic-bezier(0.22, 1, 0.36, 1)),
                    opacity 180ms var(--ease-out, cubic-bezier(0.22, 1, 0.36, 1)),
                    border-color 180ms var(--ease-out, cubic-bezier(0.22, 1, 0.36, 1)),
                    background-color 180ms var(--ease-out, cubic-bezier(0.22, 1, 0.36, 1));
    }

    .kiko-widget.minimized .kiko-bubble {
        opacity: 0;
        transform: scale(0.96) translateY(8px);
        pointer-events: none;
        position: absolute;
        right: 148px;
        bottom: 10px;
    }

    @media (prefers-reduced-motion: reduce) {
        .kiko-widget.minimized .kiko-bubble { transform: none; }
    }

    .kiko-bubble.pop {
        animation: kikoBubblePop 180ms var(--ease-out, cubic-bezier(0.22, 1, 0.36, 1));
    }

    @media (prefers-reduced-motion: reduce) {
        .kiko-bubble.pop { animation-name: kikoFade; }
    }

    .kiko-widget[data-mood="celebrate"] .kiko-bubble {
        border-color: #e6c800;
        background-color: #fffbe0;
    }

    .kiko-widget[data-mood="encourage"] .kiko-bubble {
        border-color: #059669;
        background-color: #f0fdf6;
    }

    .kiko-widget[data-mood="coach"] .kiko-bubble {
        border-color: #26b0e5;
        background-color: #f2fbfe;
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
        font-size: 0.8rem;
        font-weight: 700;
        color: #16324f;
        background: #e3f6fd;
        padding: 0.14rem 0.6rem;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }

    .kiko-name-tag i { color: #26b0e5; }

    .kiko-bubble-actions {
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .kiko-mini-btn {
        border: none;
        background: #eef4f8;
        color: #16324f;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        font-size: 0.75rem;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: background-color 160ms var(--ease-out, cubic-bezier(0.22, 1, 0.36, 1)),
                    color 160ms var(--ease-out, cubic-bezier(0.22, 1, 0.36, 1)),
                    transform 160ms var(--ease-out, cubic-bezier(0.22, 1, 0.36, 1));
    }

    @media (hover: hover) and (pointer: fine) {
        .kiko-mini-btn:hover { background: #26b0e5; color: #ffffff; }
    }

    .kiko-mini-btn:active { transform: scale(0.94); }

    .kiko-mini-btn:focus-visible,
    .kiko-avatar-wrap:focus-visible {
        outline: 3px solid #26b0e5;
        outline-offset: 3px;
    }

    .kiko-mini-btn.is-active { background: #26b0e5; color: #ffffff; }

    @media (pointer: coarse) {
        .kiko-mini-btn { width: 44px; height: 44px; font-size: 0.9rem; }
    }

    .kiko-reward-pill {
        display: none;
        margin-bottom: 0.35rem;
        background: #ffe942;
        color: #16324f;
        font-weight: 900;
        font-size: 0.78rem;
        padding: 0.2rem 0.65rem;
        border-radius: 999px;
        border: 2px solid #16324f;
    }

    .kiko-reward-pill i { margin-right: 0.25rem; }

    .kiko-text {
        font-size: 0.9rem;
        font-weight: 800;
        color: #16324f;
        line-height: 1.42;
        margin: 0;
    }

    /* ---------- Kiko v2 Holo-Pod Avatar (left-facing) ---------- */
    .kiko-avatar-wrap {
        pointer-events: auto;
        position: relative;
        width: 148px;
        height: 140px;
        padding: 0;
        border: 0;
        background: none;
        border-radius: 28px;
        cursor: pointer;
        user-select: none;
        -webkit-tap-highlight-color: transparent;
        flex-shrink: 0;
        filter: drop-shadow(0 8px 14px rgba(22, 50, 79, 0.18));
        transition: transform 160ms var(--ease-out, cubic-bezier(0.22, 1, 0.36, 1));
    }

    @media (hover: hover) and (pointer: fine) {
        .kiko-avatar-wrap:hover { transform: scale(1.04); }
    }

    .kiko-avatar-wrap:active { transform: scale(0.96); }

    .kiko-svg {
        width: 100%;
        height: 100%;
        overflow: visible;
        display: block;
    }

    .kiko-svg .kiko-blink,
    .kiko-svg .kiko-mouth-talk,
    .kiko-svg .kiko-holo-card,
    .kiko-svg .kiko-holo-beam,
    .kiko-svg .kiko-pod-lens,
    .kiko-svg .kiko-antenna-star,
    .kiko-svg .kiko-holo-sparkle,
    .kiko-svg .kiko-holo-heart {
        transform-box: fill-box;
        transform-origin: center;
    }

    .kiko-svg .kiko-tail { transform-box: view-box; transform-origin: 216px 208px; }
    .kiko-svg .kiko-ear-left { transform-box: view-box; transform-origin: 156px 56px; }
    .kiko-svg .kiko-ear-right { transform-box: view-box; transform-origin: 212px 56px; }
    .kiko-svg .kiko-look { transition: transform 160ms var(--ease-out, cubic-bezier(0.22, 1, 0.36, 1)); }

    /* ---------- Expression & Hologram State Visibility ---------- */
    .kiko-eyes-happy,
    .kiko-eyes-soft,
    .kiko-eyes-focus,
    .kiko-eyes-wow,
    .kiko-mouth-big,
    .kiko-mouth-soft,
    .kiko-mouth-focus,
    .kiko-mouth-wow,
    .kiko-mouth-talk,
    .kiko-holo-celebrate,
    .kiko-holo-encourage,
    .kiko-holo-coach,
    .kiko-holo-poke,
    .kiko-holo-speaking,
    .kiko-sound-waves {
        display: none;
    }

    /* celebrate: ^ ^ eyes, big laugh, thumbs-up + star hologram */
    .kiko-widget[data-mood="celebrate"] .kiko-eyes-open,
    .kiko-widget[data-mood="celebrate"] .kiko-mouth-normal,
    .kiko-widget[data-mood="celebrate"] .kiko-holo-happy { display: none; }
    .kiko-widget[data-mood="celebrate"] .kiko-eyes-happy,
    .kiko-widget[data-mood="celebrate"] .kiko-mouth-big,
    .kiko-widget[data-mood="celebrate"] .kiko-holo-celebrate { display: block; }

    /* encourage: soft eyes, caring brows, gentle smile, heart hologram */
    .kiko-widget[data-mood="encourage"] .kiko-eyes-open,
    .kiko-widget[data-mood="encourage"] .kiko-mouth-normal,
    .kiko-widget[data-mood="encourage"] .kiko-holo-happy { display: none; }
    .kiko-widget[data-mood="encourage"] .kiko-eyes-soft,
    .kiko-widget[data-mood="encourage"] .kiko-mouth-soft,
    .kiko-widget[data-mood="encourage"] .kiko-holo-encourage { display: block; }

    /* coach: focused brows & eyes, open 5-finger scan hologram */
    .kiko-widget[data-mood="coach"] .kiko-eyes-open,
    .kiko-widget[data-mood="coach"] .kiko-mouth-normal,
    .kiko-widget[data-mood="coach"] .kiko-holo-happy { display: none; }
    .kiko-widget[data-mood="coach"] .kiko-eyes-focus,
    .kiko-widget[data-mood="coach"] .kiko-mouth-focus,
    .kiko-widget[data-mood="coach"] .kiko-holo-coach { display: block; }

    /* speaking: talking mouth + speaker hologram + holo sound waves */
    .kiko-widget.is-speaking .kiko-mouth:not(.kiko-mouth-talk),
    .kiko-widget.is-speaking:not(.is-poked) .kiko-holo-icon:not(.kiko-holo-speaking) { display: none; }
    .kiko-widget.is-speaking .kiko-mouth-talk,
    .kiko-widget.is-speaking:not(.is-poked) .kiko-holo-speaking,
    .kiko-widget.is-speaking .kiko-sound-waves { display: block; }

    /* poke: brief surprised face + "!" holo burst */
    .kiko-widget.is-poked .kiko-eyes:not(.kiko-eyes-wow),
    .kiko-widget.is-poked .kiko-mouth:not(.kiko-mouth-wow),
    .kiko-widget.is-poked .kiko-holo-icon:not(.kiko-holo-poke) { display: none; }
    .kiko-widget.is-poked .kiko-eyes-wow,
    .kiko-widget.is-poked .kiko-mouth-wow,
    .kiko-widget.is-poked .kiko-holo-poke { display: block; }

    /* ---------- Motion (opt-in; reduced motion keeps every face & hologram intact) ---------- */
    @media (prefers-reduced-motion: no-preference) {
        .kiko-svg { animation: kikoFloat 3.2s ease-in-out infinite; }
        .kiko-widget[data-mood="celebrate"] .kiko-svg { animation: kikoJump 0.7s var(--ease-out, cubic-bezier(0.22, 1, 0.36, 1)) infinite alternate; }
        .kiko-widget.is-poked .kiko-svg { animation: kikoSquash 260ms var(--ease-out, cubic-bezier(0.22, 1, 0.36, 1)); }

        .kiko-svg .kiko-blink { animation: kikoBlink 4.2s infinite; }
        .kiko-svg .kiko-tail { animation: kikoTailWag 2.4s ease-in-out infinite; }
        .kiko-svg .kiko-ear-left { animation: kikoEarTwitch 5s ease-in-out infinite; }
        .kiko-svg .kiko-ear-right { animation: kikoEarTwitchR 5s ease-in-out infinite; }
        .kiko-svg .kiko-holo-card { animation: kikoHoloHover 2.8s ease-in-out infinite; }
        .kiko-svg .kiko-holo-beam { animation: kikoBeamPulse 2.8s ease-in-out infinite; }
        .kiko-svg .kiko-holo-sparkle { animation: kikoTwinkle 1.4s ease-in-out infinite alternate; }
        .kiko-svg .kiko-holo-heart { animation: kikoHeart 1.2s ease-in-out infinite; }
        .kiko-svg .kiko-eyes-focus .kiko-scan { animation: kikoScan 3s ease-in-out infinite; }
        .kiko-svg .kiko-holo-scanbar { animation: kikoHoloScan 2.2s ease-in-out infinite alternate; }
        .kiko-widget.is-speaking .kiko-mouth-talk { animation: kikoTalk 0.32s ease-in-out infinite alternate; }
        .kiko-widget.is-speaking .kiko-pod-lens { animation: kikoLensPulse 0.6s ease-in-out infinite alternate; }

        /* Minimizing acts as the WCAG 2.2.2 pause control for idle loops */
        .kiko-widget.minimized .kiko-svg,
        .kiko-widget.minimized .kiko-svg * { animation: none; }
    }

    /* Minimized badge indicator */
    .kiko-peek-dot {
        display: none;
        position: absolute;
        top: 2px;
        right: 4px;
        z-index: 2;
        background: #d63031;
        color: #fff;
        font-size: 0.72rem;
        font-weight: 900;
        padding: 0.1rem 0.5rem;
        border-radius: 999px;
        border: 2px solid #fff;
    }

    .kiko-widget.minimized .kiko-peek-dot { display: inline-block; }

    /* Palette star particle burst */
    .kiko-particles {
        position: absolute;
        inset: -20px;
        pointer-events: none;
        overflow: visible;
    }

    .kiko-particle {
        position: absolute;
        left: 55%;
        top: 45%;
        width: 14px;
        height: 14px;
        pointer-events: none;
        clip-path: polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%);
        animation: kikoParticleFly 0.9s cubic-bezier(0.22, 1, 0.36, 1) forwards;
    }

    @keyframes kikoFade { from { opacity: 0; } }
    @keyframes kikoBubblePop { from { opacity: 0.6; transform: scale(0.96); } }
    @keyframes kikoFloat {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-5px); }
    }
    @keyframes kikoJump { to { transform: translateY(-9px); } }
    @keyframes kikoSquash {
        40% { transform: scale(1.05, 0.93); }
        100% { transform: scale(1, 1); }
    }
    @keyframes kikoBlink {
        0%, 92%, 100% { transform: scaleY(1); }
        95% { transform: scaleY(0.1); }
    }
    @keyframes kikoTailWag {
        0%, 100% { transform: rotate(0deg); }
        50% { transform: rotate(12deg); }
    }
    @keyframes kikoEarTwitch {
        0%, 88%, 100% { transform: rotate(0deg); }
        92% { transform: rotate(-7deg); }
        96% { transform: rotate(3deg); }
    }
    @keyframes kikoEarTwitchR {
        0%, 88%, 100% { transform: rotate(0deg); }
        93% { transform: rotate(7deg); }
        97% { transform: rotate(-3deg); }
    }
    @keyframes kikoHoloHover {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-4px); }
    }
    @keyframes kikoBeamPulse {
        0%, 100% { opacity: 0.92; }
        50% { opacity: 0.72; }
    }
    @keyframes kikoTwinkle {
        from { transform: scale(0.82) rotate(0deg); }
        to { transform: scale(1.1) rotate(14deg); }
    }
    @keyframes kikoHeart {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }
    @keyframes kikoScan {
        0%, 20%, 100% { transform: translateX(0); }
        35%, 50% { transform: translateX(-4px); }
        65%, 80% { transform: translateX(4px); }
    }
    @keyframes kikoHoloScan {
        from { transform: translateY(-14px); }
        to { transform: translateY(14px); }
    }
    @keyframes kikoTalk {
        from { transform: scaleY(0.35); }
        to { transform: scaleY(1); }
    }
    @keyframes kikoLensPulse {
        from { transform: scale(0.88); }
        to { transform: scale(1.14); }
    }
    @keyframes kikoParticleFly {
        0% { opacity: 1; transform: translate(-50%, -50%) scale(0.6); }
        100% { opacity: 0; transform: translate(var(--tx), var(--ty)) scale(1.1) rotate(var(--rot)); }
    }

    @media (max-width: 640px) {
        .kiko-widget { right: 8px; bottom: 8px; gap: 0.35rem; }
        .kiko-bubble { max-width: 205px; padding: 0.65rem 0.8rem; }
        .kiko-text { font-size: 0.82rem; }
        .kiko-avatar-wrap { width: 118px; height: 112px; }
    }
</style>

<div class="kiko-widget" id="kiko-widget" data-mood="{{ $kikoInitialMood }}">
    {{-- Speech Bubble --}}
    <div class="kiko-bubble" id="kiko-bubble" role="status" aria-live="polite">
        <div class="kiko-bubble-header">
            <span class="kiko-name-tag"><i class="fa-solid fa-robot" aria-hidden="true"></i> Kiko &bull; Robot Penceria</span>
            <div class="kiko-bubble-actions">
                <button type="button" class="kiko-mini-btn" id="kiko-btn-speak" title="Dengar suara Kiko" aria-label="Dengar suara Kiko">
                    <i class="fa-solid fa-volume-high" aria-hidden="true"></i>
                </button>
                <button type="button" class="kiko-mini-btn" id="kiko-btn-cheer" title="Kata semangat baru" aria-label="Kata semangat baru">
                    <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
                </button>
                <button type="button" class="kiko-mini-btn" id="kiko-btn-min" title="Kecilkan mesej" aria-label="Kecilkan mesej Kiko">
                    <i class="fa-solid fa-minus" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        <div class="kiko-reward-pill" id="kiko-reward-pill"><i class="fa-solid fa-star" aria-hidden="true"></i><span id="kiko-reward-text">+3 Bintang!</span></div>
        <p class="kiko-text" id="kiko-text">{{ $kikoInitialMsg }}</p>
    </div>

    {{-- Kiko v2: Left-facing Robot Cat with Pocket Holo-Pod projecting BIM Hand Sign --}}
    <button type="button" class="kiko-avatar-wrap" id="kiko-avatar" aria-label="Tekan Kiko untuk kata semangat" title="Tekan Kiko untuk kata semangat">
        <span class="kiko-peek-dot" aria-hidden="true">Hai!</span>
        <span class="kiko-particles" id="kiko-particles" aria-hidden="true"></span>

        <svg class="kiko-svg" viewBox="0 0 272 256" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
            {{-- Tail (right side sweep, stays neatly inside right viewport edge) --}}
            <g class="kiko-tail">
                <path d="M216 208 C244 210 256 196 254 174" fill="none" stroke="#16324f" stroke-width="13" stroke-linecap="round"/>
                <path d="M216 208 C244 210 256 196 254 174" fill="none" stroke="#58caf3" stroke-width="6" stroke-linecap="round"/>
                <circle cx="254" cy="168" r="7.5" fill="#ffe942" stroke="#16324f" stroke-width="4"/>
            </g>

            {{-- Feet --}}
            <ellipse cx="166" cy="226" rx="15" ry="8.5" fill="#16324f"/>
            <ellipse cx="202" cy="226" rx="15" ry="8.5" fill="#16324f"/>

            {{-- Neck & Torso --}}
            <rect x="172" y="148" width="24" height="14" rx="5" fill="#16324f"/>
            <path d="M150 176 C150 160 164 156 184 156 C204 156 218 160 218 176 L216 208 C216 220 204 224 184 224 C164 224 152 220 152 208 Z"
                  fill="#58caf3" stroke="#16324f" stroke-width="5" stroke-linejoin="round"/>

            {{-- Hologram Light Cone projecting UP-LEFT from Pocket Pod --}}
            <polygon class="kiko-holo-beam" points="162,178 148,196 28,148 104,62" fill="#d8f4fe"/>

            {{-- Both Arms cradling Pocket Holo-Pod --}}
            <path d="M154 178 Q148 196 164 194" fill="none" stroke="#16324f" stroke-width="16" stroke-linecap="round"/>
            <path d="M154 178 Q148 196 164 194" fill="none" stroke="#58caf3" stroke-width="8" stroke-linecap="round"/>
            <path d="M214 178 Q210 198 192 196" fill="none" stroke="#16324f" stroke-width="16" stroke-linecap="round"/>
            <path d="M214 178 Q210 198 192 196" fill="none" stroke="#58caf3" stroke-width="8" stroke-linecap="round"/>

            {{-- Pocket Holo-Pod Device --}}
            <rect x="152" y="174" width="44" height="32" rx="11" fill="#16324f"/>
            <rect x="159" y="180" width="30" height="20" rx="7" fill="#ffffff"/>
            <circle class="kiko-pod-lens" cx="167" cy="186" r="5.5" fill="#ffe942" stroke="#16324f" stroke-width="2.5"/>
            <circle cx="152" cy="194" r="9.5" fill="#ffffff" stroke="#16324f" stroke-width="4.2"/>
            <circle cx="198" cy="194" r="9.5" fill="#ffffff" stroke="#16324f" stroke-width="4.2"/>

            {{-- Floating Hologram Screen Card (TOP-LEFT, pointing inward to page) --}}
            <g class="kiko-holo-card">
                <rect x="18" y="54" width="88" height="96" rx="24" fill="#ffffff" stroke="#58caf3" stroke-width="5"/>
                <rect x="27" y="63" width="70" height="78" rx="16" fill="#16324f"/>
                {{-- Top HUD bar --}}
                <circle cx="42" cy="74" r="3.2" fill="#ffe942"/>
                <circle cx="53" cy="74" r="3.2" fill="#58caf3"/>
                <line x1="63" y1="74" x2="82" y2="74" stroke="#58caf3" stroke-width="3.2" stroke-linecap="round"/>

                {{-- Holo 1: Happy / Default — ILY Hand Sign (🤟) --}}
                <g class="kiko-holo-icon kiko-holo-happy" stroke="#16324f" stroke-width="3.2" stroke-linejoin="round" stroke-linecap="round">
                    <path d="M46 128
                             L46 116
                             L35 107 C31 104 34 98 39 100 L47 105
                             L47 85 C47 79 55 79 55 85 L55 103
                             L71 103
                             L71 88 C71 82 79 82 79 88 L79 114
                             C79 125 72 131 62 131
                             C52 131 46 129 46 128 Z"
                          fill="#ffe942"/>
                    <rect x="55.5" y="100" width="8" height="13" rx="4" fill="#ffe942"/>
                    <rect x="63" y="101" width="8" height="12.5" rx="4" fill="#ffe942"/>
                </g>

                {{-- Holo 2: Celebrate — Thumbs-Up + Star (👍) --}}
                <g class="kiko-holo-icon kiko-holo-celebrate">
                    <rect x="38" y="103" width="12" height="24" rx="4" fill="#58caf3" stroke="#16324f" stroke-width="2.5"/>
                    <path d="M53 102 L57 86 C58.5 81 66 82 65 88 L63 98 L77 98 C82 98 83 105 79 107 C82 109 82 115 78 117 C80 120 79 126 73 127 L54 127 C50 127 48 124 48 120 L48 106 C48 103 50 102 53 102 Z"
                          fill="#ffe942" stroke="#16324f" stroke-width="2.6" stroke-linejoin="round"/>
                    <polygon points="79,82 81.2,86.8 86,89 81.2,91.2 79,96 76.8,91.2 72,89 76.8,86.8" fill="#ffe942"/>
                </g>

                {{-- Holo 3: Encourage — Warm Heart --}}
                <g class="kiko-holo-icon kiko-holo-encourage">
                    <circle cx="62" cy="107" r="23" fill="none" stroke="#58caf3" stroke-width="2.8" stroke-dasharray="8 6"/>
                    <path class="kiko-holo-heart" d="M62 122 C62 122 45 111 45 100 C45 93 51 90 56 93 C59 95 61 98 62 100 C63 98 65 95 68 93 C73 90 79 93 79 100 C79 111 62 122 62 122 Z"
                          fill="#ff8fa3" stroke="#ffffff" stroke-width="2.4" stroke-linejoin="round"/>
                </g>

                {{-- Holo 4: Coach — Open 5-Finger Hand + AI Scan Line (✋) --}}
                <g class="kiko-holo-icon kiko-holo-coach">
                    <path d="M47 121
                             L47 112
                             L37 104 C33 101 36 96 40 98 L47 103
                             L47 86 C47 81 54 81 54 86 L54 100
                             L54 81 C54 76 61 76 61 81 L61 99
                             L61 83 C61 78 68 78 68 83 L68 101
                             L68 89 C68 84 75 84 75 89 L75 115
                             C75 124 69 129 61 129
                             C52 129 47 125 47 121 Z"
                          fill="#ffe942" stroke="#16324f" stroke-width="2.4" stroke-linejoin="round"/>
                    <line class="kiko-holo-scanbar" x1="34" y1="106" x2="90" y2="106" stroke="#58caf3" stroke-width="3" stroke-linecap="round"/>
                </g>

                {{-- Holo 5: Poke — Surprised "!" Burst --}}
                <g class="kiko-holo-icon kiko-holo-poke">
                    <rect x="57" y="83" width="10" height="27" rx="5" fill="#ffe942"/>
                    <circle cx="62" cy="121" r="5.5" fill="#ffe942"/>
                    <path d="M41 92 L47 96 M83 92 L77 96 M39 112 L46 110 M85 112 L78 110" stroke="#58caf3" stroke-width="3" stroke-linecap="round"/>
                </g>

                {{-- Holo 6: Speaking — Yellow Speaker + Sound Waves --}}
                <g class="kiko-holo-icon kiko-holo-speaking">
                    <path d="M41 98 L48 98 L59 89 L59 119 L48 110 L41 110 Z"
                          fill="#ffe942" stroke="#ffe942" stroke-width="3.2" stroke-linejoin="round"/>
                    <path d="M66 96.5 Q71.5 104 66 111.5" fill="none" stroke="#ffe942" stroke-width="4.6" stroke-linecap="round"/>
                    <path d="M75 91 Q83 104 75 117" fill="none" stroke="#ffe942" stroke-width="4.6" stroke-linecap="round"/>
                </g>

                {{-- Sound-Wave Arcs (Speaking state) --}}
                <g class="kiko-sound-waves" fill="none" stroke-linecap="round">
                    <path d="M10 86 Q3 102 10 118" stroke="#26b0e5" stroke-width="4"/>
                    <path d="M4 78 Q-5 102 4 126" stroke="#58caf3" stroke-width="3.5"/>
                </g>

                {{-- Corner Sparkle --}}
                <polygon class="kiko-holo-sparkle" points="20,36 23,43 30,46 23,49 20,56 17,49 10,46 17,43"
                         fill="#ffe942" stroke="#16324f" stroke-width="2.5" stroke-linejoin="round"/>
            </g>

            {{-- Star-Tip Antenna --}}
            <line x1="184" y1="52" x2="184" y2="34" stroke="#16324f" stroke-width="5" stroke-linecap="round"/>
            <polygon class="kiko-antenna-star" points="184,14 187.5,22 196,25 187.5,28 184,36 180.5,28 172,25 180.5,22"
                     fill="#ffe942" stroke="#16324f" stroke-width="3" stroke-linejoin="round"/>

            {{-- Soft Rounded Cat Ears --}}
            <g class="kiko-ear-left">
                <path d="M132 76 C131 54 134 36 140 32 C147 28 162 40 172 54 C158 60 142 68 132 76 Z"
                      fill="#58caf3" stroke="#16324f" stroke-width="5" stroke-linejoin="round"/>
                <path d="M140 66 C140 53 142 44 145 42 C149 40 157 47 163 55 Z" fill="#ffe942"/>
            </g>
            <g class="kiko-ear-right">
                <path d="M236 76 C237 54 234 36 228 32 C221 28 206 40 196 54 C210 60 226 68 236 76 Z"
                      fill="#58caf3" stroke="#16324f" stroke-width="5" stroke-linejoin="round"/>
                <path d="M228 66 C228 53 226 44 223 42 C219 40 211 47 205 55 Z" fill="#ffe942"/>
            </g>

            {{-- Short Symmetric Whiskers (clean air gap from Holo Card) --}}
            <line x1="112" y1="104" x2="127" y2="108" stroke="#16324f" stroke-width="4.2" stroke-linecap="round"/>
            <line x1="113" y1="120" x2="128" y2="117" stroke="#16324f" stroke-width="4.2" stroke-linecap="round"/>
            <line x1="256" y1="104" x2="241" y2="108" stroke="#16324f" stroke-width="4.2" stroke-linecap="round"/>
            <line x1="255" y1="120" x2="240" y2="117" stroke="#16324f" stroke-width="4.2" stroke-linecap="round"/>

            {{-- Chubby Squircle Head Shell --}}
            <path d="M184 50
                     C218 50 238 58 242 92
                     C246 126 230 150 184 150
                     C138 150 122 126 126 92
                     C130 58 150 50 184 50 Z"
                  fill="#ffffff" stroke="#16324f" stroke-width="5" stroke-linejoin="round"/>

            {{-- LED Face Screen --}}
            <path d="M184 66
                     C210 66 224 72 226 96
                     C228 122 216 136 184 136
                     C152 136 140 122 142 96
                     C144 72 158 66 184 66 Z"
                  fill="#16324f"/>

            {{-- Solid Rosy Cheeks --}}
            <ellipse cx="154" cy="117" rx="7" ry="4.2" fill="#ff8fa3"/>
            <ellipse cx="214" cy="117" rx="7" ry="4.2" fill="#ff8fa3"/>

            {{-- Eyes Group (tracks pointer subtly, defaults glancing slightly left toward hologram) --}}
            <g class="kiko-look" id="kiko-look">
                {{-- Eyes: Open (happy / speaking) --}}
                <g class="kiko-eyes kiko-eyes-open">
                    <g class="kiko-blink">
                        <rect x="156" y="86" width="15" height="23" rx="7.5" fill="#58caf3"/>
                        <rect x="193" y="86" width="15" height="23" rx="7.5" fill="#58caf3"/>
                        <circle cx="161" cy="92.5" r="3.2" fill="#ffffff"/>
                        <circle cx="198" cy="92.5" r="3.2" fill="#ffffff"/>
                    </g>
                </g>

                {{-- Eyes: ^ ^ (celebrate) --}}
                <g class="kiko-eyes kiko-eyes-happy" fill="none" stroke="#58caf3" stroke-width="5.4" stroke-linecap="round">
                    <path d="M153 102 Q163 87 173 102"/>
                    <path d="M191 102 Q201 87 211 102"/>
                </g>

                {{-- Eyes: Soft + Caring Brows (encourage) --}}
                <g class="kiko-eyes kiko-eyes-soft">
                    <rect x="156" y="92" width="15" height="15" rx="7.5" fill="#58caf3"/>
                    <rect x="193" y="92" width="15" height="15" rx="7.5" fill="#58caf3"/>
                    <circle cx="161" cy="96.5" r="2.8" fill="#ffffff"/>
                    <circle cx="198" cy="96.5" r="2.8" fill="#ffffff"/>
                    <path d="M154 86 L170 81 M194 81 L210 86" stroke="#58caf3" stroke-width="4.2" stroke-linecap="round"/>
                </g>

                {{-- Eyes: Focused + Determined Brows (coach) --}}
                <g class="kiko-eyes kiko-eyes-focus">
                    <path d="M154 81 L171 86 M193 86 L210 81" stroke="#58caf3" stroke-width="4.2" stroke-linecap="round"/>
                    <g class="kiko-scan">
                        <rect x="156" y="90" width="15" height="18" rx="7.5" fill="#58caf3"/>
                        <rect x="193" y="90" width="15" height="18" rx="7.5" fill="#58caf3"/>
                        <circle cx="162" cy="99" r="3.8" fill="#16324f"/>
                        <circle cx="199" cy="99" r="3.8" fill="#16324f"/>
                        <circle cx="160.5" cy="97" r="1.5" fill="#ffffff"/>
                        <circle cx="197.5" cy="97" r="1.5" fill="#ffffff"/>
                    </g>
                </g>

                {{-- Eyes: Wide Surprised (poke) --}}
                <g class="kiko-eyes kiko-eyes-wow">
                    <circle cx="163.5" cy="97" r="11" fill="#58caf3"/>
                    <circle cx="200.5" cy="97" r="11" fill="#58caf3"/>
                    <circle cx="160.5" cy="93" r="3.8" fill="#ffffff"/>
                    <circle cx="197.5" cy="93" r="3.8" fill="#ffffff"/>
                </g>
            </g>

            {{-- Cat Nose --}}
            <path d="M179.5 109 L188.5 109 L184 114.5 Z" fill="#ffe942" stroke="#ffe942" stroke-width="1.8" stroke-linejoin="round"/>

            {{-- Mouths --}}
            <path class="kiko-mouth kiko-mouth-normal" d="M171 119 Q177.5 125.5 184 119 Q190.5 125.5 197 119" fill="none" stroke="#58caf3" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round"/>
            <g class="kiko-mouth kiko-mouth-big">
                <path d="M169 117 Q184 136 199 117 Z" fill="#58caf3" stroke="#58caf3" stroke-width="3" stroke-linejoin="round"/>
                <ellipse cx="184" cy="125.5" rx="6.5" ry="3.5" fill="#ff8fa3"/>
            </g>
            <path class="kiko-mouth kiko-mouth-soft" d="M171 119 Q184 129 197 119" fill="none" stroke="#58caf3" stroke-width="4.5" stroke-linecap="round"/>
            <path class="kiko-mouth kiko-mouth-focus" d="M174 122 Q184 125 194 119.5" fill="none" stroke="#58caf3" stroke-width="4.5" stroke-linecap="round"/>
            <ellipse class="kiko-mouth kiko-mouth-wow" cx="184" cy="123" rx="5.5" ry="6.8" fill="none" stroke="#58caf3" stroke-width="4.2"/>
            <ellipse class="kiko-mouth kiko-mouth-talk" cx="184" cy="122.5" rx="8.5" ry="6" fill="#58caf3"/>
        </svg>
    </button>
</div>

<script>
    (function () {
        const widget = document.getElementById('kiko-widget');
        const bubble = document.getElementById('kiko-bubble');
        const textEl = document.getElementById('kiko-text');
        const rewardPill = document.getElementById('kiko-reward-pill');
        const rewardText = document.getElementById('kiko-reward-text');
        const avatar = document.getElementById('kiko-avatar');
        const lookGroup = document.getElementById('kiko-look');
        const particlesBox = document.getElementById('kiko-particles');
        const btnSpeak = document.getElementById('kiko-btn-speak');
        const btnCheer = document.getElementById('kiko-btn-cheer');
        const btnMin = document.getElementById('kiko-btn-min');

        if (!widget || !textEl) return;

        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)');
        const COLORS = {
            party: ['#ffe942', '#58caf3', '#26b0e5', '#22c55e', '#ff8fa3'],
            soft: ['#22c55e', '#58caf3', '#ff8fa3'],
            spark: ['#ffe942', '#58caf3'],
        };

        const studentName = @json($kikoStudentName);
        const cheerLines = [
            { text: `High-five, ${studentName}! Awak memang rajin belajar Bahasa Isyarat hari ini!`, mood: 'celebrate' },
            { text: 'Tip Kiko: Senyuman dan mimik muka buat isyarat BIM awak jauh lebih jelas!', mood: 'coach' },
            { text: 'Jangan risau kalau tersilap! Praktis sikit-sikit, lama-lama jadi juara BIM!', mood: 'encourage' },
            { text: 'Setiap isyarat baru yang awak kuasai beri 3 bintang emas! Jom kumpul banyak-banyak!', mood: 'happy' },
            { text: `Bip bip! Kiko bangga sangat dengan usaha ${studentName}! Teruskan semangat hebat awak!`, mood: 'celebrate' },
            { text: 'Tahukah awak? Tangan yang terang di tengah kamera memudahkan AI membaca jari awak!', mood: 'coach' },
            { text: 'Yay! Belajar Bahasa Isyarat membolehkan kita berkawan dengan semua orang!', mood: 'happy' },
        ];
        let cheerIdx = 0;
        let moodResetTimer = null;
        let pokeTimer = null;

        try {
            if (sessionStorage.getItem('gs_kiko_min') === '1') {
                widget.classList.add('minimized');
            }
        } catch {}

        function setMinimized(min) {
            widget.classList.toggle('minimized', min);
            try { sessionStorage.setItem('gs_kiko_min', min ? '1' : '0'); } catch {}
        }

        function spawnParticles(colors = COLORS.party, count = 12) {
            if (!particlesBox || reduceMotion.matches) return;
            for (let i = 0; i < count; i++) {
                const star = document.createElement('span');
                star.className = 'kiko-particle';
                star.style.background = colors[i % colors.length];
                const angle = (Math.PI * 2 * i) / count + (Math.random() * 0.4 - 0.2);
                const dist = 48 + Math.random() * 40;
                star.style.setProperty('--tx', `${Math.round(Math.cos(angle) * dist)}px`);
                star.style.setProperty('--ty', `${Math.round(Math.sin(angle) * dist - 18)}px`);
                star.style.setProperty('--rot', `${Math.round(Math.random() * 120 - 60)}deg`);
                particlesBox.appendChild(star);
                setTimeout(() => star.remove(), 950);
            }
        }

        /* Brief surprised face when Kiko is tapped */
        function poke() {
            clearTimeout(pokeTimer);
            widget.classList.remove('is-poked');
            void widget.offsetWidth;
            widget.classList.add('is-poked');
            pokeTimer = setTimeout(() => widget.classList.remove('is-poked'), 650);
        }

        /* Eyes follow the mouse gently (fine pointers only, no reduced motion) */
        let lookFrame = 0;
        function onPointerMove(e) {
            if (lookFrame) return;
            lookFrame = requestAnimationFrame(() => {
                lookFrame = 0;
                if (!lookGroup || reduceMotion.matches || !finePointer.matches) return;
                const r = avatar.getBoundingClientRect();
                const dx = e.clientX - (r.left + r.width * 0.68);
                const dy = e.clientY - (r.top + r.height * 0.4);
                const len = Math.hypot(dx, dy) || 1;
                const pull = Math.min(len / 240, 1);
                lookGroup.style.transform = `translate(${((dx / len) * 5.5 * pull).toFixed(2)}px, ${((dy / len) * 4 * pull).toFixed(2)}px)`;
            });
        }
        window.addEventListener('pointermove', onPointerMove, { passive: true });

        const ttsBaseUrl = @json(route('tts.speak'));
        const kikoAudio = new Audio();
        kikoAudio.preload = 'auto';

        function setSpeakingUi(active) {
            widget.classList.toggle('is-speaking', active);
            btnSpeak?.classList.toggle('is-active', active);
        }

        kikoAudio.addEventListener('playing', () => setSpeakingUi(true));
        kikoAudio.addEventListener('ended', () => setSpeakingUi(false));
        kikoAudio.addEventListener('pause', () => setSpeakingUi(false));

        function fallbackBrowserSpeak(clean) {
            setSpeakingUi(false);
            if (!('speechSynthesis' in window) || !clean) return;
            window.speechSynthesis.cancel();
            const utter = new SpeechSynthesisUtterance(clean);
            utter.lang = 'ms-MY';
            utter.rate = 0.96;
            utter.pitch = 1.25;
            const voices = window.speechSynthesis.getVoices();
            const myVoice = voices.find(v => v.lang.startsWith('ms') || v.lang.startsWith('id') || v.lang.includes('Malay'));
            if (myVoice) utter.voice = myVoice;
            utter.onstart = () => setSpeakingUi(true);
            utter.onend = () => setSpeakingUi(false);
            utter.onerror = () => setSpeakingUi(false);
            window.speechSynthesis.speak(utter);
        }

        function speakKiko(text) {
            if (!text) return;
            const clean = String(text).replace(/[\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}\u{FE00}-\u{FE0F}]/gu, '').trim();
            if (!clean) return;

            if ('speechSynthesis' in window) {
                window.speechSynthesis.cancel();
            }

            try {
                kikoAudio.pause();
            } catch {}

            setSpeakingUi(true);
            const url = `${ttsBaseUrl}?text=${encodeURIComponent(clean)}`;
            kikoAudio.onerror = () => fallbackBrowserSpeak(clean);
            kikoAudio.src = url;

            const playPromise = kikoAudio.play();
            if (playPromise && typeof playPromise.catch === 'function') {
                playPromise.catch(err => {
                    if (err && err.name === 'AbortError') return;
                    console.warn('Audio playback notice, using speechSynthesis fallback:', err);
                    fallbackBrowserSpeak(clean);
                });
            }
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
                    if (rewardText) rewardText.textContent = opts.rewardText;
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
            const reward = opts.stars > 0 ? `+${opts.stars} Bintang Diperoleh!` : (opts.rewardText || null);
            say(message, 'celebrate', {
                openBubble: true,
                rewardText: reward,
                autoSpeak: Boolean(opts.autoSpeak),
                duration: opts.duration || 5500,
            });
            spawnParticles(COLORS.party, 16);
        }

        function encourage(message, opts = {}) {
            say(message, 'encourage', {
                openBubble: true,
                autoSpeak: Boolean(opts.autoSpeak),
                duration: opts.duration || 4500,
            });
            spawnParticles(COLORS.soft, 7);
        }

        function randomCheer(autoSpeak = false) {
            const item = cheerLines[cheerIdx % cheerLines.length];
            cheerIdx++;
            say(item.text, item.mood, { openBubble: true, autoSpeak, duration: 5000 });
            spawnParticles(COLORS.spark, 9);
        }

        avatar.addEventListener('click', () => {
            poke();
            if (widget.classList.contains('minimized')) {
                setMinimized(false);
                spawnParticles(COLORS.spark, 6);
                speakKiko(textEl.textContent);
            } else {
                randomCheer(true);
            }
        });

        btnCheer?.addEventListener('click', e => {
            e.stopPropagation();
            randomCheer(true);
        });

        btnSpeak?.addEventListener('click', e => {
            e.stopPropagation();
            speakKiko(textEl.textContent);
            spawnParticles(COLORS.spark, 5);
        });

        btnMin?.addEventListener('click', e => {
            e.stopPropagation();
            setMinimized(true);
        });

        window.Kiko = { say, celebrate, encourage, randomCheer, speak: speakKiko };
    })();
</script>
