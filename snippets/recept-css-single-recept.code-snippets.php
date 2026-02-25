<?php

/**
 * Recept CSS (single-recept) – Premium v1
 */
function recept_single_inline_styles() {
    if ( ! is_singular( 'recept' ) ) { return; }

    $css = <<<CSS

/* ═══════════════════════════════════════════════
   DESIGN TOKENS
   ═══════════════════════════════════════════════ */
:root {
    --r-accent: #2d8a63;
    --r-accent-dark: #1e6b4a;
    --r-accent-soft: rgba(45, 138, 99, 0.06);
    --r-accent-glow: rgba(45, 138, 99, 0.18);
    --r-accent-light: #e8f5ee;

    --r-bg: #f5f6f8;
    --r-card: #ffffff;
    --r-border: #e5e7eb;
    --r-border-light: #f0f1f3;

    --r-text: #111827;
    --r-text-secondary: #374151;
    --r-text-muted: #6b7280;

    --r-radius: 16px;
    --r-radius-sm: 10px;
    --r-radius-xs: 6px;

    --r-shadow-sm: 0 1px 2px rgba(0,0,0,0.04);
    --r-shadow: 0 1px 4px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.03);
    --r-shadow-lg: 0 2px 8px rgba(0,0,0,0.05), 0 8px 32px rgba(0,0,0,0.04);
    --r-shadow-xl: 0 4px 16px rgba(0,0,0,0.06), 0 16px 48px rgba(0,0,0,0.05);

    --r-transition: 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    --r-transition-slow: 0.35s cubic-bezier(0.4, 0, 0.2, 1);

    --r-focus-ring: 0 0 0 3px var(--r-accent-glow);
}

/* ═══════════════════════════════════════════════
   BASE
   ═══════════════════════════════════════════════ */
.recept-single {
    max-width: 1180px;
    margin: 0 auto;
    padding: 0 24px 64px;
    color: var(--r-text);
    font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', Roboto, sans-serif;
    line-height: 1.6;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}
.recept-single *, .recept-single *::before, .recept-single *::after {
    box-sizing: border-box;
}
.recept-single h1 {
    text-align: center;
    font-size: clamp(1.75rem, 4vw, 2.5rem);
    font-weight: 800;
    letter-spacing: -0.025em;
    line-height: 1.2;
    margin-bottom: 28px;
    color: var(--r-text);
}

/* ═══════════════════════════════════════════════
   HERO
   ═══════════════════════════════════════════════ */
.recept-hero {
    position: relative;
    width: 100%;
    height: 400px;
    background-size: cover;
    background-position: center;
    border-radius: var(--r-radius);
    overflow: hidden;
    margin-bottom: 32px;
    box-shadow: var(--r-shadow-lg);
}
.recept-hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        to top,
        rgba(0, 0, 0, 0.72) 0%,
        rgba(0, 0, 0, 0.18) 50%,
        rgba(0, 0, 0, 0.06) 100%
    );
    display: flex;
    align-items: flex-end;
    padding: 40px 36px;
}
.recept-hero-overlay h1 {
    color: #fff;
    text-align: left;
    margin: 0;
    text-shadow: 0 1px 8px rgba(0,0,0,0.4);
    font-size: clamp(1.6rem, 3.5vw, 2.5rem);
    line-height: 1.2;
    letter-spacing: -0.02em;
    max-width: 680px;
}

/* ═══════════════════════════════════════════════
   TOAST
   ═══════════════════════════════════════════════ */
.recept-toast {
    display: none;
    position: fixed;
    bottom: 28px;
    left: 50%;
    transform: translateX(-50%) translateY(16px);
    background: var(--r-text);
    color: #fff;
    padding: 12px 28px;
    border-radius: var(--r-radius-sm);
    font-size: 0.85rem;
    font-weight: 600;
    box-shadow: var(--r-shadow-xl);
    opacity: 0;
    transition: opacity 0.3s ease, transform 0.3s ease;
    z-index: 9999;
    pointer-events: none;
}
.recept-toast.is-visible {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}

/* ═══════════════════════════════════════════════
   SHARE PANEL (modal)
   ═══════════════════════════════════════════════ */
