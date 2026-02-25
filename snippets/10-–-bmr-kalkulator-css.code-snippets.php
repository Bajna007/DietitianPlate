<?php

/**
 * 10 – BMR Kalkulátor CSS
 */
/**
 * 10 - BMR Kalkulátor CSS – redesign final v3
 */
function bmr_kalkulator_inline_styles() {
    if ( ! is_page_template( 'page-bmr-kalkulator.php' ) ) { return; }

    $css = <<<'CSS'

:root {
    --b-accent: #2ecc71;
    --b-accent-dark: #27ae60;
    --b-accent-deeper: #1e8449;
    --b-accent-soft: rgba(46,204,113,0.10);
    --b-accent-glow: rgba(46,204,113,0.25);
    --b-bg: #f0f2f1;
    --b-card: #fff;
    --b-border: #e2e6e4;
    --b-text: #1a1d1b;
    --b-text-muted: #7c8a83;
    --b-radius: 16px;
    --b-radius-sm: 10px;
    --b-shadow: 0 4px 24px rgba(0,0,0,0.05);
    --b-shadow-lg: 0 8px 40px rgba(0,0,0,0.08);
    --b-transition: 0.25s cubic-bezier(0.4,0,0.2,1);
    --b-cut: #e74c3c;
    --b-maintain: #2ecc71;
    --b-bulk: #3498db;
    --b-hero-dark: #1a1d1b;
}

/* ═══════════════════════════════════════════════════════
   PAGE WRAPPER
   ═══════════════════════════════════════════════════════ */

.bmr-page {
    max-width: 100%;
    margin: 0 auto;
    padding: 0 0 60px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    color: var(--b-text);
    line-height: 1.6;
    background: var(--b-bg);
}

.bmr-page * { box-sizing: border-box; }
.bmr-container { max-width: 100%; margin: 0 auto; padding: 0; }

/* ═══════════════════════════════════════════════════════
   HERO – 1:1 recept/alapanyag/tápanyag archív mintára
   ═══════════════════════════════════════════════════════ */

.bmr-hero {
    position: relative;
    overflow: visible;
    z-index: 10;
    padding: 56px 24px 48px;
    text-align: center;
    background-color: var(--b-hero-dark);
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

.bmr-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.03'/%3E%3C/svg%3E");
    background-repeat: repeat;
    background-size: 256px 256px;
    pointer-events: none;
    z-index: 0;
}

.bmr-hero::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent 0%, rgba(46,204,113,0.3) 30%, rgba(46,204,113,0.5) 50%, rgba(46,204,113,0.3) 70%, transparent 100%);
    z-index: 1;
}

.bmr-hero > * { position: relative; z-index: 2; }

.bmr-hero h1 {
    font-size: clamp(2rem, 5vw, 3rem) !important;
    font-weight: 800 !important;
    margin: 0 0 8px !important;
    color: #fff !important;
    letter-spacing: -0.02em !important;
    line-height: 1.2 !important;
}

.bmr-hero-sub {
    font-size: 1rem !important;
    color: rgba(255,255,255,0.5) !important;
    margin: 0 0 24px !important;
    font-weight: 400 !important;
}

/* ── DISCLAIMER SÁVC ── */

.bmr-hero-disclaimer {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    max-width: 720px;
    margin: 0 auto;
    padding: 12px 24px;
    background: rgba(255,255,255,0.06);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.10);
    border-radius: 999px;
    font-size: 0.78rem;
    line-height: 1.5;
    color: rgba(255,255,255,0.55);
    text-align: left;
}

.bmr-hero-disclaimer svg {
    flex-shrink: 0;
    color: rgba(245,158,11,0.7);
    width: 16px;
    height: 16px;
}

/* ═══════════════════════════════════════════════════════
   LAYOUT – 40/60 arány, sticky bal oldal
   ═══════════════════════════════════════════════════════ */

.bmr-layout {
    display: grid;
    grid-template-columns: 2fr 3fr;
    gap: 24px;
    align-items: start;
    max-width: 1200px;
    margin: 28px auto 0;
    padding: 0 20px;
}

.bmr-card {
    background: var(--b-card);
    border-radius: var(--b-radius);
    box-shadow: var(--b-shadow-lg);
    border: 1px solid var(--b-border);
    overflow: hidden;
}

.bmr-card--form {
    position: sticky;
    top: 80px;
    z-index: 5;
}

