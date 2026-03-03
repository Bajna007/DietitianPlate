<?php

/**
 * 12 - Recept CSS (single-recept)
 */
/**
 * 12 - Recept CSS (single-recept) – CLEAN FINAL
 */
function recept_single_inline_styles() {
    if ( ! is_singular( 'recept' ) ) { return; }

    $css = <<<CSS

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
    --r-focus-ring: 0 0 0 3px var(--r-accent-glow);
}

/* ── OVERFLOW FIX – ez okozta a túlfolyást mobilon ── */
html, body { overflow-x: hidden; }

.recept-single {
    max-width: 1180px;
    margin: 0 auto;
    padding: 0 24px 64px;
    color: var(--r-text);
    font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', Roboto, sans-serif;
    line-height: 1.6;
    -webkit-font-smoothing: antialiased;
    overflow-x: hidden;
}
.recept-single *, .recept-single *::before, .recept-single *::after { box-sizing: border-box; }
.recept-single h1 {
    text-align: center;
    font-size: clamp(1.75rem, 4vw, 2.5rem);
    font-weight: 800;
    letter-spacing: -0.025em;
    line-height: 1.2;
    margin-bottom: 28px;
    color: var(--r-text);
}

/* HERO */
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
    background: linear-gradient(to top, rgba(0,0,0,0.72) 0%, rgba(0,0,0,0.18) 50%, rgba(0,0,0,0.06) 100%);
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

/* TOAST */
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
    max-width: calc(100vw - 32px);
}
.recept-toast.is-visible { opacity: 1; transform: translateX(-50%) translateY(0); }

