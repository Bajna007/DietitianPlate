<?php

/**
 * 25. – Alapanyag Archív CSS
 */
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_enqueue_scripts', function () {
    if ( ! is_post_type_archive( 'alapanyag' ) && ! is_page_template( 'page-alapanyag.php' ) ) return;

    $css = <<<CSS

.aa-archive-wrap {
    --r-accent:        #2ecc71;
    --r-accent-dark:   #27ae60;
    --r-accent-deeper: #1e8449;
    --r-accent-soft:   rgba(46,204,113,0.10);
    --r-accent-glow:   rgba(46,204,113,0.25);
    --r-bg:            #f0f2f1;
    --r-card:          #ffffff;
    --r-border:        #e2e6e4;
    --r-text:          #1a1d1b;
    --r-text-muted:    #7c8a83;
    --r-radius:        16px;
    --r-radius-sm:     10px;
    --r-shadow:        0 4px 24px rgba(0,0,0,0.05);
    --r-shadow-lg:     0 8px 40px rgba(0,0,0,0.08);
    --r-shadow-xl:     0 16px 60px rgba(0,0,0,0.10);
    --r-transition:    0.25s cubic-bezier(0.4,0,0.2,1);
    --r-hero-dark:     #1a1d1b;
    --r-pag-bg:        #eef0ef;
    --r-pag-active:    #1a3a2a;
    --r-pag-shadow:    6px 6px 14px rgba(0,0,0,0.08), -4px -4px 10px rgba(255,255,255,0.7);
    --r-pag-inset:     inset 2px 2px 5px rgba(0,0,0,0.06), inset -2px -2px 5px rgba(255,255,255,0.5);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: var(--r-bg);
    min-height: 100vh;
}

.aa-archive-wrap * { box-sizing: border-box; }

/* ═══ HERO ═══ */

.aa-archive-wrap .aa-hero {
    position: relative;
    overflow: visible;
    z-index: 10;
    padding: 56px 24px 0;
    text-align: center;
    background-color: var(--r-hero-dark);
    background-image:
        linear-gradient(rgba(255,255,255,0.015) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 1px),
        repeating-linear-gradient(45deg, transparent, transparent 40px, rgba(255,255,255,0.006) 40px, rgba(255,255,255,0.006) 41px),
        radial-gradient(ellipse at 20% 50%, rgba(46,204,113,0.12) 0%, transparent 55%),
        radial-gradient(ellipse at 80% 50%, rgba(46,204,113,0.06) 0%, transparent 50%),
        radial-gradient(circle at 50% 0%, rgba(243,156,18,0.04) 0%, transparent 40%),
        linear-gradient(160deg, #1a1d1b 0%, #22261f 30%, #1e211c 60%, #1a1d1b 100%);
    background-size: 60px 60px, 60px 60px, auto, auto, auto, auto, auto;
}

.aa-archive-wrap .aa-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.03'/%3E%3C/svg%3E");
    background-repeat: repeat;
    background-size: 256px 256px;
    pointer-events: none;
    z-index: 0;
}

.aa-archive-wrap .aa-hero::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent 0%, rgba(46,204,113,0.3) 30%, rgba(46,204,113,0.5) 50%, rgba(46,204,113,0.3) 70%, transparent 100%);
    z-index: 1;
}

.aa-archive-wrap .aa-hero > * { position: relative; z-index: 2; }

.aa-archive-wrap .aa-hero-title {
    font-size: clamp(2rem, 5vw, 3rem) !important;
    font-weight: 800 !important;
    margin: 0 0 8px !important;
    color: #fff !important;
    letter-spacing: -0.02em !important;
    line-height: 1.2 !important;
}

.aa-archive-wrap .aa-hero-subtitle {
    font-size: 1rem !important;
    color: rgba(255,255,255,0.5) !important;
    margin: 0 0 32px !important;
    font-weight: 400 !important;
}

.aa-archive-wrap .aa-hero-subtitle strong {
    color: var(--r-accent) !important;
    font-weight: 700 !important;
}

/* ═══ KERESŐSÁV ═══ */

.aa-archive-wrap .aa-search-bar {
    position: relative;
    max-width: 680px;
    margin: 0 auto;
    padding: 0 0 32px;
    z-index: 200;
}

.aa-archive-wrap .aa-search-inner {
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
    padding: 16px 24px !important;
    background: rgba(255,255,255,0.08) !important;
    backdrop-filter: blur(12px) !important;
    -webkit-backdrop-filter: blur(12px) !important;
    border: 1px solid rgba(255,255,255,0.12) !important;
    border-radius: 999px !important;
    box-shadow: 0 4px 24px rgba(0,0,0,0.2), inset 0 1px 0 rgba(255,255,255,0.05) !important;
    transition: all var(--r-transition);
    width: 100% !important;
    position: relative;
    z-index: 201;
}