.share-panel {
    position: fixed;
    inset: 0;
    background: rgba(17, 24, 39, 0.5);
    z-index: 9998;
    display: none;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
}
.share-panel.is-open { display: flex; }
.share-panel-inner {
    background: var(--r-card);
    border-radius: var(--r-radius);
    padding: 32px;
    max-width: 480px;
    width: 92%;
    position: relative;
    box-shadow: var(--r-shadow-xl);
}
.share-close {
    position: absolute;
    top: 16px; right: 18px;
    width: 32px; height: 32px;
    display: flex; align-items: center; justify-content: center;
    background: var(--r-bg); border: 1px solid var(--r-border);
    border-radius: 50%;
    font-size: 0.85rem;
    cursor: pointer;
    color: var(--r-text-muted);
    transition: all var(--r-transition);
    line-height: 1;
}
.share-close:hover { color: var(--r-text); background: var(--r-border-light); }
.share-close:focus-visible { box-shadow: var(--r-focus-ring); outline: none; }
.share-title {
    margin: 0 0 20px;
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--r-text);
}
.share-textarea {
    width: 100%;
    border: 1px solid var(--r-border);
    border-radius: var(--r-radius-sm);
    padding: 14px;
    font-size: 0.82rem;
    resize: none;
    font-family: inherit;
    color: var(--r-text);
    background: var(--r-bg);
    margin-bottom: 18px;
    line-height: 1.6;
}
.share-textarea:focus { outline: none; border-color: var(--r-accent); box-shadow: var(--r-focus-ring); }
.share-buttons { display: flex; flex-wrap: wrap; gap: 8px; }
.share-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 18px;
    border-radius: var(--r-radius-sm);
    font-size: 0.8rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all var(--r-transition);
    border: 1px solid var(--r-border);
    background: var(--r-card);
    color: var(--r-text);
}
.share-btn:hover { border-color: var(--r-accent); color: var(--r-accent-dark); background: var(--r-accent-soft); }
.share-btn:focus-visible { box-shadow: var(--r-focus-ring); outline: none; }
.share-btn--copy { background: var(--r-accent); color: #fff; border-color: var(--r-accent); }
.share-btn--copy:hover { background: var(--r-accent-dark); border-color: var(--r-accent-dark); color: #fff; }
.share-btn--whatsapp:hover { border-color: #25d366; color: #128c7e; }
.share-btn--email:hover { border-color: #6366f1; color: #4f46e5; }

/* ═══════════════════════════════════════════════
   META BAR
   ═══════════════════════════════════════════════ */
.recept-meta {
    display: flex;
    gap: 0;
    margin-bottom: 32px;
    border-radius: var(--r-radius);
    overflow: hidden;
    background: var(--r-card);
    border: 1px solid var(--r-border);
    box-shadow: var(--r-shadow-sm);
}
.recept-meta p {
    flex: 1;
    margin: 0;
    padding: 14px 20px;
    text-align: center;
    font-size: 0.88rem;
    font-weight: 500;
    color: var(--r-text-secondary);
    border-right: 1px solid var(--r-border);
    line-height: 1.4;
}
.recept-meta p:last-child { border-right: none; }
.recept-meta p strong { color: var(--r-text); font-weight: 700; }

/* ═══════════════════════════════════════════════
   LAYOUT (two-column grid)
   ═══════════════════════════════════════════════ */
.recept-layout {
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 28px;
    align-items: start;
}

/* ═══════════════════════════════════════════════
   SIDEBAR
   ═══════════════════════════════════════════════ */
.recept-sidebar {
    position: sticky;
    top: 100px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.sidebar-section {
    background: var(--r-card);
    border-radius: var(--r-radius);
    border: 1px solid var(--r-border);
    box-shadow: var(--r-shadow-sm);
    overflow: hidden;
}
.sidebar-section-title {
    padding: 12px 18px;
    margin: 0;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--r-text-muted);
    background: var(--r-bg);
    border-bottom: 1px solid var(--r-border);
}
.sidebar-cards { display: flex; flex-direction: column; }
.sidebar-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    text-decoration: none;
    color: var(--r-text);
    transition: background var(--r-transition);
    border-bottom: 1px solid var(--r-border-light);
}
.sidebar-card:last-child { border-bottom: none; }
.sidebar-card:hover { background: var(--r-accent-soft); }
.sidebar-card-img {
    width: 48px; height: 48px;
    border-radius: var(--r-radius-xs);
    overflow: hidden;
    flex-shrink: 0;
    background: var(--r-bg);
}
.sidebar-card-img img { width: 100%; height: 100%; object-fit: cover; }
.sidebar-card-img-ph {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem;
    color: var(--r-text-muted);
}
.sidebar-card-body {
    flex: 1; min-width: 0;
    display: flex; flex-direction: column; gap: 3px;
}
.sidebar-card-title {
    font-size: 0.82rem;
    font-weight: 600;
    line-height: 1.35;
    color: var(--r-text);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.sidebar-card-meta { font-size: 0.72rem; color: var(--r-text-muted); }
.sidebar-card-meta strong { color: var(--r-accent-dark); font-weight: 700; }

.sidebar-kat-chips { padding: 14px 16px; display: flex; flex-wrap: wrap; gap: 6px; }
.sidebar-kat-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    border-radius: var(--r-radius-xs);
    border: 1px solid var(--r-border);
    background: var(--r-card);
    color: var(--r-text-secondary);
    font-size: 0.72rem;
    font-weight: 600;
    text-decoration: none;
    transition: all var(--r-transition);
}
.sidebar-kat-chip:hover {
    border-color: var(--r-accent);
    background: var(--r-accent-soft);
    color: var(--r-accent-dark);
}
.sidebar-kat-count {
    font-size: 0.62rem;
    background: var(--r-bg);
    color: var(--r-text-muted);
    padding: 1px 6px;
    border-radius: var(--r-radius-xs);
    font-weight: 700;
}
.sidebar-kat-chip--all {
    background: var(--r-accent);
    color: #fff;
    border-color: var(--r-accent);
    width: 100%;
    justify-content: center;
    margin-top: 4px;
}
.sidebar-kat-chip--all:hover { background: var(--r-accent-dark); border-color: var(--r-accent-dark); color: #fff; }

/* ═══════════════════════════════════════════════
   MAIN CONTENT – section card system
   ═══════════════════════════════════════════════ */
.recept-main-content { min-width: 0; }

.recept-content-wrap {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-bottom: 28px;
    background: transparent;
    border: none;
    box-shadow: none;
    border-radius: 0;
    overflow: visible;
}

.recept-section {
    background: var(--r-card);
    border-radius: var(--r-radius);
    border: 1px solid var(--r-border);
    box-shadow: var(--r-shadow);
    overflow: hidden;
}

.recept-section-header {
    padding: 16px 24px;
    margin: 0;
    font-size: 0.82rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--r-text);
    background: var(--r-bg);
    border-bottom: 1px solid var(--r-border);
    display: flex;
    align-items: center;
    gap: 12px;
    border-top: none;
}
.recept-section:first-child .recept-section-header {
    border-top: none;
    border-radius: var(--r-radius) var(--r-radius) 0 0;
}
.recept-section-body {
    padding: 24px;
}
.recept-section:last-child .recept-section-body { border-bottom: none; }
.recept-section-body--lepesek { padding: 0; }
.recept-content-wrap h2 { all: unset; display: block; }

/* ═══════════════════════════════════════════════
   TOOLBAR (print / share buttons)
   ═══════════════════════════════════════════════ */
.hozzavalo-toolbar {
    display: flex;
    gap: 8px;
    padding: 12px 24px;
    background: var(--r-card);
    border-bottom: 1px solid var(--r-border);
}
.toolbar-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 16px;
    border-radius: var(--r-radius-xs);
    border: 1px solid var(--r-border);
    background: var(--r-card);
    color: var(--r-text-secondary);
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    transition: all var(--r-transition);
}
.toolbar-btn:hover {
    border-color: var(--r-accent);
    color: var(--r-accent-dark);
    background: var(--r-accent-soft);
}
.toolbar-btn:focus-visible { box-shadow: var(--r-focus-ring); outline: none; }

/* ═══════════════════════════════════════════════
   ADAG STEPPER
   ═══════════════════════════════════════════════ */
.adag-stepper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    margin-bottom: 24px;
}
.adag-stepper-label {
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--r-text-muted);
}
.adag-stepper-controls {
    display: flex;
    align-items: center;
    gap: 14px;
}
.adag-stepper-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: 1.5px solid var(--r-border);
    background: var(--r-card);
    color: var(--r-text-muted);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all var(--r-transition);
    padding: 0;
    flex-shrink: 0;
}
.adag-stepper-btn svg {
    width: 14px;
    height: 14px;
}
.adag-stepper-btn svg line {
    stroke: var(--r-text-muted);
    stroke-width: 2.5;
    stroke-linecap: round;
    transition: stroke var(--r-transition);
}
.adag-stepper-btn:hover {
    background: var(--r-accent);
    border-color: var(--r-accent);
    color: #fff;
}
.adag-stepper-btn:hover svg line { stroke: #fff; }
.adag-stepper-btn:active { transform: scale(0.92); }
.adag-stepper-btn:focus-visible { box-shadow: var(--r-focus-ring); outline: none; }

.adag-stepper-controls input#adagok-input {
    width: 52px;
    height: 52px;
    text-align: center;
    border: 1.5px solid var(--r-border);
    border-radius: var(--r-radius-sm);
    background: var(--r-card);
    font-weight: 800;
    font-size: 1.25rem;
    color: var(--r-text);
    padding: 0;
    -moz-appearance: textfield;
    transition: all var(--r-transition);
}
.adag-stepper-controls input#adagok-input::-webkit-outer-spin-button,
.adag-stepper-controls input#adagok-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
.adag-stepper-controls input#adagok-input:focus {
    outline: none;
    border-color: var(--r-accent);
    box-shadow: var(--r-focus-ring);
}

