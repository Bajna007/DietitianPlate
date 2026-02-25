<?php

/**
 * Recept CSS (single-recept)
 */
// Recept prémium CSS v12 – adag stepper újratervezés
function recept_single_inline_styles() {
    if ( ! is_singular( 'recept' ) ) { return; }

    $css = <<<CSS

:root {
    --r-accent: #2ecc71;
    --r-accent-dark: #27ae60;
    --r-accent-soft: rgba(46, 204, 113, 0.10);
    --r-accent-glow: rgba(46, 204, 113, 0.25);
    --r-bg: #f5f6f5;
    --r-card: #ffffff;
    --r-border: #e8ebe9;
    --r-text: #1e1e1e;
    --r-text-muted: #7c8a83;
    --r-radius: 16px;
    --r-radius-sm: 10px;
    --r-shadow: 0 4px 24px rgba(0,0,0,0.05);
    --r-shadow-lg: 0 8px 40px rgba(0,0,0,0.07);
    --r-transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.recept-single {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px 48px;
    color: var(--r-text);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    line-height: 1.6;
}
.recept-single h1 {
    text-align: center;
    font-size: clamp(1.7rem, 4vw, 2.4rem);
    font-weight: 800;
    letter-spacing: -0.02em;
    margin-bottom: 24px;
}

/* ═══ HERO ═══ */
.recept-hero {
    position: relative;
    width: 100%;
    height: 360px;
    background-size: cover;
    background-position: center;
    border-radius: var(--r-radius);
    overflow: hidden;
    margin-bottom: 28px;
    box-shadow: var(--r-shadow-lg);
}
.recept-hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        to top,
        rgba(0, 0, 0, 0.7) 0%,
        rgba(0, 0, 0, 0.25) 40%,
        rgba(0, 0, 0, 0.12) 100%
    );
    display: flex;
    align-items: flex-end;
    padding: 32px;
}
.recept-hero-overlay h1 {
    color: #fff;
    text-align: left;
    margin: 0;
    text-shadow: 0 2px 16px rgba(0,0,0,0.6), 0 1px 4px rgba(0,0,0,0.4);
    font-size: clamp(1.6rem, 3.5vw, 2.4rem);
}

/* ═══ TOAST ═══ */
.recept-toast {
    display: none;
    position: fixed;
    bottom: 24px;
    left: 50%;
    transform: translateX(-50%) translateY(20px);
    background: var(--r-accent-dark);
    color: #fff;
    padding: 10px 24px;
    border-radius: 999px;
    font-size: 0.85rem;
    font-weight: 600;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    opacity: 0;
    transition: opacity 0.3s ease, transform 0.3s ease;
    z-index: 9999;
    pointer-events: none;
}
.recept-toast.is-visible {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}