.aa-archive-wrap .aa-search-inner:focus-within {
    background: rgba(255,255,255,0.12) !important;
    border-color: rgba(46,204,113,0.4) !important;
    box-shadow: 0 4px 30px rgba(0,0,0,0.25), 0 0 20px rgba(46,204,113,0.15), inset 0 1px 0 rgba(255,255,255,0.08) !important;
}

.aa-archive-wrap .aa-search-svg {
    color: rgba(255,255,255,0.4);
    flex-shrink: 0;
    transition: color var(--r-transition);
    width: 20px;
    height: 20px;
}

.aa-archive-wrap .aa-search-inner:focus-within .aa-search-svg { color: var(--r-accent); }

.aa-archive-wrap .aa-search-input {
    flex: 1 !important;
    border: none !important;
    background: none !important;
    font-size: 0.95rem !important;
    font-weight: 500 !important;
    color: #fff !important;
    padding: 0 !important;
    outline: none !important;
    box-shadow: none !important;
    min-width: 0;
    margin: 0 !important;
    height: auto !important;
    line-height: 1.4 !important;
    -webkit-appearance: none !important;
}

.aa-archive-wrap .aa-search-input::placeholder {
    color: rgba(255,255,255,0.35) !important;
    font-weight: 400 !important;
}

.aa-archive-wrap .aa-search-clear {
    display: none;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 50% !important;
    border: none !important;
    background: rgba(255,255,255,0.1) !important;
    color: rgba(255,255,255,0.5) !important;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all var(--r-transition);
    flex-shrink: 0;
    padding: 0 !important;
    margin: 0 !important;
    -webkit-appearance: none !important;
}

.aa-archive-wrap .aa-search-clear:hover {
    background: rgba(255,255,255,0.2) !important;
    color: #fff !important;
}

/* ═══ DROPDOWN ═══ */

.aa-archive-wrap .aa-search-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    margin-top: -24px;
    background: var(--r-card);
    border: 1px solid var(--r-border);
    border-radius: 0 0 var(--r-radius) var(--r-radius);
    box-shadow: var(--r-shadow-xl);
    z-index: 300;
    display: none;
    overflow: hidden;
    max-height: 400px;
    overflow-y: auto;
}

.aa-archive-wrap .aa-search-dropdown.is-open { display: block; }

.aa-archive-wrap .aa-dd-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    text-decoration: none !important;
    color: var(--r-text) !important;
    transition: background var(--r-transition);
    border-bottom: 1px solid var(--r-border);
}

.aa-archive-wrap .aa-dd-item:last-child { border-bottom: none; }
.aa-archive-wrap .aa-dd-item:hover { background: var(--r-accent-soft); }

.aa-archive-wrap .aa-dd-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
}

.aa-archive-wrap .aa-dd-title {
    font-weight: 600;
    font-size: 0.9rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.aa-archive-wrap .aa-dd-meta { display: flex; flex-wrap: wrap; gap: 6px; }

.aa-archive-wrap .aa-dd-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 600;
    line-height: 1.4;
}

