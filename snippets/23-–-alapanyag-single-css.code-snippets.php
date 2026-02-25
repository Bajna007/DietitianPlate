<?php

/**
 * 23 – Alapanyag Single CSS
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ============================================================
// 23 – Alapanyag Single CSS (v9 – dinamikus fejléc zöld szín)
// ============================================================

function alapanyag_single_css() {
    if ( ! is_singular( 'alapanyag' ) ) return;

    $css = <<<'CSSEOF'

/* ══════════════════════════════════════════════════════════════
   ALAPANYAG SINGLE – PRÉMIUM DESIGN v9
   dinamikus fejléc zöld szín + minden korábbi funkció
   ══════════════════════════════════════════════════════════════ */

:root {
    --aa-bg:         #f7f8f9;
    --aa-card:       #ffffff;
    --aa-border:     #e4e7ec;
    --aa-text:       #1a1d23;
    --aa-text-muted: #6b7280;
    --aa-radius:     14px;
    --aa-radius-sm:  10px;
    --aa-shadow:     0 1px 3px rgba(0,0,0,0.04), 0 4px 20px rgba(0,0,0,0.03);
    --aa-shadow-lg:  0 4px 12px rgba(0,0,0,0.06), 0 12px 40px rgba(0,0,0,0.04);
    --aa-transition: 0.25s cubic-bezier(0.4,0,0.2,1);

    --aa-accent:      #2d6a4f;
    --aa-accent-light:#40916c;
    --aa-accent-soft:  rgba(45,106,79,0.08);
    --aa-accent-hover: rgba(45,106,79,0.04);

    --aa-kcal:    #e07a2f;
    --aa-protein: #2563eb;
    --aa-carb:    #059669;
    --aa-fat:     #dc2626;
}


/* ── WRAP ── */

.aa-single-wrap {
    background: var(--aa-bg);
    background-image:
        radial-gradient(ellipse at 20% 0%, rgba(45,106,79,0.03) 0%, transparent 60%),
        radial-gradient(ellipse at 80% 100%, rgba(45,106,79,0.02) 0%, transparent 60%);
    min-height: 100vh;
}


/* ══════════════════════════════════════════
   HERO
   ══════════════════════════════════════════ */

.aa-hero {
    background-color: #1a1d23;
    background-image:
        linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px),
        repeating-linear-gradient(
            45deg,
            transparent,
            transparent 40px,
            rgba(255,255,255,0.008) 40px,
            rgba(255,255,255,0.008) 41px
        ),
        radial-gradient(ellipse at 25% 30%, rgba(45,106,79,0.15) 0%, transparent 55%),
        radial-gradient(ellipse at 75% 70%, rgba(45,106,79,0.08) 0%, transparent 50%),
        radial-gradient(circle at 50% 0%, rgba(224,122,47,0.04) 0%, transparent 40%),
        linear-gradient(160deg, #1a1d23 0%, #252830 30%, #1e2127 60%, #1a1d23 100%);
    padding: 52px 24px 44px;
    text-align: center;
    position: relative;
    overflow: hidden;
    background-size:
        60px 60px,
        60px 60px,
        auto,
        auto,
        auto,
        auto,
        auto;
}

.aa-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.03'/%3E%3C/svg%3E");
    background-repeat: repeat;
    background-size: 256px 256px;
    pointer-events: none;
    z-index: 0;
}

.aa-hero::after {
    content: '';
    position: absolute;
    top: 0;
    left: 10%;
    right: 10%;
    height: 1px;
    background: linear-gradient(
        90deg,
        transparent 0%,
        rgba(45,106,79,0.3) 30%,
        rgba(45,106,79,0.5) 50%,
        rgba(45,106,79,0.3) 70%,
        transparent 100%
    );
    z-index: 1;
}

.aa-hero-content {
    max-width: 800px;
    margin: 0 auto;
    position: relative;
    z-index: 2;
}

.aa-hero-title {
    color: #ffffff;
    font-size: 2.2rem;
    font-weight: 800;
    margin: 0 0 6px;
    line-height: 1.2;
    letter-spacing: -0.02em;
}

.aa-hero-original {
    color: rgba(255,255,255,0.45);
    font-size: 1rem;
    font-style: italic;
    margin-bottom: 18px;
}

.aa-hero-badges {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-bottom: 14px;
    flex-wrap: wrap;
}

.aa-badge {
    display: inline-flex;
    align-items: center;
    padding: 5px 14px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    backdrop-filter: blur(8px);
}

.aa-badge-off {
    background: rgba(224,122,47,0.12);
    color: #e8985a;
    border: 1px solid rgba(224,122,47,0.2);
}

