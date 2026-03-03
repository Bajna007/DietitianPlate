<?php

/**
 * 14 – Tápanyag archív CSS
 */
/**
 * 14 – Tápanyag archív CSS – v2 REWORK
 * Recept archív dizájn mintájára, konzisztens kártya méret,
 * dark hero, chip szűrők, glassmorphism kereső
 */
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_enqueue_scripts', function () {
    if ( ! is_post_type_archive( 'tapanyag' )
        && ! is_tax( 'tapanyag_tipus' )
        && ! is_tax( 'oldhatosag' )
        && ! is_tax( 'tapanyag_csoport' )
        && ! is_tax( 'tapanyag_hatas' )
        && ! is_tax( 'esszencialis' )
    ) { return; }

    $css = <<<'CSS'

:root {
    --r-accent:       #2ecc71;
    --r-accent-dark:  #27ae60;
    --r-accent-deeper:#1e8449;
    --r-accent-soft:  rgba(46,204,113,0.10);
    --r-accent-glow:  rgba(46,204,113,0.25);
    --r-bg:           #f0f2f1;
    --r-card:         #ffffff;
    --r-border:       #e2e6e4;
    --r-text:         #1a1d1b;
    --r-text-muted:   #7c8a83;
    --r-radius:       16px;
    --r-radius-sm:    10px;
    --r-shadow:       0 4px 24px rgba(0,0,0,0.05);
    --r-shadow-lg:    0 8px 40px rgba(0,0,0,0.08);
    --r-shadow-xl:    0 16px 60px rgba(0,0,0,0.10);
    --r-transition:   0.25s cubic-bezier(0.4,0,0.2,1);
    --r-hero-dark:    #1a1d1b;
    --r-pag-bg:       #eef0ef;
    --r-pag-active:   #1a3a2a;
    --r-pag-shadow:   6px 6px 14px rgba(0,0,0,0.08), -4px -4px 10px rgba(255,255,255,0.7);
    --r-pag-inset:    inset 2px 2px 5px rgba(0,0,0,0.06), inset -2px -2px 5px rgba(255,255,255,0.5);
}

.tapanyag-archiv { max-width: 100%; margin: 0 auto; padding: 0; color: var(--r-text); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
.tapanyag-archiv * { box-sizing: border-box; }


/* ═══════════════════════════════════════════
   HERO
   ═══════════════════════════════════════════ */

.tapanyag-archiv-header {
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
    padding: 56px 24px 0;
    text-align: center;
    position: relative;
    overflow: visible;
    z-index: 10;
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
    bottom: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent 0%, rgba(46,204,113,0.3) 30%, rgba(46,204,113,0.5) 50%, rgba(46,204,113,0.3) 70%, transparent 100%);
    z-index: 1;
}

.tapanyag-archiv-header h1 {
    font-size: clamp(2rem, 5vw, 3rem);
    font-weight: 800;
    margin: 0 0 8px;
    color: #fff;
    letter-spacing: -0.02em;
    position: relative;
    z-index: 2;
}

.tapanyag-archiv-subtitle {
    font-size: 1rem;
    color: rgba(255,255,255,0.5);
    margin: 0 0 32px;
    position: relative;
    z-index: 2;
}

.tapanyag-archiv-subtitle strong {
    color: var(--r-accent);
    font-weight: 700;
}