.bmr-card-header {
    background: var(--b-accent);
    color: #fff;
    padding: 14px 24px;
    font-size: 0.82rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.bmr-card-body { padding: 24px; }

/* ═══════════════════════════════════════════════════════
   KÉPLET VÁLASZTÓ CHIPS
   ═══════════════════════════════════════════════════════ */

.bmr-formula-bar { display: flex; flex-direction: column; gap: 6px; margin-bottom: 12px; }

.bmr-formula-chip {
    display: flex; align-items: center; justify-content: space-between; width: 100%;
    padding: 10px 16px; border: 2px solid var(--b-border); border-radius: var(--b-radius-sm);
    background: var(--b-card); color: var(--b-text-muted); font-size: 0.82rem; font-weight: 600;
    cursor: pointer; transition: all var(--b-transition);
}

.bmr-formula-chip-name { font-weight: 700; color: var(--b-text); transition: color var(--b-transition); }

.bmr-formula-chip-year {
    font-size: 0.68rem; font-weight: 600; color: var(--b-text-muted);
    background: var(--b-bg); padding: 2px 10px; border-radius: 999px;
    flex-shrink: 0; transition: all var(--b-transition);
}

.bmr-formula-chip:hover { border-color: var(--b-accent); }
.bmr-formula-chip.is-active { border-color: var(--b-accent); background: var(--b-accent-soft); }
.bmr-formula-chip.is-active .bmr-formula-chip-name { color: var(--b-accent-dark); }
.bmr-formula-chip.is-active .bmr-formula-chip-year { background: var(--b-accent); color: #fff; }

/* ═══════════════════════════════════════════════════════
   KÉPLET ÖSSZEFOGLALÓ
   ═══════════════════════════════════════════════════════ */

.bmr-formula-summary { margin-top: 0; }
.bmr-formula-summary-text { font-size: 0.78rem; color: var(--b-text-muted); line-height: 1.55; margin: 0 0 8px; }
.bmr-formula-summary-formula { background: var(--b-bg); border-radius: 8px; padding: 8px 14px; }
.bmr-formula-summary-formula code { font-family: 'SF Mono', 'Fira Code', Consolas, monospace; font-size: 0.78rem; color: var(--b-accent-dark); font-weight: 600; }

/* ═══════════════════════════════════════════════════════
   FIELDS
   ═══════════════════════════════════════════════════════ */

.bmr-field { margin-bottom: 22px; }
.bmr-field:last-child { margin-bottom: 0; }

.bmr-field-label {
    display: block; font-size: 0.72rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.06em; color: var(--b-text-muted); margin-bottom: 8px;
}

/* ═══════════════════════════════════════════════════════
   GENDER TOGGLE
   ═══════════════════════════════════════════════════════ */

.bmr-gender-toggle { display: flex; gap: 8px; }

.bmr-gender-btn {
    flex: 1; padding: 12px; border: 2px solid var(--b-border); border-radius: var(--b-radius-sm);
    background: var(--b-card); color: var(--b-text-muted); font-size: 0.88rem; font-weight: 700;
    cursor: pointer; transition: all var(--b-transition);
    display: flex; align-items: center; justify-content: center; gap: 6px;
}

.bmr-gender-btn:hover { border-color: var(--b-accent); color: var(--b-text); }
.bmr-gender-btn.is-active { border-color: var(--b-accent); background: var(--b-accent-soft); color: var(--b-accent-dark); }
.bmr-gender-icon { font-size: 1.1rem; }

/* ═══════════════════════════════════════════════════════
   INPUT ROW
   ═══════════════════════════════════════════════════════ */

.bmr-input-row { display: flex; align-items: center; gap: 14px; }
.bmr-input-inline { display: flex; align-items: baseline; gap: 4px; flex-shrink: 0; }

.bmr-input-num {
    width: 52px; border: none; border-bottom: 2px solid var(--b-border); background: transparent;
    text-align: center; font-size: 1.05rem; font-weight: 800; color: var(--b-text);
    padding: 4px 0; -moz-appearance: textfield; outline: none; transition: border-color var(--b-transition);
}

.bmr-input-num:focus { border-bottom-color: var(--b-accent); }
.bmr-input-num::-webkit-outer-spin-button, .bmr-input-num::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.bmr-input-unit { font-size: 0.78rem; font-weight: 600; color: var(--b-text-muted); }

/* ═══════════════════════════════════════════════════════
   SLIDER
   ═══════════════════════════════════════════════════════ */

.bmr-slider {
    flex: 1; height: 6px; -webkit-appearance: none; appearance: none;
    border-radius: 999px; outline: none; cursor: pointer; background: var(--b-border);
}

.bmr-slider::-webkit-slider-runnable-track {
    height: 6px; border-radius: 999px;
    background: linear-gradient(to right, var(--b-accent) 0%, var(--b-accent) var(--pct, 50%), var(--b-border) var(--pct, 50%), var(--b-border) 100%);
}

.bmr-slider::-webkit-slider-thumb {
    -webkit-appearance: none; width: 20px; height: 20px; border-radius: 50%;
    background: #fff; border: 3px solid var(--b-accent);
    box-shadow: 0 1px 4px rgba(0,0,0,0.12); cursor: pointer;
    transition: all var(--b-transition); margin-top: -7px;
}

.bmr-slider::-webkit-slider-thumb:hover { transform: scale(1.15); box-shadow: 0 0 0 6px var(--b-accent-glow), 0 1px 4px rgba(0,0,0,0.12); }
.bmr-slider::-webkit-slider-thumb:active { transform: scale(1.05); border-color: var(--b-accent-dark); }

.bmr-slider::-moz-range-track { height: 6px; border-radius: 999px; background: var(--b-border); border: none; }
.bmr-slider::-moz-range-progress { height: 6px; border-radius: 999px 0 0 999px; background: var(--b-accent); }
.bmr-slider::-moz-range-thumb { width: 20px; height: 20px; border-radius: 50%; background: #fff; border: 3px solid var(--b-accent); box-shadow: 0 1px 4px rgba(0,0,0,0.12); cursor: pointer; }

.bmr-slider--deficit::-webkit-slider-runnable-track { background: linear-gradient(to right, var(--deficit-color, #22c55e) 0%, var(--deficit-color, #22c55e) var(--pct, 33%), var(--b-border) var(--pct, 33%), var(--b-border) 100%); }
.bmr-slider--deficit::-webkit-slider-thumb { border-color: var(--deficit-color, #22c55e); }
.bmr-slider--deficit::-webkit-slider-thumb:hover { box-shadow: 0 0 0 6px rgba(231,76,60,0.15), 0 1px 4px rgba(0,0,0,0.12); }
.bmr-slider--deficit::-moz-range-progress { background: var(--deficit-color, #22c55e); }
.bmr-slider--deficit::-moz-range-thumb { border-color: var(--deficit-color, #22c55e); }

/* ═══════════════════════════════════════════════════════
   ACTIVITY – ÉRINTETLEN
   ═══════════════════════════════════════════════════════ */

.bmr-activity-list { display: flex; flex-direction: column; gap: 6px; }
.bmr-activity-option { cursor: pointer; }
.bmr-activity-option input { display: none; }

.bmr-activity-card {
    display: flex; align-items: center; gap: 10px; padding: 10px 14px;
    border: 2px solid var(--b-border); border-radius: var(--b-radius-sm);
    transition: all var(--b-transition); background: var(--b-card);
}

.bmr-activity-option:hover .bmr-activity-card { border-color: var(--b-accent); }
.bmr-activity-option input:checked + .bmr-activity-card { border-color: var(--b-accent); background: var(--b-accent-soft); }
.bmr-activity-name { font-size: 0.85rem; font-weight: 700; color: var(--b-text); min-width: 110px; }
.bmr-activity-desc { flex: 1; font-size: 0.75rem; color: var(--b-text-muted); line-height: 1.3; }

.bmr-activity-factor {
    font-size: 0.72rem; font-weight: 800; color: var(--b-accent-dark);
    background: var(--b-accent-soft); padding: 2px 10px; border-radius: 999px;
    white-space: nowrap; flex-shrink: 0;
}

.bmr-activity-card--athlete { border-color: rgba(231,76,60,0.25); background: rgba(231,76,60,0.03); }
.bmr-activity-option--athlete:hover .bmr-activity-card--athlete { border-color: rgba(231,76,60,0.5); }
.bmr-activity-option--athlete input:checked + .bmr-activity-card--athlete { border-color: #e74c3c; background: rgba(231,76,60,0.06); }
.bmr-activity-athlete-body { flex: 1; display: flex; flex-direction: column; gap: 1px; }
.bmr-activity-athlete-body .bmr-activity-name { min-width: auto; color: #c0392b; }
.bmr-activity-athlete-body .bmr-activity-desc { min-width: auto; }

.bmr-athlete-sub {
    max-height: 0; overflow: hidden; opacity: 0;
    transition: max-height 0.35s ease, opacity 0.25s ease, margin 0.25s ease;
    margin: 0; padding: 0 0 0 16px; border-left: 3px solid rgba(231,76,60,0.15);
}

.bmr-athlete-sub.is-visible { max-height: 300px; opacity: 1; margin: 6px 0 0; padding-top: 4px; padding-bottom: 2px; }
.bmr-athlete-level { display: block; cursor: pointer; margin-bottom: 4px; }
.bmr-athlete-level:last-child { margin-bottom: 0; }
.bmr-athlete-level input { display: none; }

.bmr-athlete-level-card {
    display: flex; align-items: center; gap: 8px; padding: 7px 12px;
    border: 1.5px solid var(--b-border); border-radius: 8px;
    transition: all var(--b-transition); background: var(--b-card); font-size: 0.78rem;
}

.bmr-athlete-level:hover .bmr-athlete-level-card { border-color: rgba(231,76,60,0.4); }
.bmr-athlete-level input:checked + .bmr-athlete-level-card { border-color: var(--b-cut); background: rgba(231,76,60,0.04); }
.bmr-athlete-level-name { font-weight: 700; color: var(--b-text); min-width: 100px; font-size: 0.78rem; }
.bmr-athlete-level-desc { flex: 1; color: var(--b-text-muted); font-size: 0.7rem; line-height: 1.3; }
.bmr-athlete-level-factor { font-size: 0.68rem; font-weight: 800; color: var(--b-cut); background: rgba(231,76,60,0.06); padding: 2px 8px; border-radius: 999px; white-space: nowrap; flex-shrink: 0; }

/* ═══════════════════════════════════════════════════════
   BMI SZEKCIÓ
   ═══════════════════════════════════════════════════════ */

.bmr-bmi-section { margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid var(--b-border); }
.bmr-bmi-header { display: flex; align-items: baseline; justify-content: space-between; margin-bottom: 10px; flex-wrap: wrap; gap: 6px; }
.bmr-bmi-value-wrap { display: flex; align-items: baseline; gap: 8px; }
.bmr-bmi-number { font-size: 1.8rem; font-weight: 800; font-variant-numeric: tabular-nums; }
.bmr-bmi-cat { font-size: 0.82rem; font-weight: 700; }
.bmr-bmi-bar-wrap { position: relative; }
.bmr-bmi-bar { position: relative; display: flex; height: 10px; border-radius: 999px; overflow: visible; margin-bottom: 4px; }
.bmr-bmi-segment { height: 100%; }
.bmr-bmi-segment[data-cat="sovany"] { width: 19.7%; background: linear-gradient(to right, #818cf8, #a5b4fc); border-radius: 999px 0 0 999px; }
.bmr-bmi-segment[data-cat="normal"] { width: 19.7%; background: #22c55e; }
.bmr-bmi-segment[data-cat="tulsuly"] { width: 15.15%; background: #f59e0b; }
.bmr-bmi-segment[data-cat="elhizas"] { width: 45.45%; background: linear-gradient(to right, #f97316, #ef4444, #dc2626); border-radius: 0 999px 999px 0; }

.bmr-bmi-marker {
    position: absolute; top: -4px; width: 18px; height: 18px; border-radius: 50%;
    background: #22c55e; border: 3px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    transform: translateX(-50%); transition: left 0.4s ease, background 0.4s ease; z-index: 2;
}

.bmr-bmi-ticks { position: relative; height: 18px; margin-top: 2px; }
.bmr-bmi-tick { position: absolute; font-size: 0.62rem; color: var(--b-text-muted); font-weight: 600; transform: translateX(-50%); white-space: nowrap; }

/* ═══════════════════════════════════════════════════════
   SPORTOLÓ BMI NOTICE
   ═══════════════════════════════════════════════════════ */

.bmr-athlete-bmi-notice { display: none; background: rgba(59,130,246,0.06); border: 1.5px solid rgba(59,130,246,0.25); border-radius: var(--b-radius-sm); padding: 14px 16px; margin-top: 12px; font-size: 0.8rem; line-height: 1.6; color: var(--b-text); animation: bmrFadeInUp 0.3s ease; }
.bmr-athlete-bmi-badge { font-weight: 700; font-size: 0.85rem; color: #3b82f6; margin-bottom: 6px; }
.bmr-athlete-bmi-notice > p { margin: 0 0 8px; }
.bmr-athlete-bmi-comparison { display: flex; align-items: center; gap: 0; background: rgba(59,130,246,0.04); border-radius: 8px; padding: 12px 8px; margin: 10px 0; }
.bmr-athlete-bmi-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 2px; text-align: center; }
.bmr-athlete-bmi-col-icon { font-size: 1.4rem; margin-bottom: 2px; }
.bmr-athlete-bmi-col-title { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--b-text-muted); }
.bmr-athlete-bmi-col-data { font-size: 0.85rem; color: var(--b-text); }
.bmr-athlete-bmi-col-sub { font-size: 0.68rem; color: var(--b-text-muted); }
.bmr-athlete-bmi-vs { font-size: 0.72rem; font-weight: 800; color: var(--b-text-muted); padding: 0 10px; flex-shrink: 0; }
.bmr-athlete-bmi-explain { font-size: 0.78rem; color: var(--b-text-muted); line-height: 1.55; margin: 0 0 8px; }
.bmr-athlete-bmi-accurate { background: rgba(59,130,246,0.04); border: 1px solid rgba(59,130,246,0.15); border-radius: 8px; padding: 12px 14px; margin: 10px 0 8px; }
.bmr-athlete-bmi-accurate-badge { font-weight: 700; font-size: 0.8rem; color: #3b82f6; margin-bottom: 4px; }
.bmr-athlete-bmi-accurate p { font-size: 0.76rem; color: var(--b-text-muted); margin: 0 0 6px; line-height: 1.5; }
.bmr-athlete-bmi-accurate-list { margin: 0; padding: 0 0 0 18px; font-size: 0.76rem; color: var(--b-text-muted); line-height: 1.55; }
.bmr-athlete-bmi-accurate-list li { margin-bottom: 4px; }
.bmr-athlete-bmi-accurate-list li:last-child { margin-bottom: 0; }
.bmr-athlete-bmi-accurate-list strong { color: var(--b-text); }
.bmr-athlete-bmi-source { font-size: 0.68rem; color: var(--b-text-muted); font-style: italic; margin: 0; }

/* ═══════════════════════════════════════════════════════
   BROCA
   ═══════════════════════════════════════════════════════ */

.bmr-broca-notice { display: none; background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.25); border-radius: var(--b-radius-sm); padding: 14px 16px; margin-bottom: 16px; font-size: 0.8rem; line-height: 1.6; color: var(--b-text); animation: bmrFadeInUp 0.3s ease; }
.bmr-broca-badge { font-weight: 700; font-size: 0.85rem; margin-bottom: 6px; }
.bmr-broca-notice p { margin: 0 0 6px; }
.bmr-broca-notice p:last-of-type { margin-bottom: 8px; }
.bmr-broca-step { margin-bottom: 10px; }
.bmr-broca-step code { display: block; font-family: 'SF Mono', 'Fira Code', Consolas, monospace; font-size: 0.8rem; color: #d97706; font-weight: 600; background: rgba(245,158,11,0.08); padding: 6px 10px; border-radius: 6px; margin-top: 4px; }
.bmr-broca-explain { font-size: 0.76rem; color: var(--b-text-muted); line-height: 1.55; margin: 8px 0 0; }

/* ═══════════════════════════════════════════════════════
   RESULTS
   ═══════════════════════════════════════════════════════ */

.bmr-result-block { padding: 16px 0; }
.bmr-result-label { display: block; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--b-text-muted); margin-bottom: 6px; }
.bmr-result-value-wrap { display: flex; align-items: baseline; gap: 8px; }
.bmr-result-value { font-size: 2.4rem; font-weight: 800; color: var(--b-text); line-height: 1; font-variant-numeric: tabular-nums; }
.bmr-result-block--tdee .bmr-result-value { color: var(--b-accent-dark); font-size: 2.8rem; }
.bmr-result-unit { font-size: 0.82rem; font-weight: 600; color: var(--b-text-muted); }
.bmr-result-desc { margin: 8px 0 0; font-size: 0.78rem; color: var(--b-text-muted); line-height: 1.5; }
.bmr-result-divider { height: 1px; background: var(--b-border); }

/* ═══════════════════════════════════════════════════════
   GOALS
   ═══════════════════════════════════════════════════════ */

.bmr-goals-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 10px; }

.bmr-goal-card {
    text-align: center; padding: 14px 8px; border-radius: var(--b-radius-sm);
    border: 2px solid var(--b-border); transition: all var(--b-transition);
    display: flex; flex-direction: column; align-items: center; gap: 2px;
}

.bmr-goal-card:hover { transform: translateY(-2px); box-shadow: var(--b-shadow); }
.bmr-goal-card--cut { border-color: rgba(231,76,60,0.3); }
.bmr-goal-card--cut:hover { border-color: var(--b-cut); }
.bmr-goal-card--maintain { border-color: rgba(46,204,113,0.3); }
.bmr-goal-card--maintain:hover { border-color: var(--b-maintain); }
.bmr-goal-card--bulk { border-color: rgba(52,152,219,0.3); }
.bmr-goal-card--bulk:hover { border-color: var(--b-bulk); }
.bmr-goal-icon { font-size: 1.3rem; }
.bmr-goal-title { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--b-text-muted); }
.bmr-goal-value { font-size: 1.3rem; font-weight: 800; font-variant-numeric: tabular-nums; }
.bmr-goal-card--cut .bmr-goal-value { color: var(--b-cut); }
.bmr-goal-card--maintain .bmr-goal-value { color: var(--b-maintain); }
.bmr-goal-card--bulk .bmr-goal-value { color: var(--b-bulk); }
.bmr-goal-unit { font-size: 0.65rem; color: var(--b-text-muted); }
.bmr-goal-note { font-size: 0.62rem; color: var(--b-text-muted); font-weight: 600; background: var(--b-bg); padding: 1px 8px; border-radius: 999px; margin-top: 2px; }

/* ═══════════════════════════════════════════════════════
   FOGYÁSI PROGNÓZIS – ÉRINTETLEN
   ═══════════════════════════════════════════════════════ */

.bmr-cut-prognosis { margin-top: 16px; animation: bmrFadeInUp 0.3s ease; }
.bmr-cut-prog-inner { background: var(--b-card); border: 2px solid rgba(231,76,60,0.2); border-radius: var(--b-radius-sm); overflow: hidden; }
.bmr-cut-prog-header { background: rgba(231,76,60,0.06); padding: 10px 16px; display: flex; align-items: center; gap: 6px; border-bottom: 1px solid rgba(231,76,60,0.1); }
.bmr-cut-prog-icon { font-size: 0.9rem; }
.bmr-cut-prog-title { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--b-cut); }
.bmr-cut-prog-slider-section { padding: 14px 16px; border-bottom: 1px solid rgba(231,76,60,0.1); }
.bmr-cut-prog-slider-label { display: block; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--b-text-muted); margin-bottom: 8px; }
.bmr-cut-prog-slider-row { display: flex; align-items: center; gap: 12px; }
.bmr-cut-prog-slider-value-wrap { display: flex; align-items: baseline; gap: 3px; flex-shrink: 0; }
.bmr-cut-prog-slider-value { font-size: 1.1rem; font-weight: 800; color: var(--b-text); font-variant-numeric: tabular-nums; min-width: 36px; text-align: right; }
.bmr-cut-prog-slider-unit { font-size: 0.72rem; font-weight: 600; color: var(--b-text-muted); }
.bmr-cut-prog-slider-zones { display: flex; gap: 8px; margin-top: 8px; flex-wrap: wrap; }
.bmr-cut-prog-zone { font-size: 0.62rem; font-weight: 700; padding: 2px 8px; border-radius: 999px; }
.bmr-cut-prog-zone--green { background: rgba(34,197,94,0.1); color: #16a34a; }
.bmr-cut-prog-zone--yellow { background: rgba(245,158,11,0.1); color: #d97706; }
.bmr-cut-prog-zone--red { background: rgba(231,76,60,0.1); color: #dc2626; }
.bmr-cut-prog-intake-inner { display: flex; align-items: baseline; gap: 8px; padding: 12px 16px; background: rgba(231,76,60,0.03); border-bottom: 1px solid rgba(231,76,60,0.1); flex-wrap: wrap; }
.bmr-cut-prog-intake-label { font-size: 0.76rem; font-weight: 700; color: var(--b-text-muted); }
.bmr-cut-prog-intake-value { font-size: 1.2rem; font-weight: 800; color: var(--b-cut); font-variant-numeric: tabular-nums; }
.bmr-cut-prog-intake-detail { font-size: 0.7rem; color: var(--b-text-muted); }
.bmr-cut-prog-stats { display: flex; align-items: center; gap: 0; padding: 16px; }
.bmr-cut-prog-stat { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 2px; padding: 4px 0; }
.bmr-cut-prog-stat-value { font-size: 1.1rem; font-weight: 800; color: var(--b-text); font-variant-numeric: tabular-nums; }
.bmr-cut-prog-stat-value--highlight { font-size: 1.4rem; color: var(--b-cut); }
.bmr-cut-prog-stat-label { font-size: 0.62rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--b-text-muted); }
.bmr-cut-prog-stat-divider { width: 1px; height: 32px; background: var(--b-border); flex-shrink: 0; }
.bmr-cut-prog-tempo { font-size: 0.72rem; font-weight: 700; padding: 6px 16px; text-align: center; }
.bmr-cut-prog-tempo--green { background: rgba(34,197,94,0.08); color: #16a34a; border-bottom: 1px solid rgba(34,197,94,0.15); }
.bmr-cut-prog-tempo--red { background: rgba(231,76,60,0.08); color: #dc2626; border-bottom: 1px solid rgba(231,76,60,0.15); }
.bmr-cut-prog-note { font-size: 0.78rem; line-height: 1.55; color: var(--b-text-muted); margin: 0; padding: 0 16px 12px; }
.bmr-cut-prog-note strong { color: var(--b-text); }
.bmr-cut-prog-healthy-note { font-size: 0.76rem; line-height: 1.5; color: #1e40af; background: rgba(59,130,246,0.05); padding: 10px 16px; margin: 0; border-top: 1px solid rgba(59,130,246,0.1); }
.bmr-cut-prog-healthy-note strong { color: #1e40af; }
.bmr-cut-prog-warning { margin: 0; font-size: 0.76rem; line-height: 1.5; color: #991b1b; background: rgba(231,76,60,0.05); padding: 10px 16px; border-top: 1px solid rgba(231,76,60,0.1); }

/* ═══════════════════════════════════════════════════════
   MAKRÓ NOTICE
   ═══════════════════════════════════════════════════════ */

.bmr-macro-notice { margin-bottom: 12px; }
.bmr-macro-notice:empty { margin-bottom: 0; }
.bmr-macro-notice-inner { border-radius: var(--b-radius-sm); padding: 14px 16px; font-size: 0.8rem; line-height: 1.6; color: var(--b-text); animation: bmrFadeInUp 0.3s ease; }
.bmr-macro-notice--underweight { background: rgba(245,158,11,0.06); border: 1.5px solid rgba(245,158,11,0.25); }
.bmr-macro-notice--athlete { background: rgba(59,130,246,0.06); border: 1.5px solid rgba(59,130,246,0.25); }
.bmr-macro-notice--default { background: var(--b-bg); border: 1.5px solid var(--b-border); }
.bmr-macro-notice-badge { font-weight: 700; font-size: 0.85rem; margin-bottom: 6px; }
.bmr-macro-notice--underweight .bmr-macro-notice-badge { color: #d97706; }
.bmr-macro-notice--athlete .bmr-macro-notice-badge { color: #3b82f6; }
.bmr-macro-notice--default .bmr-macro-notice-badge { color: var(--b-text-muted); }
.bmr-macro-notice-inner p { margin: 0 0 6px; }
.bmr-macro-notice-list { margin: 6px 0 8px; padding: 0 0 0 18px; font-size: 0.78rem; line-height: 1.55; }
.bmr-macro-notice-list li { margin-bottom: 3px; }
.bmr-macro-notice-list li:last-child { margin-bottom: 0; }
.bmr-macro-notice-list strong { color: var(--b-text); }
.bmr-macro-notice-advice { font-size: 0.76rem; color: #92400e; font-weight: 600; margin: 8px 0 0; }
.bmr-macro-notice-source { font-size: 0.7rem; color: var(--b-text-muted); margin: 4px 0 0; }
.bmr-macro-notice-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 0.72rem; }
.bmr-macro-notice-table th, .bmr-macro-notice-table td { padding: 5px 8px; text-align: left; border-bottom: 1px solid rgba(59,130,246,0.12); }
.bmr-macro-notice-table th { font-weight: 700; color: var(--b-text); font-size: 0.66rem; text-transform: uppercase; letter-spacing: 0.04em; background: rgba(59,130,246,0.04); }
.bmr-macro-notice-table td { color: var(--b-text-muted); }
.bmr-macro-notice-table td:nth-child(2) { font-weight: 700; color: #3b82f6; }

/* ═══════════════════════════════════════════════════════
   MAKRÓ GRID
   ═════════════════════════════════════��═════════════════ */

.bmr-macro-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 10px; }

.bmr-macro-card { border: 2px solid var(--b-border); border-radius: var(--b-radius-sm); overflow: hidden; transition: all var(--b-transition); }
.bmr-macro-card:hover { transform: translateY(-2px); box-shadow: var(--b-shadow); }
.bmr-macro-card--ch { border-color: rgba(245,158,11,0.3); }
.bmr-macro-card--ch:hover { border-color: #f59e0b; }
.bmr-macro-card--fat { border-color: rgba(239,68,68,0.3); }
.bmr-macro-card--fat:hover { border-color: #ef4444; }
.bmr-macro-card--protein { border-color: rgba(59,130,246,0.3); }
.bmr-macro-card--protein:hover { border-color: #3b82f6; }
.bmr-macro-card-header { display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: var(--b-bg); border-bottom: 1px solid var(--b-border); }
.bmr-macro-card-title { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--b-text); }
.bmr-macro-card-pct { font-size: 0.6rem; font-weight: 700; color: var(--b-accent-dark); background: var(--b-accent-soft); padding: 1px 8px; border-radius: 999px; }
.bmr-macro-card-values { padding: 12px; text-align: center; }
.bmr-macro-card-main { display: flex; align-items: baseline; justify-content: center; gap: 3px; margin-bottom: 4px; }
.bmr-macro-value { font-size: 1.4rem; font-weight: 800; color: var(--b-text); font-variant-numeric: tabular-nums; }
.bmr-macro-g { font-size: 0.85rem; font-weight: 700; color: var(--b-text-muted); }
.bmr-macro-card-perkg { display: flex; align-items: baseline; justify-content: center; gap: 3px; padding-top: 4px; border-top: 1px dashed var(--b-border); }
.bmr-macro-perkg-value { font-size: 0.88rem; font-weight: 800; color: var(--b-text-muted); font-variant-numeric: tabular-nums; }
.bmr-macro-perkg-unit { font-size: 0.62rem; font-weight: 600; color: var(--b-text-muted); }
.bmr-macro-explain { font-size: 0.76rem; line-height: 1.55; color: var(--b-text-muted); margin: 12px 0 0; padding: 10px 14px; background: var(--b-bg); border-radius: var(--b-radius-sm); }
.bmr-macro-explain strong { color: var(--b-text); }

/* ═══════════════════════════════════════════════════════
   INFO SZEKCIÓ
   ═══════════════════════════════════════════════════════ */

.bmr-info-section { max-width: 1200px; margin: 32px auto 0; padding: 0 20px; }

.bmr-info-card { background: var(--b-card); border-radius: var(--b-radius); box-shadow: var(--b-shadow); border: 1px solid var(--b-border); padding: 24px; }
.bmr-info-title { font-size: 0.92rem; font-weight: 700; margin: 0 0 16px; }
.bmr-info-meta { display: flex; flex-direction: column; gap: 4px; margin-bottom: 12px; font-size: 0.8rem; color: var(--b-text-muted); }
.bmr-info-meta strong { color: var(--b-text); }
.bmr-info-desc { font-size: 0.85rem; color: var(--b-text-muted); line-height: 1.6; margin: 0 0 12px; }
.bmr-info-formula { background: var(--b-bg); border-radius: var(--b-radius-sm); padding: 14px 18px; font-size: 0.82rem; line-height: 1.8; margin-bottom: 16px; }
.bmr-info-formula code { display: block; font-family: 'SF Mono', 'Fira Code', Consolas, monospace; font-size: 0.82rem; color: var(--b-accent-dark); font-weight: 600; margin-top: 4px; }
.bmr-info-broca { background: rgba(245,158,11,0.06); border: 1px solid rgba(245,158,11,0.2); border-radius: var(--b-radius-sm); padding: 14px 18px; font-size: 0.82rem; line-height: 1.7; margin-bottom: 16px; }
.bmr-info-broca code { font-family: 'SF Mono', 'Fira Code', Consolas, monospace; font-size: 0.82rem; color: #d97706; font-weight: 600; }
.bmr-info-athlete { background: rgba(59,130,246,0.06); border: 1px solid rgba(59,130,246,0.2); border-radius: var(--b-radius-sm); padding: 14px 18px; font-size: 0.82rem; line-height: 1.7; margin-bottom: 16px; }
.bmr-info-activity { margin-bottom: 16px; font-size: 0.82rem; line-height: 1.6; }
.bmr-info-bmi { margin-bottom: 16px; font-size: 0.82rem; line-height: 1.6; }
.bmr-info-fat { font-size: 0.82rem; line-height: 1.6; color: var(--b-text-muted); margin-bottom: 16px; }
.bmr-info-macro { font-size: 0.82rem; line-height: 1.6; color: var(--b-text-muted); margin-bottom: 16px; }
.bmr-info-table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 0.78rem; }
.bmr-info-table th, .bmr-info-table td { padding: 6px 10px; text-align: left; border-bottom: 1px solid var(--b-border); }
.bmr-info-table th { font-weight: 700; color: var(--b-text); font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.04em; background: var(--b-bg); }
.bmr-info-table td { color: var(--b-text-muted); }
.bmr-info-table td:nth-child(2) { font-weight: 700; color: var(--b-accent-dark); }
.bmr-info-table-ath td { color: #3b82f6 !important; font-style: italic; }
.bmr-info-table-ath td:nth-child(2) { color: #3b82f6 !important; }

/* ═══════════════════════════════════════════════════════
   FOCUS RESET
   ═══════════════════════════════════════════════════════ */

.bmr-page *:focus, .bmr-page *:focus-visible { outline: none !important; }
.bmr-page button:focus, .bmr-page button:focus-visible, .bmr-page button:active { outline: none !important; outline-color: transparent !important; }

/* ═══════════════════════════════════════════════════════
   ANIMÁCIÓK
   ═══════════════════════════════════════════════════════ */

@keyframes bmrFadeInUp {
    from { opacity: 0; transform: translateY(14px); }
    to { opacity: 1; transform: translateY(0); }
}

.bmr-container > * { animation: bmrFadeInUp 0.35s ease-out both; }

/* ═══════════════════════════════════════════════════════
   RESZPONZÍV
   ═══════════════════════════════════════════════════════ */

@media (max-width: 900px) {
    .bmr-layout { grid-template-columns: 1fr; }
    .bmr-card--form { position: static; }
}

@media (max-width: 768px) {
    .bmr-hero { padding: 36px 16px 32px; }
    .bmr-hero h1 { font-size: 1.6rem !important; }
    .bmr-hero-sub { font-size: 0.88rem !important; }
    .bmr-hero-disclaimer { flex-direction: column; text-align: center; gap: 6px; border-radius: var(--b-radius-sm); padding: 10px 16px; font-size: 0.72rem; }
    .bmr-layout { padding: 0 12px; margin-top: 20px; }
    .bmr-card-body { padding: 18px; }
    .bmr-goals-grid { grid-template-columns: 1fr; gap: 8px; }
    .bmr-goal-card { flex-direction: row; gap: 10px; text-align: left; padding: 10px 14px; }
    .bmr-activity-card { flex-wrap: wrap; }
    .bmr-activity-desc { min-width: 100%; order: 3; margin-top: 2px; }
    .bmr-input-row { flex-wrap: wrap; }
    .bmr-macro-grid { grid-template-columns: 1fr; gap: 8px; }
    .bmr-macro-card-values { display: flex; align-items: center; justify-content: center; gap: 16px; padding: 10px 12px; }
    .bmr-macro-card-main { margin-bottom: 0; }
    .bmr-macro-card-perkg { border-top: none; border-left: 1px dashed var(--b-border); padding-top: 0; padding-left: 16px; }
    .bmr-cut-prog-stats { flex-direction: column; gap: 8px; }
    .bmr-cut-prog-stat { flex-direction: row; gap: 8px; justify-content: center; }
    .bmr-cut-prog-stat-divider { width: 100%; height: 1px; }
    .bmr-athlete-level-card { flex-wrap: wrap; }
    .bmr-athlete-level-desc { min-width: 100%; order: 3; margin-top: 2px; }
    .bmr-athlete-bmi-comparison { flex-direction: column; gap: 6px; }
    .bmr-athlete-bmi-vs { padding: 4px 0; }
    .bmr-cut-prog-intake-inner { flex-direction: column; gap: 4px; }
    .bmr-macro-notice-table { font-size: 0.66rem; }
    .bmr-macro-notice-table th, .bmr-macro-notice-table td { padding: 4px 6px; }
    .bmr-info-section { padding: 0 12px; }
}

@media (max-width: 480px) {
    .bmr-result-value { font-size: 2rem; }
    .bmr-result-block--tdee .bmr-result-value { font-size: 2.2rem; }
    .bmr-bmi-number { font-size: 1.4rem; }
}
/* ═══ FOCUS/HOVER OVERRIDE – theme kék outline eltávolítása ═══ */

.bmr-page button,
.bmr-page button:hover,
.bmr-page button:focus,
.bmr-page button:focus-visible,
.bmr-page button:active,
.bmr-page [type="button"],
.bmr-page [type="button"]:focus,
.bmr-page [type="button"]:focus-visible,
.bmr-page [type="button"]:active {
    outline: none !important;
    outline-color: transparent !important;
    outline-offset: 0 !important;
    box-shadow: none !important;
}

.bmr-gender-btn:focus,
.bmr-gender-btn:focus-visible,
.bmr-gender-btn:active {
    outline: none !important;
    box-shadow: none !important;
}

.bmr-gender-btn:hover:not(.is-active) {
    border-color: var(--b-accent) !important;
    background: var(--b-card) !important;
    color: var(--b-text) !important;
}

.bmr-gender-btn.is-active,
.bmr-gender-btn.is-active:hover,
.bmr-gender-btn.is-active:focus {
    border-color: var(--b-accent) !important;
    background: var(--b-accent-soft) !important;
    color: var(--b-accent-dark) !important;
    box-shadow: none !important;
}

.bmr-formula-chip:focus,
.bmr-formula-chip:focus-visible,
.bmr-formula-chip:active,
.bmr-activity-card:focus,
.bmr-cols-btn:focus,
.bmr-cols-btn:focus-visible {
    outline: none !important;
    box-shadow: none !important;
}
.bmr-formula-chip:focus,
.bmr-formula-chip:focus-visible,
.bmr-formula-chip:active,
.bmr-formula-chip:hover:not(.is-active) {
    outline: none !important;
    box-shadow: none !important;
    background: var(--b-card) !important;
    border-color: var(--b-accent) !important;
    color: var(--b-text-muted) !important;
}

.bmr-formula-chip.is-active,
.bmr-formula-chip.is-active:hover,
.bmr-formula-chip.is-active:focus,
.bmr-formula-chip.is-active:focus-visible,
.bmr-formula-chip.is-active:active {
    border-color: var(--b-accent) !important;
    background: var(--b-accent-soft) !important;
    box-shadow: none !important;
    outline: none !important;
}

.bmr-formula-chip.is-active:focus .bmr-formula-chip-name,
.bmr-formula-chip.is-active .bmr-formula-chip-name {
    color: var(--b-accent-dark) !important;
}

.bmr-formula-chip.is-active:focus .bmr-formula-chip-year,
.bmr-formula-chip.is-active .bmr-formula-chip-year {
    background: var(--b-accent) !important;
    color: #fff !important;
}
CSS;

    wp_register_style( 'bmr-inline-style', false );
    wp_enqueue_style( 'bmr-inline-style' );
    wp_add_inline_style( 'bmr-inline-style', $css );
}
add_action( 'wp_enqueue_scripts', 'bmr_kalkulator_inline_styles' );
