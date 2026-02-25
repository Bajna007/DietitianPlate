<?php

/**
 * 08 – Recept archív CSS
 */
/**
 * 08 – Recept archív CSS – VÉGLEGES v11
 * Dropdown z-index fix + alaphelyzet gomb balra (Összes alá)
 */
function recept_archive_inline_styles() {
    if ( ! is_post_type_archive( 'recept' ) && ! is_tax( 'recept_kategoria' ) && ! is_tax( 'recept_jelleg' ) && ! is_tax( 'recept_dieta' ) ) {
        return;
    }

    $css = <<<CSS

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

.recept-archiv { max-width: 100%; margin: 0 auto; padding: 0; color: var(--r-text); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
.recept-archiv * { box-sizing: border-box; }

.recept-archiv-header {
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

.recept-archiv-header::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.03'/%3E%3C/svg%3E");
    background-repeat: repeat;
    background-size: 256px 256px;
    pointer-events: none;
    z-index: 0;
}

.recept-archiv-header::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent 0%, rgba(46,204,113,0.3) 30%, rgba(46,204,113,0.5) 50%, rgba(46,204,113,0.3) 70%, transparent 100%);
    z-index: 1;
}

.recept-archiv-header h1 { font-size: clamp(2rem, 5vw, 3rem); font-weight: 800; margin: 0 0 8px; color: #fff; letter-spacing: -0.02em; position: relative; z-index: 2; }
.recept-archiv-subtitle { font-size: 1rem; color: rgba(255,255,255,0.5); margin: 0 0 32px; position: relative; z-index: 2; }

.recept-archiv .recept-search-bar {
    position: relative;
    max-width: 680px;
    margin: 0 auto;
    padding: 0 0 32px;
    z-index: 200;
}

.recept-archiv .recept-search-inner {
    display: flex !important; align-items: center !important; gap: 12px !important;
    padding: 16px 24px !important;
    background: rgba(255,255,255,0.08) !important;
    backdrop-filter: blur(12px) !important; -webkit-backdrop-filter: blur(12px) !important;
    border: 1px solid rgba(255,255,255,0.12) !important;
    border-radius: 999px !important;
    box-shadow: 0 4px 24px rgba(0,0,0,0.2), inset 0 1px 0 rgba(255,255,255,0.05) !important;
    transition: all var(--r-transition); width: 100% !important;
    position: relative;
    z-index: 201;
}

.recept-archiv .recept-search-inner:focus-within {
    background: rgba(255,255,255,0.12) !important;
    border-color: rgba(46,204,113,0.4) !important;
    box-shadow: 0 4px 30px rgba(0,0,0,0.25), 0 0 20px rgba(46,204,113,0.15), inset 0 1px 0 rgba(255,255,255,0.08) !important;
}

.recept-archiv .recept-search-svg { color: rgba(255,255,255,0.4); flex-shrink: 0; transition: color var(--r-transition); width: 20px; height: 20px; }
.recept-archiv .recept-search-inner:focus-within .recept-search-svg { color: var(--r-accent); }

.recept-archiv .recept-search-input {
    flex: 1 !important; border: none !important; background: none !important;
    font-size: 0.95rem !important; font-weight: 500 !important; color: #fff !important;
    padding: 0 !important; outline: none !important; box-shadow: none !important;
    min-width: 0; margin: 0 !important; height: auto !important; line-height: 1.4 !important;
}

.recept-archiv .recept-search-input::placeholder { color: rgba(255,255,255,0.35); font-weight: 400; }

.recept-archiv .recept-search-clear {
    display: none; align-items: center; justify-content: center;
    width: 28px; height: 28px; border-radius: 50% !important; border: none !important;
    background: rgba(255,255,255,0.1) !important; color: rgba(255,255,255,0.5) !important;
    font-size: 0.8rem; cursor: pointer; transition: all var(--r-transition);
    flex-shrink: 0; padding: 0 !important; margin: 0 !important;
}

.recept-archiv .recept-search-clear:hover { background: rgba(255,255,255,0.2) !important; color: #fff !important; }

.recept-archiv .szuro-dropdown {
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

.recept-archiv .szuro-dropdown.is-open { display: block; }

.dd-item { display: flex; align-items: center; gap: 12px; padding: 12px 20px; text-decoration: none; color: var(--r-text); transition: background var(--r-transition); border-bottom: 1px solid var(--r-border); }
.dd-item:last-child { border-bottom: none; }
.dd-item:hover { background: var(--r-accent-soft); }
.dd-item-img { width: 44px; height: 44px; border-radius: 8px; object-fit: cover; flex-shrink: 0; background: var(--r-bg); }
.dd-item-img--placeholder { display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
.dd-item-body { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 3px; }
.dd-item-title { font-size: 0.9rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dd-item-badges { display: flex; flex-wrap: wrap; gap: 4px; }
.dd-badge { font-size: 0.62rem; font-weight: 700; padding: 1px 8px; border-radius: 999px; text-transform: uppercase; letter-spacing: 0.04em; white-space: nowrap; }
.dd-badge--vegan { background: var(--r-accent-dark); color: #fff; }
.dd-badge--vege { background: transparent; color: var(--r-accent-dark); border: 1.5px solid var(--r-accent); }
.dd-badge--tejmentes { background: rgba(155,89,182,0.12); color: #8e44ad; }
.dd-badge--tojasmentes { background: rgba(230,126,34,0.12); color: #d35400; }
.dd-badge--konnyu { background: rgba(46,204,113,0.12); color: #27ae60; }
.dd-badge--kozepes { background: rgba(243,156,18,0.12); color: #e67e22; }
.dd-badge--nehez { background: rgba(231,76,60,0.12); color: #e74c3c; }
.dd-badge--ido { background: var(--r-bg); color: var(--r-text-muted); }
.dd-empty { padding: 16px 20px; text-align: center; font-size: 0.85rem; color: var(--r-text-muted); }

.recept-archiv .recept-toolbar,
.recept-archiv .szuro-panel,
.recept-archiv .recept-grid,
.recept-archiv .recept-pagination { max-width: 1200px; margin-left: auto; margin-right: auto; padding-left: 20px; padding-right: 20px; }

.recept-archiv .recept-toolbar {
    display: flex !important; align-items: center !important; justify-content: space-between !important;
    margin-top: 28px; margin-bottom: 20px; gap: 12px; flex-wrap: wrap;
    background: var(--r-card); border: 1px solid var(--r-border); border-radius: var(--r-radius);
    padding: 14px 20px; box-shadow: var(--r-shadow);
    position: relative; z-index: 5;
}

.recept-archiv .szuro-toggle {
    display: inline-flex !important; align-items: center !important; gap: 8px !important;
    padding: 10px 22px !important; border: none !important; border-radius: var(--r-radius-sm) !important;
    background: var(--r-accent) !important; color: #fff !important;
    font-size: 0.85rem !important; font-weight: 700 !important;
    cursor: pointer; transition: all var(--r-transition);
    position: relative; line-height: 1.4 !important;
    box-shadow: 0 3px 10px var(--r-accent-glow) !important; outline: none !important;
}

.recept-archiv .szuro-toggle:hover { background: var(--r-accent-dark) !important; box-shadow: 0 4px 16px var(--r-accent-glow) !important; transform: translateY(-1px); }
.recept-archiv .szuro-toggle.is-active { background: var(--r-accent-deeper) !important; box-shadow: 0 2px 8px rgba(30,132,73,0.3), inset 0 1px 2px rgba(0,0,0,0.1) !important; transform: translateY(0); }
.recept-archiv .szuro-toggle-icon { flex-shrink: 0; width: 18px; height: 18px; stroke: #fff; }
.recept-archiv .szuro-toggle-badge { display: inline-flex; align-items: center; justify-content: center; min-width: 20px; height: 20px; padding: 0 6px; border-radius: 999px; background: rgba(255,255,255,0.25); color: #fff; font-size: 0.68rem; font-weight: 800; line-height: 1; }

.recept-archiv .recept-toolbar-right { display: flex; align-items: center; gap: 20px; }
.recept-archiv .recept-toolbar-count { font-size: 0.82rem; font-weight: 700; color: var(--r-text-muted); }
.recept-archiv .recept-cols { display: flex; align-items: center; gap: 6px; }
.recept-archiv .recept-cols-label { font-size: 0.75rem; color: var(--r-text-muted); font-weight: 600; }

.recept-archiv .recept-cols-btn {
    display: inline-flex !important; align-items: center !important; justify-content: center !important;
    width: 34px !important; height: 34px !important; padding: 0 !important;
    border-radius: 8px !important; border: 1.5px solid var(--r-border) !important;
    background: var(--r-bg) !important; color: var(--r-text) !important;
    font-size: 0.8rem !important; font-weight: 700 !important;
    cursor: pointer; transition: all var(--r-transition);
    line-height: 1 !important; box-shadow: none !important; outline: none !important;
}

.recept-archiv .recept-cols-btn:hover { border-color: var(--r-accent) !important; color: var(--r-accent-dark) !important; background: var(--r-accent-soft) !important; }
.recept-archiv .recept-cols-btn.is-active { background: var(--r-accent) !important; color: #fff !important; border-color: var(--r-accent) !important; box-shadow: 0 2px 6px var(--r-accent-glow) !important; }

.recept-archiv .recept-perpage { display: flex; align-items: center; gap: 6px; }
.recept-archiv .recept-perpage-label { font-size: 0.75rem; color: var(--r-text-muted); font-weight: 600; white-space: nowrap; }

.recept-archiv .recept-perpage-btn {
    display: inline-flex !important; align-items: center !important; justify-content: center !important;
    padding: 6px 14px !important; border-radius: 999px !important;
    border: 1.5px solid var(--r-border) !important; background: var(--r-bg) !important;
    color: var(--r-text) !important; font-size: 0.8rem !important; font-weight: 600 !important;
    text-decoration: none !important; transition: all var(--r-transition); line-height: 1.4 !important;
}

.recept-archiv .recept-perpage-btn:hover { border-color: var(--r-accent) !important; color: var(--r-accent-dark) !important; background: var(--r-accent-soft) !important; }
.recept-archiv .recept-perpage-btn.is-active { background: var(--r-accent) !important; color: #fff !important; border-color: var(--r-accent) !important; box-shadow: 0 2px 6px var(--r-accent-glow) !important; }

.recept-archiv .szuro-panel {
    background: var(--r-card); border-radius: var(--r-radius);
    margin-bottom: 24px; overflow: hidden; max-height: 0;
    transition: max-height 0.4s cubic-bezier(0.4,0,0.2,1), border-color 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid transparent; box-shadow: none;
    position: relative; z-index: 4;
}

.recept-archiv .szuro-panel.is-open { border-color: var(--r-border); box-shadow: var(--r-shadow-lg); }
.recept-archiv .szuro-panel-inner { padding: 0; }
.recept-archiv .szuro-rows { padding: 20px 28px 12px; display: flex; flex-direction: column; gap: 0; }

.recept-archiv .szuro-row { display: flex; align-items: center; gap: 16px; padding: 11px 0; }
.recept-archiv .szuro-row-label { min-width: 90px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: var(--r-text-muted); flex-shrink: 0; }
.recept-archiv .szuro-row-separator { height: 1px; background: linear-gradient(90deg, transparent, var(--r-border) 10%, var(--r-border) 90%, transparent); margin: 0; }
.recept-archiv .szuro-chips { display: flex; flex-wrap: wrap; gap: 7px; }

.recept-archiv .szuro-chip {
    padding: 7px 18px !important; border-radius: 999px !important;
    border: 1.5px solid var(--r-border) !important; background: var(--r-card) !important;
    color: var(--r-text) !important; font-size: 0.8rem !important; font-weight: 600 !important;
    cursor: pointer; transition: all var(--r-transition); white-space: nowrap;
    line-height: 1.4; box-shadow: none !important; outline: none !important;
}

.recept-archiv .szuro-chip:hover { border-color: var(--r-accent) !important; color: var(--r-accent-dark) !important; background: var(--r-accent-soft) !important; }
.recept-archiv .szuro-chip.is-active { background: var(--r-accent) !important; color: #fff !important; border-color: var(--r-accent) !important; box-shadow: 0 2px 8px var(--r-accent-glow) !important; }

.recept-archiv .szuro-footer {
    display: flex; align-items: center; justify-content: flex-start;
    padding: 14px 28px 14px calc(28px + 90px + 16px);
    border-top: 1px solid var(--r-border);
    background: linear-gradient(135deg, var(--r-bg) 0%, rgba(255,255,255,0.5) 100%);
}

.recept-archiv .szuro-alaphelyzet {
    background: var(--r-card) !important;
    border: 1.5px solid var(--r-border) !important;
    padding: 8px 22px !important; border-radius: 999px !important;
    font-size: 0.8rem !important; font-weight: 700 !important;
    color: var(--r-text-muted) !important; cursor: pointer;
    transition: all var(--r-transition);
    box-shadow: 0 1px 4px rgba(0,0,0,0.04) !important; outline: none !important;
}

.recept-archiv .szuro-alaphelyzet:hover { border-color: var(--r-accent) !important; color: var(--r-accent-dark) !important; background: var(--r-accent-soft) !important; box-shadow: 0 2px 8px var(--r-accent-glow) !important; }
.recept-archiv .recept-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-bottom: 40px; }

@keyframes fadeInUp { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }

.recept-archiv .recept-kartya {
    background: var(--r-card); border-radius: var(--r-radius); box-shadow: var(--r-shadow);
    border: 1px solid var(--r-border); overflow: hidden;
    text-decoration: none !important; color: var(--r-text) !important;
    transition: all var(--r-transition); display: flex; flex-direction: column;
    animation: fadeInUp 0.35s ease-out both;
}

.recept-archiv .recept-kartya:hover { transform: translateY(-4px); box-shadow: var(--r-shadow-lg); border-color: var(--r-accent); }

.kartya-kep { position: relative; aspect-ratio: 16 / 10; overflow: hidden; background: var(--r-bg); }
.kartya-kep img { width: 100%; height: 100%; object-fit: cover; transition: transform var(--r-transition); }
.recept-kartya:hover .kartya-kep img { transform: scale(1.05); }
.kartya-kep-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 3rem; background: var(--r-bg); }

.kartya-nehezseg { position: absolute; top: 12px; right: 12px; padding: 4px 12px; border-radius: 999px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; }
.kartya-nehezseg--konnyu { background: #2ecc71; color: #fff; }
.kartya-nehezseg--kozepes { background: #f39c12; color: #fff; }
.kartya-nehezseg--nehez { background: #e74c3c; color: #fff; }

.kartya-badges-left { position: absolute; top: 12px; left: 12px; display: flex; flex-direction: column; gap: 5px; }
.kartya-vbadge { display: flex; align-items: center; gap: 4px; padding: 5px 12px 5px 8px; border-radius: 999px; font-size: 0.72rem; font-weight: 800; letter-spacing: 0.04em; text-transform: uppercase; backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); box-shadow: 0 2px 8px rgba(0,0,0,0.15); transition: transform var(--r-transition); width: fit-content; }
.recept-kartya:hover .kartya-vbadge { transform: scale(1.05); }
.kartya-vbadge-icon { font-size: 0.9rem; line-height: 1; }
.kartya-vbadge-text { line-height: 1; }
.kartya-vbadge-svg { width: 16px; height: 16px; flex-shrink: 0; }
.kartya-vbadge--vegan { background: rgba(39,174,96,0.92); color: #fff; border: 2px solid rgba(255,255,255,0.3); }
.kartya-vbadge--vegetarianus { background: rgba(255,255,255,0.88); color: #27ae60; border: 2px solid var(--r-accent); }
.kartya-vbadge--tejmentes { background: rgba(155,89,182,0.88); color: #fff; border: 2px solid rgba(255,255,255,0.3); padding: 5px 8px; }
.kartya-vbadge--tojasmentes { background: rgba(230,126,34,0.88); color: #fff; border: 2px solid rgba(255,255,255,0.3); padding: 5px 8px; }

.kartya-body { padding: 16px 18px 18px; display: flex; flex-direction: column; gap: 8px; flex: 1; }
.kartya-cim { font-size: 1.05rem; font-weight: 700; margin: 0; line-height: 1.3; }
.kartya-meta { display: flex; gap: 12px; font-size: 0.82rem; color: var(--r-text-muted); }
.kartya-makro { display: flex; gap: 12px; font-size: 0.8rem; color: var(--r-text-muted); }
.kartya-makro strong { color: var(--r-accent-dark); font-weight: 700; }
.kartya-cimkek { display: flex; flex-wrap: wrap; gap: 4px; margin-top: auto; }
.kartya-cimke { font-size: 0.68rem; padding: 2px 10px; border-radius: 999px; background: var(--r-accent-soft); color: var(--r-accent-dark); font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; }
.kartya-cimke--vegan { background: var(--r-accent-dark); color: #fff; }
.kartya-cimke--vegetarianus { background: transparent; color: var(--r-accent-dark); border: 1.5px solid var(--r-accent); }
.kartya-cimke--laktozmentes { background: rgba(52,152,219,0.10); color: #2980b9; }
.kartya-cimke--glutenmentes { background: rgba(243,156,18,0.10); color: #e67e22; }
.kartya-cimke--tejmentes { background: rgba(155,89,182,0.10); color: #8e44ad; }
.kartya-cimke--tojasmentes { background: rgba(230,126,34,0.10); color: #d35400; }
.recept-nincs { grid-column: 1 / -1; text-align: center; font-size: 1.1rem; color: var(--r-text-muted); padding: 48px 0; }

.recept-archiv .recept-pagination { margin-top: 0; margin-bottom: 60px; display: flex; justify-content: center; }

.recept-archiv .recept-pagination .page-numbers {
    list-style: none; display: flex; align-items: center; gap: 8px;
    margin: 0; padding: 16px 24px; flex-wrap: wrap; justify-content: center;
    background: var(--r-pag-bg); border-radius: var(--r-radius);
    box-shadow: var(--r-pag-shadow);
}

.recept-archiv .recept-pagination .page-numbers li { list-style: none; margin: 0; padding: 0; }

.recept-archiv .recept-pagination .page-numbers li a,
.recept-archiv .recept-pagination .page-numbers li span {
    display: inline-flex !important; align-items: center !important; justify-content: center !important;
    min-width: 42px; height: 42px; padding: 0 14px !important;
    border-radius: 12px !important; border: none !important;
    background: var(--r-pag-bg) !important; color: var(--r-text) !important;
    font-size: 0.88rem !important; font-weight: 600 !important;
    text-decoration: none !important; transition: all var(--r-transition);
    box-shadow: var(--r-pag-shadow) !important;
}

.recept-archiv .recept-pagination .page-numbers li a:hover { background: #e4e8e6 !important; box-shadow: var(--r-pag-inset) !important; color: var(--r-accent-dark) !important; }
.recept-archiv .recept-pagination .page-numbers li span.current { background: var(--r-pag-active) !important; color: #fff !important; box-shadow: 0 4px 12px rgba(26,58,42,0.3), inset 0 1px 0 rgba(255,255,255,0.05) !important; font-weight: 700 !important; }
.recept-archiv .recept-pagination .page-numbers li span.dots { border: none !important; background: transparent !important; color: var(--r-text-muted) !important; min-width: auto; padding: 0 6px !important; box-shadow: none !important; font-weight: 700 !important; letter-spacing: 0.1em; }
.recept-archiv .recept-pagination .page-numbers li .prev,
.recept-archiv .recept-pagination .page-numbers li .next { font-size: 0.95rem !important; font-weight: 700 !important; min-width: 42px !important; padding: 0 !important; }

.recept-archiv *:focus, .recept-archiv *:focus-visible, .recept-archiv *:focus-within { outline: none !important; outline-offset: 0 !important; }
.recept-archiv button:focus, .recept-archiv button:focus-visible, .recept-archiv button:active, .recept-archiv [type="button"]:focus, .recept-archiv [type="button"]:focus-visible { outline: none !important; outline-color: transparent !important; }
.recept-archiv a:focus, .recept-archiv a:focus-visible { outline: none !important; text-decoration: none !important; }
.recept-archiv input:focus, .recept-archiv input:focus-visible { outline: none !important; border-color: inherit !important; }
.recept-archiv button, .recept-archiv .szuro-chip, .recept-archiv .szuro-toggle, .recept-archiv .szuro-alaphelyzet, .recept-archiv .recept-search-clear, .recept-archiv .recept-cols-btn { -webkit-appearance: none !important; -moz-appearance: none !important; appearance: none !important; }

@media (max-width: 900px) {
    .recept-archiv .recept-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 16px; }
    .recept-archiv .recept-cols { display: none !important; }
}

@media (max-width: 600px) {
    .recept-archiv-header { padding: 36px 16px 0; }
    .recept-archiv-header h1 { font-size: 1.6rem; }
    .recept-archiv-subtitle { font-size: 0.88rem; margin-bottom: 24px; }
    .recept-archiv .recept-search-bar { padding: 0 0 24px; }
    .recept-archiv .recept-search-inner { padding: 12px 18px !important; gap: 10px !important; }
    .recept-archiv .recept-search-input { font-size: 0.88rem !important; }
    .recept-archiv .recept-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 10px; }
    .kartya-kep { aspect-ratio: 4 / 3; }
    .kartya-body { padding: 10px 12px 14px; gap: 5px; }
    .kartya-cim { font-size: 0.85rem; line-height: 1.25; }
    .kartya-meta { font-size: 0.72rem; gap: 8px; }
    .kartya-makro { font-size: 0.7rem; gap: 8px; }
    .kartya-cimkek { gap: 3px; }
    .kartya-cimke { font-size: 0.58rem; padding: 1px 7px; }
    .kartya-nehezseg { top: 8px; right: 8px; padding: 3px 8px; font-size: 0.6rem; }
    .kartya-badges-left { top: 8px; left: 8px; gap: 3px; }
    .kartya-vbadge { padding: 3px 7px 3px 5px; font-size: 0.6rem; gap: 3px; }
    .kartya-vbadge-icon { font-size: 0.75rem; }
    .kartya-vbadge-svg { width: 12px; height: 12px; }
    .recept-archiv .recept-toolbar { flex-direction: column !important; align-items: stretch !important; gap: 10px; margin-top: 20px; padding: 12px 16px; }
    .recept-archiv .recept-toolbar-right { justify-content: space-between; flex-wrap: wrap; gap: 10px; }
    .recept-archiv .szuro-toggle { align-self: flex-start; }
    .recept-archiv .szuro-row { flex-direction: column; align-items: flex-start; gap: 6px; }
    .recept-archiv .szuro-row-label { min-width: auto; }
    .recept-archiv .szuro-rows { padding: 12px 16px 8px; }
    .recept-archiv .szuro-footer { padding: 10px 16px; padding-left: 16px; }
    .recept-archiv .szuro-dropdown { left: 0; right: 0; margin-top: -18px; }
    .dd-item { padding: 8px 16px; }
    .dd-item-img { width: 36px; height: 36px; }
    .recept-archiv .recept-pagination .page-numbers { padding: 12px 16px; gap: 6px; }
    .recept-archiv .recept-pagination .page-numbers li a,
    .recept-archiv .recept-pagination .page-numbers li span { min-width: 38px; height: 38px; padding: 0 10px !important; font-size: 0.82rem !important; border-radius: 10px !important; }
    .recept-archiv .recept-pagination .page-numbers li .prev,
    .recept-archiv .recept-pagination .page-numbers li .next { min-width: 38px !important; }
}

@media (max-width: 380px) {
    .recept-archiv .recept-grid { gap: 8px; }
    .kartya-cim { font-size: 0.78rem; }
    .kartya-body { padding: 8px 10px 12px; }
    .recept-archiv .recept-perpage-label { display: none; }
    .recept-archiv .recept-cols-label { display: none; }
}

CSS;

    wp_register_style( 'recept-archive-style', false );
    wp_enqueue_style( 'recept-archive-style' );
    wp_add_inline_style( 'recept-archive-style', $css );
}
add_action( 'wp_enqueue_scripts', 'recept_archive_inline_styles' );