.aa-archive-wrap .aa-dd-badge-kcal { background: rgba(46,204,113,0.12); color: #27ae60; }
.aa-archive-wrap .aa-dd-badge-prot { background: rgba(52,152,219,0.10); color: #2980b9; }
.aa-archive-wrap .aa-dd-badge-off { background: rgba(243,156,18,0.12); color: #e67e22; }
.aa-archive-wrap .aa-dd-badge-usda { background: rgba(52,152,219,0.12); color: #2471a3; }

.aa-archive-wrap .aa-dd-empty {
    padding: 16px 20px;
    text-align: center;
    font-size: 0.85rem;
    color: var(--r-text-muted);
}

/* ═══ KÖZÖS MAX-WIDTH ═══ */

.aa-archive-wrap .aa-toolbar,
.aa-archive-wrap .aa-szuro-panel,
.aa-archive-wrap .aa-grid,
.aa-archive-wrap .aa-pagination {
    max-width: 1200px;
    margin-left: auto;
    margin-right: auto;
    padding-left: 20px;
    padding-right: 20px;
}

/* ═══ TOOLBAR ═══ */

.aa-archive-wrap .aa-toolbar {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    margin-top: 28px;
    margin-bottom: 20px;
    gap: 12px;
    flex-wrap: wrap;
    background: var(--r-card);
    border: 1px solid var(--r-border);
    border-radius: var(--r-radius);
    padding: 14px 20px;
    box-shadow: var(--r-shadow);
    position: relative;
    z-index: 5;
}

.aa-archive-wrap .aa-toolbar-left { display: flex; align-items: center; }

.aa-archive-wrap .aa-toolbar-right {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.aa-archive-wrap .aa-filter-toggle-btn {
    display: inline-flex !important;
    align-items: center !important;
    gap: 8px !important;
    padding: 10px 22px !important;
    border: none !important;
    border-radius: var(--r-radius-sm) !important;
    background: var(--r-accent) !important;
    color: #fff !important;
    font-size: 0.85rem !important;
    font-weight: 700 !important;
    cursor: pointer;
    transition: all var(--r-transition);
    position: relative;
    line-height: 1.4 !important;
    box-shadow: 0 3px 10px var(--r-accent-glow) !important;
    outline: none !important;
    -webkit-appearance: none !important;
}

.aa-archive-wrap .aa-filter-toggle-btn:hover {
    background: var(--r-accent-dark) !important;
    box-shadow: 0 4px 16px var(--r-accent-glow) !important;
    transform: translateY(-1px);
}

.aa-archive-wrap .aa-filter-toggle-btn.is-active {
    background: var(--r-accent-deeper) !important;
    box-shadow: 0 2px 8px rgba(30,132,73,0.3), inset 0 1px 2px rgba(0,0,0,0.1) !important;
    transform: translateY(0);
}

.aa-archive-wrap .aa-filter-toggle-btn svg {
    flex-shrink: 0;
    width: 18px;
    height: 18px;
    stroke: #fff !important;
}

.aa-archive-wrap .aa-filter-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    border-radius: 999px;
    background: rgba(255,255,255,0.25);
    color: #fff;
    font-size: 0.68rem;
    font-weight: 800;
    line-height: 1;
}

.aa-archive-wrap .aa-toolbar-count {
    font-size: 0.82rem;
    color: var(--r-text-muted);
    font-weight: 700;
}

.aa-archive-wrap .aa-toolbar-count strong { color: var(--r-text); }

.aa-archive-wrap .aa-toolbar-group { display: flex; align-items: center; gap: 6px; }

.aa-archive-wrap .aa-toolbar-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--r-text-muted);
    white-space: nowrap;
}

.aa-archive-wrap .aa-col-btn {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 34px !important;
    height: 34px !important;
    padding: 0 !important;
    border-radius: 8px !important;
    border: 1.5px solid var(--r-border) !important;
    background: var(--r-bg) !important;
    color: var(--r-text) !important;
    font-size: 0.8rem !important;
    font-weight: 700 !important;
    cursor: pointer;
    transition: all var(--r-transition);
    line-height: 1 !important;
    box-shadow: none !important;
    outline: none !important;
    -webkit-appearance: none !important;
}

.aa-archive-wrap .aa-col-btn:hover {
    border-color: var(--r-accent) !important;
    color: var(--r-accent-dark) !important;
    background: var(--r-accent-soft) !important;
}

.aa-archive-wrap .aa-col-btn.is-active {
    background: var(--r-accent) !important;
    color: #fff !important;
    border-color: var(--r-accent) !important;
    box-shadow: 0 2px 6px var(--r-accent-glow) !important;
}

.aa-archive-wrap .aa-pp-btn {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 6px 14px !important;
    border-radius: 999px !important;
    border: 1.5px solid var(--r-border) !important;
    background: var(--r-bg) !important;
    color: var(--r-text) !important;
    font-size: 0.8rem !important;
    font-weight: 600 !important;
    text-decoration: none !important;
    transition: all var(--r-transition);
    line-height: 1.4 !important;
    cursor: pointer;
    outline: none !important;
    box-shadow: none !important;
    -webkit-appearance: none !important;
}

.aa-archive-wrap .aa-pp-btn:hover {
    border-color: var(--r-accent) !important;
    color: var(--r-accent-dark) !important;
    background: var(--r-accent-soft) !important;
}

.aa-archive-wrap .aa-pp-btn.is-active {
    background: var(--r-accent) !important;
    color: #fff !important;
    border-color: var(--r-accent) !important;
    box-shadow: 0 2px 6px var(--r-accent-glow) !important;
}

.aa-archive-wrap .aa-toolbar-select {
    padding: 8px 32px 8px 12px !important;
    border-radius: var(--r-radius-sm) !important;
    border: 1.5px solid var(--r-border) !important;
    background: var(--r-card) !important;
    color: var(--r-text) !important;
    font-size: 0.82rem !important;
    font-weight: 600 !important;
    cursor: pointer;
    -webkit-appearance: none !important;
    appearance: none !important;
    outline: none !important;
    box-shadow: none !important;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%237c8a83' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E") !important;
    background-repeat: no-repeat !important;
    background-position: right 10px center !important;
    background-size: 12px !important;
    transition: all var(--r-transition);
}

.aa-archive-wrap .aa-toolbar-select:hover,
.aa-archive-wrap .aa-toolbar-select:focus {
    border-color: var(--r-accent) !important;
}

/* ═══ SZŰRŐ PANEL ═══ */

.aa-archive-wrap .aa-szuro-panel {
    background: var(--r-card);
    border-radius: var(--r-radius);
    margin-bottom: 24px;
    overflow: hidden;
    max-height: 0;
    transition: max-height 0.4s cubic-bezier(0.4,0,0.2,1), border-color 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid transparent;
    box-shadow: none;
    position: relative;
    z-index: 4;
}

.aa-archive-wrap .aa-szuro-panel.is-open {
    border-color: var(--r-border);
    box-shadow: var(--r-shadow-lg);
}

.aa-archive-wrap .aa-szuro-rows {
    padding: 20px 28px 12px;
    display: flex;
    flex-direction: column;
    gap: 0;
}

.aa-archive-wrap .aa-szuro-row {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 11px 0;
}

.aa-archive-wrap .aa-szuro-row-range { align-items: center; }

.aa-archive-wrap .aa-szuro-label {
    min-width: 90px;
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--r-text-muted);
    flex-shrink: 0;
}

.aa-archive-wrap .aa-szuro-row-separator {
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--r-border) 10%, var(--r-border) 90%, transparent);
    margin: 0;
}