/* ═══════════════════════════════════════════════
   ÖSSZETEVŐK LISTA
   ═══════════════════════════════════════════════ */
.recept-osszetevok { list-style: none; margin: 0; padding: 0; }
.osszetevo-sor {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 16px;
    transition: all var(--r-transition);
    border-left: 3px solid transparent;
    border-bottom: 1px solid var(--r-border-light);
}
.osszetevo-sor:last-child { border-bottom: none; }
.osszetevo-sor:hover { background: var(--r-bg); }
.osszetevo-sor.is-modified {
    border-left-color: var(--r-accent);
    background: var(--r-accent-soft);
}
.osszetevo-checkbox-label { flex-shrink: 0; display: flex; align-items: center; cursor: pointer; }
.osszetevo-checkbox-label input[type="checkbox"] {
    width: 18px; height: 18px;
    accent-color: var(--r-accent);
    cursor: pointer;
    border-radius: 4px;
}
.osszetevo-checkbox-label input[type="checkbox"]:focus-visible { outline: 2px solid var(--r-accent); outline-offset: 2px; }
.osszetevo-mennyiseg-wrap { display: flex; align-items: center; gap: 5px; flex-shrink: 0; }
.osszetevo-mennyiseg-input {
    width: 64px;
    padding: 6px 8px;
    border: 1px solid var(--r-border);
    border-radius: var(--r-radius-xs);
    text-align: center;
    font-size: 0.88rem;
    font-weight: 600;
    background: var(--r-card);
    transition: all var(--r-transition);
    font-variant-numeric: tabular-nums;
    -moz-appearance: textfield;
    color: var(--r-text);
}
.osszetevo-mennyiseg-input::-webkit-outer-spin-button,
.osszetevo-mennyiseg-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.osszetevo-mennyiseg-input:focus {
    outline: none;
    border-color: var(--r-accent);
    box-shadow: var(--r-focus-ring);
}
.osszetevo-mertekegyseg {
    font-size: 0.8rem;
    color: var(--r-text-muted);
    font-weight: 600;
    min-width: 24px;
}
.osszetevo-nev {
    font-size: 0.92rem;
    flex: 1;
    color: var(--r-text);
    line-height: 1.4;
}
.osszetevo-nev em { color: var(--r-text-muted); font-size: 0.82rem; font-style: italic; }
.osszetevo-sor:has(input[type="checkbox"]:checked) .osszetevo-nev {
    text-decoration: line-through;
    opacity: 0.35;
}
.osszetevo-sor:has(input[type="checkbox"]:checked) .osszetevo-mennyiseg-input { opacity: 0.35; }
.osszetevo-sor:has(input[type="checkbox"]:checked) .osszetevo-mertekegyseg { opacity: 0.35; }