/* SHARE PANEL */
.share-panel {
    position: fixed;
    inset: 0;
    background: rgba(17,24,39,0.5);
    z-index: 9998;
    display: none;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(6px);
    padding: 16px;
}
.share-panel.is-open { display: flex; }
.share-panel-inner {
    background: var(--r-card);
    border-radius: var(--r-radius);
    padding: 32px;
    max-width: 480px;
    width: 100%;
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
.share-title { margin: 0 0 20px; font-size: 1.05rem; font-weight: 700; color: var(--r-text); }
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
    display: inline-flex; align-items: center; gap: 6px;
    padding: 9px 18px; border-radius: var(--r-radius-sm);
    font-size: 0.8rem; font-weight: 600; text-decoration: none;
    cursor: pointer; transition: all var(--r-transition);
    border: 1px solid var(--r-border); background: var(--r-card); color: var(--r-text);
}
.share-btn:hover { border-color: var(--r-accent); color: var(--r-accent-dark); background: var(--r-accent-soft); }
.share-btn--copy { background: var(--r-accent); color: #fff; border-color: var(--r-accent); }
.share-btn--copy:hover { background: var(--r-accent-dark); border-color: var(--r-accent-dark); color: #fff; }

/* META BAR */
.recept-single .recept-meta {
    position: relative;
    display: flex;
    gap: 0;
    margin-bottom: 32px;
    border-radius: var(--r-radius);
    overflow: hidden;
    background: linear-gradient(135deg, rgba(45,138,99,0.03) 0%, rgba(45,138,99,0.07) 40%, rgba(45,138,99,0.03) 100%);
    border: 1px solid rgba(45,138,99,0.13);
    box-shadow: 0 1px 3px rgba(45,138,99,0.04), 0 4px 14px rgba(0,0,0,0.025);
    border-top: 2.5px solid rgba(45,138,99,0.35);
}
.recept-single .recept-meta::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%232d8a63' fill-opacity='0.02'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    pointer-events: none;
    z-index: 0;
}
.recept-single .recept-meta p {
    position: relative; z-index: 1; flex: 1; margin: 0;
    padding: 16px 22px; text-align: center; font-size: 0.88rem;
    font-weight: 500; color: var(--r-text-secondary);
    border-right: 1px solid rgba(45,138,99,0.10); line-height: 1.4;
}
.recept-single .recept-meta p:last-child { border-right: none; }
.recept-single .recept-meta p strong { color: var(--r-text); font-weight: 700; }

/* LAYOUT */
.recept-layout { display: grid; grid-template-columns: 260px 1fr; gap: 28px; align-items: start; }
/* Grid gyerekek min-width:0 – overflow fix */
.recept-sidebar, .recept-main-content { min-width: 0; }

/* SIDEBAR */
.recept-sidebar { position: sticky; top: 100px; display: flex; flex-direction: column; gap: 16px; }
.sidebar-section {
    background: var(--r-card); border-radius: var(--r-radius);
    border: 1px solid var(--r-border); box-shadow: var(--r-shadow-sm); overflow: hidden;
}
.sidebar-section-title {
    padding: 13px 18px; margin: 0; font-size: 0.7rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.08em; color: #fff;
    background: linear-gradient(135deg, rgba(45,138,99,0.85) 0%, rgba(35,120,82,0.92) 50%, rgba(45,138,99,0.85) 100%);
    border-bottom: 1px solid rgba(30,107,74,0.5);
    text-shadow: 0 1px 2px rgba(0,0,0,0.15);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.15), 0 1px 3px rgba(45,138,99,0.2);
}
.sidebar-cards { display: flex; flex-direction: column; }
.sidebar-card {
    display: flex; align-items: center; gap: 12px; padding: 12px 16px;
    text-decoration: none; color: var(--r-text); transition: background var(--r-transition);
    border-bottom: 1px solid var(--r-border-light);
}
.sidebar-card:last-child { border-bottom: none; }
.sidebar-card:hover { background: var(--r-accent-soft); }
.sidebar-card-img { width: 48px; height: 48px; border-radius: var(--r-radius-xs); overflow: hidden; flex-shrink: 0; background: var(--r-bg); }
.sidebar-card-img img { width: 100%; height: 100%; object-fit: cover; }
.sidebar-card-img-ph { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; color: var(--r-text-muted); }
.sidebar-card-body { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 3px; }
.sidebar-card-title { font-size: 0.82rem; font-weight: 600; line-height: 1.35; color: var(--r-text); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.sidebar-card-meta { font-size: 0.72rem; color: var(--r-text-muted); }
.sidebar-card-meta strong { color: var(--r-accent-dark); font-weight: 700; }
.sidebar-kat-chips { padding: 14px 16px; display: flex; flex-wrap: wrap; gap: 6px; }
.sidebar-kat-chip {
    display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px;
    border-radius: var(--r-radius-xs); border: 1px solid var(--r-border);
    background: var(--r-card); color: var(--r-text-secondary);
    font-size: 0.72rem; font-weight: 600; text-decoration: none; transition: all var(--r-transition);
}
.sidebar-kat-chip:hover { border-color: var(--r-accent); background: var(--r-accent-soft); color: var(--r-accent-dark); }
.sidebar-kat-count { font-size: 0.62rem; background: var(--r-bg); color: var(--r-text-muted); padding: 1px 6px; border-radius: var(--r-radius-xs); font-weight: 700; }
.sidebar-kat-chip--all { background: var(--r-accent); color: #fff; border-color: var(--r-accent); width: 100%; justify-content: center; margin-top: 4px; }
.sidebar-kat-chip--all:hover { background: var(--r-accent-dark); border-color: var(--r-accent-dark); color: #fff; }

/* MAIN CONTENT */
.recept-main-content { min-width: 0; overflow: hidden; }
.recept-content-wrap { display: flex; flex-direction: column; gap: 16px; margin-bottom: 28px; }
.recept-section { background: var(--r-card); border-radius: var(--r-radius); border: 1px solid var(--r-border); box-shadow: var(--r-shadow); overflow: hidden; }
.recept-section-header {
    padding: 16px 24px; margin: 0; font-size: 0.82rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.06em; color: #fff;
    background: linear-gradient(135deg, rgba(45,138,99,0.85) 0%, rgba(35,120,82,0.92) 50%, rgba(45,138,99,0.85) 100%);
    border-bottom: 1px solid rgba(30,107,74,0.5);
    display: flex; align-items: center; gap: 12px;
    text-shadow: 0 1px 2px rgba(0,0,0,0.15);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.15), 0 1px 3px rgba(45,138,99,0.2);
}
.recept-section:first-child .recept-section-header { border-radius: var(--r-radius) var(--r-radius) 0 0; }
.recept-section-body { padding: 24px; }
.recept-section-body--lepesek { padding: 0; }

/* TOOLBAR */
.hozzavalo-toolbar {
    display: flex; flex-wrap: wrap; gap: 8px;
    padding: 12px 24px; background: var(--r-card);
    border-bottom: 1px solid var(--r-border); align-items: center;
}
.toolbar-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 16px; border-radius: var(--r-radius-xs);
    border: 1px solid var(--r-border); background: var(--r-card);
    color: var(--r-text-secondary); font-size: 0.75rem; font-weight: 600;
    cursor: pointer; transition: all var(--r-transition);
    white-space: nowrap; outline: none; -webkit-appearance: none; line-height: 1.4;
}
.toolbar-btn:hover { border-color: var(--r-accent); color: var(--r-accent-dark); background: var(--r-accent-soft); }
.toolbar-btn:focus-visible { box-shadow: var(--r-focus-ring); outline: none; }

/* KEDVENC */
#btn-kedvenc:hover { border-color: #f87171 !important; color: #dc2626 !important; background: #fef2f2 !important; }
#btn-kedvenc[data-fav="1"] { border-color: #ef4444; background: #fef2f2; color: #dc2626; }
#btn-kedvenc[data-fav="1"]:hover { background: #fee2e2; border-color: #dc2626; }
#btn-kedvenc.dp-fav-loading { opacity: 0.5; pointer-events: none; }
@keyframes dpFavBounce {
    0%   { transform: scale(1); }
    20%  { transform: scale(0.8); }
    50%  { transform: scale(1.3); }
    70%  { transform: scale(0.9); }
    100% { transform: scale(1); }
}
#btn-kedvenc.dp-fav-bounce { animation: dpFavBounce 0.5s cubic-bezier(0.34, 1.56, 0.64, 1); }

/* KEDVENC TOAST */
.dp-fav-toast {
    position: fixed; bottom: 28px; left: 50%;
    transform: translateX(-50%) translateY(20px);
    display: flex; align-items: center; gap: 10px;
    padding: 14px 28px; border-radius: 14px;
    font-size: 0.88rem; font-weight: 700; z-index: 99999;
    pointer-events: none; opacity: 0;
    transition: opacity 0.3s ease, transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    box-shadow: 0 8px 32px rgba(0,0,0,0.18);
    max-width: calc(100vw - 32px);
}
.dp-fav-toast.is-visible { opacity: 1; transform: translateX(-50%) translateY(0); }
.dp-fav-toast--added { background: rgba(220,38,38,0.95); color: #fff; }
.dp-fav-toast--removed { background: rgba(17,24,39,0.92); color: #fff; }

/* ADAG STEPPER */
.adag-stepper { display: flex; flex-direction: column; align-items: center; gap: 8px; margin-bottom: 24px; }
.adag-stepper-label { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--r-text-muted); }
.adag-stepper-controls { display: flex; align-items: center; gap: 14px; }
.recept-single .adag-stepper-btn {
    width: 36px; height: 36px; border-radius: 50%; border: 1.5px solid var(--r-border);
    background: #fff; color: var(--r-text-muted); cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.15s ease, border-color 0.15s ease, transform 0.1s ease;
    padding: 0; flex-shrink: 0;
}
.recept-single .adag-stepper-btn svg { width: 14px; height: 14px; }
.recept-single .adag-stepper-btn svg line { stroke: var(--r-text-muted); stroke-width: 2.5; stroke-linecap: round; transition: stroke 0.15s ease; }
.recept-single .adag-stepper-btn:hover { background: #f3f4f6; border-color: #d1d5db; }
.recept-single .adag-stepper-btn:hover svg line { stroke: var(--r-text-secondary); }
.recept-single .adag-stepper-btn:active { background: #e5e7eb; transform: scale(0.94); }
.adag-stepper-controls input#adagok-input {
    width: 52px; height: 52px; text-align: center; border: 1.5px solid var(--r-border);
    border-radius: var(--r-radius-sm); background: var(--r-card); font-weight: 800;
    font-size: 1.25rem; color: var(--r-text); padding: 0; -moz-appearance: textfield;
    transition: all var(--r-transition);
}
.adag-stepper-controls input#adagok-input::-webkit-outer-spin-button,
.adag-stepper-controls input#adagok-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.adag-stepper-controls input#adagok-input:focus { outline: none; border-color: var(--r-accent); box-shadow: var(--r-focus-ring); }

/* ÖSSZETEVŐK */
.recept-osszetevok { list-style: none; margin: 0; padding: 0; }
.osszetevo-sor {
    display: flex; align-items: center; gap: 12px; padding: 10px 16px;
    transition: all var(--r-transition); border-left: 3px solid transparent;
    border-bottom: 1px solid var(--r-border-light);
}
.osszetevo-sor:last-child { border-bottom: none; }
.osszetevo-sor:hover { background: var(--r-bg); }
.osszetevo-sor.is-modified { border-left-color: var(--r-accent); background: var(--r-accent-soft); }
.osszetevo-checkbox-label { flex-shrink: 0; display: flex; align-items: center; cursor: pointer; }
.osszetevo-checkbox-label input[type="checkbox"] { width: 18px; height: 18px; accent-color: var(--r-accent); cursor: pointer; }
.osszetevo-mennyiseg-wrap { display: flex; align-items: center; gap: 5px; flex-shrink: 0; }
.osszetevo-mennyiseg-input {
    width: 64px; padding: 6px 8px; border: 1px solid var(--r-border);
    border-radius: var(--r-radius-xs); text-align: center; font-size: 0.88rem;
    font-weight: 600; background: var(--r-card); transition: all var(--r-transition);
    -moz-appearance: textfield; color: var(--r-text);
}
.osszetevo-mennyiseg-input::-webkit-outer-spin-button,
.osszetevo-mennyiseg-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.osszetevo-mennyiseg-input:focus { outline: none; border-color: var(--r-accent); box-shadow: var(--r-focus-ring); }
.osszetevo-mertekegyseg { font-size: 0.8rem; color: var(--r-text-muted); font-weight: 600; min-width: 24px; }
.osszetevo-nev { font-size: 0.92rem; flex: 1; min-width: 0; color: var(--r-text); line-height: 1.4; overflow: hidden; }
.osszetevo-nev em { color: var(--r-text-muted); font-size: 0.82rem; font-style: italic; }
.osszetevo-sor:has(input[type="checkbox"]:checked) .osszetevo-nev { text-decoration: line-through; opacity: 0.35; }
.osszetevo-sor:has(input[type="checkbox"]:checked) .osszetevo-mennyiseg-input { opacity: 0.35; }
.osszetevo-sor:has(input[type="checkbox"]:checked) .osszetevo-mertekegyseg { opacity: 0.35; }
.osszetevo-sor:has(input[type="checkbox"]:checked) .osszetevo-reszletek { opacity: 0.35; }

/* RÉSZLETEK GOMB */
.osszetevo-reszletek {
    display: inline-flex; align-items: center; gap: 3px; padding: 4px 12px 4px 10px;
    margin-left: auto; flex-shrink: 0; border-radius: var(--r-radius-xs);
    border: 1px solid var(--r-border); background: var(--r-card); color: var(--r-text-muted);
    font-size: 0.7rem; font-weight: 600; text-decoration: none; white-space: nowrap;
    transition: all var(--r-transition); line-height: 1;
}
.osszetevo-reszletek svg { flex-shrink: 0; transition: transform var(--r-transition); }
.osszetevo-reszletek:hover { border-color: var(--r-accent); color: var(--r-accent-dark); background: var(--r-accent-soft); }
.osszetevo-reszletek:hover svg { transform: translateX(2px); }

/* RESET */
.recept-single .alaphelyzet-btn {
    display: block; margin: 20px auto 0; background: #fff; border: 1px solid #d1d5db;
    padding: 8px 24px; border-radius: var(--r-radius-xs); font-size: 0.78rem; font-weight: 600;
    color: #374151; cursor: pointer; transition: all var(--r-transition);
    opacity: 0; pointer-events: none; transform: translateY(-6px);
}
.recept-single .alaphelyzet-btn.is-visible { opacity: 1; pointer-events: auto; transform: translateY(0); }
.recept-single .alaphelyzet-btn:hover { background: #f3f4f6; border-color: #9ca3af; color: #111827; }

/* TÁPANYAG */
.recept-single .recept-section:has(.recept-section-body--makro) > .recept-section-header {
    background: linear-gradient(135deg, rgba(45,138,99,0.85) 0%, rgba(35,120,82,0.92) 50%, rgba(45,138,99,0.85) 100%) !important;
    color: #fff !important;
}
.recept-single #tapanyag-adag-label { font-weight: 600; font-size: 0.9em; color: rgba(255,255,255,0.75); }
.recept-single .recept-section-body--makro { padding: 20px 24px; background: #fff; }
.recept-single .makro-warning {
    background: rgba(220,38,38,0.05); border-left: 3px solid #dc2626;
    padding: 10px 16px; margin-bottom: 14px; font-size: 0.82rem;
    border-radius: 0 var(--r-radius-xs) var(--r-radius-xs) 0; color: #991b1b;
}
.recept-single .makro-strip { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
.recept-single .makro-strip-divider { display: none; }
.recept-single .makro-strip-item {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 22px 10px 18px; background: #f9fafb; border: 1px solid var(--r-border);
    border-radius: var(--r-radius-sm); transition: border-color var(--r-transition); min-height: 130px;
    min-width: 0;
}
.recept-single .makro-strip-item:hover { border-color: #d1d5db; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
.recept-single .makro-strip-item:first-child {
    background: linear-gradient(135deg, rgba(45,138,99,0.04) 0%, rgba(45,138,99,0.09) 100%);
    border-color: rgba(45,138,99,0.2);
}
.recept-single .makro-strip-value {
    font-size: 1.6rem; font-weight: 800; color: var(--r-text); line-height: 1;
    margin-bottom: 10px; display: inline-flex; align-items: flex-start; gap: 2px;
}
.recept-single .makro-strip-item:first-child .makro-strip-value { color: var(--r-accent-dark); font-size: 1.75rem; }
.recept-single .makro-strip-unit { font-size: 0.55em; font-weight: 600; color: var(--r-text-muted); margin-top: 0.15em; }
.recept-single .makro-strip-item:first-child .makro-strip-unit { color: var(--r-accent-dark); opacity: 0.6; }
.recept-single .makro-strip-label {
    font-size: 0.68rem; font-weight: 700; color: var(--r-text-muted);
    text-transform: uppercase; letter-spacing: 0.05em; text-align: center; margin-bottom: 8px;
}
.recept-single .makro-strip-item:first-child .makro-strip-label { color: var(--r-accent-dark); }
.recept-single .makro-strip-pct {
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 0.62rem; font-weight: 700; color: var(--r-accent-dark);
    background: var(--r-accent-light); padding: 4px 14px; border-radius: 100px;
    line-height: 1; margin-top: auto;
}
.recept-single .makro-per-adag {
    display: none; text-align: center; margin-top: 14px; padding: 10px 16px;
    background: #f9fafb; border-radius: var(--r-radius-xs); font-size: 0.78rem;
    color: #4b5563; border: 1px solid #e5e7eb;
}
.recept-single .makro-per-adag strong { color: #111827; }
.makro-info {
    margin-top: 14px; padding: 12px 16px 12px 18px; background: var(--r-bg);
    border-left: 3px solid var(--r-accent); border-radius: 0 var(--r-radius-xs) var(--r-radius-xs) 0;
    font-size: 0.76rem; line-height: 1.55; color: var(--r-text-muted);
}
.makro-info p { margin: 0; }
.makro-info p + p { margin-top: 5px; }
.makro-info strong { color: var(--r-accent-dark); font-weight: 700; }
.makro-info-factors { font-size: 0.7rem; opacity: 0.8; }

/* PROGRESS BAR */
.elkeszites-progress-wrap { flex: 1; height: 6px; min-width: 80px; background: rgba(255,255,255,0.3); border-radius: 999px; overflow: hidden; margin-left: 8px; }
.elkeszites-progress-bar { display: block; height: 100%; width: 0%; background: #fff; border-radius: 999px; transition: width 0.4s ease; }
.elkeszites-progress-text { font-size: 0.7rem; font-weight: 700; color: rgba(255,255,255,0.9); min-width: 36px; text-align: right; flex-shrink: 0; }

/* ELKÉSZÍTÉS */
.recept-lepesek { counter-reset: lepes; list-style: none; margin: 0; padding: 0; }
.recept-lepesek li {
    position: relative; padding: 20px 24px 20px 64px;
    border-bottom: 1px solid var(--r-border-light); font-size: 0.92rem;
    line-height: 1.7; transition: all var(--r-transition); cursor: pointer; color: var(--r-text-secondary);
}
.recept-lepesek li:last-child { border-bottom: none; }
.recept-lepesek li:hover { background: var(--r-bg); }
.recept-lepesek li::before {
    counter-increment: lepes; content: counter(lepes); position: absolute;
    left: 20px; top: 20px; width: 30px; height: 30px; border-radius: 50%;
    background: var(--r-accent); color: #fff; font-weight: 800;
    display: flex; align-items: center; justify-content: center; font-size: 0.78rem;
}
.recept-lepesek li.lepes-done { opacity: 0.4; }
.recept-lepesek li.lepes-done .lepes-text { text-decoration: line-through; }
.recept-lepesek li.lepes-done::before { background: var(--r-text-muted); }
.recept-lepesek li.lepes-done::after {
    content: ''; position: absolute; left: 20px; top: 20px; width: 30px; height: 30px; border-radius: 50%;
    background: var(--r-text-muted) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 16 16' fill='none'%3E%3Cpath d='M3 8.5L6.5 12L13 4' stroke='white' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") center center no-repeat;
    z-index: 1;
}
.lepes-checkbox-label { display: flex; align-items: flex-start; cursor: pointer; }
.lepes-checkbox { position: absolute; opacity: 0; width: 1px; height: 1px; overflow: hidden; }
.lepes-text { display: block; }

/* TARTALOM */
.recept-tartalom {
    background: var(--r-card); border-radius: var(--r-radius); box-shadow: var(--r-shadow);
    border: 1px solid var(--r-border); padding: 28px; line-height: 1.75;
    font-size: 0.95rem; color: var(--r-text-secondary);
}

@keyframes fadeInUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
.recept-single article > * { animation: fadeInUp 0.3s ease-out both; }

/* RESPONSIVE 1024 */
@media (max-width: 1024px) { .recept-layout { grid-template-columns: 240px 1fr; gap: 20px; } }

/* RESPONSIVE 768 */
@media (max-width: 768px) {
    .recept-single { padding: 0 16px 32px; }
    .recept-layout { grid-template-columns: 1fr; }
    .recept-sidebar { position: static; order: 2; }
    .recept-main-content { order: 1; }
    .sidebar-cards { flex-direction: row; overflow-x: auto; scroll-snap-type: x mandatory; -webkit-overflow-scrolling: touch; gap: 0; padding-bottom: 4px; }
    .sidebar-card { flex: 0 0 200px; scroll-snap-align: start; flex-direction: column; padding: 12px; text-align: center; border-bottom: none; border-right: 1px solid var(--r-border-light); }
    .sidebar-card:last-child { border-right: none; }
    .sidebar-card-img { width: 100%; height: 80px; }
    .recept-hero { height: 260px; border-radius: 0; margin-left: -16px; margin-right: -16px; width: calc(100% + 32px); }
    .recept-hero-overlay { padding: 24px 20px; }
    .recept-hero-overlay h1 { font-size: 1.45rem; }
    .recept-single .recept-meta { flex-direction: column; }
    .recept-single .recept-meta p { border-right: none; border-bottom: 1px solid rgba(45,138,99,0.08); }
    .recept-single .recept-meta p:last-child { border-bottom: none; }
    .recept-section-body { padding: 20px; }
    .recept-section-body--makro { padding: 16px 20px; }
    .recept-single .makro-strip { grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .recept-single .makro-strip-item { min-height: 110px; padding: 18px 8px 14px; }
    .recept-single .makro-strip-value { font-size: 1.35rem; margin-bottom: 8px; }
    .recept-single .makro-strip-item:first-child .makro-strip-value { font-size: 1.5rem; }
    .hozzavalo-toolbar { padding: 10px 20px; gap: 6px; }
    .toolbar-btn { font-size: 0.72rem; padding: 6px 12px; }
    .osszetevo-reszletek-text { display: none; }
}

/* RESPONSIVE 480 */
@media (max-width: 480px) {
    .recept-single { padding: 0 12px 24px; }
    .recept-hero { height: 200px; margin-left: -12px; margin-right: -12px; width: calc(100% + 24px); }
    .recept-hero-overlay { padding: 16px; }
    .recept-hero-overlay h1 { font-size: 1.25rem; }
    .recept-section-header { font-size: 0.76rem; padding: 14px 18px; }
    .recept-section-body { padding: 16px; }
    .recept-section-body--makro { padding: 14px 16px; }
    .osszetevo-sor { gap: 8px; padding: 8px 12px; }
    .osszetevo-mennyiseg-input { width: 54px; font-size: 0.82rem; padding: 5px 6px; }
    .osszetevo-nev { font-size: 0.85rem; }
    .recept-single .makro-strip { gap: 8px; }
    .recept-single .makro-strip-item { padding: 16px 6px 12px; min-height: 100px; }
    .recept-single .makro-strip-value { font-size: 1.2rem; margin-bottom: 6px; }
    .recept-single .makro-strip-item:first-child .makro-strip-value { font-size: 1.3rem; }
    .recept-single .makro-strip-label { font-size: 0.6rem; margin-bottom: 6px; }
    .recept-single .makro-strip-pct { font-size: 0.56rem; padding: 3px 10px; }
    .recept-lepesek li { padding: 16px 16px 16px 56px; font-size: 0.88rem; }
    .recept-lepesek li::before { left: 14px; top: 16px; width: 28px; height: 28px; font-size: 0.72rem; }
    .recept-lepesek li.lepes-done::after { left: 14px; top: 16px; width: 28px; height: 28px; }
    .sidebar-card { flex: 0 0 170px; }
    .adag-stepper-btn { width: 34px; height: 34px; }
    .adag-stepper-controls input#adagok-input { width: 46px; height: 46px; font-size: 1.1rem; }
    .adag-stepper-controls { gap: 10px; }
    .hozzavalo-toolbar { padding: 8px 16px; }
    .toolbar-btn { font-size: 0.68rem; padding: 5px 10px; }
    .osszetevo-reszletek { padding: 3px 8px 3px 6px; }
    .osszetevo-reszletek-text { display: none; }
}

/* PRINT */
@page { margin: 0; }
@media print {
    html { margin: 0; }
    body { margin: 1cm; overflow: visible; }
    #wpadminbar, .site-header, header, .site-footer, footer, nav.main-navigation, .navigation { display: none !important; }
    .recept-single { max-width: 100%; margin: 0; padding: 0; overflow: visible; }
    .alaphelyzet-btn, .makro-warning, .makro-info, .recept-sidebar, .share-panel, .recept-toast, .hozzavalo-toolbar, .osszetevo-reszletek, .dp-fav-toast { display: none !important; }
    .recept-layout { grid-template-columns: 1fr; }
    .recept-section { box-shadow: none; border: 1px solid #ddd; }
    .recept-single article > * { animation: none !important; }
    .osszetevo-mennyiseg-input { border: none; background: none; }
    .recept-hero { height: auto; print-color-adjust: exact; }
    .recept-print-header {
        display: flex !important; align-items: center; gap: 16px;
        padding: 0 0 16px; margin-bottom: 20px; border-bottom: 2px solid #2d6a4f;
    }
    .recept-print-logo-img { height: 40px; width: auto; object-fit: contain; flex-shrink: 0; }
    .recept-print-site-name { font-size: 18px; font-weight: 800; color: #2d6a4f; flex-shrink: 0; }
    .recept-print-title-wrap { flex: 1; }
    .recept-print-title { font-size: 20px !important; font-weight: 800; color: #1a1d1b; margin: 0 0 2px !important; text-align: left !important; }
    .recept-print-url { font-size: 10px; color: #888; margin: 0; }
}
.recept-print-header { display: none; }

CSS;

    wp_register_style( 'recept-inline-style', false );
    wp_enqueue_style( 'recept-inline-style' );
    wp_add_inline_style( 'recept-inline-style', $css );
}
add_action( 'wp_enqueue_scripts', 'recept_single_inline_styles' );