.aa-archive-wrap .aa-szuro-chips { display: flex; flex-wrap: wrap; gap: 7px; }

.aa-archive-wrap .aa-chip {
    padding: 7px 18px !important;
    border-radius: 999px !important;
    border: 1.5px solid var(--r-border) !important;
    background: var(--r-card) !important;
    color: var(--r-text) !important;
    font-size: 0.8rem !important;
    font-weight: 600 !important;
    cursor: pointer;
    transition: all var(--r-transition);
    white-space: nowrap;
    line-height: 1.4;
    box-shadow: none !important;
    outline: none !important;
    -webkit-appearance: none !important;
}

.aa-archive-wrap .aa-chip:hover {
    border-color: var(--r-accent) !important;
    color: var(--r-accent-dark) !important;
    background: var(--r-accent-soft) !important;
}

.aa-archive-wrap .aa-chip.is-active {
    background: var(--r-accent) !important;
    color: #fff !important;
    border-color: var(--r-accent) !important;
    box-shadow: 0 2px 8px var(--r-accent-glow) !important;
}

/* ═══ FORRÁS LEÍRÁS ═══ */

.aa-archive-wrap .aa-source-desc { display: none; margin-top: 4px; padding-left: 0; }

.aa-archive-wrap .aa-source-desc-inner {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px 16px;
    background: rgba(46,204,113,0.05);
    border: 1px solid rgba(46,204,113,0.15);
    border-radius: var(--r-radius-sm);
    font-size: 0.78rem;
    line-height: 1.5;
    color: var(--r-text-muted);
    animation: aaFadeIn 0.3s ease;
}

.aa-archive-wrap .aa-source-desc-icon {
    flex-shrink: 0;
    color: var(--r-accent);
    margin-top: 1px;
}

/* ═══ RANGE SLIDER ═══ */

.aa-archive-wrap .aa-range-wrap {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 16px;
    min-width: 0;
}

.aa-archive-wrap .aa-range-track {
    position: relative;
    flex: 1;
    height: 6px;
    background: var(--r-border);
    border-radius: 999px;
    min-width: 80px;
    z-index: 1;
}

.aa-archive-wrap .aa-range-fill {
    position: absolute;
    top: 0;
    height: 100%;
    background: linear-gradient(90deg, var(--r-accent), var(--r-accent-dark));
    border-radius: 999px;
    pointer-events: none;
    z-index: 2;
}

.aa-archive-wrap .aa-range-thumb {
    position: absolute;
    top: 50%;
    left: 0;
    width: 100%;
    height: 24px;
    margin: 0 !important;
    padding: 0 !important;
    background: transparent !important;
    border: none !important;
    outline: none !important;
    box-shadow: none !important;
    -webkit-appearance: none !important;
    appearance: none !important;
    pointer-events: none;
    transform: translateY(-50%);
    z-index: 3;
}

.aa-archive-wrap .aa-range-thumb::-webkit-slider-runnable-track {
    height: 6px;
    background: transparent !important;
    border: none !important;
}

.aa-archive-wrap .aa-range-thumb::-webkit-slider-thumb {
    -webkit-appearance: none !important;
    appearance: none !important;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: var(--r-card) !important;
    border: 3px solid var(--r-accent) !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15) !important;
    cursor: pointer;
    pointer-events: all;
    transition: box-shadow 0.2s ease, transform 0.2s ease;
    position: relative;
    z-index: 4;
    margin-top: -8px;
}