/* ═══════════════════════════════════════════════
   RESET BUTTON
   ═══════════════════════════════════════════════ */
.alaphelyzet-btn {
    display: block;
    margin: 20px auto 0;
    background: var(--r-card);
    border: 1px solid var(--r-border);
    padding: 8px 24px;
    border-radius: var(--r-radius-xs);
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--r-text-muted);
    cursor: pointer;
    transition: all var(--r-transition);
    opacity: 0;
    pointer-events: none;
    transform: translateY(-6px);
}
.alaphelyzet-btn.is-visible { opacity: 1; pointer-events: auto; transform: translateY(0); }
.alaphelyzet-btn:hover { border-color: #dc2626; color: #dc2626; background: rgba(220,38,38,0.04); }
.alaphelyzet-btn:focus-visible { box-shadow: var(--r-focus-ring); outline: none; }

/* ═══════════════════════════════════════════════
   MAKRÓ – card grid
   ═══════════════════════════════════════════════ */
.recept-section-body--makro { padding: 20px 24px; }

.makro-warning {
    background: rgba(220, 38, 38, 0.05);
    border-left: 3px solid #dc2626;
    padding: 10px 16px;
    margin-bottom: 14px;
    font-size: 0.82rem;
    border-radius: 0 var(--r-radius-xs) var(--r-radius-xs) 0;
    color: #991b1b;
    line-height: 1.5;
}

.makro-strip {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
}
.makro-strip-divider { display: none; }

.makro-strip-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 16px 8px;
    background: var(--r-bg);
    border: 1px solid var(--r-border-light);
    border-radius: var(--r-radius-sm);
    transition: border-color var(--r-transition);
}
.makro-strip-item:hover { border-color: var(--r-border); }

