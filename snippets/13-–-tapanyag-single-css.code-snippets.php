<?php

/**
 * 13 – Tápanyag single CSS
 */
/**
 * 13 – Tápanyag single CSS – v2 REWORK
 * Emoji→SVG ikonok, természetes források gombos link,
 * chip-ek nem kattinthatók, konzisztens design language
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function tapanyag_single_inline_styles() {
    if ( ! is_singular( 'tapanyag' ) ) { return; }

    $css = <<<'CSS'

:root {
    --t-accent:       #F59E0B;
    --t-accent-dark:  #D97706;
    --t-accent-soft:  rgba(245, 158, 11, 0.10);
    --t-accent-glow:  rgba(245, 158, 11, 0.25);
    --t-bg:           #f5f6f5;
    --t-card:         #ffffff;
    --t-border:       #e8ebe9;
    --t-text:         #1e1e1e;
    --t-text-muted:   #7c8a83;
    --t-radius:       16px;
    --t-radius-sm:    10px;
    --t-shadow:       0 4px 24px rgba(0,0,0,0.05);
    --t-shadow-lg:    0 8px 40px rgba(0,0,0,0.07);
    --t-transition:   0.25s cubic-bezier(0.4,0,0.2,1);
}

/* ═══ WRAPPER ═══ */
.tapanyag-single {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px 48px;
    color: var(--t-text);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    line-height: 1.6;
}
.tapanyag-single * { box-sizing: border-box; }

/* ═══ HERO ═══ */
.tapanyag-hero {
    position: relative;
    width: 100%;
    height: 360px;
    background-size: cover;
    background-position: center;
    border-radius: var(--t-radius);
    overflow: hidden;
    margin-bottom: 28px;
    box-shadow: var(--t-shadow-lg);
}
.tapanyag-hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.72) 0%, rgba(0,0,0,0.28) 45%, rgba(0,0,0,0.10) 100%);
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: 32px;
    gap: 12px;
}
.tapanyag-hero h1 {
    color: #fff;
    margin: 0;
    font-size: clamp(1.6rem, 3.5vw, 2.4rem);
    font-weight: 800;
    letter-spacing: -0.02em;
    text-shadow: 0 2px 16px rgba(0,0,0,0.6);
    line-height: 1.2;
}
.tapanyag-hero-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}
.tapanyag-keplet-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 14px;
    border-radius: 999px;
    background: rgba(255,255,255,0.18);
    border: 1.5px solid rgba(255,255,255,0.35);
    color: #fff;
    font-size: 0.82rem;
    font-weight: 700;
    backdrop-filter: blur(6px);
    font-family: 'SF Mono', 'Fira Code', Consolas, monospace;
    letter-spacing: 0.02em;
}
.tapanyag-hero-chip {
    display: inline-flex;
    align-items: center;
    padding: 4px 12px;
    border-radius: 999px;
    background: rgba(255,255,255,0.15);
    border: 1.5px solid rgba(255,255,255,0.25);
    color: #fff;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    backdrop-filter: blur(6px);
    cursor: default;
    user-select: none;
}

/* ═══ SVG IKONOK ═══ */
.ts-ico {
    display: inline-block;
    vertical-align: middle;
    flex-shrink: 0;
}

.ts-section-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.ts-section-icon .ts-ico {
    width: 18px;
    height: 18px;
    stroke: #fff;
    opacity: 0.85;
}

.ts-sidebar-icon {
    display: inline-flex;
    align-items: center;
    flex-shrink: 0;
}
.ts-sidebar-icon .ts-ico {
    width: 14px;
    height: 14px;
    stroke: #fff;
    opacity: 0.85;
}

.toc-icon .ts-ico {
    width: 14px;
    height: 14px;
    stroke: var(--t-text-muted);
    opacity: 0.6;
}

/* ═══ LAYOUT ═══ */
.tapanyag-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 28px;
    align-items: start;
}

/* ═══ SIDEBAR ═══ */
.tapanyag-sidebar {
    position: sticky;
    top: 100px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.tapanyag-sidebar-section {
    background: var(--t-card);
    border-radius: var(--t-radius);
    box-shadow: var(--t-shadow);
    border: 1px solid var(--t-border);
    overflow: hidden;
}
.tapanyag-sidebar-title {
    background: var(--t-accent);
    color: #fff;
    padding: 10px 16px;
    margin: 0;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    display: flex;
    align-items: center;
    gap: 8px;
}
.tapanyag-sidebar-body {
    padding: 14px 16px;
    font-size: 0.85rem;
    line-height: 1.65;
    color: var(--t-text-muted);
}

/* Tartalomjegyzék */
.tapanyag-toc {
    list-style: none;
    margin: 0;
    padding: 8px 0;
}
.tapanyag-toc li a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 16px;
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--t-text);
    text-decoration: none !important;
    transition: all var(--t-transition);
    border-left: 3px solid transparent;
}
.tapanyag-toc li a:hover {
    background: var(--t-accent-soft);
    border-left-color: var(--t-accent);
    color: var(--t-accent-dark);
}
.tapanyag-toc li a .toc-icon {
    display: inline-flex;
    align-items: center;
    flex-shrink: 0;
}