.aa-archive-wrap .aa-range-thumb::-webkit-slider-thumb:hover {
    box-shadow: 0 2px 12px rgba(0,0,0,0.18), 0 0 0 6px var(--r-accent-glow) !important;
    transform: scale(1.1);
}

.aa-archive-wrap .aa-range-thumb::-webkit-slider-thumb:active {
    box-shadow: 0 2px 12px rgba(0,0,0,0.22), 0 0 0 8px var(--r-accent-glow) !important;
    transform: scale(1.15);
}

.aa-archive-wrap .aa-range-thumb::-moz-range-thumb {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: var(--r-card) !important;
    border: 3px solid var(--r-accent) !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15) !important;
    cursor: pointer;
    pointer-events: all;
}

.aa-archive-wrap .aa-range-thumb::-moz-range-track {
    background: transparent !important;
    border: none !important;
    height: 6px;
}

/* ── RANGE INPUTOK ── */

.aa-archive-wrap .aa-range-inputs {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
    white-space: nowrap;
}

.aa-archive-wrap .aa-range-input-group {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    background: var(--r-bg);
    border: 1.5px solid var(--r-border);
    border-radius: var(--r-radius-sm);
    padding: 5px 8px;
    transition: border-color var(--r-transition);
}

.aa-archive-wrap .aa-range-input-group:focus-within {
    border-color: var(--r-accent);
    box-shadow: 0 0 0 3px var(--r-accent-glow);
}

.aa-archive-wrap .aa-range-num {
    width: 44px;
    border: none !important;
    background: none !important;
    outline: none !important;
    box-shadow: none !important;
    color: var(--r-text) !important;
    font-size: 0.8rem !important;
    font-weight: 700 !important;
    text-align: center;
    padding: 0 !important;
    margin: 0 !important;
    -webkit-appearance: none !important;
    -moz-appearance: textfield !important;
}

.aa-archive-wrap .aa-range-num::-webkit-inner-spin-button,
.aa-archive-wrap .aa-range-num::-webkit-outer-spin-button {
    -webkit-appearance: none !important;
    margin: 0;
}

.aa-archive-wrap .aa-range-unit {
    font-size: 0.68rem;
    font-weight: 600;
    color: var(--r-text-muted);
    white-space: nowrap;
    flex-shrink: 0;
}

.aa-archive-wrap .aa-range-separator {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--r-text-muted);
    padding: 0 2px;
    flex-shrink: 0;
}

.aa-archive-wrap .aa-range-per {
    font-size: 0.68rem;
    font-weight: 700;
    color: var(--r-text-muted);
    white-space: nowrap;
    opacity: 0.7;
    flex-shrink: 0;
    margin-left: 2px;
}

/* ═══ SZŰRŐ FOOTER ═══ */

.aa-archive-wrap .aa-szuro-footer {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    padding: 14px 28px 14px calc(28px + 90px + 16px);
    border-top: 1px solid var(--r-border);
    background: linear-gradient(135deg, var(--r-bg) 0%, rgba(255,255,255,0.5) 100%);
}

.aa-archive-wrap .aa-szuro-reset-btn {
    background: var(--r-card) !important;
    border: 1.5px solid var(--r-border) !important;
    padding: 8px 22px !important;
    border-radius: 999px !important;
    font-size: 0.8rem !important;
    font-weight: 700 !important;
    color: var(--r-text-muted) !important;
    cursor: pointer;
    transition: all var(--r-transition);
    box-shadow: 0 1px 4px rgba(0,0,0,0.04) !important;
    outline: none !important;
    -webkit-appearance: none !important;
}

.aa-archive-wrap .aa-szuro-reset-btn:hover {
    border-color: var(--r-accent) !important;
    color: var(--r-accent-dark) !important;
    background: var(--r-accent-soft) !important;
    box-shadow: 0 2px 8px var(--r-accent-glow) !important;
}

/* ═══ GRID ═══ */

.aa-archive-wrap .aa-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
    margin-bottom: 40px;
    min-height: 200px;
}

/* ═══ KÁRTYA ═══ */

.aa-archive-wrap .aa-card {
    display: flex;
    flex-direction: column;
    background: var(--r-card);
    border: 1px solid var(--r-border);
    border-radius: var(--r-radius);
    box-shadow: var(--r-shadow);
    text-decoration: none !important;
    color: var(--r-text) !important;
    overflow: hidden;
    transition: all var(--r-transition);
    animation: aaFadeInUp 0.35s ease-out both;
    position: relative;
}

.aa-archive-wrap .aa-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.10);
    border-color: var(--r-accent);
}

.aa-archive-wrap .aa-card-header { padding: 20px 20px 0; }

.aa-archive-wrap .aa-card-title {
    font-size: 0.88rem !important;
    font-weight: 700 !important;
    line-height: 1.4 !important;
    margin: 0 !important;
    color: var(--r-text) !important;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    transition: color var(--r-transition);
}