/* ═══ SHARE PANEL ═══ */
.share-panel {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 9998;
    display: none;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(4px);
}
.share-panel.is-open { display: flex; }
.share-panel-inner {
    background: var(--r-card);
    border-radius: var(--r-radius);
    padding: 28px;
    max-width: 480px;
    width: 90%;
    position: relative;
    box-shadow: var(--r-shadow-lg);
}
.share-close {
    position: absolute;
    top: 12px; right: 16px;
    background: none; border: none;
    font-size: 1.2rem;
    cursor: pointer;
    color: var(--r-text-muted);
    transition: color var(--r-transition);
}
.share-close:hover { color: var(--r-text); }
.share-title { margin: 0 0 16px; font-size: 1rem; font-weight: 700; }
.share-textarea {
    width: 100%;
    border: 1px solid var(--r-border);
    border-radius: var(--r-radius-sm);
    padding: 12px;
    font-size: 0.82rem;
    resize: none;
    font-family: inherit;
    color: var(--r-text);
    background: var(--r-bg);
    margin-bottom: 16px;
    line-height: 1.5;
    box-sizing: border-box;
}
.share-buttons { display: flex; flex-wrap: wrap; gap: 8px; }
.share-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 8px 16px;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all var(--r-transition);
    border: 1.5px solid var(--r-border);
    background: var(--r-card);
    color: var(--r-text);
}
.share-btn:hover { border-color: var(--r-accent); background: var(--r-accent-soft); }
.share-btn--copy { background: var(--r-accent); color: #fff; border-color: var(--r-accent); }
.share-btn--copy:hover { background: var(--r-accent-dark); }
.share-btn--whatsapp:hover { border-color: #25d366; color: #25d366; }
.share-btn--email:hover { border-color: #ea4335; color: #ea4335; }

/* ═══ META ═══ */
.recept-meta {
    display: flex;
    gap: 0;
    margin-bottom: 28px;
    border-radius: var(--r-radius);
    overflow: hidden;
    box-shadow: var(--r-shadow);
    border: 1px solid var(--r-border);
    background: var(--r-card);
}
.recept-meta p {
    flex: 1; margin: 0;
    padding: 13px 16px;
    text-align: center;
    font-size: 0.88rem;
    font-weight: 500;
    border-right: 1px solid var(--r-border);
}
.recept-meta p:last-child { border-right: none; }

/* ═══ LAYOUT ═══ */
.recept-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 28px;
    align-items: start;
}

/* ═══ SIDEBAR ═══ */
.recept-sidebar {
    position: sticky;
    top: 100px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.sidebar-section {
    background: var(--r-card);
    border-radius: var(--r-radius);
    box-shadow: var(--r-shadow);
    border: 1px solid var(--r-border);
    overflow: hidden;
}
.sidebar-section-title {
    background: var(--r-accent);
    color: #fff;
    padding: 10px 16px;
    margin: 0;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}
.sidebar-cards { display: flex; flex-direction: column; }
.sidebar-card {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    text-decoration: none;
    color: var(--r-text);
    transition: background var(--r-transition);
    border-bottom: 1px solid var(--r-border);
}
.sidebar-card:last-child { border-bottom: none; }
.sidebar-card:hover { background: var(--r-accent-soft); }
.sidebar-card-img {
    width: 48px; height: 48px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
    background: var(--r-bg);
}
.sidebar-card-img img { width: 100%; height: 100%; object-fit: cover; }
.sidebar-card-img-ph {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem;
}
.sidebar-card-body {
    flex: 1; min-width: 0;
    display: flex; flex-direction: column; gap: 2px;
}
.sidebar-card-title {
    font-size: 0.82rem;
    font-weight: 600;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.sidebar-card-meta { font-size: 0.7rem; color: var(--r-text-muted); }
.sidebar-card-meta strong { color: var(--r-accent-dark); font-weight: 700; }
.sidebar-kat-chips { padding: 12px 14px; display: flex; flex-wrap: wrap; gap: 6px; }
.sidebar-kat-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    border-radius: 999px;
    border: 1.5px solid var(--r-border);
    background: var(--r-card);
    color: var(--r-text);
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
    border-radius: 999px;
    font-weight: 700;
}
.sidebar-kat-chip--all {
    background: var(--r-accent);
    color: #fff;
    border-color: var(--r-accent);
    width: 100%;
    justify-content: center;
    margin-top: 2px;
}
.sidebar-kat-chip--all:hover { background: var(--r-accent-dark); color: #fff; }

/* ═══ FŐ TARTALOM ═══ */
.recept-main-content { min-width: 0; }
.recept-content-wrap {
    background: var(--r-card);
    border-radius: var(--r-radius);
    box-shadow: var(--r-shadow-lg);
    border: 1px solid var(--r-border);
    overflow: hidden;
    margin-bottom: 32px;
}

.recept-section-header {
    background: var(--r-accent);
    color: #fff;
    padding: 14px 24px;
    margin: 0;
    font-size: 0.92rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    border-top: 1px solid rgba(255,255,255,0.15);
    display: flex;
    align-items: center;
    gap: 12px;
}
.recept-section:first-child .recept-section-header {
    border-top: none;
    border-radius: var(--r-radius) var(--r-radius) 0 0;
}
.recept-section-body {
    padding: 24px;
    border-bottom: 1px solid var(--r-border);
}
.recept-section:last-child .recept-section-body { border-bottom: none; }
.recept-section-body--lepesek { padding: 0; }
.recept-content-wrap h2 { all: unset; display: block; }

/* ═══ TOOLBAR ═══ */
.hozzavalo-toolbar {
    display: flex;
    gap: 6px;
    padding: 10px 24px;
    background: var(--r-bg);
    border-bottom: 1px solid var(--r-border);
}
.toolbar-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 14px;
    border-radius: 999px;
    border: 1.5px solid var(--r-border);
    background: var(--r-card);
    color: var(--r-text);
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

/* ═══ ADAG STEPPER v3 ═══ */
.adag-stepper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    margin-bottom: 22px;
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
    gap: 12px;
}
.adag-stepper-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: 2px solid var(--r-accent);
    background: var(--r-card);
    color: var(--r-accent);
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
    stroke: var(--r-accent);
    stroke-width: 2.5;
    stroke-linecap: round;
}
.adag-stepper-btn:hover {
    background: var(--r-accent);
    color: #fff;
}
.adag-stepper-btn:hover svg line {
    stroke: #fff;
}
.adag-stepper-btn:active {
    transform: scale(0.9);
}
.adag-stepper-controls input#adagok-input {
    width: 48px;
    height: 48px;
    text-align: center;
    border: 2px solid var(--r-border);
    border-radius: 50%;
    background: var(--r-card);
    font-weight: 800;
    font-size: 1.2rem;
    color: var(--r-accent-dark);
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
    box-shadow: 0 0 0 3px var(--r-accent-glow);
}

/* ═══ ÖSSZETEVŐK ═══ */
.recept-osszetevok { list-style: none; margin: 0; padding: 0; }
.osszetevo-sor {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    border-radius: var(--r-radius-sm);
    transition: all var(--r-transition);
    border-left: 3px solid transparent;
}
.osszetevo-sor:nth-child(odd) { background: var(--r-bg); }
.osszetevo-sor:hover { background: var(--r-accent-soft); }
.osszetevo-sor.is-modified { border-left-color: var(--r-accent); background: var(--r-accent-soft); }
.osszetevo-checkbox-label { flex-shrink: 0; display: flex; align-items: center; cursor: pointer; }
.osszetevo-checkbox-label input[type="checkbox"] { width: 20px; height: 20px; accent-color: var(--r-accent); cursor: pointer; }
.osszetevo-mennyiseg-wrap { display: flex; align-items: center; gap: 4px; flex-shrink: 0; }
.osszetevo-mennyiseg-input {
    width: 62px; padding: 5px 8px;
    border: 1px solid var(--r-border);
    border-radius: var(--r-radius-sm);
    text-align: center;
    font-size: 0.9rem; font-weight: 600;
    background: #fff;
    transition: all var(--r-transition);
    font-variant-numeric: tabular-nums;
    -moz-appearance: textfield;
}
.osszetevo-mennyiseg-input::-webkit-outer-spin-button,
.osszetevo-mennyiseg-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.osszetevo-mennyiseg-input:focus { outline: none; border-color: var(--r-accent); box-shadow: 0 0 0 3px var(--r-accent-glow); }
.osszetevo-mertekegyseg { font-size: 0.82rem; color: var(--r-text-muted); font-weight: 600; min-width: 22px; }
.osszetevo-nev { font-size: 0.95rem; flex: 1; }
.osszetevo-nev em { color: var(--r-text-muted); font-size: 0.85rem; }
.osszetevo-sor:has(input[type="checkbox"]:checked) .osszetevo-nev { text-decoration: line-through; opacity: 0.4; }
.osszetevo-sor:has(input[type="checkbox"]:checked) .osszetevo-mennyiseg-input { opacity: 0.4; }

.alaphelyzet-btn {
    display: block;
    margin: 18px auto 0;
    background: none;
    border: 2px solid var(--r-border);
    padding: 8px 24px;
    border-radius: 999px;
    font-size: 0.82rem; font-weight: 700;
    color: var(--r-text-muted);
    cursor: pointer;
    transition: all var(--r-transition);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    opacity: 0;
    pointer-events: none;
    transform: translateY(-6px);
}
.alaphelyzet-btn.is-visible { opacity: 1; pointer-events: auto; transform: translateY(0); }
.alaphelyzet-btn:hover { border-color: #e74c3c; color: #e74c3c; background: rgba(231,76,60,0.06); }

/* ═══ MAKRÓ STRIP ═══ */
.recept-section-body--makro { padding: 16px 24px; }
.makro-warning {
    background: rgba(231,76,60,0.07);
    border-left: 3px solid #e74c3c;
    padding: 8px 14px;
    margin-bottom: 12px;
    font-size: 0.82rem;
    border-radius: 0 var(--r-radius-sm) var(--r-radius-sm) 0;
    color: #c0392b;
}
.makro-strip {
    display: flex;
    align-items: center;
    gap: 0;
}
.makro-strip-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1px;
    padding: 8px 4px;
}
.makro-strip-value {
    font-size: 1.35rem;
    font-weight: 800;
    color: var(--r-text);
    line-height: 1;
    font-variant-numeric: tabular-nums;
}
.makro-strip-item:first-child .makro-strip-value {
    color: var(--r-accent-dark);
    font-size: 1.5rem;
}
.makro-strip-label {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--r-text-muted);
    font-weight: 600;
}
.makro-strip-pct {
    font-size: 0.68rem;
    font-weight: 700;
    color: var(--r-accent-dark);
    background: var(--r-accent-soft);
    padding: 1px 8px;
    border-radius: 999px;
    margin-top: 2px;
}
.makro-strip-divider {
    width: 1px;
    height: 36px;
    background: var(--r-border);
    flex-shrink: 0;
}
.makro-per-adag {
    display: none;
    text-align: center;
    margin-top: 10px;
    padding: 8px 14px;
    background: var(--r-bg);
    border-radius: var(--r-radius-sm);
    font-size: 0.78rem;
    color: var(--r-text-muted);
}
.makro-per-adag strong { color: var(--r-text); font-weight: 700; }

