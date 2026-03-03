<?php

/**
 * 35. – Profil oldal CSS v2.0
 */
/**
 * 35 – Profil + Gyűjtemények + Recept User Bar CSS
 * v5.1 – Goals tab summary + CTA stílusok hozzáadva
 */
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_head', function() {
    $is_profil = is_page( 'profil' ) || is_page_template( 'page-profil.php' );
    $is_recept = is_singular( 'recept' );

    if ( ! $is_profil && ! $is_recept ) return;

    $c = '';

    /* ══════════════════════════════════════════
       PROFIL OLDAL CSS (csak profil oldalon)
       ══════════════════════════════════════════ */
    if ( $is_profil ) {

        /* ── RESET ── */
        $c .= '.dp-profil,.dp-profil *{box-sizing:border-box}';
        $c .= '.dp-profil{max-width:100%!important;margin:0 auto!important;padding:0!important;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif!important}';

        /* ── HERO ── */
        $c .= '.dp-profil-hero{position:relative;margin-bottom:32px}';
        $c .= '.dp-profil-banner{position:relative;height:200px;background-color:#1a1d1b;background-image:radial-gradient(ellipse at 20% 50%,rgba(45,106,79,0.15) 0%,transparent 55%),radial-gradient(ellipse at 80% 50%,rgba(45,106,79,0.08) 0%,transparent 50%),linear-gradient(160deg,#1a1d1b 0%,#22261f 30%,#1e211c 60%,#1a1d1b 100%);background-size:cover!important;background-position:center!important;overflow:hidden}';
        $c .= '.dp-profil-banner-overlay{position:absolute;inset:0;background:linear-gradient(180deg,transparent 50%,rgba(0,0,0,0.4) 100%);pointer-events:none}';
        $c .= '.dp-profil-banner-edit{position:absolute;top:16px;right:16px;width:36px;height:36px;display:flex!important;align-items:center!important;justify-content:center!important;border:none!important;border-radius:10px!important;background:rgba(255,255,255,0.15)!important;backdrop-filter:blur(8px);color:#fff!important;cursor:pointer;transition:all .2s ease;outline:none!important;box-shadow:none!important;-webkit-appearance:none!important;padding:0!important;z-index:3}.dp-profil-banner-edit:hover{background:rgba(255,255,255,0.25)!important;transform:scale(1.05)}.dp-profil-banner-edit svg{stroke:currentColor}';
        $c .= '.dp-profil-hero-content{position:relative;max-width:1000px;margin:-48px auto 0!important;padding:0 24px!important;display:flex!important;align-items:flex-start!important;gap:24px;z-index:2}';

        /* ── AVATAR ── */
        $c .= '.dp-profil-avatar-wrap{position:relative;flex-shrink:0}';
        $c .= 'img.dp-profil-avatar{width:108px!important;height:108px!important;border-radius:50%!important;border:4px solid #fff!important;box-shadow:0 4px 24px rgba(0,0,0,0.12)!important;object-fit:cover!important;background:#f0f2f1;display:block!important}';
        $c .= '.dp-profil-avatar-edit{position:absolute;bottom:4px;right:4px;width:30px;height:30px;display:flex!important;align-items:center!important;justify-content:center!important;border:2px solid #fff!important;border-radius:50%!important;background:#2d6a4f!important;color:#fff!important;cursor:pointer;transition:all .2s ease;outline:none!important;box-shadow:0 2px 8px rgba(0,0,0,0.15)!important;-webkit-appearance:none!important;padding:0!important}.dp-profil-avatar-edit:hover{background:#245a42!important;transform:scale(1.1)}.dp-profil-avatar-edit svg{stroke:currentColor}';

        /* ── HERO INFO ── */
        $c .= '.dp-profil-hero-info{flex:1;min-width:0;padding-top:56px}';
        $c .= 'h1.dp-profil-hero-name{font-size:1.5rem!important;font-weight:800!important;color:#1e1e1e!important;margin:0 0 4px!important;line-height:1.2!important;letter-spacing:-.02em;padding:0!important;border:none!important;text-align:left!important}';
        $c .= '.dp-profil-hero-meta{display:flex!important;align-items:center;flex-wrap:wrap;gap:6px;font-size:.85rem;color:#7c8a83}';
        $c .= '.dp-profil-hero-sep{opacity:.4}.dp-profil-user-id{font-weight:700;color:#2d6a4f}.dp-profil-hero-google{display:inline-flex;align-items:center;gap:4px}';
        $c .= '.dp-profil-logout-btn{display:inline-flex!important;align-items:center!important;gap:6px;padding:10px 20px;border-radius:10px;border:1.5px solid #e2e6e4!important;background:#fff!important;color:#7c8a83!important;font-size:13px!important;font-weight:600!important;text-decoration:none!important;transition:all .2s ease;flex-shrink:0;margin-top:56px;margin-left:auto}.dp-profil-logout-btn:hover{border-color:#dc2626!important;color:#dc2626!important;background:rgba(220,38,38,0.04)!important}.dp-profil-logout-btn svg{stroke:currentColor}';

        /* ── CONTENT ── */
        $c .= '.dp-profil-content{max-width:1000px!important;margin:0 auto!important;padding:0 24px 60px!important}';

        /* ── STATS ── */
        $c .= '.dp-profil-stats{display:grid!important;grid-template-columns:repeat(4,1fr)!important;gap:16px!important;margin-bottom:32px!important}';
        $c .= '.dp-profil-stat-card{background:#fff!important;border:1px solid #e2e6e4!important;border-radius:14px!important;padding:20px!important;text-align:center!important;display:flex!important;flex-direction:column!important;align-items:center!important;gap:6px!important;box-shadow:0 2px 12px rgba(0,0,0,0.03)!important;transition:all .2s ease}.dp-profil-stat-card:hover{transform:translateY(-2px);box-shadow:0 6px 24px rgba(0,0,0,0.06)!important}';
        $c .= '.dp-profil-stat-icon{font-size:1.5rem;line-height:1}.dp-profil-stat-value{font-size:1.3rem;font-weight:800;color:#1e1e1e;line-height:1.2}.dp-profil-stat-email{font-size:.72rem!important;font-weight:600;word-break:break-all}.dp-profil-stat-label{font-size:.78rem;color:#7c8a83;font-weight:500}';

        /* ── TABS ── */
        $c .= '.dp-profil-tabs{display:flex!important;gap:4px;margin-bottom:24px!important;border-bottom:2px solid #f0f1f3;padding-bottom:0}';
        $c .= 'button.dp-profil-tab{display:inline-flex!important;align-items:center!important;gap:6px;padding:12px 20px!important;border:none!important;background:none!important;font-size:14px!important;font-weight:600!important;color:#999!important;cursor:pointer;border-bottom:2px solid transparent!important;margin-bottom:-2px;transition:color .2s ease;outline:none!important;box-shadow:none!important;-webkit-appearance:none!important;border-radius:0!important}button.dp-profil-tab:hover{color:#666!important}button.dp-profil-tab.is-active{color:#2d6a4f!important;border-bottom-color:#2d6a4f!important}button.dp-profil-tab svg{width:16px;height:16px;stroke:currentColor;opacity:.7}';
        $c .= '.dp-profil-tab-badge{background:rgba(45,106,79,0.1);color:#2d6a4f;font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px;margin-left:2px}';

        /* ── PANELS ── */
        $c .= '.dp-profil-panel{display:none!important}.dp-profil-panel.is-active{display:block!important;animation:dpProfFadeIn .3s ease}';
        $c .= '@keyframes dpProfFadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}';

        /* ── SECTIONS ── */
        $c .= '.dp-profil-section{background:#fff!important;border:1px solid #e2e6e4!important;border-radius:16px!important;box-shadow:0 4px 24px rgba(0,0,0,0.04)!important;overflow:hidden;margin-bottom:24px!important}';
        $c .= '.dp-profil-section--danger{border-color:rgba(220,38,38,0.2)!important}';
        $c .= '.dp-profil-section-header{display:flex!important;align-items:center!important;gap:10px;padding:16px 24px!important;background:#2d6a4f!important;color:#fff!important;font-size:1rem!important;font-weight:700!important}.dp-profil-section-header svg{stroke:#fff;opacity:.85}';
        $c .= '.dp-profil-section-header--danger{background:#dc2626!important}';
        $c .= '.dp-profil-count{margin-left:auto;background:rgba(255,255,255,0.2);padding:2px 12px;border-radius:999px;font-size:13px;font-weight:700}';
        $c .= '.dp-profil-badge{font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px;margin-left:auto}.dp-profil-badge--info{background:rgba(255,255,255,0.2);color:#fff}';

        /* ── KEDVENCEK GRID ── */
        $c .= '.dp-profil-grid{display:grid!important;grid-template-columns:repeat(3,1fr)!important;gap:20px!important;padding:24px!important}';
        $c .= '.dp-profil-recept-card{background:#fff!important;border:1px solid #e2e6e4!important;border-radius:14px!important;overflow:hidden;text-decoration:none!important;color:#1e1e1e!important;transition:all .25s ease;display:flex!important;flex-direction:column!important;box-shadow:0 2px 12px rgba(0,0,0,0.04)}.dp-profil-recept-card:hover{transform:translateY(-4px);box-shadow:0 8px 32px rgba(0,0,0,0.08)!important;border-color:#2d6a4f!important}';
        $c .= '.dp-profil-recept-kep{position:relative;aspect-ratio:16/10;overflow:hidden;background:#f0f2f1}.dp-profil-recept-kep img{width:100%!important;height:100%!important;object-fit:cover!important;transition:transform .25s ease}.dp-profil-recept-card:hover .dp-profil-recept-kep img{transform:scale(1.05)}.dp-profil-recept-ph{width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:2.5rem}';
        $c .= '.dp-profil-recept-body{padding:14px 16px 18px;display:flex;flex-direction:column;gap:6px}.dp-profil-recept-body h3{font-size:.95rem!important;font-weight:700!important;margin:0!important;line-height:1.3!important;color:#1e1e1e!important}.dp-profil-recept-meta{display:flex;gap:10px;font-size:.82rem;color:#7c8a83}';
        $c .= '.dp-profil-empty{padding:60px 24px!important;text-align:center}.dp-profil-empty-icon{font-size:3rem;display:block;margin-bottom:16px;opacity:.3}.dp-profil-empty p{font-size:1.1rem;font-weight:600;color:#7c8a83;margin:0 0 20px}.dp-profil-empty-btn{display:inline-flex;align-items:center;gap:6px;padding:12px 28px;border-radius:999px;background:#2d6a4f!important;color:#fff!important;text-decoration:none!important;font-size:14px;font-weight:700;transition:all .2s ease;box-shadow:0 4px 16px rgba(45,106,79,0.3)}.dp-profil-empty-btn:hover{background:#245a42!important;transform:translateY(-2px)}';

        /* ── GYŰJTEMÉNY CSEMPÉK ── */
        $c .= '.dp-coll-grid{display:grid!important;grid-template-columns:repeat(3,1fr)!important;gap:20px!important;padding:24px!important}';
        $c .= '.dp-coll-card{border:1px solid #e2e6e4;border-radius:14px;overflow:hidden;cursor:pointer;transition:all .25s ease;background:#fff;box-shadow:0 2px 12px rgba(0,0,0,0.04)}.dp-coll-card:hover{transform:translateY(-4px);box-shadow:0 8px 32px rgba(0,0,0,0.08);border-color:#2d6a4f}';
        $c .= '.dp-coll-card-top{position:relative;height:80px;display:flex;align-items:center;justify-content:center;overflow:hidden}.dp-coll-card-top--new{background:#f5f6f5!important;border-bottom:2px dashed #d0d5dd}';
        $c .= '.dp-coll-card-icon{font-size:2rem;filter:drop-shadow(0 2px 4px rgba(0,0,0,0.2))}';
        $c .= '.dp-coll-new-icon{font-size:2rem;color:#999;font-weight:300}';
        $c .= '.dp-coll-card-actions{position:absolute;top:8px;right:8px;display:flex;gap:4px;opacity:0;transition:opacity .2s ease}.dp-coll-card:hover .dp-coll-card-actions{opacity:1}';
        $c .= '.dp-coll-edit-btn,.dp-coll-delete-btn{width:28px;height:28px;display:flex;align-items:center;justify-content:center;border:none!important;border-radius:8px!important;background:rgba(255,255,255,0.9)!important;cursor:pointer;font-size:13px;transition:all .15s ease;padding:0!important;outline:none!important;box-shadow:0 1px 4px rgba(0,0,0,0.1)!important;-webkit-appearance:none!important}.dp-coll-edit-btn:hover{background:#fff!important;transform:scale(1.1)}.dp-coll-delete-btn:hover{background:#fee2e2!important;transform:scale(1.1)}';
        $c .= '.dp-coll-card-body{padding:14px 16px}.dp-coll-card-name{font-size:.95rem;font-weight:700;color:#1e1e1e;margin:0 0 4px!important;line-height:1.3}.dp-coll-card-count{font-size:.8rem;color:#7c8a83;font-weight:500}';
        $c .= '.dp-coll-card--new{border-style:dashed;border-color:#d0d5dd;opacity:.7;transition:all .25s ease}.dp-coll-card--new:hover{opacity:1;border-color:#2d6a4f;border-style:solid}';
        $c .= '.dp-coll-back-btn{display:inline-flex!important;align-items:center!important;gap:6px;padding:10px 20px;border:1.5px solid #e2e6e4!important;border-radius:10px!important;background:#fff!important;color:#666!important;font-size:13px!important;font-weight:600!important;cursor:pointer;transition:all .2s ease;margin-bottom:16px;outline:none!important;box-shadow:none!important;-webkit-appearance:none!important}.dp-coll-back-btn:hover{border-color:#2d6a4f!important;color:#2d6a4f!important}.dp-coll-back-btn svg{stroke:currentColor}';

        /* ── GYŰJTEMÉNY MODAL ── */
        $c .= '.dp-coll-modal-overlay{position:fixed;inset:0;z-index:200000;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0);backdrop-filter:blur(0);pointer-events:none;visibility:hidden;transition:all .3s ease}.dp-coll-modal-overlay.is-open{background:rgba(0,0,0,0.5);backdrop-filter:blur(8px);pointer-events:none;visibility:visible}.dp-coll-modal-overlay.is-open .dp-coll-modal{pointer-events:auto}';
        $c .= '.dp-coll-modal{position:relative;width:400px;max-width:92vw;background:#fff;border-radius:20px;padding:32px;box-shadow:0 24px 80px rgba(0,0,0,0.15);opacity:0;transform:translateY(20px) scale(.97);transition:all .3s ease}.dp-coll-modal-overlay.is-open .dp-coll-modal{opacity:1;transform:translateY(0) scale(1)}';
        $c .= '.dp-coll-modal-title{font-size:1.2rem;font-weight:800;color:#1e1e1e;margin:0 0 24px!important}';
        $c .= '.dp-coll-modal-field{margin-bottom:20px}.dp-coll-modal-field label{display:block;font-size:13px;font-weight:600;color:#555;margin-bottom:8px}';
        $c .= '.dp-coll-color-picker{display:flex;flex-wrap:wrap;gap:8px}';
        $c .= '.dp-coll-color-swatch{width:32px;height:32px;border-radius:50%;border:3px solid transparent!important;cursor:pointer;transition:all .15s ease;outline:none!important;box-shadow:none!important;-webkit-appearance:none!important;padding:0!important}.dp-coll-color-swatch:hover{transform:scale(1.15)}.dp-coll-color-swatch.is-selected{border-color:#1e1e1e!important;transform:scale(1.15);box-shadow:0 0 0 2px #fff,0 0 0 4px #1e1e1e!important}';
        $c .= '.dp-coll-modal-actions{display:flex;gap:10px;margin-top:24px}';

        /* ══════════════════════════════════════════
           CÉLJAIM TAB – ÖSSZEFOGLALÓ + CTA
           ══════════════════════════════════════════ */

        /* Összefoglaló kártya */
        $c .= '.dp-goals-summary{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin:20px 24px 0}';
        $c .= '.dp-goals-summary-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:8px}';
        $c .= '.dp-goals-summary-badge{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:20px;color:#fff;font-size:.82rem;font-weight:600}';
        $c .= '.dp-goals-summary-date{font-size:.75rem;color:#94a3b8}';
        $c .= '.dp-goals-summary-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}';
        $c .= '.dp-goals-summary-item{text-align:center;padding:14px 8px;background:#fff;border-radius:10px;border:1px solid #e5e7eb;transition:all .2s ease}.dp-goals-summary-item:hover{border-color:#2d6a4f;box-shadow:0 2px 8px rgba(45,106,79,0.08)}';
        $c .= '.dp-goals-summary-val{display:block;font-size:1.3rem;font-weight:800;color:#1e293b;line-height:1.2}';
        $c .= '.dp-goals-summary-label{display:block;font-size:.72rem;color:#94a3b8;margin-top:4px;font-weight:500}';

        /* Üres állapot */
        $c .= '.dp-goals-empty{text-align:center;padding:48px 24px;color:#94a3b8}';
        $c .= '.dp-goals-empty-icon{font-size:2.8rem;display:block;margin-bottom:12px;opacity:.4}';
        $c .= '.dp-goals-empty p{margin:0;font-size:.95rem;font-weight:500}';

        /* CTA gomb */
        $c .= '.dp-goals-cta{text-align:center;padding:24px 24px 28px}';
        $c .= 'a.dp-goals-cta-btn{display:inline-flex!important;align-items:center!important;gap:8px;padding:14px 32px!important;font-size:.95rem!important;font-weight:700!important;text-decoration:none!important;border-radius:12px!important;background:#2d6a4f!important;color:#fff!important;border:none!important;transition:all .25s ease;box-shadow:0 4px 16px rgba(45,106,79,0.25)}a.dp-goals-cta-btn:hover{background:#245a42!important;transform:translateY(-2px);box-shadow:0 8px 24px rgba(45,106,79,0.35)}a.dp-goals-cta-btn svg{stroke:currentColor;width:16px;height:16px}';
        $c .= '.dp-goals-cta-sub{margin:12px 0 0;font-size:.78rem;color:#94a3b8;font-weight:400}';

        /* ── BEÁLLÍTÁSOK ── */
        $c .= '.dp-profil-settings-body{padding:24px!important}';
        $c .= '.dp-profil-settings-note{font-size:14px;color:#666;line-height:1.6;margin:0 0 20px;padding:12px 16px;background:rgba(45,106,79,0.06);border-radius:10px;border-left:3px solid #2d6a4f}';
        $c .= '.dp-profil-setting-row{padding:16px 0!important;border-bottom:1px solid #f0f1f3}.dp-profil-setting-row:last-child{border-bottom:none}';
        $c .= '.dp-profil-setting-label{margin-bottom:10px}.dp-profil-setting-label strong{display:block;font-size:14px;font-weight:700;color:#1e1e1e;margin-bottom:2px}.dp-profil-setting-label span{font-size:12.5px;color:#999}';
        $c .= '.dp-profil-setting-action{display:flex!important;align-items:center!important;gap:10px!important;flex-wrap:wrap}';
        $c .= 'input.dp-profil-input{padding:10px 16px!important;border:1.5px solid #e2e6e4!important;border-radius:10px!important;font-size:14px!important;font-weight:500!important;color:#1e1e1e!important;background:#fafbfa!important;outline:none!important;box-shadow:none!important;transition:border-color .2s ease,box-shadow .2s ease;-webkit-appearance:none!important;line-height:1.4!important;flex:1;min-width:180px;max-width:320px;margin:0!important;height:auto!important}input.dp-profil-input:focus{border-color:#2d6a4f!important;box-shadow:0 0 0 3px rgba(45,106,79,0.12)!important;background:#fff!important}input.dp-profil-input:disabled{opacity:.6;cursor:not-allowed;background:#f0f1f3!important}';
        $c .= 'input.dp-profil-input--danger{border-color:rgba(220,38,38,0.3)!important;max-width:200px}input.dp-profil-input--danger:focus{border-color:#dc2626!important;box-shadow:0 0 0 3px rgba(220,38,38,0.12)!important}';

        /* ── GOMBOK ── */
        $c .= 'button.dp-profil-btn,a.dp-profil-btn{display:inline-flex!important;align-items:center!important;gap:6px;padding:10px 20px!important;border-radius:10px!important;font-size:13px!important;font-weight:600!important;cursor:pointer;transition:all .2s ease;outline:none!important;box-shadow:none!important;-webkit-appearance:none!important;white-space:nowrap;border:1.5px solid transparent!important;line-height:1.4!important;text-decoration:none!important}button.dp-profil-btn:disabled,a.dp-profil-btn:disabled{opacity:.5;cursor:not-allowed;transform:none!important}';
        $c .= 'button.dp-profil-btn--primary,a.dp-profil-btn--primary{background:#2d6a4f!important;color:#fff!important;border-color:#2d6a4f!important}button.dp-profil-btn--primary:hover,a.dp-profil-btn--primary:hover{background:#245a42!important;border-color:#245a42!important;box-shadow:0 4px 12px rgba(45,106,79,0.3)!important;transform:translateY(-1px)}';
        $c .= 'button.dp-profil-btn--secondary{background:#fff!important;color:#2d6a4f!important;border-color:#2d6a4f!important}button.dp-profil-btn--secondary:hover{background:#2d6a4f!important;color:#fff!important}';
        $c .= 'button.dp-profil-btn--danger{background:#dc2626!important;color:#fff!important;border-color:#dc2626!important}button.dp-profil-btn--danger:hover{background:#b91c1c!important;border-color:#b91c1c!important}';
        $c .= 'button.dp-profil-btn--danger-ghost{background:transparent!important;color:#dc2626!important;border-color:rgba(220,38,38,0.3)!important}button.dp-profil-btn--danger-ghost:hover{background:rgba(220,38,38,0.06)!important;border-color:#dc2626!important}';

        /* ── MESSAGES ── */
        $c .= '.dp-profil-setting-msg{font-size:13px;font-weight:600;margin-top:8px;min-height:0;transition:all .2s ease}.dp-profil-setting-msg:empty{display:none}.dp-profil-msg--success{color:#2d6a4f}.dp-profil-msg--error{color:#dc2626}';

        /* ── INFO GRID ── */
        $c .= '.dp-profil-info-grid{display:grid!important;grid-template-columns:repeat(2,1fr)!important;gap:16px!important}';
        $c .= '.dp-profil-info-item{display:flex!important;flex-direction:column!important;gap:2px;padding:12px 16px;background:#fafbfa;border-radius:10px;border:1px solid #f0f1f3}';
        $c .= '.dp-profil-info-label{font-size:12px;color:#999;font-weight:500;text-transform:uppercase;letter-spacing:.5px}.dp-profil-info-value{font-size:14px;font-weight:700;color:#1e1e1e}';

        /* ── DANGER ZONE ── */
        $c .= '.dp-profil-danger-zone{padding:0}.dp-profil-danger-text{font-size:14px;color:#666;line-height:1.6;margin:0 0 20px;padding:16px;background:rgba(220,38,38,0.04);border-radius:10px;border-left:3px solid #dc2626}.dp-profil-danger-confirm{display:flex!important;flex-direction:column!important;gap:12px!important;max-width:400px}.dp-profil-danger-label{font-size:13px;font-weight:600;color:#666}';

        /* ── RESPONSIVE ── */
        $c .= '@media(max-width:768px){.dp-profil-banner{height:160px}.dp-profil-hero-content{flex-direction:column!important;align-items:center!important;text-align:center;margin-top:-36px!important;gap:0!important}.dp-profil-hero-info{padding-top:12px!important}h1.dp-profil-hero-name{text-align:center!important;font-size:1.3rem!important}.dp-profil-hero-meta{justify-content:center}.dp-profil-logout-btn{margin:12px auto 0!important;margin-top:12px!important}.dp-profil-stats{grid-template-columns:repeat(2,1fr)!important;gap:12px!important}.dp-profil-grid,.dp-coll-grid{grid-template-columns:repeat(2,1fr)!important;gap:12px!important;padding:16px!important}.dp-profil-tabs{overflow-x:auto;-webkit-overflow-scrolling:touch}.dp-profil-info-grid{grid-template-columns:1fr!important}.dp-profil-setting-action{flex-direction:column!important;align-items:stretch!important}input.dp-profil-input{max-width:100%!important}img.dp-profil-avatar{width:88px!important;height:88px!important}.dp-goals-summary-grid{grid-template-columns:1fr!important}.dp-goals-summary{margin:16px 16px 0}.dp-goals-cta{padding:20px 16px 24px}}';
        $c .= '@media(max-width:480px){.dp-profil-banner{height:120px}.dp-profil-content{padding:0 16px 40px!important}h1.dp-profil-hero-name{font-size:1.2rem!important}.dp-profil-stat-card{padding:14px 10px!important}.dp-profil-stat-value{font-size:1.1rem}button.dp-profil-tab{padding:10px 14px!important;font-size:13px!important}.dp-profil-settings-body{padding:16px!important}img.dp-profil-avatar{width:76px!important;height:76px!important}.dp-profil-grid,.dp-coll-grid{grid-template-columns:1fr!important}}';
    }

    /* ══════════════════════════════════════════
       RECEPT SINGLE – USER BAR + DROPDOWN + NOTE
       ══════════════════════════════════════════ */
    if ( $is_recept && is_user_logged_in() ) {

        /* ── User toolbar ── */
        $c .= '.dp-recipe-user-bar{position:fixed;bottom:24px;left:50%;transform:translateX(-50%);display:flex;gap:8px;z-index:99999;background:#fff;padding:8px 12px;border-radius:16px;box-shadow:0 8px 40px rgba(0,0,0,0.12),0 0 0 1px rgba(0,0,0,0.04);backdrop-filter:blur(12px)}';
        $c .= '.dp-rub-btn{display:inline-flex!important;align-items:center!important;gap:8px;padding:10px 18px!important;border:1.5px solid #e2e6e4!important;border-radius:12px!important;background:#fff!important;color:#555!important;font-size:13px!important;font-weight:600!important;cursor:pointer;transition:all .2s ease;outline:none!important;box-shadow:none!important;-webkit-appearance:none!important;white-space:nowrap}.dp-rub-btn:hover{border-color:#2d6a4f!important;color:#2d6a4f!important;background:#f0fdf4!important}.dp-rub-btn svg{width:18px;height:18px;stroke:currentColor;flex-shrink:0}';
        $c .= '.dp-rub-btn--note.has-note{border-color:#2d6a4f!important;color:#2d6a4f!important;background:rgba(45,106,79,0.06)!important}';

        /* ── Gyűjtemény dropdown ── */
        $c .= '.dp-rub-dropdown,.dp-rub-note-panel{position:fixed;bottom:80px;left:50%;transform:translateX(-50%) translateY(10px);width:340px;max-width:92vw;background:#fff;border-radius:16px;box-shadow:0 16px 60px rgba(0,0,0,0.15),0 0 0 1px rgba(0,0,0,0.04);z-index:100000;opacity:0;visibility:hidden;pointer-events:none;transition:all .25s ease}.dp-rub-dropdown.is-open,.dp-rub-note-panel.is-open{opacity:1;visibility:visible;pointer-events:auto;transform:translateX(-50%) translateY(0)}';
        $c .= '.dp-rub-dropdown-header{display:flex;align-items:center;justify-content:space-between;padding:16px 20px 12px;border-bottom:1px solid #f0f1f3}.dp-rub-dropdown-header strong{font-size:14px;font-weight:700;color:#1e1e1e}';
        $c .= '.dp-rub-dropdown-close{width:28px;height:28px;display:flex;align-items:center;justify-content:center;border:none!important;background:#f5f6f5!important;border-radius:50%!important;cursor:pointer;font-size:14px;color:#666!important;transition:all .15s ease;outline:none!important;padding:0!important;-webkit-appearance:none!important}.dp-rub-dropdown-close:hover{background:#e8ebe9!important;color:#1e1e1e!important}';

        /* ── Gyűjtemény lista ── */
        $c .= '.dp-rub-coll-list{max-height:240px;overflow-y:auto;padding:8px 0}';
        $c .= '.dp-rub-coll-item{display:flex!important;align-items:center!important;gap:12px;padding:10px 20px;cursor:pointer;transition:background .15s ease;border:none;background:none;width:100%}.dp-rub-coll-item:hover{background:#f5f6f5}';
        $c .= '.dp-rub-coll-color{width:12px;height:12px;border-radius:50%;flex-shrink:0}';
        $c .= '.dp-rub-coll-name{flex:1;font-size:14px;font-weight:500;color:#1e1e1e;text-align:left}';
        $c .= '.dp-rub-coll-check{width:20px;font-size:14px;font-weight:700;color:#2d6a4f;text-align:center}';
        $c .= '.dp-rub-coll-item.is-checked{background:rgba(45,106,79,0.04)}.dp-rub-coll-item.is-checked .dp-rub-coll-name{font-weight:600;color:#2d6a4f}';
        $c .= '.dp-rub-loading,.dp-rub-empty{padding:20px;text-align:center;font-size:13px;color:#999}';

        /* ── Új gyűjtemény sor ── */
        $c .= '.dp-rub-coll-new{display:flex;gap:8px;padding:12px 16px;border-top:1px solid #f0f1f3}';
        $c .= '.dp-rub-input{flex:1;padding:8px 14px!important;border:1.5px solid #e2e6e4!important;border-radius:10px!important;font-size:13px!important;outline:none!important;box-shadow:none!important;-webkit-appearance:none!important}.dp-rub-input:focus{border-color:#2d6a4f!important;box-shadow:0 0 0 3px rgba(45,106,79,0.12)!important}';
        $c .= '.dp-rub-new-btn{width:36px;height:36px;display:flex!important;align-items:center!important;justify-content:center!important;border:1.5px solid #2d6a4f!important;border-radius:10px!important;background:#2d6a4f!important;color:#fff!important;font-size:18px!important;cursor:pointer;transition:all .2s ease;outline:none!important;box-shadow:none!important;-webkit-appearance:none!important;padding:0!important;flex-shrink:0}.dp-rub-new-btn:hover{background:#245a42!important}';

        /* ── Jegyzet panel ── */
        $c .= '.dp-rub-textarea{display:block;width:100%;min-height:120px;padding:16px 20px!important;border:none!important;border-bottom:1px solid #f0f1f3;font-size:14px!important;font-family:inherit!important;color:#1e1e1e!important;resize:vertical;outline:none!important;box-shadow:none!important;-webkit-appearance:none!important;line-height:1.6!important;background:#fff!important}.dp-rub-textarea:focus{background:#fafffe!important}.dp-rub-textarea::placeholder{color:#bbb!important}';
        $c .= '.dp-rub-note-footer{display:flex;align-items:center;justify-content:space-between;padding:12px 20px}';
        $c .= '.dp-rub-note-counter{font-size:12px;color:#bbb;font-weight:500}';

        /* ── Responsive ── */
        $c .= '@media(max-width:600px){.dp-recipe-user-bar{bottom:16px;padding:6px 10px}.dp-rub-btn{padding:8px 14px!important;font-size:12px!important}.dp-rub-btn span{display:none}.dp-rub-dropdown,.dp-rub-note-panel{width:calc(100vw - 24px);bottom:70px}}';
    }

    /* ── RECEPT KERESŐ A GYŰJTEMÉNYEN BELÜL ── */
    $c .= '.dp-coll-search-wrap{padding:16px 24px;border-bottom:1px solid #f0f1f3;position:relative}';
    $c .= '.dp-coll-search-bar{display:flex;align-items:center;gap:10px;padding:10px 16px;background:#f5f6f5;border:1.5px solid #e2e6e4;border-radius:12px;transition:border-color .2s ease,box-shadow .2s ease}.dp-coll-search-bar:focus-within{border-color:#2d6a4f;box-shadow:0 0 0 3px rgba(45,106,79,0.12);background:#fff}';
    $c .= '.dp-coll-search-bar svg{flex-shrink:0}';
    $c .= 'input.dp-coll-search-input{flex:1;border:none!important;background:none!important;font-size:14px!important;font-weight:500!important;color:#1e1e1e!important;outline:none!important;box-shadow:none!important;padding:0!important;margin:0!important;-webkit-appearance:none!important;min-width:0}input.dp-coll-search-input::placeholder{color:#999!important}';

    /* Keresési eredmények dropdown */
    $c .= '.dp-coll-search-results{position:absolute;left:24px;right:24px;top:100%;background:#fff;border:1px solid #e2e6e4;border-radius:12px;box-shadow:0 12px 40px rgba(0,0,0,0.12);max-height:300px;overflow-y:auto;z-index:100}';
    $c .= '.dp-coll-search-loading,.dp-coll-search-empty{padding:20px;text-align:center;font-size:13px;color:#999}';

    /* Keresési eredmény sor */
    $c .= '.dp-coll-sr-item{display:flex!important;align-items:center!important;gap:12px;padding:10px 16px;border-bottom:1px solid #f5f6f5;transition:background .15s ease}.dp-coll-sr-item:last-child{border-bottom:none}.dp-coll-sr-item:hover{background:#f9faf9}';
    $c .= '.dp-coll-sr-item.is-in{background:rgba(45,106,79,0.04)}';
    $c .= '.dp-coll-sr-thumb{width:40px;height:40px;border-radius:8px;object-fit:cover;flex-shrink:0}';
    $c .= '.dp-coll-sr-nothumb{width:40px;height:40px;border-radius:8px;background:#f0f2f1;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}';
    $c .= '.dp-coll-sr-title{flex:1;font-size:14px;font-weight:600;color:#1e1e1e;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}';
    $c .= 'button.dp-coll-sr-add{padding:6px 14px!important;border:1.5px solid #e2e6e4!important;border-radius:8px!important;background:#fff!important;color:#666!important;font-size:12px!important;font-weight:600!important;cursor:pointer;transition:all .2s ease;white-space:nowrap;outline:none!important;box-shadow:none!important;-webkit-appearance:none!important}button.dp-coll-sr-add:hover{border-color:#2d6a4f!important;color:#2d6a4f!important}';
    $c .= 'button.dp-coll-sr-add.is-in{border-color:#2d6a4f!important;color:#2d6a4f!important;background:rgba(45,106,79,0.06)!important}';

    /* Gyűjtemény detail recept eltávolítás gomb */
    $c .= 'button.dp-coll-remove-btn{display:inline-flex!important;align-items:center!important;gap:4px;padding:4px 12px!important;border:1px solid rgba(220,38,38,0.2)!important;border-radius:6px!important;background:transparent!important;color:#dc2626!important;font-size:11px!important;font-weight:600!important;cursor:pointer;transition:all .2s ease;margin-top:6px;outline:none!important;box-shadow:none!important;-webkit-appearance:none!important}button.dp-coll-remove-btn:hover{background:rgba(220,38,38,0.06)!important;border-color:#dc2626!important}';

    if ( ! empty( $c ) ) {
        echo '<style id="dp-profil-css-v5-1">' . $c . '</style>';
    }
}, 22 );
