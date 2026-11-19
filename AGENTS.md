<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>

## Sign Cilik UI Rulebook

When creating or editing any UI view (`resources/views/**`), stylesheet (`public/css/**`), or UI script, read and follow [docs/UI-RULEBOOK.md](./docs/UI-RULEBOOK.md) as the authoritative source of truth. Non-negotiable invariants:

- **Brand & Icons:** Always use **Sign Cilik** (never *GestureSpeak* in visible UI or `<title>`). Use `<x-sc-icon>` / `<x-sc-category-icon>` with custom SVGs in `public/media/icons/` — never use raw UI emojis in headings, buttons, stats, or badges.
- **Palette & Quiet Chrome:** Use tokens in `public/css/app.css` (`#16324f`, `#0f2238`, `#26b0e5`, `#58caf3`, `#ffe942`, `#fffdf7`). Avoid AI-slop purple (`#6c5ce7` / `#8b5cf6`), neon gradient text, `2px dashed` card borders, or heavy `4px–6px` brutalist box-shadows on standard chrome.
- **Typography & `CutePixel`:** `Fredoka` (`600`) for headings, `Nunito` (`400`–`700`) for body/labels, and `CutePixel` (`500`, `tabular-nums`) **only** for short telemetry, scores, student codes, PIN slots, and sign counts — never paragraphs.
- **Layout, Focus & Zero Overflow:** Stick to the `4px` spacing grid and nested radii (`20px -> 16/12px -> 8/10px`). Keep Kiko mascot scoped strictly to `learn.*` and `quiz.*`. Ensure `:focus-visible` outlines, `44px` coarse-pointer targets, `100dvh`, and `scrollWidth === clientWidth` at both `1440px` and `390px`.