/* ═══ MAKRÓ INFO-BOX ═══ */
.makro-info {
    margin-top: 14px;
    padding: 12px 16px 12px 18px;
    background: var(--r-bg);
    border-left: 3px solid var(--r-accent);
    border-radius: 0 var(--r-radius-sm) var(--r-radius-sm) 0;
    font-size: 0.78rem;
    line-height: 1.55;
    color: var(--r-text-muted);
}
.makro-info p { margin: 0; }
.makro-info p + p { margin-top: 6px; }
.makro-info strong {
    color: var(--r-accent-dark);
    font-weight: 700;
}
.makro-info-factors {
    font-size: 0.72rem;
    letter-spacing: 0.01em;
    opacity: 0.85;
}

/* ═══ PROGRESS BAR ═══ */
.elkeszites-progress-wrap {
    flex: 1;
    height: 8px;
    min-width: 80px;
    background: rgba(255,255,255,0.2);
    border-radius: 999px;
    overflow: hidden;
    margin-left: 8px;
    border: 1px solid rgba(255,255,255,0.15);
}
.elkeszites-progress-bar {
    display: block;
    height: 100%;
    width: 0%;
    background: #fff;
    border-radius: 999px;
    transition: width 0.4s ease;
    box-shadow: 0 0 6px rgba(255,255,255,0.5);
}
.elkeszites-progress-text {
    font-size: 0.72rem;
    font-weight: 800;
    opacity: 0.9;
    min-width: 36px;
    text-align: right;
    flex-shrink: 0;
}