.makro-strip-item:first-child {
    background: var(--r-accent-soft);
    border-color: var(--r-accent-light);
}

.makro-strip-value {
    font-size: 1.3rem;
    font-weight: 800;
    color: var(--r-text);
    line-height: 1;
    font-variant-numeric: tabular-nums;
}
.makro-strip-item:first-child .makro-strip-value {
    color: var(--r-accent-dark);
    font-size: 1.45rem;
}
.makro-strip-label {
    font-size: 0.62rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--r-text-muted);
    font-weight: 600;
    text-align: center;
}
.makro-strip-pct {
    font-size: 0.68rem;
    font-weight: 700;
    color: var(--r-accent-dark);
    background: var(--r-accent-light);
    padding: 2px 10px;
    border-radius: var(--r-radius-xs);
    margin-top: 2px;
}

/* Per-adag row */
.makro-per-adag {
    display: none;
    text-align: center;
    margin-top: 14px;
    padding: 10px 16px;
    background: var(--r-bg);
    border-radius: var(--r-radius-xs);
    font-size: 0.78rem;
    color: var(--r-text-muted);
    border: 1px solid var(--r-border-light);
}
.makro-per-adag strong { color: var(--r-text); font-weight: 700; }

/* ═══════════════════════════════════════════════
   MAKRÓ INFO-BOX
   ═══════════════════════════════════════════════ */
.makro-info {
    margin-top: 14px;
    padding: 12px 16px 12px 18px;
    background: var(--r-bg);
    border-left: 3px solid var(--r-accent);
    border-radius: 0 var(--r-radius-xs) var(--r-radius-xs) 0;
    font-size: 0.76rem;
    line-height: 1.55;
    color: var(--r-text-muted);
}
.makro-info p { margin: 0; }
.makro-info p + p { margin-top: 5px; }
.makro-info strong { color: var(--r-accent-dark); font-weight: 700; }
.makro-info-factors { font-size: 0.7rem; opacity: 0.8; }

/* ═══════════════════════════════════════════════
   PROGRESS BAR (in Elkészítés header)
   ═══════════════════════════════════════════════ */