/* ═══ FŐ TARTALOM ═══ */
.tapanyag-main { min-width: 0; }

.tapanyag-content-wrap {
    background: var(--t-card);
    border-radius: var(--t-radius);
    box-shadow: var(--t-shadow-lg);
    border: 1px solid var(--t-border);
    overflow: hidden;
    margin-bottom: 28px;
}

.tapanyag-section-header {
    background: var(--t-accent);
    color: #fff;
    padding: 14px 24px;
    margin: 0;
    font-size: 0.92rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    display: flex;
    align-items: center;
    gap: 10px;
    border-top: 1px solid rgba(255,255,255,0.15);
}
.tapanyag-section:first-child .tapanyag-section-header {
    border-top: none;
}
.tapanyag-section-body {
    padding: 24px;
    border-bottom: 1px solid var(--t-border);
    font-size: 0.95rem;
    line-height: 1.75;
    color: var(--t-text);
}
.tapanyag-section:last-child .tapanyag-section-body {
    border-bottom: none;
}
.tapanyag-section-body h2,
.tapanyag-section-body h3 {
    font-size: 1rem;
    font-weight: 700;
    margin: 16px 0 8px;
    color: var(--t-text);
}
.tapanyag-section-body ul,
.tapanyag-section-body ol {
    padding-left: 20px;
    margin: 8px 0;
}
.tapanyag-section-body li { margin-bottom: 4px; }
.tapanyag-section-body p { margin: 0 0 12px; }
.tapanyag-section-body p:last-child { margin-bottom: 0; }

/* ═══ TERMÉSZETES FORRÁSOK – GOMBOS LINK ═══ */
.tapanyag-forras-lista {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.tapanyag-forras-item {
    display: grid;
    grid-template-columns: auto auto 1fr auto;
    align-items: center;
    gap: 14px;
    padding: 10px 14px;
    background: var(--t-bg);
    border-radius: var(--t-radius-sm);
    transition: all var(--t-transition);
}
.tapanyag-forras-item:hover {
    background: var(--t-accent-soft);
    transform: translateX(4px);
}
.tapanyag-forras-nev {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--t-text);
    flex-shrink: 0;
    white-space: nowrap;
}
.tapanyag-forras-link {
    display: inline-flex;
    align-items: center;
    gap: 2px;
    padding: 4px 8px 4px 6px;
    border-radius: 6px;
    background: rgba(0,0,0,0.04);
    color: var(--t-text-muted);
    text-decoration: none !important;
    font-size: 0.72rem;
    font-weight: 600;
    transition: all var(--t-transition);
    flex-shrink: 0;
    white-space: nowrap;
    border: 1px solid transparent;
}
.tapanyag-forras-link:hover {
    background: var(--t-accent-soft);
    color: var(--t-accent-dark);
    border-color: var(--t-accent);
}
.tapanyag-forras-link svg {
    width: 12px;
    height: 12px;
    flex-shrink: 0;
}
.tapanyag-forras-link-text {
    line-height: 1;
}
.tapanyag-forras-bar-wrap {
    flex: 1;
    height: 8px;
    background: var(--t-border);
    border-radius: 999px;
    overflow: hidden;
    min-width: 40px;
}
.tapanyag-forras-bar {
    height: 100%;
    background: var(--t-accent);
    border-radius: 999px;
    transition: width 0.6s cubic-bezier(0.4,0,0.2,1);
}
.tapanyag-forras-ertek {
    font-size: 0.88rem;
    font-weight: 800;
    color: var(--t-accent-dark);
    min-width: 72px;
    text-align: right;
    font-variant-numeric: tabular-nums;
    flex-shrink: 0;
    white-space: nowrap;
}