/* ═══ ELKÉSZÍTÉS LÉPÉSEK ═══ */
.recept-lepesek {
    counter-reset: lepes;
    list-style: none;
    margin: 0;
    padding: 0;
}
.recept-lepesek li {
    position: relative;
    padding: 18px 20px 18px 60px;
    border-bottom: 1px solid var(--r-border);
    font-size: 0.95rem;
    line-height: 1.65;
    transition: all var(--r-transition);
    cursor: pointer;
}
.recept-lepesek li:last-child { border-bottom: none; }
.recept-lepesek li:hover { background: var(--r-accent-soft); }

.recept-lepesek li::before {
    counter-increment: lepes;
    content: counter(lepes);
    position: absolute;
    left: 18px;
    top: 18px;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: var(--r-accent);
    color: #fff;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.82rem;
    transition: background var(--r-transition);
}

.recept-lepesek li.lepes-done {
    opacity: 0.45;
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
    left: 18px;
    top: 18px;
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

.recept-tartalom {
    background: var(--r-card);
    border-radius: var(--r-radius);
    box-shadow: var(--r-shadow);
    border: 1px solid var(--r-border);
    padding: 24px;
    line-height: 1.7;
    font-size: 0.95rem;
}

/* ═══ ANIMÁCIÓ ═══ */
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(14px); }
    to   { opacity: 1; transform: translateY(0); }
}
.recept-single article > * {
    animation: fadeInUp 0.35s ease-out both;
}

