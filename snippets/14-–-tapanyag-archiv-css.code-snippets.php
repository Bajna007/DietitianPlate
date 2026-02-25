<?php

/**
 * 14 – Tápanyag archív CSS
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function tapanyag_archive_inline_styles() {
    if ( ! is_post_type_archive( 'tapanyag' )
        && ! is_tax( 'tapanyag_tipus' )
        && ! is_tax( 'oldhatosag' )
        && ! is_tax( 'tapanyag_csoport' )
        && ! is_tax( 'tapanyag_hatas' )
        && ! is_tax( 'esszencialis' )
    ) { return; }

    $css = <<<CSS

.tapanyag-archiv {
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
    --t-vitamin:       #F59E0B;
    --t-vitamin-soft:  rgba(245,158,11,0.10);
    --t-zsirsav:       #3B82F6;
    --t-zsirsav-soft:  rgba(59,130,246,0.10);
    --t-szenhidrat:    #10B981;
    --t-szenhidrat-soft: rgba(16,185,129,0.10);
    --t-aminosav:      #EF4444;
    --t-aminosav-soft: rgba(239,68,68,0.10);
    --t-asvany:        #8B5CF6;
    --t-asvany-soft:   rgba(139,92,246,0.10);
    max-width: 100%;
    margin: 0 auto;
    padding: 0;
    color: var(--r-text);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: var(--r-bg);
    min-height: 100vh;
}

.tapanyag-archiv * {
    box-sizing: border-box;
}

.tapanyag-archiv-header {
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

.tapanyag-archiv-header::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.03'/%3E%3C/svg%3E");
    background-repeat: repeat;
    background-size: 256px 256px;
    pointer-events: none;
    z-index: 0;
}

.tapanyag-archiv-header::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent 0%, rgba(46,204,113,0.3) 30%, rgba(46,204,113,0.5) 50%, rgba(46,204,113,0.3) 70%, transparent 100%);
    z-index: 1;
}

.tapanyag-archiv-header > * {
    position: relative;
    z-index: 2;
}

.tapanyag-archiv-header h1 {
    font-size: clamp(2rem, 5vw, 3rem) !important;
    font-weight: 800 !important;
    margin: 0 0 8px !important;
    color: #fff !important;
    letter-spacing: -0.02em !important;
    line-height: 1.2 !important;
}

.tapanyag-archiv-subtitle {
    font-size: 1rem !important;
    color: rgba(255,255,255,0.5) !important;
    margin: 0 0 32px !important;
    font-weight: 400 !important;
}

.tapanyag-archiv-subtitle strong {
    color: var(--r-accent) !important;
    font-weight: 700 !important;
}

.tapanyag-archiv .ta-search-bar {
    position: relative;
    max-width: 680px;
    margin: 0 auto;
    padding: 0 0 32px;
    z-index: 200;
}

.tapanyag-archiv .ta-search-inner {
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

.tapanyag-archiv .ta-search-inner:focus-within {
    background: rgba(255,255,255,0.12) !important;
    border-color: rgba(46,204,113,0.4) !important;
    box-shadow: 0 4px 30px rgba(0,0,0,0.25), 0 0 20px rgba(46,204,113,0.15), inset 0 1px 0 rgba(255,255,255,0.08) !important;
}

.tapanyag-archiv .ta-search-svg {
    color: rgba(255,255,255,0.4);
    flex-shrink: 0;
    transition: color var(--r-transition);
    width: 20px;
    height: 20px;
}

.tapanyag-archiv .ta-search-inner:focus-within .ta-search-svg {
    color: var(--r-accent);
}

.tapanyag-archiv .ta-search-input {
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

.tapanyag-archiv .ta-search-input::placeholder {
    color: rgba(255,255,255,0.35) !important;
    font-weight: 400 !important;
}

.tapanyag-archiv .ta-search-clear {
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

.tapanyag-archiv .ta-search-clear:hover {
    background: rgba(255,255,255,0.2) !important;
    color: #fff !important;
}

.tapanyag-archiv .ta-search-dropdown {
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

.tapanyag-archiv .ta-search-dropdown.is-open {
    display: block;
}

.tapanyag-archiv .ta-dd-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    text-decoration: none !important;
    color: var(--r-text) !important;
    transition: background var(--r-transition);
    border-bottom: 1px solid var(--r-border);
}

.tapanyag-archiv .ta-dd-item:last-child {
    border-bottom: none;
}

.tapanyag-archiv .ta-dd-item:hover {
    background: var(--r-accent-soft);
}

.tapanyag-archiv .ta-dd-item-img {
    width: 44px;
    height: 44px;
    border-radius: 8px;
    object-fit: cover;
    flex-shrink: 0;
    background: var(--r-bg);
}

.tapanyag-archiv .ta-dd-item-img--placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
}

.tapanyag-archiv .ta-dd-item-body {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.tapanyag-archiv .ta-dd-item-title {
    font-size: 0.9rem;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.tapanyag-archiv .ta-dd-item-sub {
    font-size: 0.72rem;
    color: var(--r-text-muted);
}

.tapanyag-archiv .ta-dd-badge {
    display: inline-block;
    padding: 1px 8px;
    border-radius: 999px;
    font-size: 0.62rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    width: fit-content;
}

.tapanyag-archiv .ta-dd-badge-tipus {
    background: rgba(245,158,11,0.12);
    color: #d97706;
}

.tapanyag-archiv .ta-dd-empty {
    padding: 16px 20px;
    text-align: center;
    font-size: 0.85rem;
    color: var(--r-text-muted);
}

.tapanyag-archiv .ta-toolbar,
.tapanyag-archiv .ta-szuro-panel,
.tapanyag-archiv .ta-grid,
.tapanyag-archiv .ta-pagination {
    max-width: 1200px;
    margin-left: auto;
    margin-right: auto;
    padding-left: 20px;
    padding-right: 20px;
}

.tapanyag-archiv .ta-toolbar {
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

.tapanyag-archiv .ta-toolbar-left {
    display: flex;
    align-items: center;
}

.tapanyag-archiv .ta-toolbar-right {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.tapanyag-archiv .ta-filter-toggle-btn {
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

.tapanyag-archiv .ta-filter-toggle-btn:hover {
    background: var(--r-accent-dark) !important;
    box-shadow: 0 4px 16px var(--r-accent-glow) !important;
    transform: translateY(-1px);
}

.tapanyag-archiv .ta-filter-toggle-btn.is-active {
    background: var(--r-accent-deeper) !important;
    box-shadow: 0 2px 8px rgba(30,132,73,0.3), inset 0 1px 2px rgba(0,0,0,0.1) !important;
    transform: translateY(0);
}

.tapanyag-archiv .ta-filter-toggle-btn svg {
    flex-shrink: 0;
    width: 18px;
    height: 18px;
    stroke: #fff !important;
}

.tapanyag-archiv .ta-filter-badge {
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

.tapanyag-archiv .ta-toolbar-count {
    font-size: 0.82rem;
    color: var(--r-text-muted);
    font-weight: 700;
}

.tapanyag-archiv .ta-toolbar-count strong {
    color: var(--r-text);
}

.tapanyag-archiv .ta-toolbar-group {
    display: flex;
    align-items: center;
    gap: 6px;
}

.tapanyag-archiv .ta-toolbar-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--r-text-muted);
    white-space: nowrap;
}

.tapanyag-archiv .ta-col-btn {
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

.tapanyag-archiv .ta-col-btn:hover {
    border-color: var(--r-accent) !important;
    color: var(--r-accent-dark) !important;
    background: var(--r-accent-soft) !important;
}

.tapanyag-archiv .ta-col-btn.is-active {
    background: var(--r-accent) !important;
    color: #fff !important;
    border-color: var(--r-accent) !important;
    box-shadow: 0 2px 6px var(--r-accent-glow) !important;
}

.tapanyag-archiv .ta-pp-btn {
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

.tapanyag-archiv .ta-pp-btn:hover {
    border-color: var(--r-accent) !important;
    color: var(--r-accent-dark) !important;
    background: var(--r-accent-soft) !important;
}

.tapanyag-archiv .ta-pp-btn.is-active {
    background: var(--r-accent) !important;
    color: #fff !important;
    border-color: var(--r-accent) !important;
    box-shadow: 0 2px 6px var(--r-accent-glow) !important;
}

.tapanyag-archiv .ta-toolbar-select {
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

.tapanyag-archiv .ta-toolbar-select:hover,
.tapanyag-archiv .ta-toolbar-select:focus {
    border-color: var(--r-accent) !important;
}

.tapanyag-archiv .ta-szuro-panel {
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

.tapanyag-archiv .ta-szuro-panel.is-open {
    border-color: var(--r-border);
    box-shadow: var(--r-shadow-lg);
}

.tapanyag-archiv .ta-szuro-rows {
    padding: 20px 28px 12px;
    display: flex;
    flex-direction: column;
    gap: 0;
}

.tapanyag-archiv .ta-szuro-row {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 11px 0;
}

.tapanyag-archiv .ta-szuro-label {
    min-width: 90px;
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--r-text-muted);
    flex-shrink: 0;
}

.tapanyag-archiv .ta-szuro-row-separator {
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--r-border) 10%, var(--r-border) 90%, transparent);
    margin: 0;
}

.tapanyag-archiv .ta-szuro-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
}

.tapanyag-archiv .ta-chip {
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

.tapanyag-archiv .ta-chip:hover {
    border-color: var(--r-accent) !important;
    color: var(--r-accent-dark) !important;
    background: var(--r-accent-soft) !important;
}

.tapanyag-archiv .ta-chip.is-active {
    background: var(--r-accent) !important;
    color: #fff !important;
    border-color: var(--r-accent) !important;
    box-shadow: 0 2px 8px var(--r-accent-glow) !important;
}

.tapanyag-archiv .ta-szuro-footer {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    padding: 14px 28px 14px calc(28px + 90px + 16px);
    border-top: 1px solid var(--r-border);
    background: linear-gradient(135deg, var(--r-bg) 0%, rgba(255,255,255,0.5) 100%);
}

.tapanyag-archiv .ta-szuro-reset-btn {
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

.tapanyag-archiv .ta-szuro-reset-btn:hover {
    border-color: var(--r-accent) !important;
    color: var(--r-accent-dark) !important;
    background: var(--r-accent-soft) !important;
    box-shadow: 0 2px 8px var(--r-accent-glow) !important;
}

.tapanyag-archiv .ta-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
    margin-bottom: 40px;
    min-height: 200px;
}

.tapanyag-archiv .ta-card {
    background: var(--r-card);
    border-radius: var(--r-radius);
    box-shadow: var(--r-shadow);
    border: 1px solid var(--r-border);
    overflow: hidden;
    text-decoration: none !important;
    color: var(--r-text) !important;
    transition: all var(--r-transition);
    display: flex;
    flex-direction: column;
    animation: taFadeInUp 0.35s ease-out both;
}

.tapanyag-archiv .ta-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--r-shadow-lg);
    border-color: var(--r-accent);
}

.tapanyag-archiv .ta-card-img {
    position: relative;
    aspect-ratio: 16 / 10;
    overflow: hidden;
    background: var(--r-bg);
}

.tapanyag-archiv .ta-card-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform var(--r-transition);
}

.tapanyag-archiv .ta-card:hover .ta-card-img img {
    transform: scale(1.05);
}

.tapanyag-archiv .ta-card-img-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    background: var(--r-bg);
}

.tapanyag-archiv .ta-card-tipus {
    position: absolute;
    top: 12px;
    left: 12px;
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    color: #fff;
    border: 2px solid rgba(255,255,255,0.25);
}

.tapanyag-archiv .ta-card-tipus--vitamin {
    background: rgba(245,158,11,0.90);
}

.tapanyag-archiv .ta-card-tipus--zsirsav {
    background: rgba(59,130,246,0.90);
}

.tapanyag-archiv .ta-card-tipus--szenhidrat {
    background: rgba(16,185,129,0.90);
}

.tapanyag-archiv .ta-card-tipus--aminosav {
    background: rgba(239,68,68,0.90);
}

.tapanyag-archiv .ta-card-tipus--asvany {
    background: rgba(139,92,246,0.90);
}

.tapanyag-archiv .ta-card-oldhat {
    position: absolute;
    top: 12px;
    right: 12px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 0.65rem;
    font-weight: 700;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border: 1.5px solid rgba(255,255,255,0.3);
    color: #fff;
}

.tapanyag-archiv .ta-card-oldhat--vizben {
    background: rgba(59,130,246,0.80);
}

.tapanyag-archiv .ta-card-oldhat--zsirban {
    background: rgba(245,158,11,0.80);
}

.tapanyag-archiv .ta-card:hover .ta-card-tipus,
.tapanyag-archiv .ta-card:hover .ta-card-oldhat {
    transform: scale(1.05);
}

.tapanyag-archiv .ta-card-body {
    padding: 16px 18px 18px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    flex: 1;
}

.tapanyag-archiv .ta-card-title {
    font-size: 1.05rem !important;
    font-weight: 700 !important;
    margin: 0 !important;
    line-height: 1.3 !important;
    color: var(--r-text) !important;
}

.tapanyag-archiv .ta-card:hover .ta-card-title {
    color: var(--r-accent-dark) !important;
}

.tapanyag-archiv .ta-card-kemiai {
    font-size: 0.78rem;
    color: var(--r-text-muted);
    font-style: italic;
    margin: 0;
}

.tapanyag-archiv .ta-card-desc {
    font-size: 0.82rem;
    color: var(--r-text-muted);
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    margin: 2px 0 0;
}

.tapanyag-archiv .ta-card-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-top: auto;
    padding-top: 8px;
}

.tapanyag-archiv .ta-card-chip {
    font-size: 0.66rem;
    padding: 2px 10px;
    border-radius: 999px;
    background: var(--r-accent-soft);
    color: var(--r-accent-dark);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.tapanyag-archiv .ta-pagination {
    margin-top: 0;
    margin-bottom: 60px;
    display: flex;
    justify-content: center;
}

.tapanyag-archiv .ta-pag-inner {
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

.tapanyag-archiv .ta-pag-btn {
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

.tapanyag-archiv .ta-pag-btn:hover:not(.is-disabled):not(.is-active) {
    background: #e4e8e6 !important;
    box-shadow: var(--r-pag-inset) !important;
    color: var(--r-accent-dark) !important;
}

.tapanyag-archiv .ta-pag-btn.is-active {
    background: var(--r-pag-active) !important;
    color: #fff !important;
    box-shadow: 0 4px 12px rgba(26,58,42,0.3), inset 0 1px 0 rgba(255,255,255,0.05) !important;
    font-weight: 700 !important;
}

.tapanyag-archiv .ta-pag-btn.is-disabled {
    opacity: 0.35;
    cursor: default;
    box-shadow: none !important;
}

.tapanyag-archiv .ta-pag-dots {
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

.tapanyag-archiv .ta-loading {
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

.tapanyag-archiv .ta-loading-spinner {
    width: 36px;
    height: 36px;
    border: 3px solid var(--r-border);
    border-top-color: var(--r-accent);
    border-radius: 50%;
    animation: taSpin 0.8s linear infinite;
}

.tapanyag-archiv .ta-empty {
    grid-column: 1 / -1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    text-align: center;
}

.tapanyag-archiv .ta-empty-icon {
    font-size: 2.5rem;
    margin-bottom: 12px;
}

.tapanyag-archiv .ta-empty-text {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--r-text);
    margin-bottom: 4px;
}

.tapanyag-archiv .ta-empty-sub {
    font-size: 0.88rem;
    color: var(--r-text-muted);
}

.tapanyag-archiv *:focus,
.tapanyag-archiv *:focus-visible,
.tapanyag-archiv *:focus-within {
    outline: none !important;
    outline-offset: 0 !important;
}

.tapanyag-archiv button:focus,
.tapanyag-archiv button:focus-visible,
.tapanyag-archiv button:active,
.tapanyag-archiv [type="button"]:focus,
.tapanyag-archiv [type="button"]:focus-visible {
    outline: none !important;
    outline-color: transparent !important;
}

.tapanyag-archiv a:focus,
.tapanyag-archiv a:focus-visible {
    outline: none !important;
    text-decoration: none !important;
}

.tapanyag-archiv input:focus,
.tapanyag-archiv input:focus-visible {
    outline: none !important;
    border-color: inherit !important;
}

@keyframes taFadeInUp {
    from { opacity: 0; transform: translateY(14px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes taSpin {
    to { transform: rotate(360deg); }
}

@media (max-width: 900px) {
    .tapanyag-archiv .ta-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 16px;
    }
    .tapanyag-archiv .ta-toolbar-cols {
        display: none !important;
    }
}

@media (max-width: 600px) {
    .tapanyag-archiv-header {
        padding: 36px 16px 0;
    }
    .tapanyag-archiv-header h1 {
        font-size: 1.6rem !important;
    }
    .tapanyag-archiv-subtitle {
        font-size: 0.88rem !important;
        margin-bottom: 24px !important;
    }
    .tapanyag-archiv .ta-search-bar {
        padding: 0 0 24px;
    }
    .tapanyag-archiv .ta-search-inner {
        padding: 12px 18px !important;
        gap: 10px !important;
    }
    .tapanyag-archiv .ta-search-input {
        font-size: 0.88rem !important;
    }
    .tapanyag-archiv .ta-toolbar {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 10px;
        margin-top: 20px;
        padding: 12px 16px;
    }
    .tapanyag-archiv .ta-toolbar-right {
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
    }
    .tapanyag-archiv .ta-filter-toggle-btn {
        align-self: flex-start;
    }
    .tapanyag-archiv .ta-szuro-rows {
        padding: 12px 16px 8px;
    }
    .tapanyag-archiv .ta-szuro-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 6px;
    }
    .tapanyag-archiv .ta-szuro-label {
        min-width: auto;
    }
    .tapanyag-archiv .ta-szuro-footer {
        padding: 10px 16px;
        padding-left: 16px;
    }
    .tapanyag-archiv .ta-search-dropdown {
        left: 0;
        right: 0;
        margin-top: -18px;
    }
    .tapanyag-archiv .ta-dd-item {
        padding: 8px 16px;
    }
    .tapanyag-archiv .ta-dd-item-img {
        width: 36px;
        height: 36px;
    }
    .tapanyag-archiv .ta-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 10px;
    }
    .tapanyag-archiv .ta-card-img {
        aspect-ratio: 4 / 3;
    }
    .tapanyag-archiv .ta-card-body {
        padding: 10px 12px 14px;
        gap: 5px;
    }
    .tapanyag-archiv .ta-card-title {
        font-size: 0.85rem !important;
        line-height: 1.25 !important;
    }
    .tapanyag-archiv .ta-card-kemiai {
        font-size: 0.7rem;
    }
    .tapanyag-archiv .ta-card-desc {
        font-size: 0.72rem;
    }
    .tapanyag-archiv .ta-card-chips {
        gap: 3px;
    }
    .tapanyag-archiv .ta-card-chip {
        font-size: 0.58rem;
        padding: 1px 7px;
    }
    .tapanyag-archiv .ta-card-tipus {
        top: 8px;
        left: 8px;
        padding: 3px 8px;
        font-size: 0.6rem;
        gap: 3px;
    }
    .tapanyag-archiv .ta-card-oldhat {
        top: 8px;
        right: 8px;
        padding: 3px 7px;
        font-size: 0.55rem;
    }
    .tapanyag-archiv .ta-pag-inner {
        padding: 12px 16px;
        gap: 6px;
    }
    .tapanyag-archiv .ta-pag-btn {
        min-width: 38px;
        height: 38px;
        padding: 0 10px !important;
        font-size: 0.82rem !important;
        border-radius: 10px !important;
    }
}

@media (max-width: 380px) {
    .tapanyag-archiv .ta-grid {
        gap: 8px;
    }
    .tapanyag-archiv .ta-card-title {
        font-size: 0.78rem !important;
    }
    .tapanyag-archiv .ta-card-body {
        padding: 8px 10px 12px;
    }
    .tapanyag-archiv .ta-toolbar-label {
        display: none;
    }
}


CSS;

    wp_register_style( 'tapanyag-archive-style', false );
    wp_enqueue_style( 'tapanyag-archive-style' );
    wp_add_inline_style( 'tapanyag-archive-style', $css );
}
add_action( 'wp_enqueue_scripts', 'tapanyag_archive_inline_styles' );