/* ═══════════════════════════════════════════
   KERESŐSÁV
   ═══════════════════════════════════════════ */

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
.tapanyag-archiv .ta-search-inner:focus-within .ta-search-svg { color: var(--r-accent); }

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
}
.tapanyag-archiv .ta-search-input::placeholder { color: rgba(255,255,255,0.35); font-weight: 400; }

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
}
.tapanyag-archiv .ta-search-clear:hover { background: rgba(255,255,255,0.2) !important; color: #fff !important; }

.tapanyag-archiv .ta-dropdown {
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
.tapanyag-archiv .ta-dropdown.is-open { display: block; }

.ta-dd-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    text-decoration: none !important;
    color: var(--r-text) !important;
    transition: background var(--r-transition);
    border-bottom: 1px solid var(--r-border);
}
.ta-dd-item:last-child { border-bottom: none; }
.ta-dd-item:hover { background: var(--r-accent-soft); }
.ta-dd-item-img {
    width: 44px;
    height: 44px;
    border-radius: 8px;
    object-fit: cover;
    flex-shrink: 0;
    background: var(--r-bg);
}
.ta-dd-item-img--placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    background: var(--r-bg);
    width: 44px;
    height: 44px;
    border-radius: 8px;
    flex-shrink: 0;
}
.ta-dd-item-body { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 3px; }
.ta-dd-item-title { font-size: 0.9rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ta-dd-item-sub { font-size: 0.75rem; color: var(--r-text-muted); font-style: italic; }
.ta-dd-item-badges { display: flex; flex-wrap: wrap; gap: 4px; }
.ta-dd-badge {
    font-size: 0.62rem;
    font-weight: 700;
    padding: 1px 8px;
    border-radius: 999px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    white-space: nowrap;
}
.ta-dd-badge--vitamin { background: rgba(245,158,11,0.12); color: #d97706; }
.ta-dd-badge--asvany { background: rgba(139,92,246,0.12); color: #7c3aed; }
.ta-dd-badge--nyomelem { background: rgba(59,130,246,0.12); color: #2563eb; }
.ta-dd-badge--oldhatosag { background: var(--r-bg); color: var(--r-text-muted); }
.ta-dd-empty { padding: 16px 20px; text-align: center; font-size: 0.85rem; color: var(--r-text-muted); }


/* ═══════════════════════════════════════════
   KÖZÖS MAX-WIDTH
   ═══════════════════════════════════════════ */

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


/* ═══════════════════════════════════════════
   TOOLBAR
   ═══════════════════════════════════════════ */

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

.tapanyag-archiv .ta-filter-toggle {
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
}
.tapanyag-archiv .ta-filter-toggle:hover { background: var(--r-accent-dark) !important; box-shadow: 0 4px 16px var(--r-accent-glow) !important; transform: translateY(-1px); }
.tapanyag-archiv .ta-filter-toggle.is-active { background: var(--r-accent-deeper) !important; box-shadow: 0 2px 8px rgba(30,132,73,0.3), inset 0 1px 2px rgba(0,0,0,0.1) !important; transform: translateY(0); }
.tapanyag-archiv .ta-filter-icon { flex-shrink: 0; width: 18px; height: 18px; stroke: #fff; }
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

.tapanyag-archiv .ta-toolbar-right { display: flex; align-items: center; gap: 20px; }
.tapanyag-archiv .ta-toolbar-count { font-size: 0.82rem; font-weight: 700; color: var(--r-text-muted); }
.tapanyag-archiv .ta-cols { display: flex; align-items: center; gap: 6px; }
.tapanyag-archiv .ta-cols-label { font-size: 0.75rem; color: var(--r-text-muted); font-weight: 600; }

.tapanyag-archiv .ta-cols-btn {
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
}
.tapanyag-archiv .ta-cols-btn:hover { border-color: var(--r-accent) !important; color: var(--r-accent-dark) !important; background: var(--r-accent-soft) !important; }
.tapanyag-archiv .ta-cols-btn.is-active { background: var(--r-accent) !important; color: #fff !important; border-color: var(--r-accent) !important; box-shadow: 0 2px 6px var(--r-accent-glow) !important; }

.tapanyag-archiv .ta-perpage { display: flex; align-items: center; gap: 6px; }
.tapanyag-archiv .ta-perpage-label { font-size: 0.75rem; color: var(--r-text-muted); font-weight: 600; white-space: nowrap; }

.tapanyag-archiv .ta-perpage-btn {
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
}
.tapanyag-archiv .ta-perpage-btn:hover { border-color: var(--r-accent) !important; color: var(--r-accent-dark) !important; background: var(--r-accent-soft) !important; }
.tapanyag-archiv .ta-perpage-btn.is-active { background: var(--r-accent) !important; color: #fff !important; border-color: var(--r-accent) !important; box-shadow: 0 2px 6px var(--r-accent-glow) !important; }


/* ═══════════════════════════════════════════
   SZŰRŐ PANEL
   ═══════════════════════════════════════════ */

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
.tapanyag-archiv .ta-szuro-panel.is-open { border-color: var(--r-border); box-shadow: var(--r-shadow-lg); }
.tapanyag-archiv .ta-szuro-rows { padding: 20px 28px 12px; display: flex; flex-direction: column; gap: 0; }

.tapanyag-archiv .ta-szuro-row { display: flex; align-items: center; gap: 16px; padding: 11px 0; }
.tapanyag-archiv .ta-szuro-label { min-width: 110px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: var(--r-text-muted); flex-shrink: 0; }
.tapanyag-archiv .ta-szuro-separator { height: 1px; background: linear-gradient(90deg, transparent, var(--r-border) 10%, var(--r-border) 90%, transparent); margin: 0; }
.tapanyag-archiv .ta-szuro-chips { display: flex; flex-wrap: wrap; gap: 7px; }

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
.tapanyag-archiv .ta-chip:hover { border-color: var(--r-accent) !important; color: var(--r-accent-dark) !important; background: var(--r-accent-soft) !important; }
.tapanyag-archiv .ta-chip.is-active { background: var(--r-accent) !important; color: #fff !important; border-color: var(--r-accent) !important; box-shadow: 0 2px 8px var(--r-accent-glow) !important; }

.tapanyag-archiv .ta-szuro-footer {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    padding: 14px 28px 14px calc(28px + 110px + 16px);
    border-top: 1px solid var(--r-border);
    background: linear-gradient(135deg, var(--r-bg) 0%, rgba(255,255,255,0.5) 100%);
}

.tapanyag-archiv .ta-szuro-reset {
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
}
.tapanyag-archiv .ta-szuro-reset:hover { border-color: var(--r-accent) !important; color: var(--r-accent-dark) !important; background: var(--r-accent-soft) !important; box-shadow: 0 2px 8px var(--r-accent-glow) !important; }


/* ═══════════════════════════════════════════
   GRID
   ═══════════════════════════════════════════ */

.tapanyag-archiv .ta-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
    margin-bottom: 40px;
}

@keyframes taFadeInUp {
    from { opacity: 0; transform: translateY(14px); }
    to { opacity: 1; transform: translateY(0); }
}


/* ═══════════════════════════════════════════
   KÁRTYA – recept mérethez igazítva
   ═══════════════════════════════════════════ */

.tapanyag-archiv .ta-kartya {
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
.tapanyag-archiv .ta-kartya:hover { transform: translateY(-4px); box-shadow: var(--r-shadow-lg); border-color: var(--r-accent); }

/* Kép */
.ta-kartya-kep { position: relative; aspect-ratio: 16 / 10; overflow: hidden; background: var(--r-bg); }
.ta-kartya-kep img { width: 100%; height: 100%; object-fit: cover; transition: transform var(--r-transition); }
.ta-kartya:hover .ta-kartya-kep img { transform: scale(1.05); }
.ta-kartya-kep-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    background: linear-gradient(135deg, #f0f2f1 0%, #e2e6e4 100%);
    color: var(--r-text-muted);
    opacity: 0.4;
}

/* Típus badge a képen */
.ta-kartya-tipus-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
.ta-kartya-tipus-badge--vitamin { background: rgba(245,158,11,0.92); color: #fff; }
.ta-kartya-tipus-badge--asvany { background: rgba(139,92,246,0.92); color: #fff; }
.ta-kartya-tipus-badge--nyomelem { background: rgba(59,130,246,0.92); color: #fff; }
.ta-kartya-tipus-badge--aminosav { background: rgba(239,68,68,0.92); color: #fff; }
.ta-kartya-tipus-badge--szenhidrat { background: rgba(16,185,129,0.92); color: #fff; }
.ta-kartya-tipus-badge--zsirsav { background: rgba(59,130,246,0.92); color: #fff; }
.ta-kartya-tipus-badge--egyeb { background: rgba(107,114,128,0.85); color: #fff; }

/* Oldhatóság badge a képen bal felső */
.ta-kartya-oldhatosag-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 0.62rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.12);
    background: rgba(255,255,255,0.88);
    color: var(--r-text-muted);
    border: 1.5px solid rgba(0,0,0,0.06);
}

/* Body */
.ta-kartya-body { padding: 16px 18px 18px; display: flex; flex-direction: column; gap: 6px; flex: 1; }
.ta-kartya-cim { font-size: 1.05rem; font-weight: 700; margin: 0; line-height: 1.3; }
.ta-kartya-kemiai { font-size: 0.78rem; color: var(--r-text-muted); font-style: italic; opacity: 0.7; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ta-kartya-osszefoglalo {
    font-size: 0.8rem;
    color: var(--r-text-muted);
    line-height: 1.45;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    margin-top: 4px;
}

/* Alsó chipek */
.ta-kartya-chips { display: flex; flex-wrap: wrap; gap: 4px; margin-top: auto; padding-top: 6px; }
.ta-kartya-chip {
    font-size: 0.62rem;
    padding: 2px 8px;
    border-radius: 999px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.ta-kartya-chip--hatas { background: rgba(46,204,113,0.10); color: #27ae60; }
.ta-kartya-chip--esszencialis { background: rgba(239,68,68,0.08); color: #dc2626; }

/* Nincs találat */
.ta-nincs { grid-column: 1 / -1; text-align: center; font-size: 1.1rem; color: var(--r-text-muted); padding: 48px 0; }

/* Loading */
.ta-loading {
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
.ta-loading-spinner {
    width: 36px;
    height: 36px;
    border: 3px solid var(--r-border);
    border-top-color: var(--r-accent);
    border-radius: 50%;
    animation: taSpin 0.8s linear infinite;
}
@keyframes taSpin { to { transform: rotate(360deg); } }


/* ═══════════════════════════════════════════
   PAGINATION
   ═══════════════════════════════════════════ */

.tapanyag-archiv .ta-pagination { margin-top: 0; margin-bottom: 60px; display: flex; justify-content: center; }

.tapanyag-archiv .ta-pagination .ta-pag-inner {
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

.tapanyag-archiv .ta-pagination .ta-pag-btn {
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
.tapanyag-archiv .ta-pag-btn:hover:not(.is-active):not(.is-disabled) { background: #e4e8e6 !important; box-shadow: var(--r-pag-inset) !important; color: var(--r-accent-dark) !important; }
.tapanyag-archiv .ta-pag-btn.is-active { background: var(--r-pag-active) !important; color: #fff !important; box-shadow: 0 4px 12px rgba(26,58,42,0.3), inset 0 1px 0 rgba(255,255,255,0.05) !important; font-weight: 700 !important; }
.tapanyag-archiv .ta-pag-btn.is-disabled { opacity: 0.35; cursor: default; box-shadow: none !important; }
.tapanyag-archiv .ta-pag-dots { border: none !important; background: transparent !important; color: var(--r-text-muted) !important; min-width: auto; padding: 0 6px !important; box-shadow: none !important; font-weight: 700 !important; letter-spacing: 0.1em; font-size: 0.88rem; }


/* ═══════════════════════════════════════════
   FOCUS RESET
   ═══════════════════════════════════════════ */

.tapanyag-archiv *:focus,
.tapanyag-archiv *:focus-visible,
.tapanyag-archiv *:focus-within { outline: none !important; outline-offset: 0 !important; }
.tapanyag-archiv button:focus,
.tapanyag-archiv button:focus-visible,
.tapanyag-archiv button:active,
.tapanyag-archiv [type="button"]:focus,
.tapanyag-archiv [type="button"]:focus-visible { outline: none !important; outline-color: transparent !important; }
.tapanyag-archiv a:focus,
.tapanyag-archiv a:focus-visible { outline: none !important; text-decoration: none !important; }
.tapanyag-archiv input:focus,
.tapanyag-archiv input:focus-visible { outline: none !important; border-color: inherit !important; }
.tapanyag-archiv button,
.tapanyag-archiv .ta-chip,
.tapanyag-archiv .ta-filter-toggle,
.tapanyag-archiv .ta-szuro-reset,
.tapanyag-archiv .ta-search-clear,
.tapanyag-archiv .ta-cols-btn { -webkit-appearance: none !important; -moz-appearance: none !important; appearance: none !important; }


/* ═══════════════════════════════════════════
   RESPONSIVE – 900px
   ═══════════════════════════════════════════ */

@media (max-width: 900px) {
    .tapanyag-archiv .ta-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 16px; }
    .tapanyag-archiv .ta-cols { display: none !important; }
}


/* ═══════════════════════════════════════════
   RESPONSIVE – 600px
   ═══════════════════════════════════════════ */

@media (max-width: 600px) {
    .tapanyag-archiv-header { padding: 36px 16px 0; }
    .tapanyag-archiv-header h1 { font-size: 1.6rem; }
    .tapanyag-archiv-subtitle { font-size: 0.88rem; margin-bottom: 24px; }
    .tapanyag-archiv .ta-search-bar { padding: 0 0 24px; }
    .tapanyag-archiv .ta-search-inner { padding: 12px 18px !important; gap: 10px !important; }
    .tapanyag-archiv .ta-search-input { font-size: 0.88rem !important; }
    .tapanyag-archiv .ta-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 10px; }
    .ta-kartya-kep { aspect-ratio: 4 / 3; }
    .ta-kartya-body { padding: 10px 12px 14px; gap: 5px; }
    .ta-kartya-cim { font-size: 0.85rem; line-height: 1.25; }
    .ta-kartya-kemiai { font-size: 0.68rem; }
    .ta-kartya-osszefoglalo { font-size: 0.72rem; }
    .ta-kartya-chips { gap: 3px; }
    .ta-kartya-chip { font-size: 0.58rem; padding: 1px 7px; }
    .ta-kartya-tipus-badge { top: 8px; right: 8px; padding: 3px 8px; font-size: 0.6rem; }
    .ta-kartya-oldhatosag-badge { top: 8px; left: 8px; padding: 3px 8px; font-size: 0.55rem; }
    .tapanyag-archiv .ta-toolbar { flex-direction: column !important; align-items: stretch !important; gap: 10px; margin-top: 20px; padding: 12px 16px; }
    .tapanyag-archiv .ta-toolbar-right { justify-content: space-between; flex-wrap: wrap; gap: 10px; }
    .tapanyag-archiv .ta-filter-toggle { align-self: flex-start; }
    .tapanyag-archiv .ta-szuro-row { flex-direction: column; align-items: flex-start; gap: 6px; }
    .tapanyag-archiv .ta-szuro-label { min-width: auto; }
    .tapanyag-archiv .ta-szuro-rows { padding: 12px 16px 8px; }
    .tapanyag-archiv .ta-szuro-footer { padding: 10px 16px; padding-left: 16px; }
    .tapanyag-archiv .ta-dropdown { left: 0; right: 0; margin-top: -18px; }
    .ta-dd-item { padding: 8px 16px; }
    .ta-dd-item-img, .ta-dd-item-img--placeholder { width: 36px; height: 36px; }
    .tapanyag-archiv .ta-pagination .ta-pag-inner { padding: 12px 16px; gap: 6px; }
    .tapanyag-archiv .ta-pag-btn { min-width: 38px; height: 38px; padding: 0 10px !important; font-size: 0.82rem !important; border-radius: 10px !important; }
}


/* ═══════════════════════════════════════════
   RESPONSIVE – 380px
   ═══════════════════════════════════════════ */

@media (max-width: 380px) {
    .tapanyag-archiv .ta-grid { gap: 8px; }
    .ta-kartya-cim { font-size: 0.78rem; }
    .ta-kartya-body { padding: 8px 10px 12px; }
    .ta-kartya-osszefoglalo { display: none; }
    .tapanyag-archiv .ta-perpage-label { display: none; }
    .tapanyag-archiv .ta-cols-label { display: none; }
}


CSS;

    wp_register_style( 'tapanyag-archive-style', false );
    wp_enqueue_style( 'tapanyag-archive-style' );
    wp_add_inline_style( 'tapanyag-archive-style', $css );
});
