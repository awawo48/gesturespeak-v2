<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Portal pentadbir — Sign Cilik' }}</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('media/brand/signcilik-icon.svg') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <link rel="preload" href="{{ asset('fonts/CutePixel.ttf') }}" as="font" type="font/ttf" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600&family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>

    <style>
        :root {
            --adm-canvas: var(--bg-gradient, #fffdf7);
            --adm-rail: var(--primary-dark, #0f2238);
            --adm-surface: #ffffff;
            --adm-raised: #f0f8fd;
            --adm-ink: var(--primary, #16324f);
            --adm-text-secondary: #334155;
            --adm-text-muted: var(--text-muted, #475569);
            --adm-hairline: rgba(22, 50, 79, 0.1);
            --adm-hairline-strong: rgba(22, 50, 79, 0.18);
            --adm-accent: var(--secondary-dark, #0284c7);
            --adm-sky: var(--secondary, #26b0e5);
            --adm-sun: var(--accent, #ffe942);
            --adm-pos: var(--success, #059669);
            --adm-warn: #d97706;
            --adm-neg: var(--danger, #d63031);
            --adm-sans: var(--font-main, 'Nunito', system-ui, -apple-system, sans-serif);
            --adm-display: var(--font-display, 'Fredoka', 'Nunito', sans-serif);
            --adm-mono: var(--font-pixel, 'CutePixel', ui-monospace, monospace);
            --adm-ease: cubic-bezier(0.22, 1, 0.36, 1);
        }

        body.adm-body {
            font-family: var(--adm-sans);
            font-weight: 500;
            background-color: var(--adm-canvas);
            background-image: radial-gradient(rgba(22, 50, 79, 0.055) 1px, transparent 1px);
            background-size: 24px 24px;
            color: var(--adm-ink);
            min-height: 100dvh;
            margin: 0;
            overflow-x: clip;
            line-height: 1.5;
        }

        .adm-body h1, .adm-body h2, .adm-body h3, .adm-body h4 {
            font-family: var(--adm-display);
            font-weight: 600;
            color: var(--adm-ink);
        }

        .adm-body :focus-visible {
            outline: 2px solid var(--adm-sky);
            outline-offset: 2px;
        }

        /* Override chunky kids-app buttons/inputs inside admin workspace */
        .adm-body .btn {
            min-block-size: 36px;
            padding: 0 14px;
            border-radius: 4px;
            font-family: var(--adm-sans);
            font-size: 0.84rem;
            font-weight: 500;
            border: 1px solid transparent;
            box-shadow: none;
            transition: background-color 140ms var(--adm-ease), border-color 140ms var(--adm-ease), color 140ms var(--adm-ease), transform 120ms var(--adm-ease);
        }
        @media (hover: hover) and (pointer: fine) {
            .adm-body .btn:hover { transform: none; }
        }
        .adm-body .btn:active { transform: scale(0.98); }
        .adm-body .btn-primary {
            background: var(--adm-ink);
            color: #fff;
            border-color: var(--adm-ink);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.14), 0 2px 0 #091523;
        }
        @media (hover: hover) and (pointer: fine) {
            .adm-body .btn-primary:hover { background: #1e3f63; border-color: #1e3f63; box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.14), 0 2px 0 #091523; }
        }
        .adm-body .btn-secondary {
            background: var(--adm-raised);
            color: var(--adm-ink);
            border-color: var(--adm-hairline-strong);
        }
        .adm-body .btn-outline {
            background: var(--adm-surface);
            color: var(--adm-ink);
            border: 1px solid var(--adm-hairline-strong);
            box-shadow: 0 1px 0 rgba(22, 50, 79, 0.06);
        }
        @media (hover: hover) and (pointer: fine) {
            .adm-body .btn-outline:hover { background: var(--adm-raised); color: var(--adm-ink); box-shadow: 0 1px 0 rgba(22, 50, 79, 0.06); }
        }
        .adm-body .btn-sm {
            min-block-size: 32px;
            padding: 0 12px;
            font-size: 0.8125rem;
        }
        .adm-body .form-input,
        .adm-body .form-select,
        .adm-body .form-textarea {
            font-family: var(--adm-sans);
            font-size: 0.84rem;
            font-weight: 400;
            color: var(--adm-ink);
            background: var(--adm-surface);
            border: 1px solid var(--adm-hairline-strong);
            border-radius: 4px;
            padding: 0.45rem 0.75rem;
        }
        .adm-body .form-input,
        .adm-body .form-select { min-height: 36px; }
        .adm-body .form-label {
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--adm-text-secondary);
            margin-bottom: 0.35rem;
        }
        .adm-body .card {
            background: var(--adm-surface);
            border: 1px solid var(--adm-hairline-strong);
            border-radius: 6px;
            box-shadow: 0 2px 0 rgba(22, 50, 79, 0.05);
        }

        /* Layout Shell */
        .adm-shell {
            display: grid;
            grid-template-columns: 240px minmax(0, 1fr);
            min-height: 100dvh;
        }
        .adm-sidebar {
            background: var(--adm-rail);
            border-inline-end: 1px solid rgba(88, 202, 243, 0.16);
            display: flex;
            flex-direction: column;
            padding: 20px 12px 16px;
            position: sticky;
            top: 0;
            height: 100dvh;
            color: #fff;
        }
        .adm-brand {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 4px;
            padding: 4px 8px 16px;
            text-decoration: none;
            color: #fff;
        }
        .adm-brand-logo {
            height: 36px;
            width: auto;
            max-width: 100%;
            display: block;
        }
        .adm-brand-sub {
            font-size: 0.75rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.62);
            padding-inline-start: 2px;
        }
        .adm-nav-group-label {
            font-size: 0.75rem;
            font-weight: 500;
            color: rgba(148, 179, 212, 0.76);
            padding: 20px 8px 8px;
        }
        .adm-nav {
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex: 1;
        }
        .adm-nav-link {
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 0 10px;
            border-radius: 4px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            border: 1px solid transparent;
            transition: background-color 140ms var(--adm-ease), border-color 140ms var(--adm-ease), color 140ms var(--adm-ease);
        }
        .adm-nav-link-main {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }
        .adm-nav-icon {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            color: rgba(255, 255, 255, 0.56);
        }
        @media (hover: hover) and (pointer: fine) {
            .adm-nav-link:hover {
                background: rgba(88, 202, 243, 0.1);
                color: #ffffff;
            }
            .adm-nav-link:hover .adm-nav-icon {
                color: #58caf3;
            }
        }
        .adm-nav-link.active {
            background: #183552;
            color: #ffffff;
            border-color: rgba(88, 202, 243, 0.36);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
            font-weight: 600;
        }
        .adm-nav-link.active .adm-nav-icon {
            color: var(--adm-sun);
        }
        .adm-nav-count {
            font-family: var(--adm-mono);
            font-size: 0.78rem;
            font-variant-numeric: tabular-nums;
            color: var(--adm-sun);
            background: rgba(255, 233, 66, 0.14);
            border: 1px solid rgba(255, 233, 66, 0.35);
            border-radius: 2px;
            padding: 1px 6px;
            line-height: 1.2;
        }
        .adm-sidebar-footer {
            margin-top: auto;
            padding-top: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .adm-profile {
            padding: 8px 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }
        .adm-logout-btn {
            border: none;
            background: transparent;
            color: rgba(255, 255, 255, 0.62);
            cursor: pointer;
            padding: 6px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        @media (hover: hover) and (pointer: fine) {
            .adm-logout-btn:hover { background: rgba(88, 202, 243, 0.12); color: #ffffff; }
        }

        /* Content Area */
        .adm-main-wrap {
            min-width: 0;
            display: flex;
            flex-direction: column;
        }
        .adm-mobile-bar {
            display: none;
            align-items: center;
            justify-content: space-between;
            padding: 10px 16px;
            background: var(--adm-rail);
            color: #fff;
            border-bottom: 1px solid rgba(88, 202, 243, 0.16);
            position: sticky;
            top: 0;
            z-index: 40;
        }
        .adm-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            height: 48px;
            padding: 0 32px;
            background: rgba(255, 255, 255, 0.92);
            border-bottom: 1px solid var(--adm-hairline);
            font-size: 0.8125rem;
            color: var(--adm-text-muted);
        }
        .adm-topbar-left, .adm-topbar-right {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }
        .adm-topbar-crumb {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--adm-ink);
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .adm-content {
            padding: 24px 32px 40px;
            max-width: 1360px;
            width: 100%;
            margin-inline: auto;
        }

        /* Shared Primitives */
        .adm-page-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }
        .adm-page-head h1 {
            font-size: 1.5rem;
            font-weight: 600;
            letter-spacing: -0.015em;
            line-height: 1.2;
            margin: 0;
        }
        .adm-page-sub {
            color: var(--adm-text-muted);
            font-size: 0.875rem;
            margin: 4px 0 0;
        }
        .adm-card {
            background: var(--adm-surface);
            border: 1px solid var(--adm-hairline-strong);
            border-radius: 6px;
            padding: 20px;
            box-shadow: 0 2px 0 rgba(22, 50, 79, 0.05);
        }
        .adm-card-flush {
            padding: 0;
            overflow-x: auto;
        }
        .adm-card-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }
        .adm-card-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--adm-ink);
            margin: 0;
        }
        .adm-mono {
            font-family: var(--adm-mono);
            font-size: 0.92rem;
            font-variant-numeric: tabular-nums;
            letter-spacing: 0.02em;
        }
        .adm-pixel-track,
        .adm-loading-track {
            position: relative;
            height: 16px;
            padding: 4px;
            background: var(--adm-ink);
            clip-path: polygon(
                4px 0, calc(100% - 4px) 0,
                calc(100% - 4px) 2px, calc(100% - 2px) 2px, calc(100% - 2px) 4px, 100% 4px,
                100% calc(100% - 4px), calc(100% - 2px) calc(100% - 4px), calc(100% - 2px) calc(100% - 2px), calc(100% - 4px) calc(100% - 2px), calc(100% - 4px) 100%,
                4px 100%, 4px calc(100% - 2px), 2px calc(100% - 2px), 2px calc(100% - 4px), 0 calc(100% - 4px),
                0 4px, 2px 4px, 2px 2px, 4px 2px
            );
        }
        .adm-pixel-track::before,
        .adm-loading-track::before {
            content: "";
            position: absolute;
            inset: 2px;
            background-color: #ffffff;
            clip-path: polygon(
                2px 0, calc(100% - 2px) 0,
                calc(100% - 2px) 2px, 100% 2px,
                100% calc(100% - 2px), calc(100% - 2px) calc(100% - 2px),
                calc(100% - 2px) 100%, 2px 100%,
                2px calc(100% - 2px), 0 calc(100% - 2px),
                0 2px, 2px 2px
            );
        }
        .adm-pixel-track::before {
            padding: 2px;
            background-color: #ffffff;
            background-image: linear-gradient(#eef4fa, #eef4fa);
            background-origin: content-box;
            background-clip: content-box;
        }
        .adm-pixel-track--dark {
            background: var(--adm-sky-light, #58caf3);
        }
        .adm-pixel-track--dark::before {
            background-color: var(--adm-rail);
            background-image: linear-gradient(rgba(88, 202, 243, 0.16), rgba(88, 202, 243, 0.16));
        }
        .adm-pixel-fill {
            position: relative;
            z-index: 1;
            height: 100%;
            background: var(--adm-ink);
            transition: width 180ms var(--adm-ease);
        }

        /* Compact Notched Pixel Loading Bar (Sel Berkapsul) */
        .adm-loading-bar {
            display: inline-flex;
            flex-direction: column;
            gap: 4px;
            width: min(220px, 100%);
        }
        .adm-loading-label {
            font-family: var(--adm-mono);
            font-size: 0.78rem;
            font-weight: 500;
            letter-spacing: 0.06em;
            color: var(--adm-ink);
            line-height: 1.1;
        }
        .adm-loading-track {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 3px;
        }
        .adm-loading-cell {
            position: relative;
            z-index: 1;
            height: 100%;
            background: #ffffff;
            border: 1.5px solid var(--adm-ink);
        }
        .adm-loading-cell.is-done { background: var(--adm-ink); }
        .adm-loading-cell.is-active { background: var(--adm-sun); }
        .adm-loading-track.is-animating .adm-loading-cell {
            animation: admPixelCellPulse 1.25s steps(1, end) infinite;
        }
        .adm-loading-track.is-animating .adm-loading-cell:nth-child(2) { animation-delay: 0.2s; }
        .adm-loading-track.is-animating .adm-loading-cell:nth-child(3) { animation-delay: 0.4s; }
        .adm-loading-track.is-animating .adm-loading-cell:nth-child(4) { animation-delay: 0.6s; }
        .adm-loading-track.is-animating .adm-loading-cell:nth-child(5) { animation-delay: 0.8s; }
        @keyframes admPixelCellPulse {
            0%, 100% { background: #ffffff; }
            20%, 55% { background: var(--adm-ink); }
            60%, 75% { background: var(--adm-sun); }
        }
        @media (prefers-reduced-motion: reduce) {
            .adm-pixel-fill {
                transition: none;
            }
            .adm-loading-track.is-animating .adm-loading-cell {
                animation-duration: 6s;
            }
        }
        @media (forced-colors: active) {
            .adm-pixel-track, .adm-loading-track {
                background: ButtonText;
                clip-path: none;
                border: 1px solid ButtonText;
            }
            .adm-pixel-track::before, .adm-loading-track::before {
                background: Canvas;
                background-image: none;
                clip-path: none;
            }
            .adm-pixel-fill, .adm-loading-cell.is-done, .adm-loading-cell.is-active {
                background: Highlight;
                forced-color-adjust: none;
            }
        }

        /* Quiet Notice Bar */
        .adm-notice {
            background: #fffbeb;
            border: 1px solid rgba(217, 119, 6, 0.36);
            border-radius: 4px;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 20px;
            font-size: 0.875rem;
            color: var(--adm-ink);
        }

        /* Status Indicator (Pixel Square + Plain Text — no pill soup) */
        .adm-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--adm-text-secondary);
            white-space: nowrap;
        }
        .adm-dot {
            width: 7px;
            height: 7px;
            border-radius: 1px;
            flex-shrink: 0;
        }
        .adm-dot--ok { background: var(--adm-pos); }
        .adm-dot--warn { background: var(--adm-warn); }
        .adm-dot--danger { background: var(--adm-neg); }
        .adm-dot--muted { background: #94a3b8; }

        /* Underline Tabs */
        .adm-filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }
        .adm-tabs {
            display: flex;
            gap: 20px;
            border-bottom: 1px solid var(--adm-hairline);
            overflow-x: auto;
        }
        .adm-tab {
            position: relative;
            padding: 8px 0;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--adm-text-muted);
            text-decoration: none;
            white-space: nowrap;
        }
        .adm-tab.active {
            color: var(--adm-ink);
        }
        .adm-tab.active::after {
            content: "";
            position: absolute;
            inset: auto 0 0;
            border-block-end: 2px solid var(--adm-sky);
        }

        /* Clean Responsive Data Table */
        .adm-card-flush {
            padding: 0;
            overflow: auto;
            max-inline-size: 100%;
            container-type: inline-size;
        }
        .adm-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            text-align: left;
            font-size: 0.875rem;
        }
        .adm-table thead {
            position: sticky;
            inset-block-start: 0;
            z-index: 1;
        }
        .adm-table th {
            padding: 10px 16px;
            background: var(--adm-raised);
            border-bottom: 1px solid var(--adm-hairline);
            color: var(--adm-text-muted);
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
        }
        .adm-table td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--adm-hairline);
            vertical-align: middle;
        }
        .adm-table tbody tr:last-child td { border-bottom: none; }
        @media (hover: hover) and (pointer: fine) {
            .adm-table tbody tr:hover { background: #f7fbfe; }
        }
        .adm-row-action {
            border: 1px solid transparent;
            background: transparent;
            color: var(--adm-text-secondary);
            font-family: var(--adm-sans);
            font-size: 0.8125rem;
            font-weight: 500;
            padding: 4px 10px;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        @media (hover: hover) and (pointer: fine) {
            .adm-row-action:hover { background: var(--adm-raised); color: var(--adm-ink); }
            .adm-row-action--danger:hover { background: #fef2f2; color: var(--adm-neg); }
        }
        .adm-row-action--ok {
            background: var(--adm-ink);
            color: #fff;
        }
        @media (hover: hover) and (pointer: fine) {
            .adm-row-action--ok:hover { background: #1e3f63; color: #fff; }
        }

        .adm-list-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid var(--adm-hairline);
        }
        .adm-list-row:last-child { border-bottom: none; padding-bottom: 0; }
        .adm-list-row:first-child { padding-top: 0; }

        .adm-dialog {
            border: 1px solid var(--adm-hairline-strong);
            border-radius: 6px;
            padding: 24px;
            width: min(480px, calc(100vw - 32px));
            max-height: calc(100dvh - 32px);
            background: var(--adm-surface);
            box-shadow: 0 8px 24px rgba(15, 34, 56, 0.16);
            margin: auto;
        }
        dialog:modal {
            overscroll-behavior: contain;
        }
        dialog::backdrop,
        .adm-dialog::backdrop {
            background: rgba(15, 34, 56, 0.42);
            overflow: hidden;
            overscroll-behavior: contain;
        }

        .adm-mobile-backdrop {
            display: none;
        }
        .adm-mobile-close {
            display: none;
            border: 1px solid rgba(88, 202, 243, 0.25);
            background: transparent;
            color: rgba(255, 255, 255, 0.8);
            border-radius: 8px;
            width: 34px;
            height: 34px;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        @media (max-width: 960px) {
            .adm-shell { grid-template-columns: 1fr; }
            .adm-topbar { display: none; }
            .adm-mobile-bar { display: flex; }
            .adm-mobile-close { display: inline-flex; }
            .adm-mobile-backdrop.is-open {
                display: block;
                position: fixed;
                inset: 0;
                background: rgba(15, 34, 56, 0.45);
                z-index: 55;
            }
            .adm-sidebar {
                position: fixed;
                inset: 0 auto 0 0;
                width: min(264px, 82dvw);
                height: 100svh;
                z-index: 60;
                transform: translateX(-100%);
                transition: transform 180ms var(--adm-ease);
            }
            .adm-sidebar.is-open { transform: translateX(0); }
            .adm-content { padding: 16px 16px 32px; }
        }
        @media (prefers-reduced-motion: reduce) {
            .adm-body .btn, .adm-nav-link, .adm-sidebar { transition: none; }
        }
        @media (pointer: coarse) {
            .adm-body .btn, .adm-nav-link, .adm-row-action { min-height: 44px; }
        }
    </style>

    @stack('styles')
</head>
<body class="adm-body" x-data="{ navOpen: false }" @keydown.escape.window="navOpen = false">
    <a href="#main-content" class="skip-link">Langkau ke kandungan utama</a>

    @php
        $adminUser = Auth::guard('web')->user();
        $pendingTeachersBadge = \App\Models\User::where('role', \App\Models\User::ROLE_TEACHER)
            ->where('status', \App\Models\User::STATUS_PENDING)
            ->count();
    @endphp

    <div class="adm-mobile-backdrop" :class="{ 'is-open': navOpen }" @click="navOpen = false" aria-hidden="true"></div>

    <div class="adm-shell">
        <aside id="adm-sidebar" class="adm-sidebar" :class="{ 'is-open': navOpen }" aria-label="Menu pentadbir">
            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 8px;">
                <a href="{{ route('admin.dashboard') }}" class="adm-brand" style="flex: 1;">
                    <img src="{{ asset('media/brand/signcilik-logo-dark.svg') }}" alt="Sign Cilik" class="adm-brand-logo">
                    <div class="adm-brand-sub">Portal pentadbir</div>
                </a>
                <button type="button" class="adm-mobile-close" @click="navOpen = false" aria-label="Tutup menu">&times;</button>
            </div>

            <nav class="adm-nav" aria-label="Navigasi pentadbir">
                <div class="adm-nav-group-label" style="padding-top: 4px;">Pengurusan</div>
                <a href="{{ route('admin.dashboard') }}" class="adm-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>
                    <span class="adm-nav-link-main">
                        <svg class="adm-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
                        <span>Papan pemuka</span>
                    </span>
                </a>
                <a href="{{ route('admin.users') }}" class="adm-nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}" @if(request()->routeIs('admin.users*')) aria-current="page" @endif>
                    <span class="adm-nav-link-main">
                        <svg class="adm-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <span>Pengguna &amp; guru</span>
                    </span>
                    @if($pendingTeachersBadge > 0)
                        <span class="adm-nav-count" title="Guru menunggu kelulusan">{{ $pendingTeachersBadge }}</span>
                    @endif
                </a>
                <a href="{{ route('admin.students') }}" class="adm-nav-link {{ request()->routeIs('admin.students*') ? 'active' : '' }}" @if(request()->routeIs('admin.students*')) aria-current="page" @endif>
                    <span class="adm-nav-link-main">
                        <svg class="adm-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 1 0-16 0"/></svg>
                        <span>Profil murid</span>
                    </span>
                </a>

                <div class="adm-nav-group-label">Kurikulum &amp; data AI</div>
                <a href="{{ route('admin.signs') }}" class="adm-nav-link {{ request()->routeIs('admin.signs*') ? 'active' : '' }}" @if(request()->routeIs('admin.signs*')) aria-current="page" @endif>
                    <span class="adm-nav-link-main">
                        <svg class="adm-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>
                        <span>Kurikulum BIM</span>
                    </span>
                </a>
                <a href="{{ route('admin.ai.studio') }}" class="adm-nav-link {{ request()->routeIs('admin.ai.*') ? 'active' : '' }}" @if(request()->routeIs('admin.ai.*')) aria-current="page" @endif>
                    <span class="adm-nav-link-main">
                        <svg class="adm-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                        <span>Studio latihan AI</span>
                    </span>
                </a>

                <div class="adm-nav-group-label">Lain</div>
                <a href="{{ route('home') }}" class="adm-nav-link">
                    <span class="adm-nav-link-main">
                        <svg class="adm-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        <span>Laman utama</span>
                    </span>
                </a>
            </nav>

            <div class="adm-sidebar-footer">
                <div class="adm-profile">
                    <div style="min-width: 0;">
                        <div style="font-size: 0.8125rem; font-weight: 600; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $adminUser->name ?? 'Pentadbir' }}</div>
                        <div style="font-size: 0.75rem; color: rgba(255, 255, 255, 0.62); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $adminUser->email ?? '' }}</div>
                    </div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="adm-logout-btn" title="Log keluar" aria-label="Log keluar">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <div class="adm-main-wrap">
            <div class="adm-mobile-bar">
                <button type="button" @click="navOpen = !navOpen" :aria-expanded="navOpen.toString()" aria-controls="adm-sidebar" style="border: 1px solid rgba(88, 202, 243, 0.3); background: var(--adm-ink); color: #fff; border-radius: 8px; padding: 6px 10px; cursor: pointer;" aria-label="Buka menu">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <a href="{{ route('admin.dashboard') }}" style="display: inline-flex; align-items: center;">
                    <img src="{{ asset('media/brand/signcilik-logo-dark.svg') }}" alt="Sign Cilik" style="height: 26px; width: auto; display: block;">
                </a>
                <a href="{{ route('admin.ai.studio') }}" style="color: #58caf3; font-size: 0.8125rem; font-weight: 600; text-decoration: none;">Studio AI</a>
            </div>

            <div class="adm-topbar">
                <div class="adm-topbar-left">
                    <span class="adm-status">
                        <span class="adm-dot adm-dot--ok" aria-hidden="true"></span>
                        <span>Sistem aktif</span>
                    </span>
                    <span aria-hidden="true" style="color: var(--adm-hairline-strong);">/</span>
                    <span class="adm-topbar-crumb">{{ str_replace(' — Sign Cilik', '', $title ?? 'Portal pentadbir') }}</span>
                </div>
                <div class="adm-topbar-right">
                    <span class="adm-mono" style="color: var(--adm-text-muted); font-size: 0.85rem;">{{ now()->format('d/m/Y') }}</span>
                    @if($pendingTeachersBadge > 0)
                        <a href="{{ route('admin.users', ['role' => 'teacher', 'status' => 'pending']) }}" class="adm-status" style="text-decoration: none; color: var(--adm-ink);">
                            <span class="adm-dot adm-dot--warn" aria-hidden="true"></span>
                            <span>{{ $pendingTeachersBadge }} guru menunggu</span>
                        </a>
                    @endif
                    @if(!request()->routeIs('admin.ai.*'))
                        <a href="{{ route('admin.ai.studio') }}" style="color: var(--adm-accent); font-weight: 600; text-decoration: none;">Studio AI &rarr;</a>
                    @endif
                </div>
            </div>

            <main class="adm-content" id="main-content" tabindex="-1">
                @if(session('success'))
                    <div class="alert alert-success" role="alert" style="margin-bottom: 16px; border-radius: 10px; padding: 10px 14px; font-size: 0.875rem; font-weight: 500;">
                        <span>{{ session('success') }}</span>
                        <button type="button" class="alert-dismiss" aria-label="Tutup makluman" onclick="this.closest('.alert').remove()">&times;</button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-error" role="alert" style="margin-bottom: 16px; border-radius: 10px; padding: 10px 14px; font-size: 0.875rem; font-weight: 500;">
                        <span>{{ session('error') }}</span>
                        <button type="button" class="alert-dismiss" aria-label="Tutup makluman" onclick="this.closest('.alert').remove()">&times;</button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
    <script type="module">
        // Feature-detected Invoker Commands polyfill & <dialog closedby="any"> Safari fallback
        if (!('commandForElement' in HTMLButtonElement.prototype)) {
            import('https://esm.run/invokers-polyfill').catch(() => {});
        }
        if (!('closedBy' in HTMLDialogElement.prototype)) {
            const isOutside = (e, dlg) => {
                const r = dlg.getBoundingClientRect();
                return e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom;
            };
            for (const dlg of document.querySelectorAll('dialog[closedby="any"]')) {
                let pressedOutside = false;
                dlg.addEventListener('pointerdown', (e) => { pressedOutside = isOutside(e, dlg); });
                dlg.addEventListener('pointerup', (e) => { if (pressedOutside && isOutside(e, dlg)) dlg.close(); });
            }
        }
    </script>
</body>
</html>