.aa-badge-usda {
    background: rgba(37,99,235,0.12);
    color: #7ba3f0;
    border: 1px solid rgba(37,99,235,0.2);
}

.aa-badge-deepl {
    background: rgba(139,92,246,0.12);
    color: #b09cf5;
    border: 1px solid rgba(139,92,246,0.2);
}

.aa-hero-ids {
    display: flex;
    justify-content: center;
    gap: 20px;
    color: rgba(255,255,255,0.3);
    font-size: 0.8rem;
    font-family: 'SF Mono', 'Fira Code', monospace;
    letter-spacing: 0.03em;
}


/* ══════════════════════════════════════════
   TARTALOM
   ══════════════════════════════════════════ */

.aa-content {
    max-width: 900px;
    margin: 0 auto;
    padding: 32px 24px 60px;
}


/* ── SZEKCIÓ ── */

.aa-section {
    background: var(--aa-card);
    border: 1px solid var(--aa-border);
    border-radius: var(--aa-radius);
    box-shadow: var(--aa-shadow);
    margin-bottom: 24px;
    overflow: hidden;
    animation: aaFadeIn 0.45s ease both;
}

.aa-section:nth-child(1) { animation-delay: 0.05s; }
.aa-section:nth-child(2) { animation-delay: 0.15s; }
.aa-section:nth-child(3) { animation-delay: 0.25s; }

@keyframes aaFadeIn {
    from { opacity: 0; transform: translateY(14px); }
    to   { opacity: 1; transform: translateY(0); }
}

.aa-section-header {
    background: var(--aa-accent);
    color: #fff;
    padding: 14px 24px;
    font-size: 1rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
    letter-spacing: -0.01em;
}

.aa-section-icon {
    font-size: 1.1rem;
}

.aa-section-sub {
    font-weight: 400;
    font-size: 0.82rem;
    opacity: 0.7;
    margin-left: auto;
}


/* ══════════════════════════════════════════
   MAKRÓ KÁRTYÁK
   ══════════════════════════════════════════ */

.aa-macro-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    padding: 24px;
}

.aa-macro-card {
    text-align: center;
    padding: 20px 12px 16px;
    border-radius: var(--aa-radius-sm);
    border: 1px solid var(--aa-border);
    transition: var(--aa-transition);
}

.aa-macro-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--aa-shadow);
}

.aa-macro-kcal    { background: rgba(224,122,47,0.04);  border-color: rgba(224,122,47,0.15); }
.aa-macro-protein { background: rgba(37,99,235,0.04);   border-color: rgba(37,99,235,0.15); }
.aa-macro-carb    { background: rgba(5,150,105,0.04);   border-color: rgba(5,150,105,0.15); }
.aa-macro-fat     { background: rgba(220,38,38,0.04);   border-color: rgba(220,38,38,0.15); }

.aa-macro-value {
    font-size: 1.8rem;
    font-weight: 800;
    line-height: 1;
    margin-bottom: 6px;
    letter-spacing: -0.03em;
}

.aa-macro-kcal    .aa-macro-value { color: var(--aa-kcal); }
.aa-macro-protein .aa-macro-value { color: var(--aa-protein); }
.aa-macro-carb    .aa-macro-value { color: var(--aa-carb); }
.aa-macro-fat     .aa-macro-value { color: var(--aa-fat); }

.aa-macro-unit {
    font-size: 0.78rem;
    color: var(--aa-text-muted);
    margin-bottom: 4px;
}

.aa-macro-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--aa-text);
}

.aa-macro-pct {
    font-weight: 400;
    color: var(--aa-text-muted);
    font-size: 0.74rem;
}

.aa-macro-bar {
    height: 5px;
    background: #f0f1f3;
    border-radius: 3px;
    margin: 8px 0 6px;
    overflow: hidden;
}

.aa-macro-bar-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.6s ease;
}

.aa-macro-protein .aa-macro-bar-fill { background: var(--aa-protein); }
.aa-macro-carb    .aa-macro-bar-fill { background: var(--aa-carb); }
.aa-macro-fat     .aa-macro-bar-fill { background: var(--aa-fat); }


/* ── ENERGIA% MAGYARÁZÓ SÁV ── */

.aa-macro-notice {
    margin: 0 24px 16px;
    padding: 10px 14px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-left: 3px solid var(--aa-accent);
    border-radius: 6px;
    font-size: 0.75rem;
    line-height: 1.5;
    color: var(--aa-text-muted);
}


/* ── MAKRÓ EXTRA ── */