.elkeszites-progress-wrap {
    flex: 1;
    height: 6px;
    min-width: 80px;
    background: var(--r-border);
    border-radius: 999px;
    overflow: hidden;
    margin-left: 8px;
}
.elkeszites-progress-bar {
    display: block;
    height: 100%;
    width: 0%;
    background: var(--r-accent);
    border-radius: 999px;
    transition: width 0.4s ease;
}
.elkeszites-progress-text {
    font-size: 0.7rem;
    font-weight: 700;
    color: var(--r-accent-dark);
    min-width: 36px;
    text-align: right;
    flex-shrink: 0;
}

/* ═══════════════════════════════════════════════
   ELKÉSZÍTÉS LÉPÉSEK
   ═══════════════════════════════════════════════ */
.recept-lepesek {
    counter-reset: lepes;
    list-style: none;
    margin: 0;
    padding: 0;
}
.recept-lepesek li {
    position: relative;
    padding: 20px 24px 20px 64px;
    border-bottom: 1px solid var(--r-border-light);
    font-size: 0.92rem;
    line-height: 1.7;
    transition: all var(--r-transition);
    cursor: pointer;
    color: var(--r-text-secondary);
}
.recept-lepesek li:last-child { border-bottom: none; }
.recept-lepesek li:hover { background: var(--r-bg); }

.recept-lepesek li::before {
    counter-increment: lepes;
    content: counter(lepes);
    position: absolute;
    left: 20px;
    top: 20px;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: var(--r-accent);
    color: #fff;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.78rem;
    transition: background var(--r-transition);
}

.recept-lepesek li.lepes-done {
    opacity: 0.4;
}
.recept-lepesek li.lepes-done .lepes-text {
    text-decoration: line-through;
}
.recept-lepesek li.lepes-done::before {
    background: var(--r-text-muted);
}
.recept-lepesek li.lepes-done::after {
    content: '';
    position: absolute;
    left: 20px;
    top: 20px;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: var(--r-text-muted) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 16 16' fill='none'%3E%3Cpath d='M3 8.5L6.5 12L13 4' stroke='white' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") center center no-repeat;
    z-index: 1;
}

.lepes-checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 0;
    cursor: pointer;
}
.lepes-checkbox {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}
.lepes-text { display: block; }

/* ═══════════════════════════════════════════════
   TARTALOM (WP editor content block)
   ═══════════════════════════════════════════════ */
.recept-tartalom {
    background: var(--r-card);
    border-radius: var(--r-radius);
    box-shadow: var(--r-shadow);
    border: 1px solid var(--r-border);
    padding: 28px;
    line-height: 1.75;
    font-size: 0.95rem;
    color: var(--r-text-secondary);
}

/* ═══════════════════════════════════════════════
   ENTRANCE ANIMATION
   ═══════════════════════════════════════════════ */
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}
.recept-single article > * {
    animation: fadeInUp 0.3s ease-out both;
}

/* ═══════════════════════════════════════════════
   RESPONSIVE – 1024
   ═══════════════════════════════════════════════ */
@media (max-width: 1024px) {
    .recept-layout {
        grid-template-columns: 240px 1fr;
        gap: 20px;
    }
}

/* ═══════════════════════════════════════════════
   RESPONSIVE – 768
   ═══════════════════════════════════════════════ */