/* ═══ RDA TÁBLÁZAT ═══ */
.tapanyag-rda-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.88rem;
}
.tapanyag-rda-table th {
    background: var(--t-bg);
    padding: 8px 14px;
    text-align: left;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--t-text-muted);
    border-bottom: 2px solid var(--t-border);
}
.tapanyag-rda-table td {
    padding: 10px 14px;
    border-bottom: 1px solid var(--t-border);
    color: var(--t-text);
    vertical-align: middle;
}
.tapanyag-rda-table tr:last-child td { border-bottom: none; }
.tapanyag-rda-table tr:nth-child(odd) td { background: var(--t-bg); }
.tapanyag-rda-value {
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--t-accent-dark);
    font-variant-numeric: tabular-nums;
}
.tapanyag-rda-ul {
    font-size: 0.78rem;
    color: var(--t-text-muted);
    font-weight: 600;
}
.tapanyag-rda-ul span { color: #e74c3c; font-weight: 700; }
.tapanyag-rda-empty { color: var(--t-text-muted); font-size: 0.78rem; }
.tapanyag-rda-note { font-size: 0.82rem; color: var(--t-text-muted); }

/* ═══ HIVATKOZÁSOK ═══ */
.tapanyag-hivatkozas-lista {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.tapanyag-hivatkozas-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    background: var(--t-bg);
    border-radius: var(--t-radius-sm);
    text-decoration: none !important;
    color: var(--t-text) !important;
    border: 1px solid var(--t-border);
    transition: all var(--t-transition);
}
.tapanyag-hivatkozas-item:hover {
    border-color: var(--t-accent);
    background: var(--t-accent-soft);
}
.tapanyag-hivatkozas-tipus {
    font-size: 0.62rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 2px 10px;
    border-radius: 999px;
    flex-shrink: 0;
}
.tapanyag-hivatkozas-tipus--tudomanyos { background: rgba(59,130,246,0.1); color: #2563eb; }
.tapanyag-hivatkozas-tipus--hatosagi { background: rgba(139,92,246,0.1); color: #7c3aed; }
.tapanyag-hivatkozas-tipus--egyeb { background: var(--t-bg); color: var(--t-text-muted); border: 1px solid var(--t-border); }
.tapanyag-hivatkozas-cim { font-size: 0.85rem; font-weight: 600; flex: 1; }
.tapanyag-hivatkozas-nyil { font-size: 0.75rem; color: var(--t-text-muted); flex-shrink: 0; }

/* ═══ FOCUS RESET ═══ */
.tapanyag-single *:focus,
.tapanyag-single *:focus-visible,
.tapanyag-single *:focus-within { outline: none !important; outline-offset: 0 !important; }
.tapanyag-single button:focus,
.tapanyag-single button:focus-visible,
.tapanyag-single button:active { outline: none !important; box-shadow: none !important; }
.tapanyag-single a:focus,
.tapanyag-single a:focus-visible { outline: none !important; box-shadow: none !important; }

/* ═══ ANIMÁCIÓ ═══ */
@keyframes tsFadeInUp {
    from { opacity: 0; transform: translateY(14px); }
    to   { opacity: 1; transform: translateY(0); }
}
.tapanyag-section { animation: tsFadeInUp 0.35s ease-out both; }
.tapanyag-section:nth-child(1) { animation-delay: 0.05s; }
.tapanyag-section:nth-child(2) { animation-delay: 0.10s; }
.tapanyag-section:nth-child(3) { animation-delay: 0.15s; }
.tapanyag-section:nth-child(4) { animation-delay: 0.20s; }
.tapanyag-section:nth-child(5) { animation-delay: 0.25s; }
.tapanyag-section:nth-child(6) { animation-delay: 0.30s; }
.tapanyag-section:nth-child(7) { animation-delay: 0.35s; }

/* ═══ RESPONSIVE ═══ */
@media (max-width: 900px) {
    .tapanyag-layout { grid-template-columns: 1fr; }
    .tapanyag-sidebar { position: static; order: 2; }
    .tapanyag-main { order: 1; }
    .tapanyag-forras-item {
        grid-template-columns: 1fr auto;
        grid-template-rows: auto auto;
        gap: 8px 14px;
    }
    .tapanyag-forras-bar-wrap { grid-column: 1 / -1; order: 3; }
}
@media (max-width: 768px) {
    .tapanyag-single { padding: 0 12px 24px; }
    .tapanyag-hero { height: 240px; border-radius: 0; margin-left: -12px; margin-right: -12px; width: calc(100% + 24px); }
    .tapanyag-hero-overlay { padding: 20px; }
    .tapanyag-section-body { padding: 18px; }
    .tapanyag-forras-nev { min-width: 0; }
    .tapanyag-rda-table { font-size: 0.82rem; }
}
@media (max-width: 480px) {
    .tapanyag-hero { height: 200px; }
    .tapanyag-section-header { font-size: 0.82rem; padding: 12px 16px; }
    .tapanyag-forras-item {
        grid-template-columns: 1fr;
        gap: 6px;
    }
    .tapanyag-forras-link { align-self: flex-start; }
    .tapanyag-forras-bar-wrap { grid-column: 1; width: 100%; }
    .tapanyag-forras-ertek { text-align: left; }
}

CSS;

    wp_register_style( 'tapanyag-single-style', false );
    wp_enqueue_style( 'tapanyag-single-style' );
    wp_add_inline_style( 'tapanyag-single-style', $css );
}
add_action( 'wp_enqueue_scripts', 'tapanyag_single_inline_styles' );