/* ═══ RESPONSIVE ═══ */
@media (max-width: 900px) {
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
        border-right: 1px solid var(--r-border);
    }
    .sidebar-card:last-child { border-right: none; }
    .sidebar-card-img { width: 100%; height: 80px; border-radius: 8px; }
    .recept-hero { height: 260px; }
}

@media (max-width: 768px) {
    .recept-single { padding: 0 12px 24px; }
    .recept-meta { flex-direction: column; }
    .recept-meta p { border-right: none; border-bottom: 1px solid var(--r-border); }
    .recept-meta p:last-child { border-bottom: none; }
    .recept-section-body { padding: 18px; }
    .recept-section-body--makro { padding: 12px 18px; }
    .recept-hero {
        height: 220px;
        border-radius: 0;
        margin-left: -12px;
        margin-right: -12px;
        width: calc(100% + 24px);
    }
    .recept-hero-overlay { padding: 20px; }
    .recept-hero-overlay h1 { font-size: 1.4rem; }
    .hozzavalo-toolbar { padding: 8px 18px; gap: 4px; flex-wrap: wrap; }
    .toolbar-btn { font-size: 0.7rem; padding: 5px 10px; }
    .makro-strip { flex-wrap: wrap; }
    .makro-strip-item { min-width: 45%; }
    .makro-strip-divider { display: none; }
}

@media (max-width: 480px) {
    .osszetevo-mennyiseg-input { width: 52px; font-size: 0.85rem; }
    .recept-section-header { font-size: 0.82rem; padding: 12px 18px; }
    .makro-per-adag { font-size: 0.72rem; }
    .sidebar-card { flex: 0 0 160px; }
    .recept-hero { height: 180px; }
    .adag-stepper-btn { width: 32px; height: 32px; }
    .adag-stepper-controls input#adagok-input { width: 42px; height: 42px; font-size: 1.05rem; }
    .adag-stepper-controls { gap: 8px; }
}

@media print {
    .recept-single { max-width: 100%; margin: 0; padding: 0; }
    .alaphelyzet-btn,
    .makro-warning,
    .recept-sidebar,
    .share-panel,
    .recept-toast,
    .hozzavalo-toolbar { display: none !important; }
    .recept-layout { grid-template-columns: 1fr; }
    .recept-content-wrap { box-shadow: none; }
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