@media (max-width: 768px) {
    .recept-single { padding: 0 16px 32px; }

    .recept-layout { grid-template-columns: 1fr; }
    .recept-sidebar { position: static; order: 2; }
    .recept-main-content { order: 1; }

    .sidebar-cards {
        flex-direction: row;
        overflow-x: auto;
        scroll-snap-type: x mandatory;
        -webkit-overflow-scrolling: touch;
        gap: 0;
        padding-bottom: 4px;
    }
    .sidebar-card {
        flex: 0 0 200px;
        scroll-snap-align: start;
        flex-direction: column;
        padding: 12px;
        text-align: center;
        border-bottom: none;
        border-right: 1px solid var(--r-border-light);
    }
    .sidebar-card:last-child { border-right: none; }
    .sidebar-card-img { width: 100%; height: 80px; border-radius: var(--r-radius-xs); }

    .recept-hero {
        height: 260px;
        border-radius: 0;
        margin-left: -16px;
        margin-right: -16px;
        width: calc(100% + 32px);
    }
    .recept-hero-overlay { padding: 24px 20px; }
    .recept-hero-overlay h1 { font-size: 1.45rem; }

    .recept-meta { flex-direction: column; }
    .recept-meta p { border-right: none; border-bottom: 1px solid var(--r-border-light); }
    .recept-meta p:last-child { border-bottom: none; }

    .recept-section-body { padding: 20px; }
    .recept-section-body--makro { padding: 16px 20px; }

    .makro-strip {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    .hozzavalo-toolbar { padding: 10px 20px; gap: 6px; flex-wrap: wrap; }
    .toolbar-btn { font-size: 0.72rem; padding: 6px 12px; }
}

/* ═══════════════════════════════════════════════
   RESPONSIVE – 480
   ═══════════════════════════════════════════════ */
@media (max-width: 480px) {
    .recept-single { padding: 0 12px 24px; }

    .recept-hero {
        height: 200px;
        margin-left: -12px;
        margin-right: -12px;
        width: calc(100% + 24px);
    }
    .recept-hero-overlay { padding: 16px; }
    .recept-hero-overlay h1 { font-size: 1.25rem; }

    .recept-section-header { font-size: 0.76rem; padding: 14px 18px; }
    .recept-section-body { padding: 16px; }
    .recept-section-body--makro { padding: 14px 16px; }

    .osszetevo-sor { gap: 8px; padding: 8px 12px; }
    .osszetevo-mennyiseg-input { width: 54px; font-size: 0.82rem; padding: 5px 6px; }
    .osszetevo-nev { font-size: 0.85rem; }

    .makro-strip { gap: 8px; }
    .makro-strip-item { padding: 12px 6px; }
    .makro-strip-value { font-size: 1.1rem; }
    .makro-strip-item:first-child .makro-strip-value { font-size: 1.2rem; }
    .makro-strip-label { font-size: 0.58rem; }
    .makro-strip-pct { font-size: 0.62rem; padding: 1px 7px; }

    .makro-per-adag { font-size: 0.72rem; }
    .makro-info { font-size: 0.7rem; padding: 10px 14px 10px 16px; }

    .recept-lepesek li { padding: 16px 16px 16px 56px; font-size: 0.88rem; }
    .recept-lepesek li::before { left: 14px; top: 16px; width: 28px; height: 28px; font-size: 0.72rem; }
    .recept-lepesek li.lepes-done::after { left: 14px; top: 16px; width: 28px; height: 28px; }

    .sidebar-card { flex: 0 0 170px; }

    .adag-stepper-btn { width: 34px; height: 34px; }
    .adag-stepper-controls input#adagok-input { width: 46px; height: 46px; font-size: 1.1rem; }
    .adag-stepper-controls { gap: 10px; }

    .hozzavalo-toolbar { padding: 8px 16px; }
    .toolbar-btn { font-size: 0.68rem; padding: 5px 10px; }
}

/* ═══════════════════════════════════════════════
   PRINT
   ═══════════════════════════════════════════════ */
@media print {
    .recept-single { max-width: 100%; margin: 0; padding: 0; }
    .alaphelyzet-btn,
    .makro-warning,
    .makro-info,
    .recept-sidebar,
    .share-panel,
    .recept-toast,
    .hozzavalo-toolbar { display: none !important; }
    .recept-layout { grid-template-columns: 1fr; }
    .recept-section { box-shadow: none; border: 1px solid #ddd; }
    .recept-content-wrap { gap: 0; }
    .recept-single article > * { animation: none !important; }
    .osszetevo-mennyiseg-input { border: none; background: none; }
    .recept-lepesek li.lepes-done { opacity: 1; text-decoration: none; }
    .recept-hero { height: auto; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
}

CSS;

    wp_register_style( 'recept-inline-style', false );
    wp_enqueue_style( 'recept-inline-style' );
    wp_add_inline_style( 'recept-inline-style', $css );
}
add_action( 'wp_enqueue_scripts', 'recept_single_inline_styles' );