.aa-macro-extra {
    display: flex;
    justify-content: center;
    gap: 24px;
    padding: 0 24px 20px;
    font-size: 0.86rem;
    color: var(--aa-text-muted);
}


/* ══════════════════════════════════════════
   ZSÍR EMOJI FIX
   ══════════════════════════════════════════ */

.aa-emoji-zsir {
    font-family: "Apple Color Emoji", "Segoe UI Emoji", "Noto Color Emoji", "Twemoji Mozilla", sans-serif;
    font-style: normal;
    font-weight: normal;
    font-size: 1em;
    line-height: 1;
    vertical-align: middle;
    display: inline-block;
}


/* ══════════════════════════════════════════
   TABOK
   ══════════════════════════════════════════ */

.aa-tabs {
    display: flex;
    gap: 0;
    padding: 16px 24px 0;
    border-bottom: 1px solid var(--aa-border);
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    -ms-overflow-style: none;
}

.aa-tabs::-webkit-scrollbar {
    display: none;
}

.aa-tab {
    padding: 10px 18px;
    font-size: 0.86rem;
    font-weight: 600;
    color: var(--aa-text-muted);
    background: none !important;
    border: none !important;
    border-bottom: 2px solid transparent !important;
    border-radius: 0 !important;
    cursor: pointer;
    transition: color 0.2s ease, border-color 0.2s ease;
    outline: none !important;
    box-shadow: none !important;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    white-space: nowrap;
    flex-shrink: 0;
}

.aa-tab:hover {
    color: var(--aa-text);
    background: none !important;
    border-color: transparent !important;
    border-bottom-color: #d1d5db !important;
    outline: none !important;
    box-shadow: none !important;
}

.aa-tab:focus,
.aa-tab:focus-visible,
.aa-tab:focus-within,
.aa-tab:active {
    color: var(--aa-text);
    background: none !important;
    border: none !important;
    border-bottom: 2px solid transparent !important;
    outline: none !important;
    outline-offset: 0 !important;
    box-shadow: none !important;
    -webkit-box-shadow: none !important;
}

.aa-tab.is-active {
    color: var(--aa-accent) !important;
    border-bottom: 2px solid var(--aa-accent) !important;
    background: none !important;
    outline: none !important;
    box-shadow: none !important;
}

.aa-tab.is-active:focus,
.aa-tab.is-active:focus-visible,
.aa-tab.is-active:hover {
    color: var(--aa-accent) !important;
    border-bottom: 2px solid var(--aa-accent) !important;
    background: none !important;
    outline: none !important;
    box-shadow: none !important;
}


/* ══════════════════════════════════════════
   TÁBLÁZAT
   ══════════════════════════════════════════ */

.aa-table-wrap {
    padding: 0 24px 24px;
}

.aa-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
    table-layout: fixed;
}

/* ── FEJLÉC – DINAMIKUS ZÖLD CÍMKE ── */

.aa-table thead th {
    padding: 12px 0;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    border-bottom: 2px solid var(--aa-border);
}

.aa-table thead th:first-child {
    text-align: left;
    width: 60%;
    padding-left: 4px;
    color: var(--aa-accent);
}

.aa-table thead th:last-child {
    text-align: right;
    width: 40%;
    padding-right: 4px;
    color: var(--aa-text-muted);
}

/* ── CELLÁK ── */

.aa-table td {
    padding: 10px 0;
    border-bottom: 1px solid #f0f1f3;
    color: var(--aa-text);
    vertical-align: middle;
}

.aa-table td:first-child {
    text-align: left;
    padding-left: 4px;
}

.aa-table td:last-child {
    text-align: right;
    padding-right: 4px;
}

.aa-table tbody tr:last-child td {
    border-bottom: none;
}

.aa-row-main td {
    font-weight: 500;
}

.aa-row-sub td {
    color: var(--aa-text-muted);
    font-size: 0.84rem;
    font-weight: 400;
}

.aa-row-sub td:first-child {
    padding-left: 24px;
}

/* ─── KATEGÓRIA ELVÁLASZTÓ SOROK ─── */

.aa-row-divider td {
    padding: 0;
    border-bottom: none;
}

.aa-row-divider td[colspan] {
    padding: 24px 0 8px 0;
    background: transparent;
    border-bottom: none;
    font-size: 0.76rem;
    font-weight: 700;
    color: var(--aa-accent);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    position: relative;
}

.aa-row-divider td[colspan]::after {
    content: '';
    display: block;
    margin-top: 6px;
    height: 2px;
    background: linear-gradient(90deg, var(--aa-accent) 0%, rgba(45,106,79,0.15) 50%, transparent 100%);
    border-radius: 1px;
}