.aa-archive-wrap .aa-card:hover .aa-card-title { color: var(--r-accent-dark) !important; }

.aa-archive-wrap .aa-card-original {
    font-size: 0.68rem;
    color: var(--r-text-muted);
    font-style: italic;
    margin-top: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    opacity: 0.55;
}

.aa-archive-wrap .aa-card-source-badges {
    position: absolute;
    top: 0;
    right: 0;
    display: flex;
    gap: 0;
}

.aa-archive-wrap .aa-badge {
    padding: 5px 10px;
    font-size: 0.55rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    line-height: 1;
    border-bottom-left-radius: 8px;
}

.aa-archive-wrap .aa-badge-off { background: #f39c12; color: #fff; }
.aa-archive-wrap .aa-badge-usda { background: #2980b9; color: #fff; }
.aa-archive-wrap .aa-badge-off + .aa-badge-usda { border-bottom-left-radius: 0; }

.aa-archive-wrap .aa-card-kcal {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0;
    margin: 16px 16px 0;
    padding: 20px 16px;
    background: linear-gradient(135deg, #f7faf8 0%, #eef5f0 100%);
    border: 1px solid rgba(46,204,113,0.10);
    border-radius: 14px;
    text-align: center;
    position: relative;
}

.aa-archive-wrap .aa-card-kcal-num {
    font-size: 2.2rem;
    font-weight: 900;
    letter-spacing: -0.04em;
    line-height: 1;
    color: #1a1d1b;
}

.aa-archive-wrap .aa-card-kcal-side {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    margin-left: 6px;
    padding-top: 2px;
}

.aa-archive-wrap .aa-card-kcal-unit {
    font-size: 0.78rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #1a1d1b;
    line-height: 1;
}

.aa-archive-wrap .aa-card-kcal-per {
    font-size: 0.68rem;
    font-weight: 600;
    color: rgba(26,29,27,0.3);
    line-height: 1.5;
    margin-top: 1px;
}

.aa-archive-wrap .aa-card:hover .aa-card-kcal {
    background: linear-gradient(135deg, #f2f9f4 0%, #e8f5eb 100%);
    border-color: rgba(46,204,113,0.18);
}

/* ═══ TÁPANYAG SÁV ═══ */

.aa-archive-wrap .aa-card-nutrients {
    display: flex;
    align-items: stretch;
    margin: 12px 16px 20px;
    overflow: hidden;
    gap: 2px;
}

.aa-archive-wrap .aa-nutrient {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 12px 4px;
    background: var(--r-bg);
    gap: 5px;
}

.aa-archive-wrap .aa-nutrient:first-child { border-radius: 10px 0 0 10px; }
.aa-archive-wrap .aa-nutrient:last-child { border-radius: 0 10px 10px 0; }

.aa-archive-wrap .aa-nutrient-value {
    font-size: 1.1rem;
    font-weight: 800;
    line-height: 1;
    letter-spacing: -0.02em;
}

.aa-archive-wrap .aa-nutrient-g { font-size: 0.62rem; font-weight: 600; opacity: 0.5; margin-left: 1px; }

.aa-archive-wrap .aa-nutrient-name {
    font-size: 0.52rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    line-height: 1;
}

.aa-archive-wrap .aa-nutrient-prot .aa-nutrient-value { color: #27ae60; }
.aa-archive-wrap .aa-nutrient-prot .aa-nutrient-name { color: #27ae60; opacity: 0.7; }
.aa-archive-wrap .aa-nutrient-carb .aa-nutrient-value { color: #2980b9; }
.aa-archive-wrap .aa-nutrient-carb .aa-nutrient-name { color: #2980b9; opacity: 0.7; }
.aa-archive-wrap .aa-nutrient-fat .aa-nutrient-value { color: #e67e22; }
.aa-archive-wrap .aa-nutrient-fat .aa-nutrient-name { color: #e67e22; opacity: 0.7; }

.aa-archive-wrap .aa-card:hover .aa-nutrient { background: rgba(46,204,113,0.04); }

/* ═══ PAGINATION ═══ */

.aa-archive-wrap .aa-pagination {
    margin-top: 0;
    margin-bottom: 60px;
    display: flex;
    justify-content: center;
}

.aa-archive-wrap .aa-pag-inner {
    list-style: none;
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
    padding: 16px 24px;
    flex-wrap: wrap;
    justify-content: center;
    background: var(--r-pag-bg);
    border-radius: var(--r-radius);
    box-shadow: var(--r-pag-shadow);
}

.aa-archive-wrap .aa-pag-btn {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    min-width: 42px;
    height: 42px;
    padding: 0 14px !important;
    border-radius: 12px !important;
    border: none !important;
    background: var(--r-pag-bg) !important;
    color: var(--r-text) !important;
    font-size: 0.88rem !important;
    font-weight: 600 !important;
    text-decoration: none !important;
    transition: all var(--r-transition);
    box-shadow: var(--r-pag-shadow) !important;
    cursor: pointer;
    outline: none !important;
    -webkit-appearance: none !important;
}

.aa-archive-wrap .aa-pag-btn:hover:not(.is-disabled):not(.is-active) {
    background: #e4e8e6 !important;
    box-shadow: var(--r-pag-inset) !important;
    color: var(--r-accent-dark) !important;
}

.aa-archive-wrap .aa-pag-btn.is-active {
    background: var(--r-pag-active) !important;
    color: #fff !important;
    box-shadow: 0 4px 12px rgba(26,58,42,0.3), inset 0 1px 0 rgba(255,255,255,0.05) !important;
    font-weight: 700 !important;
}

.aa-archive-wrap .aa-pag-btn.is-disabled {
    opacity: 0.35;
    cursor: default;
    box-shadow: none !important;
}

.aa-archive-wrap .aa-pag-dots {
    border: none !important;
    background: transparent !important;
    color: var(--r-text-muted) !important;
    min-width: auto;
    padding: 0 6px !important;
    box-shadow: none !important;
    font-weight: 700 !important;
    letter-spacing: 0.1em;
    font-size: 0.88rem;
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* ═══ LOADING / EMPTY ═══ */

.aa-archive-wrap .aa-loading {
    grid-column: 1 / -1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 60px 20px;
    color: var(--r-text-muted);
    font-size: 0.9rem;
}

.aa-archive-wrap .aa-loading-spinner {
    width: 36px;
    height: 36px;
    border: 3px solid var(--r-border);
    border-top-color: var(--r-accent);
    border-radius: 50%;
    animation: aaSpin 0.8s linear infinite;
}

.aa-archive-wrap .aa-empty {
    grid-column: 1 / -1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    text-align: center;
}

.aa-archive-wrap .aa-empty-icon { font-size: 2.5rem; margin-bottom: 12px; }
.aa-archive-wrap .aa-empty-text { font-size: 1.1rem; font-weight: 700; color: var(--r-text); margin-bottom: 4px; }
.aa-archive-wrap .aa-empty-sub { font-size: 0.88rem; color: var(--r-text-muted); }

/* ═══ FOCUS RESET ═══ */

.aa-archive-wrap *:focus,
.aa-archive-wrap *:focus-visible,
.aa-archive-wrap *:focus-within { outline: none !important; outline-offset: 0 !important; }

.aa-archive-wrap button:focus,
.aa-archive-wrap button:focus-visible,
.aa-archive-wrap button:active,
.aa-archive-wrap [type="button"]:focus,
.aa-archive-wrap [type="button"]:focus-visible { outline: none !important; outline-color: transparent !important; }

.aa-archive-wrap a:focus,
.aa-archive-wrap a:focus-visible { outline: none !important; text-decoration: none !important; }

.aa-archive-wrap input:focus,
.aa-archive-wrap input:focus-visible { outline: none !important; border-color: inherit !important; }

/* ═══ ANIMÁCIÓK ═══ */

@keyframes aaFadeInUp {
    from { opacity: 0; transform: translateY(14px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes aaFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes aaSpin {
    to { transform: rotate(360deg); }
}

/* ═══ RESPONSIVE – 900px ═══ */

@media (max-width: 900px) {
    .aa-archive-wrap .aa-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 16px;
    }
    .aa-archive-wrap .aa-toolbar-cols {
        display: none !important;
    }
    .aa-archive-wrap .aa-range-wrap {
        flex-wrap: wrap;
        gap: 10px;
    }
    .aa-archive-wrap .aa-range-track {
        flex: 1 1 100%;
        min-width: 0;
        order: 1;
    }
    .aa-archive-wrap .aa-range-inputs {
        order: 2;
        justify-content: center;
        flex: 1 1 100%;
    }
}

/* ═══ RESPONSIVE – 600px ═══ */

@media (max-width: 600px) {
    .aa-archive-wrap .aa-hero { padding: 36px 16px 0; }
    .aa-archive-wrap .aa-hero-title { font-size: 1.6rem !important; }
    .aa-archive-wrap .aa-hero-subtitle { font-size: 0.88rem !important; margin-bottom: 24px !important; }
    .aa-archive-wrap .aa-search-bar { padding: 0 0 24px; }
    .aa-archive-wrap .aa-search-inner { padding: 12px 18px !important; gap: 10px !important; }
    .aa-archive-wrap .aa-search-input { font-size: 0.88rem !important; }

    .aa-archive-wrap .aa-toolbar {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 10px;
        margin-top: 20px;
        padding: 12px 16px;
    }
    .aa-archive-wrap .aa-toolbar-right { justify-content: space-between; flex-wrap: wrap; gap: 10px; }
    .aa-archive-wrap .aa-filter-toggle-btn { align-self: flex-start; }

    .aa-archive-wrap .aa-szuro-rows { padding: 12px 16px 8px; }
    .aa-archive-wrap .aa-szuro-row { flex-direction: column; align-items: flex-start; gap: 8px; }
    .aa-archive-wrap .aa-szuro-label { min-width: auto; }
    .aa-archive-wrap .aa-szuro-footer { padding: 10px 16px; padding-left: 16px; }

    .aa-archive-wrap .aa-range-wrap {
        flex-direction: column;
        gap: 10px;
        align-items: stretch;
        width: 100%;
    }
    .aa-archive-wrap .aa-range-track { min-width: 0; width: 100%; flex: none; }
    .aa-archive-wrap .aa-range-inputs { flex-wrap: nowrap; justify-content: center; gap: 5px; }
    .aa-archive-wrap .aa-range-input-group { padding: 4px 6px; }
    .aa-archive-wrap .aa-range-num { width: 38px; font-size: 0.75rem !important; }
    .aa-archive-wrap .aa-range-unit { font-size: 0.62rem; }
    .aa-archive-wrap .aa-range-separator { font-size: 0.78rem; padding: 0 1px; }
    .aa-archive-wrap .aa-range-per { font-size: 0.62rem; }

    .aa-archive-wrap .aa-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 10px; }
    .aa-archive-wrap .aa-card-header { padding: 16px 14px 0; }
    .aa-archive-wrap .aa-card-title { font-size: 0.78rem !important; }
    .aa-archive-wrap .aa-card-original { font-size: 0.6rem; }
    .aa-archive-wrap .aa-card-kcal { margin: 12px 10px 0; padding: 14px 12px; border-radius: 10px; }
    .aa-archive-wrap .aa-card-kcal-num { font-size: 1.6rem; }
    .aa-archive-wrap .aa-card-kcal-unit { font-size: 0.65rem; }
    .aa-archive-wrap .aa-card-kcal-per { font-size: 0.58rem; }
    .aa-archive-wrap .aa-card-nutrients { margin: 8px 10px 14px; }
    .aa-archive-wrap .aa-nutrient { padding: 10px 3px; }
    .aa-archive-wrap .aa-nutrient-value { font-size: 0.9rem; }
    .aa-archive-wrap .aa-nutrient-g { font-size: 0.5rem; }
    .aa-archive-wrap .aa-nutrient-name { font-size: 0.45rem; }
    .aa-archive-wrap .aa-badge { padding: 4px 8px; font-size: 0.48rem; }
    .aa-archive-wrap .aa-search-dropdown { left: 0; right: 0; margin-top: -18px; }
    .aa-archive-wrap .aa-dd-item { padding: 8px 16px; }
    .aa-archive-wrap .aa-pag-inner { padding: 12px 16px; gap: 6px; }
    .aa-archive-wrap .aa-pag-btn { min-width: 38px; height: 38px; padding: 0 10px !important; font-size: 0.82rem !important; border-radius: 10px !important; }
    .aa-archive-wrap .aa-source-desc-inner { font-size: 0.72rem; padding: 10px 12px; }
}

/* ═══ RESPONSIVE – 380px ═══ */

@media (max-width: 380px) {
    .aa-archive-wrap .aa-grid { gap: 8px; }
    .aa-archive-wrap .aa-card-header { padding: 14px 10px 0; }
    .aa-archive-wrap .aa-card-title { font-size: 0.72rem !important; }
    .aa-archive-wrap .aa-card-kcal { margin: 10px 8px 0; padding: 12px 10px; }
    .aa-archive-wrap .aa-card-kcal-num { font-size: 1.3rem; }
    .aa-archive-wrap .aa-card-nutrients { margin: 6px 8px 12px; }
    .aa-archive-wrap .aa-nutrient { padding: 8px 2px; gap: 3px; }
    .aa-archive-wrap .aa-nutrient-value { font-size: 0.78rem; }
    .aa-archive-wrap .aa-nutrient-name { font-size: 0.4rem; }
    .aa-archive-wrap .aa-toolbar-label { display: none; }
    .aa-archive-wrap .aa-range-num { width: 34px; font-size: 0.7rem !important; }
    .aa-archive-wrap .aa-range-per { display: none; }
    .aa-archive-wrap .aa-range-unit { font-size: 0.58rem; }
    .aa-archive-wrap .aa-range-input-group { padding: 3px 5px; gap: 2px; }
}


CSS;

    wp_register_style( 'aa-archive-inline', false );
    wp_enqueue_style( 'aa-archive-inline' );
    wp_add_inline_style( 'aa-archive-inline', $css );
});