/* Hover */
.aa-table tbody tr:not(.aa-row-divider):hover td {
    background: var(--aa-accent-hover);
}

.aa-table tbody tr[style*="display: none"] {
    display: none !important;
}


/* ══════════════════════════════════════════
   FORRÁS
   ══════════════════════════════════════════ */

.aa-source-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 16px;
    padding: 24px;
}

.aa-source-card {
    padding: 18px 20px;
    background: var(--aa-bg);
    border: 1px solid var(--aa-border);
    border-radius: var(--aa-radius-sm);
    transition: var(--aa-transition);
}

.aa-source-card:hover {
    border-color: rgba(45,106,79,0.2);
}

.aa-source-title {
    font-weight: 700;
    font-size: 0.93rem;
    margin-bottom: 10px;
    color: var(--aa-text);
}

.aa-source-row {
    font-size: 0.84rem;
    margin-bottom: 5px;
    color: var(--aa-text);
}

.aa-source-label {
    color: var(--aa-text-muted);
    margin-right: 4px;
}

.aa-source-link {
    color: var(--aa-accent);
    text-decoration: none;
    font-weight: 600;
    transition: var(--aa-transition);
}

.aa-source-link:hover {
    color: var(--aa-accent-light);
    text-decoration: underline;
}

.aa-source-link:focus,
.aa-source-link:focus-visible {
    outline: none !important;
    box-shadow: none !important;
}

.aa-source-muted {
    color: var(--aa-text-muted);
    font-size: 0.8rem;
}


/* ══════════════════════════════════════════
   KADENCE GLOBAL OVERRIDE
   ══════════════════════════════════════════ */

.aa-single-wrap *:focus,
.aa-single-wrap *:focus-visible,
.aa-single-wrap *:focus-within {
    outline: none !important;
    outline-offset: 0 !important;
    box-shadow: none !important;
    -webkit-box-shadow: none !important;
}

.aa-single-wrap button:focus,
.aa-single-wrap button:focus-visible,
.aa-single-wrap button:active,
.aa-single-wrap [type="button"]:focus,
.aa-single-wrap [type="button"]:focus-visible {
    outline: none !important;
    outline-color: transparent !important;
    box-shadow: none !important;
    -webkit-box-shadow: none !important;
    border-color: inherit !important;
}

.aa-single-wrap a:focus,
.aa-single-wrap a:focus-visible {
    outline: none !important;
    box-shadow: none !important;
    text-decoration: none;
}


/* ══════════════════════════════════════════
   RESPONSIVE
   ══════════════════════════════��═══════════ */

@media (max-width: 768px) {
    .aa-hero {
        padding: 36px 16px 30px;
    }

    .aa-hero-title {
        font-size: 1.6rem;
    }

    .aa-hero-ids {
        flex-direction: column;
        gap: 4px;
    }

    .aa-content {
        padding: 20px 16px 40px;
    }

    .aa-macro-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        padding: 16px;
    }

    .aa-macro-value {
        font-size: 1.4rem;
    }

    .aa-macro-extra {
        flex-direction: column;
        align-items: center;
        gap: 8px;
    }

    .aa-macro-notice {
        margin: 0 16px 12px;
    }

    .aa-tabs {
        padding: 12px 16px 0;
    }

    .aa-tab {
        padding: 8px 12px;
        font-size: 0.8rem;
    }

    .aa-table-wrap {
        padding: 0 16px 16px;
    }

    .aa-table thead th:first-child {
        width: 55%;
    }

    .aa-table thead th:last-child {
        width: 45%;
    }

    .aa-source-grid {
        grid-template-columns: 1fr;
        padding: 16px;
    }
}

@media (max-width: 480px) {
    .aa-hero-title {
        font-size: 1.3rem;
    }

    .aa-macro-grid {
        gap: 8px;
        padding: 12px;
    }

    .aa-macro-card {
        padding: 14px 8px 12px;
    }

    .aa-macro-value {
        font-size: 1.2rem;
    }

    .aa-tab {
        padding: 8px 10px;
        font-size: 0.76rem;
    }

    .aa-table thead th:first-child {
        width: 50%;
    }

    .aa-table thead th:last-child {
        width: 50%;
    }

    .aa-row-sub td:first-child {
        padding-left: 16px;
    }

    .aa-macro-notice {
        margin: 0 12px 10px;
        font-size: 0.7rem;
    }
}

CSSEOF;

    wp_register_style( 'alapanyag-single-css', false );
    wp_enqueue_style( 'alapanyag-single-css' );
    wp_add_inline_style( 'alapanyag-single-css', $css );
}
add_action( 'wp_enqueue_scripts', 'alapanyag_single_css' );
