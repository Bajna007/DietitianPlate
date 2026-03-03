<?php

/**
 * 39 – Céljaim: CSS + inline JS
 */
/**
 * 39 – Céljaim: CSS + inline JS
 */
/**
 * 39 – Céljaim: CSS + inline JS
 * v23 FINAL – PDF FIX: iframe srcdoc render (A4 px), html2pdf().from(iframe.body)
 * - Megszünteti a balról vágást / elcsúszást
 * - Nem függ a “sandbox div” pozicionálásától
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'dp_user_has_bmr' ) ) {
    function dp_user_has_bmr( $uid = null ) {
        if ( ! $uid ) $uid = get_current_user_id();
        if ( ! $uid ) return false;
        $b = get_user_meta( $uid, 'dp_bmr_profile', true );
        return ! empty( $b ) && ! empty( $b['tdee'] );
    }
}

/* 39 önállóan is betölti a html2pdf-et (ha a 38 külön futna, akkor is legyen biztos) */
add_action( 'wp_enqueue_scripts', function() {
    if ( ! is_page( 'celjaim' ) && ! is_page_template( 'page-celjaim.php' ) ) return;
    if ( ! is_user_logged_in() ) return;

    if ( ! wp_script_is( 'html2pdf', 'enqueued' ) && ! wp_script_is( 'html2pdf', 'done' ) ) {
        wp_enqueue_script(
            'html2pdf',
            'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.2/html2pdf.bundle.min.js',
            array(),
            '0.10.2',
            true
        );
    }
}, 19 );

add_action( 'wp_head', function() {
    if ( ! is_page( 'celjaim' ) && ! is_page_template( 'page-celjaim.php' ) ) return;
    ?>
<style id="dp-celjaim-css">
:root{--dg-accent:#2d6a4f;--dg-accent-dark:#245a42;--dg-accent-light:rgba(45,106,79,0.08);--dg-bg:#f0f2f1;--dg-card:#fff;--dg-border:#e2e6e4;--dg-text:#1a1d1b;--dg-text-muted:#7c8a83;--dg-radius:16px;--dg-radius-sm:10px;--dg-shadow:0 2px 12px rgba(0,0,0,0.04),0 8px 32px rgba(0,0,0,0.03);--dg-transition:0.25s cubic-bezier(0.4,0,0.2,1);--dg-prot:#ef4444;--dg-fat:#f59e0b;--dg-carb:#3b82f6;--dg-prot-soft:rgba(239,68,68,0.08);--dg-fat-soft:rgba(245,158,11,0.08);--dg-carb-soft:rgba(59,130,246,0.08)}
.cj-page,.cj-page *{outline:none!important;outline-color:transparent!important}
.cj-page button,.cj-page button:hover,.cj-page button:focus,.cj-page input,.cj-page input:hover,.cj-page input:focus,.cj-page select,.cj-page select:focus,.cj-page a,.cj-page a:hover,.cj-page a:focus{outline:none!important;box-shadow:none!important;-webkit-tap-highlight-color:transparent!important}
.cj-page{max-width:100%;padding:0 0 80px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:var(--dg-text);line-height:1.6;background:var(--dg-bg)}.cj-page *{box-sizing:border-box}
.cj-hero{background:#1a1d1b;background-image:radial-gradient(ellipse at 20% 50%,rgba(45,106,79,.12) 0%,transparent 55%),radial-gradient(ellipse at 80% 50%,rgba(45,106,79,.06) 0%,transparent 50%);padding:44px 24px 36px;text-align:center;position:relative}
.cj-hero::after{content:'';position:absolute;bottom:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,rgba(45,106,79,.4),transparent)}
.cj-hero-inner{max-width:680px;margin:0 auto}.cj-hero-title{font-size:1.8rem!important;font-weight:800!important;color:#fff!important;margin:0 0 6px!important;display:flex;align-items:center;justify-content:center;gap:10px}.cj-hero-title svg{color:rgba(45,106,79,.7)}.cj-hero-sub{font-size:.92rem;color:rgba(255,255,255,.45);margin:0}
.cj-gate{max-width:600px;margin:32px auto 0;padding:0 20px}.cj-gate-inner{background:var(--dg-card);border-radius:var(--dg-radius);box-shadow:var(--dg-shadow);border:1px solid var(--dg-border);padding:40px 32px;text-align:center}
.cj-gate-icon{margin-bottom:16px}.cj-gate-title{font-size:1.2rem;font-weight:800;margin:0 0 8px}.cj-gate-subtitle{font-size:.88rem;color:var(--dg-text-muted);margin:0 0 24px}
.cj-gate-features{display:grid;grid-template-columns:1fr 1fr;gap:12px;max-width:400px;margin:0 auto 24px;text-align:left}.cj-gate-feature{display:flex;align-items:flex-start;gap:10px;padding:10px 12px;background:var(--dg-bg);border-radius:var(--dg-radius-sm)}.cj-gate-feat-icon{font-size:1.2rem;flex-shrink:0}.cj-gate-feature strong{display:block;font-size:.78rem;font-weight:700}.cj-gate-feature span{font-size:.7rem;color:var(--dg-text-muted)}
.cj-gate-cta{display:flex;flex-direction:column;gap:8px;align-items:center}
.cj-gate-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:14px 32px;border-radius:12px;font-size:.88rem;font-weight:700;border:none!important;cursor:pointer;transition:all var(--dg-transition);outline:none!important;text-decoration:none!important;font-family:inherit}
.cj-gate-btn--primary{background:linear-gradient(135deg,#2d6a4f,#40916c)!important;color:#fff!important}.cj-gate-btn--primary:hover{background:linear-gradient(135deg,#245a42,#358a60)!important;transform:translateY(-1px)}
.cj-gate-btn--secondary{background:transparent!important;color:var(--dg-accent)!important;border:2px solid var(--dg-border)!important;padding:12px 28px}.cj-gate-btn--secondary:hover{border-color:var(--dg-accent)!important}
.cj-gate-steps{display:flex;flex-direction:column;gap:10px;max-width:380px;margin:20px auto 0;text-align:left}.cj-gate-step{display:flex;align-items:center;gap:12px;font-size:.82rem;color:var(--dg-text-muted)}.cj-gate-step-num{width:26px;height:26px;display:flex;align-items:center;justify-content:center;background:var(--dg-accent);color:#fff;border-radius:50%;font-size:.72rem;font-weight:800;flex-shrink:0}
.cj-content{max-width:760px;margin:28px auto 0;padding:0 20px;display:flex;flex-direction:column;gap:20px}
.dg-card{background:var(--dg-card);border-radius:var(--dg-radius);box-shadow:var(--dg-shadow);border:1px solid var(--dg-border);padding:24px;animation:dgFadeUp .35s ease both}
.dg-card-title{display:flex;align-items:center;gap:8px;font-size:.88rem;font-weight:700;margin:0 0 18px;padding:0 0 14px;border-bottom:1px solid var(--dg-border)}.dg-card-title svg{color:var(--dg-accent)}
.cj-origin-card{border-left:4px solid var(--dg-accent)}.cj-origin-header{display:flex;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap}
.cj-origin-icon{font-size:1.4rem;width:40px;height:40px;display:flex;align-items:center;justify-content:center;background:var(--dg-accent-light);border-radius:10px;flex-shrink:0}
.cj-origin-title{font-size:.88rem;font-weight:700;margin:0}.cj-origin-date{font-size:.7rem;color:var(--dg-text-muted)}.cj-origin-title-wrap{display:flex;flex-direction:column;gap:1px;flex:1}
.cj-origin-grid{display:grid;gap:10px;margin-bottom:10px}
.cj-origin-stat{display:flex;flex-direction:column;align-items:center;gap:2px;padding:12px 6px;background:var(--dg-bg);border-radius:var(--dg-radius-sm);text-align:center}
.cj-origin-stat-value{font-size:1.05rem;font-weight:800;font-variant-numeric:tabular-nums}.cj-origin-stat--accent{color:var(--dg-accent)!important;font-size:1.2rem!important}
.cj-origin-stat-label{font-size:.58rem;font-weight:600;color:var(--dg-text-muted);text-transform:uppercase;letter-spacing:.03em;line-height:1.2}
.cj-origin-footer{display:flex;align-items:flex-start;gap:8px;padding:10px 14px;background:var(--dg-bg);border-radius:var(--dg-radius-sm);font-size:.74rem;color:var(--dg-text-muted);line-height:1.5;margin-top:8px}.cj-origin-footer svg{flex-shrink:0;margin-top:1px}.cj-origin-footer a{color:var(--dg-accent)!important;font-weight:600;text-decoration:none!important}.cj-origin-footer a:hover{text-decoration:underline!important}
.cj-manual-toggle{display:flex;align-items:center;gap:8px;cursor:pointer;margin-left:auto;flex-shrink:0;font-size:.75rem;font-weight:600;color:var(--dg-text-muted);user-select:none}.cj-manual-toggle input{display:none}.cj-manual-slider{width:36px;height:20px;background:var(--dg-border);border-radius:999px;position:relative;transition:background var(--dg-transition);flex-shrink:0}.cj-manual-slider::after{content:'';position:absolute;top:2px;left:2px;width:16px;height:16px;background:#fff;border-radius:50%;transition:transform var(--dg-transition);box-shadow:0 1px 3px rgba(0,0,0,.15)}.cj-manual-toggle input:checked+.cj-manual-slider{background:#f59e0b}.cj-manual-toggle input:checked+.cj-manual-slider::after{transform:translateX(16px)}
.cj-manual-fields{margin-top:16px;padding-top:16px;border-top:1px dashed var(--dg-border)}
select.dg-input{padding:10px 12px;cursor:pointer;appearance:auto;-webkit-appearance:auto;-moz-appearance:auto}
.dg-type-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:20px}
button.dg-type-card{display:flex;flex-direction:column;align-items:center;gap:6px;padding:16px 10px;border:2px solid var(--dg-border)!important;border-radius:var(--dg-radius-sm);background:var(--dg-card)!important;cursor:pointer;transition:all var(--dg-transition);outline:none!important;box-shadow:none!important;font-family:inherit;color:var(--dg-text)!important}
button.dg-type-card:hover{border-color:var(--gt-color,var(--dg-accent))!important;transform:translateY(-2px)}
button.dg-type-card.is-active{border-color:var(--gt-color,var(--dg-accent))!important;background:color-mix(in srgb,var(--gt-color,var(--dg-accent)) 6%,white)!important;box-shadow:0 0 0 3px color-mix(in srgb,var(--gt-color,var(--dg-accent)) 12%,transparent)!important}
.dg-type-icon{font-size:1.4rem}.dg-type-label{font-size:.78rem;font-weight:700}
.dg-row{display:grid;gap:14px}.dg-field{display:flex;flex-direction:column;gap:5px}.dg-field-label{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--dg-text-muted)}.dg-field-wrap{display:flex;align-items:center;gap:6px}
.dg-input{flex:1;padding:10px 14px;border:2px solid var(--dg-border)!important;border-radius:var(--dg-radius-sm);font-size:16px;font-weight:600;color:var(--dg-text)!important;background:var(--dg-card)!important;transition:border-color var(--dg-transition);outline:none!important;box-shadow:none!important;font-family:inherit;-moz-appearance:textfield;-webkit-appearance:none}.dg-input:focus{border-color:var(--dg-accent)!important}.dg-input::-webkit-outer-spin-button,.dg-input::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}
.dg-input--big{font-size:18px;font-weight:800;padding:12px 16px}.dg-input-unit{font-size:.78rem;font-weight:700;color:var(--dg-text-muted);flex-shrink:0}
.dg-input--locked{background:var(--dg-accent-light)!important;color:var(--dg-accent)!important;border-color:var(--dg-accent)!important;opacity:1!important;font-weight:800!important;cursor:default!important;pointer-events:none!important}
.dg-info-box{display:flex;align-items:flex-start;gap:10px;padding:14px 16px;border-radius:var(--dg-radius-sm);font-size:.78rem;line-height:1.6;margin-bottom:14px}.dg-info-box svg{flex-shrink:0;margin-top:2px}.dg-info-box strong{display:block;font-weight:700;margin-bottom:2px}.dg-info-box p{margin:0}.dg-info-box a{color:var(--dg-accent)!important;text-decoration:underline!important;font-weight:600}
.dg-info-box--blue{background:rgba(59,130,246,.06);border:1px solid rgba(59,130,246,.15)}.dg-info-box--blue svg{color:#3b82f6}.dg-info-box--blue strong{color:#1d4ed8}
.dg-info-box--orange{background:rgba(245,158,11,.06);border:1px solid rgba(245,158,11,.2)}.dg-info-box--orange svg{color:#d97706}.dg-info-box--orange strong{color:#b45309}
.dg-info-box--warn{background:rgba(239,68,68,.05);border:1px solid rgba(239,68,68,.2)}.dg-info-box--warn svg{color:#ef4444}.dg-info-box--warn strong{color:#dc2626}
.dg-info-box--subtle{background:var(--dg-bg);border:1px solid var(--dg-border)}.dg-info-box--subtle svg{color:var(--dg-text-muted)}.dg-info-box--subtle strong{color:var(--dg-text)}
.dg-macro-warn-banner{padding:12px 16px;border-radius:var(--dg-radius-sm);font-size:.82rem;font-weight:700;line-height:1.5;margin-bottom:14px;text-align:center;border:2px solid #ef4444;background:rgba(239,68,68,.08);color:#dc2626;animation:dgWarnPulse 1.5s ease-in-out infinite}
@keyframes dgWarnPulse{0%,100%{border-color:#ef4444;background:rgba(239,68,68,.08)}50%{border-color:#dc2626;background:rgba(239,68,68,.14)}}
.dg-cut-slider-section{margin-bottom:18px}.dg-cut-slider-row{display:flex;align-items:center;gap:14px;margin:8px 0}.dg-cut-slider-val{font-size:1.1rem;font-weight:800;white-space:nowrap;min-width:80px;text-align:right}.dg-cut-slider-val small{font-size:.72rem;font-weight:600;color:var(--dg-text-muted);margin-left:2px}
.dg-slider{flex:1;height:6px;-webkit-appearance:none;appearance:none;border-radius:999px;outline:none!important;cursor:pointer;background:var(--dg-border);box-shadow:none!important}
.dg-slider::-webkit-slider-runnable-track{height:6px;border-radius:999px;background:linear-gradient(to right,var(--sl-color,var(--dg-accent)) 0%,var(--sl-color,var(--dg-accent)) var(--sl-pct,0%),var(--dg-border) var(--sl-pct,0%))}
.dg-slider::-webkit-slider-thumb{-webkit-appearance:none;width:20px;height:20px;border-radius:50%;background:#fff;border:3px solid var(--sl-color,var(--dg-accent));box-shadow:0 1px 4px rgba(0,0,0,.12)!important;cursor:pointer;margin-top:-7px}
.dg-slider::-moz-range-track{height:6px;border-radius:999px;background:var(--dg-border);border:none}.dg-slider::-moz-range-progress{height:6px;border-radius:999px 0 0 999px;background:var(--sl-color,var(--dg-accent))}.dg-slider::-moz-range-thumb{width:20px;height:20px;border-radius:50%;background:#fff;border:3px solid var(--sl-color,var(--dg-accent));box-shadow:0 1px 4px rgba(0,0,0,.12)!important;cursor:pointer}
.dg-slider--deficit{--sl-color:#22c55e}.dg-slider--surplus{--sl-color:#f59e0b}
.dg-cut-intake{padding:14px 18px;background:var(--dg-bg);border-radius:var(--dg-radius-sm);margin:14px 0;font-size:.85rem;line-height:1.6}.dg-cut-intake:empty{display:none}
.dg-cut-intake-title{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--dg-text-muted);margin-bottom:8px}
.dg-cut-intake-row{display:flex;justify-content:space-between;align-items:baseline;padding:6px 0;font-size:.82rem}.dg-cut-intake-row+.dg-cut-intake-row{border-top:1px solid var(--dg-border)}
.dg-cut-intake-label{color:var(--dg-text-muted)}.dg-cut-intake-val{font-weight:800;font-variant-numeric:tabular-nums}
.dg-cut-intake-val--accent{color:var(--dg-accent);font-size:1.05rem}
.dg-cut-intake-val--muted{color:var(--dg-text-muted);font-weight:600;font-size:.78rem}
.dg-cut-intake-val--deficit{color:#dc2626}
.dg-cut-intake-note{font-size:.7rem;color:var(--dg-text-muted);margin-top:8px;padding-top:8px;border-top:1px dashed var(--dg-border);line-height:1.5}
.dg-cut-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin:14px 0}.dg-cut-stats:empty{display:none}
.dg-cut-stat{display:flex;flex-direction:column;align-items:center;gap:2px;padding:14px 8px;background:var(--dg-bg);border-radius:var(--dg-radius-sm);text-align:center}.dg-cut-stat-val{font-size:1.15rem;font-weight:800;font-variant-numeric:tabular-nums}.dg-cut-stat-val--accent{color:var(--dg-accent)}.dg-cut-stat-label{font-size:.66rem;font-weight:600;color:var(--dg-text-muted);text-transform:uppercase;letter-spacing:.03em}
.dg-tempo{display:inline-block;padding:6px 16px;border-radius:8px;font-size:.78rem;font-weight:700;margin:4px 0 12px}.dg-tempo--green{background:rgba(34,197,94,.1);color:#16a34a}.dg-tempo--yellow{background:rgba(245,158,11,.1);color:#d97706}.dg-tempo--red{background:rgba(239,68,68,.08);color:#dc2626}
.dg-prog-table{width:100%;border-collapse:collapse;margin:16px 0}.dg-prog-table th{font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--dg-text-muted);padding:8px 10px;text-align:left;border-bottom:2px solid var(--dg-border);background:var(--dg-bg)}.dg-prog-table td{padding:10px;font-size:.82rem;border-bottom:1px solid var(--dg-border);font-variant-numeric:tabular-nums}.dg-prog-table tbody tr:nth-child(even){background:rgba(0,0,0,.015)}.dg-prog-table .dg-prog-blocked td{background:rgba(239,68,68,.04);color:#dc2626}
.dg-prog-bmi-badge{display:inline-block;padding:2px 8px;border-radius:6px;font-size:.68rem;font-weight:700}.dg-prog-bmi--ok{background:rgba(34,197,94,.1);color:#16a34a}.dg-prog-bmi--warn{background:rgba(245,158,11,.1);color:#d97706}.dg-prog-bmi--danger{background:rgba(239,68,68,.1);color:#dc2626}
.dg-expect-grid{display:grid;grid-template-columns:1fr;gap:12px;margin:8px 0}.dg-expect-card{padding:16px;background:var(--dg-bg);border-radius:var(--dg-radius-sm);border:1px solid var(--dg-border)}.dg-expect-card-title{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--dg-text-muted);margin-bottom:8px}.dg-expect-row{display:flex;justify-content:space-between;align-items:baseline;padding:4px 0;font-size:.8rem}.dg-expect-row+.dg-expect-row{border-top:1px solid var(--dg-border)}.dg-expect-val{font-weight:800;font-variant-numeric:tabular-nums}
.dg-macro-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid var(--dg-border)}
.dg-tb{font-size:.72rem;font-weight:700;padding:4px 12px;border-radius:999px}.dg-tb--ok{background:rgba(34,197,94,.1);color:#16a34a}.dg-tb--warn{background:rgba(239,68,68,.15);color:#dc2626;font-weight:800;animation:dgWarnPulse 1.5s ease-in-out infinite}
.dg-macro-mode{display:flex;gap:6px;margin-bottom:16px}
button.dg-macro-mode-btn{flex:1;padding:10px 14px;border:2px solid var(--dg-border)!important;border-radius:var(--dg-radius-sm);background:var(--dg-card)!important;font-size:.8rem;font-weight:700;color:var(--dg-text-muted)!important;cursor:pointer;transition:all var(--dg-transition);font-family:inherit;outline:none!important;box-shadow:none!important;text-align:center}button.dg-macro-mode-btn:hover{border-color:var(--dg-accent)!important;color:var(--dg-accent)!important}button.dg-macro-mode-btn.is-active{border-color:var(--dg-accent)!important;background:var(--dg-accent-light)!important;color:var(--dg-accent)!important}
.dg-macro-rec-notice{font-size:.76rem;color:var(--dg-text-muted);line-height:1.5;margin-bottom:14px;padding:10px 14px;background:var(--dg-bg);border-radius:var(--dg-radius-sm)}.dg-macro-rec-notice:empty{display:none}.dg-macro-rec-notice strong{color:var(--dg-text)}
.dg-macro-grid{display:flex;flex-direction:column;gap:12px;margin-bottom:14px}
.dg-mc{padding:14px 16px;border-radius:var(--dg-radius-sm);border:1px solid var(--dg-border)}.dg-mc--prot{border-left:4px solid var(--dg-prot);background:var(--dg-prot-soft)}.dg-mc--fat{border-left:4px solid var(--dg-fat);background:var(--dg-fat-soft)}.dg-mc--carb{border-left:4px solid var(--dg-carb);background:var(--dg-carb-soft)}
.dg-mc-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}.dg-mc-name{display:flex;align-items:center;gap:6px;font-size:.82rem;font-weight:700}.dg-mc-dot{width:8px;height:8px;border-radius:50%;display:inline-block}.dg-mc--prot .dg-mc-dot{background:var(--dg-prot)}.dg-mc--fat .dg-mc-dot{background:var(--dg-fat)}.dg-mc--carb .dg-mc-dot{background:var(--dg-carb)}.dg-mc-pct{font-size:.72rem;font-weight:700;color:var(--dg-text-muted)}
.dg-mc-body{display:flex;flex-direction:column;gap:8px}.dg-mc--prot .dg-slider{--sl-color:var(--dg-prot)}.dg-mc--fat .dg-slider{--sl-color:var(--dg-fat)}.dg-mc--carb .dg-slider{--sl-color:var(--dg-carb)}
.dg-mc-vals{display:flex;align-items:baseline;gap:16px;flex-wrap:wrap}.dg-mc-gram{font-size:1.1rem;font-weight:800;font-variant-numeric:tabular-nums}.dg-mc-gram small{font-size:.72rem;font-weight:600;color:var(--dg-text-muted);margin-left:1px}.dg-mc-perkg{font-size:.72rem;font-weight:600;color:var(--dg-text-muted)}
.dg-mc-custom-pkg{padding:4px 0}.dg-mc-custom-pkg .dg-input{font-size:14px;padding:6px 10px}
.dg-macro-bar{display:flex;height:10px;border-radius:999px;overflow:hidden;margin-bottom:12px}.dg-bar-seg{height:100%;transition:width .3s ease}.dg-bar--prot{background:var(--dg-prot)}.dg-bar--fat{background:var(--dg-fat)}.dg-bar--carb{background:var(--dg-carb)}
.dg-meals-top-center{display:flex;flex-direction:column;align-items:center;gap:10px;margin-bottom:18px;padding-bottom:14px;border-bottom:1px solid var(--dg-border)}.dg-stepper{display:flex;align-items:center;gap:12px}
button.dg-step-btn{width:36px;height:36px;border:2px solid var(--dg-border)!important;border-radius:10px;background:var(--dg-card)!important;font-size:1.3rem;font-weight:800;color:var(--dg-text)!important;cursor:pointer;display:flex;align-items:center;justify-content:center;outline:none!important;box-shadow:none!important;font-family:inherit;transition:all var(--dg-transition);line-height:1}button.dg-step-btn:hover{border-color:var(--dg-accent)!important;color:var(--dg-accent)!important}
.dg-step-num{font-size:1.2rem;font-weight:800;color:var(--dg-accent);min-width:24px;text-align:center}
.dg-meals-table{width:100%;border-collapse:collapse;margin-top:12px}.dg-meals-table th{font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--dg-text-muted);padding:6px;text-align:left;border-bottom:2px solid var(--dg-border);background:var(--dg-bg)}.dg-meals-table td{padding:8px 6px;font-size:.78rem;border-bottom:1px solid var(--dg-border);font-variant-numeric:tabular-nums}.dg-meals-table td:first-child{font-weight:700}.dg-meals-table tbody tr:nth-child(even){background:rgba(0,0,0,.018)}
.dg-meals-pct-input{width:54px;padding:4px 6px;border:1.5px solid var(--dg-border)!important;border-radius:6px;font-size:16px;font-weight:700;text-align:center;outline:none!important;box-shadow:none!important;font-family:inherit;-moz-appearance:textfield;-webkit-appearance:none;background:var(--dg-card)!important;color:var(--dg-text)!important}.dg-meals-pct-input:focus{border-color:var(--dg-accent)!important}.dg-meals-pct-input::-webkit-outer-spin-button,.dg-meals-pct-input::-webkit-inner-spin-button{-webkit-appearance:none}
.dg-meals-total{font-weight:800!important;color:var(--dg-accent)!important}.dg-meals-total td{border-bottom:none;border-top:2px solid var(--dg-border)}
.dg-actions{display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:18px}
button.dg-save--audit{display:inline-flex;align-items:center;gap:8px;padding:14px 32px;border:none!important;border-radius:14px;background:linear-gradient(135deg,#2d6a4f,#40916c)!important;color:#fff!important;font-size:.88rem;font-weight:700;cursor:pointer;box-shadow:0 2px 12px rgba(45,106,79,.25)!important;transition:all var(--dg-transition);outline:none!important;font-family:inherit}button.dg-save--audit:hover{background:linear-gradient(135deg,#245a42,#358a60)!important;transform:translateY(-1px)}button.dg-save--audit:disabled{opacity:.5;cursor:not-allowed;transform:none!important}button.dg-save--audit svg{color:#fff!important}
button.dg-save-secondary{display:inline-flex;align-items:center;gap:8px;padding:13px 28px;border:2px solid var(--dg-border)!important;border-radius:14px;background:var(--dg-card)!important;color:var(--dg-text-muted)!important;font-size:.84rem;font-weight:600;cursor:pointer;transition:all var(--dg-transition);outline:none!important;box-shadow:none!important;font-family:inherit}button.dg-save-secondary:hover{border-color:var(--dg-accent)!important;color:var(--dg-accent)!important}button.dg-save-secondary:disabled{opacity:.5;cursor:not-allowed}
.dg-saved-widget{margin-top:4px}.dg-saved-current{padding:16px;background:var(--dg-bg);border-radius:var(--dg-radius-sm);border:1px solid var(--dg-border)}.dg-saved-badge{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--dg-accent);margin-bottom:10px}.dg-saved-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:8px}.dg-saved-item{display:flex;flex-direction:column;gap:1px;text-align:center}.dg-saved-val{font-size:.88rem;font-weight:800}.dg-saved-label{font-size:.62rem;font-weight:600;color:var(--dg-text-muted);text-transform:uppercase}.dg-saved-date{font-size:.68rem;color:var(--dg-text-muted);text-align:right}.dg-saved-empty{padding:20px;text-align:center;font-size:.82rem;color:var(--dg-text-muted)}
.dp-profil-setting-msg{font-size:.78rem;min-height:1.2em}.dp-profil-msg--success{color:#16a34a!important;font-weight:600}.dp-profil-msg--error{color:#dc2626!important;font-weight:600}
@keyframes dgFadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
@media(max-width:768px){.cj-hero{padding:32px 16px 28px}.cj-hero-title{font-size:1.4rem!important}.cj-content{padding:0 12px}.dg-card{padding:18px}.cj-origin-grid{grid-template-columns:repeat(2,1fr)!important}.dg-row{grid-template-columns:1fr!important}.dg-saved-grid{grid-template-columns:repeat(2,1fr)}.dg-macro-mode{flex-direction:column}.cj-gate-features{grid-template-columns:1fr}.dg-actions{flex-direction:column}.dg-expect-grid{grid-template-columns:1fr}}
@media(max-width:480px){.cj-origin-stat{padding:10px 4px}.cj-origin-stat-value{font-size:.88rem}.dg-type-grid{gap:6px}.dg-mc{padding:10px 12px}.cj-gate-inner{padding:28px 18px}}
</style>
    <?php
}, 20 );

add_action( 'wp_footer', function() {
    if ( ! is_page( 'celjaim' ) && ! is_page_template( 'page-celjaim.php' ) ) return;

    if ( ! is_user_logged_in() ) {
        ?><script>(function(){var r=document.getElementById('cj-gate-register'),l=document.getElementById('cj-gate-login');if(r)r.addEventListener('click',function(){if(typeof window.openAuth==='function')window.openAuth('register')});if(l)l.addEventListener('click',function(){if(typeof window.openAuth==='function')window.openAuth('login')})})();</script><?php
        return;
    }

    if ( ! function_exists( 'dp_user_has_bmr' ) || ! dp_user_has_bmr() ) return;

    $uid=get_current_user_id();
    $nonce=wp_create_nonce('dp_profil_nonce');
    $ajax=admin_url('admin-ajax.php');

    $bmr=get_user_meta($uid,'dp_bmr_profile',true);
    $tdee=$bmr?intval($bmr['tdee']):0;
    $bmrV=$bmr?intval($bmr['bmr']):0;
    $bw=$bmr?floatval($bmr['weight']):75;
    $ht=$bmr?floatval($bmr['height']):170;

    $act=$bmr?floatval($bmr['activity']):1.55;
    $ath=$bmr&&!empty($bmr['is_athlete'])?1:0;

    $raw_g=$bmr&&!empty($bmr['gender'])?$bmr['gender']:'male';
    if($raw_g==='ferfi')$gen='male';elseif($raw_g==='no')$gen='female';else $gen=$raw_g;

    $age=$bmr?intval($bmr['age']):30;
    $form=$bmr&&!empty($bmr['formula'])?$bmr['formula']:'mifflin';

    $goal=function_exists('dp_get_user_goal')?dp_get_user_goal($uid):null;
    $g_md=$goal&&is_array($goal->meal_distribution)?$goal->meal_distribution:null;
    $g_mc=$goal?intval($goal->meal_count):5;

    $uname=wp_get_current_user()->display_name;
    ?>
<script id="dp-celjaim-js">
(function(){
'use strict';

var AX=<?php echo wp_json_encode($ajax);?>,NK=<?php echo wp_json_encode($nonce);?>,UN=<?php echo wp_json_encode($uname);?>;
var _T=<?php echo $tdee;?>,_BMR=<?php echo $bmrV;?>,_W=<?php echo $bw;?>,_H=<?php echo $ht;?>;
var _ACT=<?php echo $act;?>,_ATH=<?php echo $ath;?>,_GEN=<?php echo wp_json_encode($gen);?>,_AGE=<?php echo $age;?>,_FORM=<?php echo wp_json_encode($form);?>;

var KCAL_PER_KG=7000,BMI_MIN_LIMIT=18.5;
var PROT_KCAL=4.1,FAT_KCAL=9.3,CARB_KCAL=4.1;
var A4_CONFIG={WIDTH_MM:210,HEIGHT_MM:297,MM_TO_PX:2.83465,get WIDTH_PX(){return Math.floor(this.WIDTH_MM*this.MM_TO_PX)},get HEIGHT_PX(){return Math.floor(this.HEIGHT_MM*this.MM_TO_PX)}};

var T=_T,BMR_V=_BMR,W=_W,H=_H,ACT=_ACT,ATH=_ATH,GEN=_GEN,AGE=_AGE,FORM=_FORM,AB,BMI;
var TRG={broca:false,underweight:false,athlete:false};

var FORMULAS={
mifflin:{name:'Mifflin-St Jeor (1990)',calc:function(g,w,h,a){var b=(10*w)+(6.25*h)-(5*a);return g==='male'?b+5:b-161}},
harris:{name:'Harris-Benedict (1919)',calc:function(g,w,h,a){return g==='male'?66.473+(13.7516*w)+(5.0033*h)-(6.755*a):655.0955+(9.5634*w)+(1.8496*h)-(4.6756*a)}},
roza:{name:'Roza & Shizgal (1984)',calc:function(g,w,h,a){return g==='male'?88.362+(13.397*w)+(4.799*h)-(5.677*a):447.593+(9.247*w)+(3.098*h)-(4.330*a)}},
manual_input:{name:'Manuális bevitel',calc:null}
};

var mN={'1.2':{pK:0.8,fP:30,s:'EFSA DRV (2017); MDOSZ Okostányér (2021)'},'1.375':{pK:1.0,fP:30,s:'EFSA DRV (2017)'},'1.55':{pK:1.2,fP:28,s:'<a href="https://pubmed.ncbi.nlm.nih.gov/26920240/" target="_blank">Thomas et al. 2016</a>'},'1.725':{pK:1.4,fP:27,s:'<a href="https://pubmed.ncbi.nlm.nih.gov/26920240/" target="_blank">Thomas et al. 2016</a>'},'1.9':{pK:1.6,fP:25,s:'<a href="https://pubmed.ncbi.nlm.nih.gov/28698222/" target="_blank">Morton et al. 2018</a>'}};
var mAth={'2.0':{pK:1.6,fP:25,s:'<a href="https://pubmed.ncbi.nlm.nih.gov/28698222/" target="_blank">ISSN / Jäger 2017</a>'},'2.3':{pK:1.8,fP:23,s:'<a href="https://pubmed.ncbi.nlm.nih.gov/28698222/" target="_blank">ISSN 2017</a>; <a href="https://pubmed.ncbi.nlm.nih.gov/31247944/" target="_blank">Iraki 2019</a>'},'2.5':{pK:2.0,fP:22,s:'<a href="https://pubmed.ncbi.nlm.nih.gov/28698222/" target="_blank">ISSN 2017</a>; <a href="https://pubmed.ncbi.nlm.nih.gov/26920240/" target="_blank">Thomas 2016</a>'}};
var ATH_PROT_CAP=2.4;
var gM={maintain:{p:15,f:30,c:55,s:'WHO 2023; EFSA DRV (2017); <a href="https://mdosz.hu/okostanyer/" target="_blank">MDOSZ Okostányér (2021)</a>'},bulk:{p:20,f:28,c:52,s:'<a href="https://pubmed.ncbi.nlm.nih.gov/31247944/" target="_blank">Iraki et al. 2019</a>'}};
var UW={p:20,f:30,c:50,s:'<a href="https://pubmed.ncbi.nlm.nih.gov/11234459/" target="_blank">WHO TRS 894</a>; <a href="https://pubmed.ncbi.nlm.nih.gov/27637832/" target="_blank">ESPEN 2017</a>; <a href="https://mdosz.hu/okostanyer/" target="_blank">MDOSZ</a>'};

function $(i){return document.getElementById(i)}
function $$(s){return document.querySelectorAll(s)}
function r1(v){return Math.round(v*10)/10}

function ax(a,d,c){
    var f=new FormData;
    f.append('action',a);
    f.append('nonce',NK);
    if(d)for(var k in d)if(d.hasOwnProperty(k))f.append(k,d[k]);
    fetch(AX,{method:'POST',body:f,credentials:'same-origin'}).then(function(r){return r.json()}).then(function(r){if(c)c(r)}).catch(function(){if(c)c({success:false,data:{message:'Hiba.'}})})
}

function ms2(el,t,ok){
    if(!el)return;
    el.textContent=t;
    el.className='dp-profil-setting-msg '+(ok?'dp-profil-msg--success':'dp-profil-msg--error');
    clearTimeout(el._t);
    el._t=setTimeout(function(){el.textContent='';el.className='dp-profil-setting-msg'},5e3)
}

function uSl(s){
    if(!s)return;
    var mn=parseFloat(s.min),mx=parseFloat(s.max),v=parseFloat(s.value);
    s.style.setProperty('--sl-pct',(mx>mn?((v-mn)/(mx-mn))*100:0)+'%')
}

function bmiCalc(w,h){return h>0?r1(w/Math.pow(h/100,2)):0}
function minW4B(){return H>0?r1(BMI_MIN_LIMIT*Math.pow(H/100,2)):30}

function bmiBdg(b){
    if(b<18.5)return'<span class="dg-prog-bmi-badge dg-prog-bmi--danger">'+b+' kg/m\u00B2</span>';
    if(b<25)return'<span class="dg-prog-bmi-badge dg-prog-bmi--ok">'+b+' kg/m\u00B2</span>';
    if(b<30)return'<span class="dg-prog-bmi-badge dg-prog-bmi--warn">'+b+' kg/m\u00B2</span>';
    return'<span class="dg-prog-bmi-badge dg-prog-bmi--danger">'+b+' kg/m\u00B2</span>'
}

function closestKey(map,val){var bk=null,bd=999;for(var k in map){var d=Math.abs(parseFloat(k)-val);if(d<bd){bd=d;bk=k}}return bk}
function minKcal(){return GEN==='female'?1200:1500}
function idealBroca(g,h){return g==='male'?(h-100)*0.9:(h-104)*0.85}
function adjustedBW(g,h,w){var id=idealBroca(g,h);return r1(id+0.25*(w-id))}
function updDerived(){BMI=bmiCalc(W,H);var isObese=BMI>=30&&!ATH;AB=isObese?adjustedBW(GEN,H,W):r1(W)}
function recalcFromManual(){var f=FORMULAS[FORM];if(!f)f=FORMULAS.mifflin;updDerived();var cW=(BMI>=30&&!ATH)?adjustedBW(GEN,H,W):W;var rawBmr=f.calc(GEN,cW,H,AGE);if(rawBmr<0)rawBmr=0;BMR_V=Math.round(rawBmr);T=Math.round(BMR_V*ACT)}
function isUW(){return BMI>0&&BMI<18.5}

function getRM(gt){
    if(isUW())return{pP:UW.p,fP:UW.f,cP:UW.c,pK:null,src:UW.s,mode:'fixPct',uw:true};
    if(ATH){
        var bk=closestKey(mAth,ACT),pk=mAth[bk].pK;
        if(gt==='cut')pk=Math.min(pk*1.2,ATH_PROT_CAP);
        return{pK:pk,fP:mAth[bk].fP,src:mAth[bk].s,mode:'gPerKg',bwRef:W,bwLabel:'valós testtömeg'}
    }
    if(gt==='maintain')return{pP:gM.maintain.p,fP:gM.maintain.f,cP:gM.maintain.c,pK:null,src:gM.maintain.s,mode:'fixPct'};
    if(gt==='bulk')return{pP:gM.bulk.p,fP:gM.bulk.f,cP:gM.bulk.c,pK:null,src:gM.bulk.s,mode:'fixPct'};
    var bk2=closestKey(mN,ACT);
    return{pK:Math.max(mN[bk2].pK,1.2),fP:mN[bk2].fP,src:mN[bk2].s,mode:'gPerKg',bwRef:AB,bwLabel:(AB!==r1(W)?'korrigált testtömeg':'testtömeg')}
}

var lastRecNotice='';
function buildRecNotice(rc,ek){
    var h='';
    if(rc.uw){
        h+='<strong>\u26A0\uFE0F Energia- és fehérjebő étrend (BMI < 18,5 kg/m\u00B2)</strong><br>';
        h+='<strong>1. Fehérje:</strong> '+rc.pP+' energia%<br>';
        h+='<strong>2. Zsír:</strong> '+rc.fP+' energia%<br>';
        h+='<strong>3. Szénhidrát:</strong> '+rc.cP+' energia% (maradék: 100% \u2212 fehérje% \u2212 zsír%)<br>';
        h+='Ajánlott: 35\u201340 kcal/ttkg ('+Math.round(35*W)+'\u2013'+Math.round(40*W)+' kcal).<br>';
        h+='<em>'+rc.src+'</em>'
    }else if(rc.mode==='gPerKg'){
        var pG=Math.round(rc.pK*rc.bwRef),pKc=pG*PROT_KCAL,fKc=ek*(rc.fP/100),cKc=Math.max(0,ek-pKc-fKc),tot=pKc+fKc+cKc;
        var pp=tot>0?r1(pKc/tot*100):20,fp=r1(rc.fP),cp=r1(100-pp-fp);
        h+='<strong>A makrók meghatározásának sorrendje:</strong><br>';
        h+='<strong>1. Fehérje (g/ttkg alapon):</strong> '+rc.pK+' g/ttkg \u00D7 '+rc.bwRef+' kg ('+rc.bwLabel+') = <strong>'+pG+' g</strong> ('+pp+' energia%) \u2014 <em>ez a kiindulás, a fehérje a testtömeg alapján kerül meghatározásra</em><br>';
        h+='<strong>2. Zsír:</strong> '+fp+' energia% (a fennmaradó energiából rögzített arány)<br>';
        h+='<strong>3. Szénhidrát:</strong> '+cp+' energia% (maradék: 100% \u2212 fehérje% \u2212 zsír%)<br>';
        if(S.type==='cut')h+='<em>Fogyáskor a csökkentés a szénhidrátból történik, a fehérje és zsír aránya megmarad.</em><br>';
        h+='<em>'+rc.src+'</em>'
    }else{
        h+='<strong>A makrók meghatározásának sorrendje:</strong><br>';
        h+='<strong>1. Fehérje:</strong> '+rc.pP+' energia%<br>';
        h+='<strong>2. Zsír:</strong> '+rc.fP+' energia%<br>';
        h+='<strong>3. Szénhidrát:</strong> '+rc.cP+' energia% (maradék: 100% \u2212 fehérje% \u2212 zsír%)<br>';
        h+='<em>'+rc.src+'</em>'
    }
    lastRecNotice=h;
    return h
}

var S={type:'maintain',df:0,sp:0,pP:15,fP:30,cP:55,mm:'recommended',mc:<?php echo (int)$g_mc;?>,md:<?php echo $g_md?wp_json_encode($g_md):'null';?>,man:false};
var customFixed={p:null,f:null,c:null};
var recPK=null,recBwRef=null;
var manualDirty=false;

var kEl=$('dg-kcal'),bwD=$('dg-bw-display');
function eK(){if(S.type==='cut')return Math.max(minKcal(),T-S.df);if(S.type==='bulk')return T+S.sp;return T}

(function(){$$('.dg-type-card').forEach(function(c){c.classList.remove('is-active');if(c.getAttribute('data-type')==='maintain')c.classList.add('is-active')})})();

var actLabels={'1.2':'Ülő (1.2)','1.375':'Enyhén aktív (1.375)','1.55':'Mérsékelten aktív (1.55)','1.725':'Nagyon aktív (1.725)','1.9':'Extra aktív (1.9)','2':'Sportoló – hobbi (2.0)','2.0':'Sportoló – hobbi (2.0)','2.3':'Sportoló – versenyző (2.3)','2.5':'Sportoló – élsportoló (2.5)'};

function uSt(){
    var e;
    e=$('cj-stat-tdee');if(e)e.textContent=T;
    e=$('cj-stat-bmr');if(e)e.textContent=BMR_V;
    e=$('cj-stat-bw');if(e)e.textContent=W;
    e=$('cj-stat-height');if(e)e.textContent=H;
    e=$('cj-stat-bmi');if(e)e.textContent=BMI;
    e=$('cj-stat-age');if(e)e.textContent=AGE;
    e=$('cj-stat-gender');if(e)e.textContent=GEN==='male'?'Férfi':'Nő';
    e=$('cj-stat-kcalpkg');if(e)e.textContent=W>0?r1(T/W):'-';
    e=$('cj-stat-formula');if(e){if(FORM==='manual_input'){e.textContent='Manuális bevitel'}else{var fObj=FORMULAS[FORM];e.textContent=fObj?fObj.name:FORM}}
    e=$('cj-stat-activity');if(e){if(FORM==='manual_input'){e.textContent='\u2014'}else{e.textContent=actLabels[String(ACT)]||('× '+ACT)}}
    e=$('cj-stat-athlete');if(e)e.textContent=ATH?'✅ Igen':'—';
    if(bwD)bwD.value=W;
    if(kEl)kEl.value=eK()
}

function updTriggers(){
    var bn=$('cj-broca-notice'),bb=$('cj-broca-notice-body'),uw=$('cj-underweight-notice'),uwb=$('cj-underweight-body'),ath=$('cj-athlete-notice'),athb=$('cj-athlete-notice-body');
    var isObese=BMI>=30&&!ATH;TRG.broca=isObese;
    if(isObese&&bn){
        var iw=idealBroca(GEN,H),abw=adjustedBW(GEN,H,W),sub=GEN==='male'?100:104,mul=GEN==='male'?'0,9':'0,85';
        bb.innerHTML='<strong>\u2696\uFE0F Korrigált testsúllyal számolva</strong><p>A BMI értéked <strong>'+BMI+' kg/m\u00B2</strong>, ami az elhízás kategóriájába esik (BMI \u2265 30 kg/m\u00B2).</p><p>Elhízásnál a valós testsúly túlbecsüli az alapanyagcserét. A kalkulátor <strong>korrigált testsúllyal</strong> ('+abw+' kg) számol:</p><div style="background:var(--dg-card);padding:12px 16px;border-radius:8px;margin:10px 0;font-size:.78rem;line-height:1.8"><strong>1. Ideális testsúly (Broca):</strong><br><code>('+H+' \u2212 '+sub+') \u00D7 '+mul+' = '+r1(iw)+' kg</code><br><br><strong>2. Korrigált testsúly (ABW):</strong><br><code>'+r1(iw)+' + 0,25 \u00D7 ('+W+' \u2212 '+r1(iw)+') = <strong>'+abw+' kg</strong></code></div><p>A többletsúly ~25%-a metabolikusan aktív szövet, ~75%-a zsír.</p><p style="margin-top:8px"><strong>\uD83D\uDD2C Pontosabb alternatíva: zsírmentes testtömeg (FFM)</strong></p><p>A zsírmentes testtömeg (Fat-Free Mass, FFM) a teljes testtömeg mínusz a zsírszövet – tehát az izom, csont, szervek és víz összessége. Ha ismered a testzsírszázalékodat (pl. DEXA, BIA, kaliper), az FFM-alapú számítás pontosabb. Enélkül a fenti Broca-korrekció a legjobb elérhető becslés.</p><p style="font-size:.68rem;color:var(--dg-text-muted);margin-top:6px">Források: <a href="https://pubmed.ncbi.nlm.nih.gov/16207687/" target="_blank">Ireton-Jones 2005</a>; <a href="https://pubmed.ncbi.nlm.nih.gov/15883556/" target="_blank">Müller et al. 2004</a></p>';
        bn.style.display=''
    }else if(bn){bn.style.display='none'}

    var isUWv=isUW();TRG.underweight=isUWv;
    if(isUWv&&uw){
        var mW=minW4B();
        uwb.innerHTML='<strong>\u26A0\uFE0F Alultápláltság kockázata – BMI: '+BMI+' kg/m\u00B2</strong><p>A BMI értéked '+BMI+' kg/m\u00B2, ami 18,5 kg/m\u00B2 alatt van. A WHO besorolása szerint ez az alultápláltság kategóriája.</p><p>A '+H+' cm magasságodhoz a legalacsonyabb egészséges testsúly <strong>'+mW+' kg</strong> (BMI = 18,5 kg/m\u00B2).</p><p style="margin-top:8px"><strong>Ajánlott táplálkozási irányelvek:</strong></p><ul style="margin:6px 0 6px 18px;padding:0;font-size:.76rem;line-height:1.7"><li><strong>Napi energia:</strong> 35–40 kcal/ttkg \u2192 <strong>'+Math.round(35*W)+'–'+Math.round(40*W)+' kcal/nap</strong></li><li><strong>Fehérje:</strong> 1,2–1,5 g/ttkg \u2192 <strong>'+r1(1.2*W)+'–'+r1(1.5*W)+' g/nap</strong></li><li><strong>Makró elosztás:</strong> 20% fehérje \u00B7 30% zsír \u00B7 50% szénhidrát</li></ul><p>A makrók automatikusan energia- és fehérjebő módra váltottak. Fordulj dietetikushoz vagy orvoshoz.</p><p style="font-size:.68rem;color:var(--dg-text-muted);margin-top:6px">Források: <a href="https://pubmed.ncbi.nlm.nih.gov/11234459/" target="_blank">WHO TRS 894 (2000)</a>; <a href="https://pubmed.ncbi.nlm.nih.gov/27637832/" target="_blank">ESPEN – Cederholm 2017</a>; <a href="https://mdosz.hu/okostanyer/" target="_blank">MDOSZ Okostányér (2021)</a></p>';
        uw.style.display=''
    }else if(uw){uw.style.display='none'}

    TRG.athlete=!!ATH;
    if(ATH&&ath){
        athb.innerHTML='<strong>\uD83C\uDFCB\uFE0F Sportoló mód – fehérje valós testtömeg ('+W+' kg) alapján</strong><p>ISSN: 1,4–2,0 g/ttkg/nap'+(S.type==='cut'?', fogyásnál max 2,4 g/ttkg':'')+'. Forrás: <a href="https://pubmed.ncbi.nlm.nih.gov/28698222/" target="_blank">Jäger et al. 2017</a>.</p>';
        ath.style.display=''
    }else if(ath){ath.style.display='none'}
}

var mChk=$('cj-manual-check'),mFld=$('cj-manual-fields'),mLbl=$('cj-manual-label');
var mBw=$('cj-m-bw'),mHt=$('cj-m-ht'),mAge=$('cj-m-age'),mGen=$('cj-m-gender'),mForm=$('cj-m-formula'),mAct=$('cj-m-act');

if(mChk)mChk.addEventListener('change',function(){
    S.man=this.checked;
    manualDirty=false;
    mFld.style.display=this.checked?'':'none';
    mLbl.textContent=this.checked?'✏️ Manuális':'🔒 Importált';
    W=_W;H=_H;AGE=_AGE;GEN=_GEN;ACT=_ACT;ATH=_ATH;FORM=_FORM;T=_T;BMR_V=_BMR;updDerived();
    uSt();updTriggers();rAll()
});

function onManual(){
    if(!S.man)return;
    manualDirty=true;
    W=parseFloat(mBw.value)||_W;
    H=parseFloat(mHt.value)||_H;
    AGE=parseInt(mAge.value)||_AGE;
    GEN=mGen.value==='female'?'female':'male';
    FORM=mForm.value||'mifflin';

    var manKcalFields=$('cj-manual-kcal-fields');
    var mKcalInp=$('cj-m-kcal');
    var mKcalPkgInp=$('cj-m-kcalpkg');

    if(FORM==='manual_input'){
        if(mAct)mAct.disabled=true;
        if(manKcalFields)manKcalFields.style.display='';
        ACT=1;ATH=0;BMR_V=0;
        var mk=parseFloat(mKcalInp?mKcalInp.value:0)||0;
        T=mk>0?Math.round(mk):_T;
        updDerived();
    }else{
        if(mAct)mAct.disabled=false;
        if(manKcalFields)manKcalFields.style.display='none';
        ACT=parseFloat(mAct.value)||1.55;
        ATH=ACT>=2.0?1:0;
        recalcFromManual();
    }

    uSt();updTriggers();rAll()
}

[mBw,mHt,mAge,mGen,mForm,mAct].forEach(function(el){
    if(el){
        el.addEventListener('input',onManual);
        el.addEventListener('change',onManual)
    }
});

(function(){
    var mKcalInp=$('cj-m-kcal'),mKcalPkgInp=$('cj-m-kcalpkg');
    if(mKcalInp){
        mKcalInp.addEventListener('input',function(){
            if(FORM!=='manual_input')return;
            var v=parseFloat(this.value)||0;
            T=Math.round(v);
            if(mKcalPkgInp&&W>0)mKcalPkgInp.value=(v/W).toFixed(1);
            uSt();updTriggers();rAll()
        });
    }
    if(mKcalPkgInp){
        mKcalPkgInp.addEventListener('input',function(){
            if(FORM!=='manual_input')return;
            var v=parseFloat(this.value)||0;
            var kcal=Math.round(v*W);
            T=kcal;
            if(mKcalInp)mKcalInp.value=kcal;
            uSt();updTriggers();rAll()
        });
    }
})();

$$('.dg-type-card').forEach(function(c){
    c.addEventListener('click',function(){
        $$('.dg-type-card').forEach(function(x){x.classList.remove('is-active')});
        c.classList.add('is-active');
        S.type=c.getAttribute('data-type');
        $('dg-cut-section').style.display=S.type==='cut'?'':'none';
        $('dg-bulk-section').style.display=S.type==='bulk'?'':'none';
        rAll()
    })
});

(function(){$('dg-cut-section').style.display='none';$('dg-bulk-section').style.display='none'})();

/* ═══ ③ Fogyás ═══ */
var dSl=$('dg-deficit-slider'),dVl=$('dg-deficit-value');
function updCut(){
    var mk=minKcal(),df=S.df,mx=Math.min(1500,T-mk);
    if(mx<0)mx=0;
    dSl.max=mx;
    if(df>mx){df=mx;S.df=mx;dSl.value=mx}
    dVl.textContent=df;
    uSl(dSl);
    dSl.style.setProperty('--sl-color',df<=500?'#22c55e':df<=750?'#f59e0b':'#ef4444');

    var ck=eK();
    if(kEl)kEl.value=ck;

    var hi=$('dg-cut-hint');
    if(df<=0){
        $('dg-cut-intake').innerHTML='';
        $('dg-cut-stats').innerHTML='';
        $('dg-cut-tempo').innerHTML='';
        $('dg-cut-warning').style.display='none';
        $('dg-prog-wrap').innerHTML='';
        if(hi)hi.style.display='';
        return
    }
    if(hi)hi.style.display='none';

    var defPct=r1((df/T)*100);
    var ckPkg=W>0?r1(ck/W):0;
    var tPkg=W>0?r1(T/W):0;
    var wk=r1((df*7)/KCAL_PER_KG),mkg=r1((df*30)/KCAL_PER_KG);
    var bwPct=W>0?r1((wk/W)*100):0;

    var ci='<div class="dg-cut-intake-title">\uD83D\uDCCA Kalória összesítés</div>';
    ci+='<div class="dg-cut-intake-row"><span class="dg-cut-intake-label">Napi kalóriaszükségleted (TDEE)</span><span class="dg-cut-intake-val">'+T+' kcal/nap</span></div>';
    ci+='<div class="dg-cut-intake-row"><span class="dg-cut-intake-label">Napi csökkentés</span><span class="dg-cut-intake-val dg-cut-intake-val--deficit">\u2212 '+df+' kcal/nap <small style="font-weight:400;color:var(--dg-text-muted)">(\u2212'+defPct+'%)</small></span></div>';
    ci+='<div class="dg-cut-intake-row"><span class="dg-cut-intake-label"><strong>Napi kalória célod (deficites)</strong></span><span class="dg-cut-intake-val dg-cut-intake-val--accent"><strong>'+ck+' kcal/nap</strong></span></div>';
    ci+='<div class="dg-cut-intake-row"><span class="dg-cut-intake-label">Számítás</span><span class="dg-cut-intake-val--muted">'+T+' \u2212 '+df+' = '+ck+' kcal</span></div>';
    ci+='<div class="dg-cut-intake-row"><span class="dg-cut-intake-label">kcal/testtömeg-kg (deficites)</span><span class="dg-cut-intake-val">'+ckPkg+' kcal/ttkg</span></div>';
    ci+='<div class="dg-cut-intake-row"><span class="dg-cut-intake-label">kcal/testtömeg-kg (szintentartó)</span><span class="dg-cut-intake-val--muted">'+tPkg+' kcal/ttkg</span></div>';
    ci+='<div class="dg-cut-intake-note">\uD83D\uDCA1 <strong>Mit jelent ez?</strong> A napi kalóriaszükségleted '+T+' kcal – ennyi energiát használ el a tested naponta. Ha ebből napi '+df+' kcal-t elveszel, a szervezeted a tartalékaiból (elsősorban zsírból) fedezi a hiányt. Ez átlagosan heti <strong>~'+wk+' kg</strong> testsúlyváltozásnak felel meg'+(bwPct>0?' (a testtömeged <strong>'+bwPct+'%-a</strong>/hét)':'')+'.</div>';
    $('dg-cut-intake').innerHTML=ci;

    $('dg-cut-stats').innerHTML='<div class="dg-cut-stat"><span class="dg-cut-stat-val">'+df+'</span><span class="dg-cut-stat-label">kcal/nap csökkentés</span></div><div class="dg-cut-stat"><span class="dg-cut-stat-val">'+wk+'</span><span class="dg-cut-stat-label">kg / hét (becsült)</span></div><div class="dg-cut-stat"><span class="dg-cut-stat-val dg-cut-stat-val--accent">'+mkg+'</span><span class="dg-cut-stat-label">kg / hónap (becsült)</span></div>';

    var tc,tt;
    if(wk<=0.5){tc='green';tt='\uD83D\uDFE2 Kíméletes tempó'}
    else if(wk<=1){tc='yellow';tt='\uD83D\uDFE1 Egészséges tempó'}
    else{tc='red';tt='\uD83D\uDD34 Gyors tempó – fokozott kockázat'}

    var tempoDetail='';
    if(bwPct>0)tempoDetail=' <small style="font-weight:400;color:var(--dg-text-muted)">(heti '+bwPct+'% testtömeg)</small>';
    $('dg-cut-tempo').innerHTML='<div class="dg-tempo dg-tempo--'+tc+'">'+tt+tempoDetail+'</div>';

    var wn=$('dg-cut-warning'),wt=$('dg-cut-warning-text');
    if(ck<=mk){
        wn.style.display='';
        wt.innerHTML='<strong>Napi minimum ('+(GEN==='female'?'nők: 1200':'férfiak: 1500')+' kcal) elérve.</strong><p>Ennél kevesebb hosszú távon tápanyaghiányt, izomvesztést és alultápláltságot okozhat. Fordulj dietetikushoz.</p>'
    }else if(df>=750){
        wn.style.display='';
        wt.innerHTML='<strong>Nagy csökkentés ('+df+' kcal/nap = \u2212'+defPct+'%).</strong><p>Ajánlott: heti 0,5–1,0% testtömeg fogyás (neked ez heti '+r1(W*0.005)+'–'+r1(W*0.01)+' kg).</p>'
    }else wn.style.display='none';

    bldProg(wk)
}

function bldProg(wk){
    var wr=$('dg-prog-wrap');
    if(!wr||wk<=0){if(wr)wr.innerHTML='';return}
    var mW=minW4B(),ms3=[1,3,6,12,24],mo=['jan.','feb.','márc.','ápr.','máj.','jún.','júl.','aug.','szept.','okt.','nov.','dec.'],now=new Date,stopped=false;
    var h='<table class="dg-prog-table"><thead><tr><th>Időpont</th><th>Becsült súly</th><th>Fogyás</th><th>BMI</th><th></th></tr></thead><tbody>';
    h+='<tr><td><strong>Ma</strong></td><td><strong>'+W+' kg</strong></td><td>–</td><td>'+bmiBdg(BMI)+'</td><td>Kiindulás</td></tr>';
    for(var i=0;i<ms3.length;i++){
        if(stopped)break;
        var m=ms3[i],days=m*30.44,lost=r1((wk/7)*days),proj=r1(W-lost),pBmi=bmiCalc(proj,H);
        var dt=new Date(now.getTime()+days*864e5),ds=dt.getFullYear()+'. '+mo[dt.getMonth()];
        if(pBmi<BMI_MIN_LIMIT){
            h+='<tr class="dg-prog-blocked"><td>'+m+' hó<br><small>'+ds+'</small></td><td colspan="4" style="text-align:center;padding:16px"><strong>\u26D4 Tovább fogyni nem egészséges.</strong><br>Becsült súly ('+proj+' kg) túl alacsony (BMI: '+pBmi+' kg/m\u00B2). Min. egészséges: <strong>'+mW+' kg</strong>.</td></tr>';
            stopped=true
        }else{
            h+='<tr><td>'+m+' hó<br><small>'+ds+'</small></td><td><strong>'+proj+' kg</strong></td><td>\u2212'+lost+' kg</td><td>'+bmiBdg(pBmi)+'</td><td>'+r1((lost/W)*100)+'%</td></tr>'
        }
    }
    h+='</tbody></table>';
    wr.innerHTML=h
}

if(dSl)dSl.addEventListener('input',function(){S.df=parseInt(this.value)||0;updCut();if(S.mm==='recommended')setRM();updMD();updMN()});

/* ═══ ③B Tömegnövelés ═══ */
var sSl=$('dg-surplus-slider'),sVl=$('dg-surplus-value');

function updBulk(){
    var sp=S.sp;
    sVl.textContent=sp;
    uSl(sSl);

    var maxOk=Math.round(T*0.2);
    sSl.style.setProperty('--sl-color',sp<=maxOk?'#22c55e':sp<=Math.round(T*0.3)?'#f59e0b':'#ef4444');

    var bk=eK();
    if(kEl)kEl.value=bk;

    if(sp<=0){
        $('dg-bulk-intake').innerHTML='';
        $('dg-bulk-warning').style.display='none';
        return
    }

    var spPct=r1((sp/T)*100);
    var bkPkg=W>0?r1(bk/W):0;

    var bi='<div class="dg-cut-intake-title">\uD83D\uDCCA Kalória összesítés</div>';
    bi+='<div class="dg-cut-intake-row"><span class="dg-cut-intake-label">Napi kalóriaszükségleted (TDEE)</span><span class="dg-cut-intake-val">'+T+' kcal/nap</span></div>';
    bi+='<div class="dg-cut-intake-row"><span class="dg-cut-intake-label">Napi többlet</span><span class="dg-cut-intake-val" style="color:#f59e0b">+ '+sp+' kcal/nap <small style="font-weight:400;color:var(--dg-text-muted)">(+'+spPct+'%)</small></span></div>';
    bi+='<div class="dg-cut-intake-row"><span class="dg-cut-intake-label"><strong>Napi kalória célod (többletes)</strong></span><span class="dg-cut-intake-val dg-cut-intake-val--accent"><strong>'+bk+' kcal/nap</strong></span></div>';
    bi+='<div class="dg-cut-intake-row"><span class="dg-cut-intake-label">Számítás</span><span class="dg-cut-intake-val--muted">'+T+' + '+sp+' = '+bk+' kcal</span></div>';
    bi+='<div class="dg-cut-intake-row"><span class="dg-cut-intake-label">kcal/testtömeg-kg</span><span class="dg-cut-intake-val">'+bkPkg+' kcal/ttkg</span></div>';
    $('dg-bulk-intake').innerHTML=bi;

    var wn=$('dg-bulk-warning'),wt=$('dg-bulk-warning-text');
    if(sp>maxOk){
        wn.style.display='';
        wt.innerHTML='<strong>Ennyi többlet nem gyorsítja az izomépítést.</strong><p>Elég: '+Math.round(T*0.1)+'–'+maxOk+' kcal (10–20%). A felesleg zsírként raktározódik.</p>'
    }else wn.style.display='none'
}

if(sSl)sSl.addEventListener('input',function(){S.sp=parseInt(this.value)||0;updBulk();if(S.mm==='recommended')setRM();updMD();updMN()});

function buildExpect(){
    var el=$('dg-bulk-expect');if(!el)return;
    var h='<div class="dg-info-box dg-info-box--subtle" style="margin-bottom:14px"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg><div><p>Az izomépítéshez minimális kalóriatöbblet is elegendő. A nagy felesleg nem gyorsítja az izmok növekedését – csak a zsírraktározást.</p></div></div>';
    h+='<div class="dg-expect-grid"><div class="dg-expect-card"><div class="dg-expect-card-title">\uD83D\uDCAA Reális zsírmentes tömeg (FFM) gyarapodás – ellenállásos edzéssel</div>';
    h+='<div style="font-size:.72rem;color:var(--dg-text-muted);margin-bottom:10px">Vegyes nemű, rendszeres ellenállásos (rezisztencia) edzéssel. A relatív ütem nemtől független.</div>';
    h+='<div class="dg-expect-row"><span>1. év (kezdő)</span><span class="dg-expect-val" style="color:#16a34a">~4\u20137 kg/év</span></div>';
    h+='<div class="dg-expect-row"><span>2. év</span><span class="dg-expect-val" style="color:#16a34a">~2\u20134 kg/év</span></div>';
    h+='<div class="dg-expect-row"><span>3. év</span><span class="dg-expect-val" style="color:#d97706">~1,5\u20133 kg/év</span></div>';
    h+='<div class="dg-expect-row"><span>5. év</span><span class="dg-expect-val" style="color:#d97706">~0,5\u20131,5 kg/év</span></div>';
    h+='<div class="dg-expect-row"><span>9+ év (tapasztalt)</span><span class="dg-expect-val" style="color:#dc2626">~0\u20130,5 kg/év</span></div>';
    h+='<div style="font-size:.68rem;color:var(--dg-text-muted);margin-top:10px;line-height:1.6"><a href="https://pubmed.ncbi.nlm.nih.gov/35291645/" target="_blank">Lim et al. 2022</a> – szisztematikus áttekintés \u00B7 <a href="https://pubmed.ncbi.nlm.nih.gov/31174557/" target="_blank">Schoenfeld & Grgic 2020</a> – narratív áttekintés \u00B7 <a href="https://pubmed.ncbi.nlm.nih.gov/32218059/" target="_blank">Roberts et al. 2020</a></div>';
    h+='</div></div>';
    el.innerHTML=h
}

/* ═══ ④ Makrók ═══ */
var pSl=$('dg-prot-sl'),fSl=$('dg-fat-sl'),cSl=$('dg-carb-sl');

function setRM(){
    var rc=getRM(S.type),ek=eK(),pp,fp,cp;
    if(rc.mode==='fixPct'){
        pp=rc.pP;fp=rc.fP;cp=rc.cP;
        recPK=null;recBwRef=null
    }else{
        recPK=rc.pK;recBwRef=rc.bwRef;
        var pG=Math.round(rc.pK*rc.bwRef),pKc=pG*PROT_KCAL,fKc=ek*(rc.fP/100),cKc=Math.max(0,ek-pKc-fKc),tot=pKc+fKc+cKc;
        pp=tot>0?Math.round(pKc/tot*100):20;fp=Math.round(rc.fP);cp=100-pp-fp
    }
    S.pP=pp;S.fP=fp;S.cP=cp;
    pSl.value=pp;fSl.value=fp;cSl.value=cp;
    $('dg-macro-rec-notice').innerHTML=buildRecNotice(rc,ek)
}

function recalcCustomFromFixed(){
    var ek=eK();if(ek<=0)return;
    if(customFixed.p!==null){var pKcF=customFixed.p*W*PROT_KCAL;S.pP=Math.round(pKcF/ek*10000)/100}
    if(customFixed.f!==null){var fKcF=customFixed.f*W*FAT_KCAL;S.fP=Math.round(fKcF/ek*10000)/100}
    if(customFixed.c!==null){var cKcF=customFixed.c*W*CARB_KCAL;S.cP=Math.round(cKcF/ek*10000)/100}
    var remainPct=100-(customFixed.p!==null?S.pP:0)-(customFixed.f!==null?S.fP:0)-(customFixed.c!==null?S.cP:0);
    if(customFixed.p===null)S.pP=Math.max(0,remainPct);
    else if(customFixed.f===null)S.fP=Math.max(0,remainPct);
    else if(customFixed.c===null)S.cP=Math.max(0,remainPct);
    pSl.value=Math.round(S.pP);fSl.value=Math.round(S.fP);cSl.value=Math.round(S.cP)
}

function updMD(){
    var ek=eK(),pp=S.pP,fp=S.fP,cp=S.cP;
    var pG,fG,cG;
    if(S.mm==='recommended'&&recPK!==null&&recBwRef!==null){
        pG=Math.round(recPK*recBwRef);
        var pKcR=pG*PROT_KCAL;
        fG=(ek*fp/100)/FAT_KCAL;
        cG=Math.max(0,(ek-pKcR-fG*FAT_KCAL)/CARB_KCAL)
    }else if(S.mm==='custom'){
        pG=customFixed.p!==null?customFixed.p*W:(ek*pp/100)/PROT_KCAL;
        fG=customFixed.f!==null?customFixed.f*W:(ek*fp/100)/FAT_KCAL;
        cG=customFixed.c!==null?customFixed.c*W:(ek*cp/100)/CARB_KCAL
    }else{
        pG=(ek*pp/100)/PROT_KCAL;
        fG=(ek*fp/100)/FAT_KCAL;
        cG=(ek*cp/100)/CARB_KCAL
    }

    $('dg-p-pct').textContent=pp.toFixed(2)+' energia%';
    $('dg-f-pct').textContent=fp.toFixed(2)+' energia%';
    $('dg-c-pct').textContent=cp.toFixed(2)+' energia%';

    $('dg-p-g').innerHTML=pG.toFixed(2)+'<small> g</small>';
    $('dg-f-g').innerHTML=fG.toFixed(2)+'<small> g</small>';
    $('dg-c-g').innerHTML=cG.toFixed(2)+'<small> g</small>';

    $('dg-p-pk').textContent=(S.mm==='recommended'&&recPK!==null)?recPK.toFixed(2)+' g/ttkg':(W>0?(pG/W).toFixed(2)+' g/ttkg':'');
    $('dg-f-pk').textContent=W>0?(fG/W).toFixed(2)+' g/ttkg':'';
    $('dg-c-pk').textContent=W>0?(cG/W).toFixed(2)+' g/ttkg':'';

    uSl(pSl);uSl(fSl);uSl(cSl);

    $('dg-bp').style.width=pp+'%';
    $('dg-bf').style.width=fp+'%';
    $('dg-bc').style.width=cp+'%';

    var total=pp+fp+cp,diff=Math.abs(total-100),tb=$('dg-tb'),mwb=$('dg-macro-warn-top');
    tb.textContent=total.toFixed(2)+' energia%';
    if(diff>0.1){
        tb.className='dg-tb dg-tb--warn';
        if(mwb){mwb.style.display='';mwb.innerHTML='\u26A0\uFE0F <strong>Makrók: '+total.toFixed(2)+' energia%</strong> – 100,00 kell! Eltérés: '+(total>100?'+':'')+((total-100).toFixed(2))}
    }else{
        tb.className='dg-tb dg-tb--ok';
        if(mwb)mwb.style.display='none'
    }

    var dis=S.mm==='recommended';
    pSl.disabled=dis;fSl.disabled=dis;cSl.disabled=dis;
    pSl.style.opacity=dis?.5:1;fSl.style.opacity=dis?.5:1;cSl.style.opacity=dis?.5:1;

    var ppkI=$('dg-p-pkg-input'),fpkI=$('dg-f-pkg-input'),cpkI=$('dg-c-pkg-input');
    if(ppkI&&document.activeElement!==ppkI&&customFixed.p===null)ppkI.value=W>0?(pG/W).toFixed(2):'';
    if(fpkI&&document.activeElement!==fpkI&&customFixed.f===null)fpkI.value=W>0?(fG/W).toFixed(2):'';
    if(cpkI&&document.activeElement!==cpkI&&customFixed.c===null)cpkI.value=W>0?(cG/W).toFixed(2):''
}

$$('.dg-macro-mode-btn').forEach(function(b){
    b.addEventListener('click',function(){
        $$('.dg-macro-mode-btn').forEach(function(x){x.classList.remove('is-active')});
        b.classList.add('is-active');
        S.mm=b.getAttribute('data-mode');
        customFixed={p:null,f:null,c:null};
        if(S.mm==='recommended'){setRM();$('dg-macro-rec-notice').style.display=''}else $('dg-macro-rec-notice').style.display='none';
        var pkgWraps=['dg-p-pkg-wrap','dg-f-pkg-wrap','dg-c-pkg-wrap'];
        pkgWraps.forEach(function(id){var el=$(id);if(el)el.style.display=S.mm==='custom'?'':'none'});
        updMD();updMN()
    })
});

[pSl,fSl,cSl].forEach(function(sl){
    if(!sl)return;
    sl.addEventListener('input',function(){
        if(S.mm==='recommended')return;
        customFixed={p:null,f:null,c:null};
        S.pP=parseInt(pSl.value)||0;
        S.fP=parseInt(fSl.value)||0;
        S.cP=parseInt(cSl.value)||0;
        updMD();updMN()
    })
});

(function(){
    ['p','f','c'].forEach(function(m){
        var inp=$('dg-'+m+'-pkg-input');
        if(!inp)return;
        inp.addEventListener('input',function(){
            if(S.mm!=='custom')return;
            var gpk=parseFloat(this.value)||0;
            var wasFixed=customFixed[m]!==null;
            var fixCount=(customFixed.p!==null?1:0)+(customFixed.f!==null?1:0)+(customFixed.c!==null?1:0);
            if(!wasFixed&&fixCount>=2&&gpk>0)return;
            customFixed[m]=gpk>0?gpk:null;
            recalcCustomFromFixed();
            updMD();updMN()
        });
    });
})();

/* ═══ ⑤ Étkezés ═══ */
var mealN=['Reggeli','Tízórai','Ebéd','Uzsonna','Vacsora','Utóvacsora'];

function dD(n){return{1:[100],2:[55,45],3:[35,40,25],4:[20,10,40,30],5:[20,10,35,10,25],6:[20,10,35,10,20,5]}[n]||[20,10,35,10,25]}

function buildMT(){
    var bd=$('dg-meals-body');if(!bd)return;
    var n=S.mc;
    if(!S.md||S.md.length!==n)S.md=dD(n);

    var h='<table class="dg-meals-table"><thead><tr><th>Étkezés</th><th>Energia%</th><th>kcal</th><th>Fehérje</th><th>Zsír</th><th>CH</th></tr></thead><tbody>';
    for(var i=0;i<n;i++){
        h+='<tr><td>'+(mealN[i]||(i+1)+'. étkezés')+'</td><td><input type="number" class="dg-meals-pct-input" id="dg-mp-'+i+'" data-idx="'+i+'" value="'+S.md[i]+'" min="0" max="100" step="0.01"> energia%</td><td id="dg-mk-'+i+'"></td><td id="dg-mpr-'+i+'"></td><td id="dg-mf-'+i+'"></td><td id="dg-mc2-'+i+'"></td></tr>'
    }
    h+='</tbody><tfoot><tr class="dg-meals-total"><td>Összesen</td><td id="dg-mt-pct"></td><td id="dg-mt-k"></td><td id="dg-mt-p"></td><td id="dg-mt-f"></td><td id="dg-mt-c"></td></tr></tfoot></table>';
    bd.innerHTML=h;

    for(var j=0;j<n;j++)(function(idx){
        var inp=$('dg-mp-'+idx);if(!inp)return;
        inp.addEventListener('input',function(){S.md[idx]=parseFloat(this.value)||0;updMN()});
        inp.addEventListener('change',function(){S.md[idx]=parseFloat(this.value)||0;updMN()})
    })(j);

    updMN()
}

function updMN(){
    var n=S.mc,ek=eK();
    var pG=(ek*S.pP/100)/PROT_KCAL,fG=(ek*S.fP/100)/FAT_KCAL,cG=(ek*S.cP/100)/CARB_KCAL;
    var tP=0,tK=0,tPr=0,tF=0,tC=0;

    for(var i=0;i<n;i++){
        var pc=S.md[i]||0,mk=ek*pc/100,mp=pG*pc/100,mf=fG*pc/100,mc=cG*pc/100;
        tP+=pc;tK+=mk;tPr+=mp;tF+=mf;tC+=mc;
        var e1=$('dg-mk-'+i),e2=$('dg-mpr-'+i),e3=$('dg-mf-'+i),e4=$('dg-mc2-'+i);
        if(e1)e1.textContent=mk.toFixed(2)+' kcal';
        if(e2)e2.textContent=mp.toFixed(2)+' g';
        if(e3)e3.textContent=mf.toFixed(2)+' g';
        if(e4)e4.textContent=mc.toFixed(2)+' g'
    }

    var a=$('dg-mt-pct'),b=$('dg-mt-k'),c=$('dg-mt-p'),d=$('dg-mt-f'),e=$('dg-mt-c');
    if(a)a.textContent=tP.toFixed(2)+' energia%';
    if(b)b.textContent=tK.toFixed(2)+' kcal';
    if(c)c.textContent=tPr.toFixed(2)+' g';
    if(d)d.textContent=tF.toFixed(2)+' g';
    if(e)e.textContent=tC.toFixed(2)+' g';

    var mwb=$('dg-meals-warn-top'),diff=Math.abs(tP-100);
    if(diff>0.1&&mwb){
        mwb.style.display='';
        mwb.innerHTML='\u26A0\uFE0F <strong>Étkezések: '+tP.toFixed(2)+' energia%</strong> – 100,00 kell! Eltérés: '+(tP>100?'+':'')+((tP-100).toFixed(2))
    }else if(mwb)mwb.style.display='none'
}

$('dg-mc-minus').addEventListener('click',function(){if(S.mc>1){S.mc--;S.md=null;$('dg-mc-num').textContent=S.mc;buildMT()}});
$('dg-mc-plus').addEventListener('click',function(){if(S.mc<6){S.mc++;S.md=null;$('dg-mc-num').textContent=S.mc;buildMT()}});

/* ═══ ⑥ Mentés + PDF ═══ */
function doSave(cb){
    var ek=eK(),total=S.pP+S.fP+S.cP;
    if(ek<800||ek>15000){if(cb)cb(false,'Kalória: 800\u201315000 kcal.');return}
    if(Math.abs(total-100)>0.1){if(cb)cb(false,'Makrók összege nem 100%.');return}
    ax('dp_save_goal',{goal_type:S.type,daily_kcal:ek,protein_pct:S.pP,fat_pct:S.fP,carb_pct:S.cP,meal_count:S.mc,meal_distribution:JSON.stringify(S.md),gender:GEN,age:AGE,height:H,current_weight:W,activity_level:ACT,is_athlete:ATH,formula:FORM,bmr_value:BMR_V,tdee_value:T},function(r){if(cb)cb(r.success,r.success?r.data.message:r.data.message,r.success?r.data.goal:null)})
}

$('dg-save').addEventListener('click',function(){
    var btn=this,m=$('dg-save-msg');
    btn.disabled=true;
    var o=btn.innerHTML;
    btn.textContent='Mentés...';
    doSave(function(ok,mg,g){
        btn.disabled=false;
        btn.innerHTML=o;
        ms2(m,(ok?'\u2713 ':'')+mg,ok);
        if(ok&&g)updSW(g)
    })
});

function updSW(g){
    var w=$('dg-saved-widget');if(!w||!g)return;
    var tl={cut:'Fogyás',maintain:'Szintentartás',bulk:'Tömegnövelés'};
    var now=new Date,ds=now.getFullYear()+'.'+String(now.getMonth()+1).padStart(2,'0')+'.'+String(now.getDate()).padStart(2,'0')+' '+String(now.getHours()).padStart(2,'0')+':'+String(now.getMinutes()).padStart(2,'0');
    w.innerHTML='<div class="dg-saved-current"><div class="dg-saved-badge">\u2705 Aktív terv</div><div class="dg-saved-grid"><div class="dg-saved-item"><span class="dg-saved-val">'+(tl[g.goal_type]||g.goal_type)+'</span><span class="dg-saved-label">Cél</span></div><div class="dg-saved-item"><span class="dg-saved-val">'+g.daily_kcal+'</span><span class="dg-saved-label">kcal/nap</span></div><div class="dg-saved-item"><span class="dg-saved-val">'+Math.round(g.protein_pct)+'/'+Math.round(g.fat_pct)+'/'+Math.round(g.carb_pct)+'</span><span class="dg-saved-label">F/Zs/CH</span></div><div class="dg-saved-item"><span class="dg-saved-val">'+g.meal_count+'</span><span class="dg-saved-label">Étkezés</span></div></div><div class="dg-saved-date">Mentve: '+ds+'</div></div>'
}

$('dg-pdf').addEventListener('click',function(){
    var btn=this,m=$('dg-save-msg');
    btn.disabled=true;
    btn.textContent='Mentés + PDF...';
    doSave(function(ok,mg,g){
        btn.disabled=false;
        btn.innerHTML='<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg> \uD83D\uDCC4 Teljes összegzés (PDF + mentés)';
        if(ok){ms2(m,'\u2713 Mentve + PDF...',true);if(g)updSW(g)}
        dpPdfConfirm()
    })
});

/* ═══════════════════════════════════════════════════
   PDF GENERÁLÁS – FINAL FIX (iframe srcdoc + A4 px)
   - Nem vág, nem csúszik, mindig középen lesz
   ═══════════════════════════════════════════════════ */

/* ═══════════════════════════════════════════════════
   PDF FIX – html2pdf override (tökéletes formázás)
   ═══════════════════════════════════════════════ */
(function(){
    var timer = setInterval(function(){
        if (typeof html2pdf === 'undefined') return;
        clearInterval(timer);

        var _orig = window.html2pdf;
        window.html2pdf = function() {
            var filename = 'terv.pdf';
            var fake = {
                set: function(opts) {
                    if (opts && opts.filename) filename = opts.filename;
                    return fake;
                },
                from: function(body) {
                    var fr = document.getElementById('dp-pdf-frame');
                    if (fr) { fr.style.width='595px'; fr.style.height='842px'; }

                    var inst = _orig().set({
                        margin: [10,10,10,10],
                        filename: filename,
                        image: { type:'jpeg', quality:0.95 },
                        html2canvas: {
                            scale: 1.5,
                            useCORS: true,
                            logging: false,
                            windowWidth: 595,
                            windowHeight: 842,
                            scrollX: 0,
                            scrollY: 0,
                            backgroundColor: '#ffffff',
                            foreignObjectRendering: true,
                            allowTaint: true
                        },
                        jsPDF: { unit:'mm', format:'a4', orientation:'portrait', compress:true },
                        pagebreak: {
                            mode: ['avoid-all','css','legacy'],
                            avoid: ['.dg-card','.cj-origin-card','.dg-info-box','.dg-cut-intake','.dg-macro-grid','table']
                        }
                    }).from(body);

                    return {
                        save: function() {
                            return inst.outputPdf('datauristring').then(function(uri){
                                var pf = document.createElement('iframe');
                                pf.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:1px;height:1px;opacity:0;pointer-events:none';
                                pf.src = uri;
                                document.body.appendChild(pf);
                                pf.onload = function() {
                                    try {
                                        pf.contentWindow.focus();
                                        pf.contentWindow.print();
                                    } catch(e) {
                                        var a = document.createElement('a');
                                        a.href = uri;
                                        a.download = filename;
                                        a.click();
                                    }
                                    setTimeout(function(){ document.body.removeChild(pf); }, 60000);
                                };
                            });
                        }
                    };
                }
            };
            return fake;
        };
    }, 100);
})();

/* ═══════════════════════════════════════════════════
   PDF MODAL – popup magyarázat a print előtt
   ═══════════════════════════════════════════════ */
function dpPdfConfirm(){
    var ov=document.createElement('div');
    ov.style.cssText='position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:999999;display:flex;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(3px)';
    ov.innerHTML='<div style="background:#fff;border-radius:16px;padding:28px 24px;max-width:360px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,0.3);font-family:-apple-system,BlinkMacSystemFont,sans-serif;text-align:center">'
    +'<div style="font-size:36px;margin-bottom:10px">📄</div>'
    +'<h3 style="margin:0 0 6px;font-size:17px;color:#1a1d1b">PDF mentése</h3>'
    +'<p style="margin:0 0 14px;font-size:13px;color:#666;line-height:1.6">Megnyílik a böngésző nyomtatás ablaka.<br>A PDF mentéséhez kövesd az alábbi lépéseket:</p>'
    +'<div style="background:#f0f7f4;border:1px solid #d4e7dd;border-radius:10px;padding:12px 14px;margin-bottom:18px;text-align:left">'
    +'<div style="font-size:12px;font-weight:700;color:#2d6a4f;margin-bottom:8px">🖨️ Chrome / Edge (PC):</div>'
    +'<div style="font-size:12px;color:#333;line-height:1.9;margin-bottom:10px">'
    +'1. <strong>Cél:</strong> mezőnél válaszd:<br>'
    +'&nbsp;&nbsp;&nbsp;→ <strong>"Mentés PDF-ként"</strong><br>'
    +'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(ne: Microsoft Print to PDF)<br>'
    +'2. Kattints a <strong>Mentés</strong> gombra'
    +'</div>'
    +'<div style="border-top:1px solid #d4e7dd;padding-top:8px;font-size:12px;color:#333;line-height:1.9">'
    +'📱 <strong>Android Chrome:</strong><br>'
    +'&nbsp;&nbsp;&nbsp;Nyomtató → <strong>PDF mentése</strong> → Letöltés<br>'
    +'🍎 <strong>iPhone / iPad:</strong><br>'
    +'&nbsp;&nbsp;&nbsp;Share gomb → <strong>Fájlokba mentés</strong>'
    +'</div></div>'
    +'<button id="dpok" style="width:100%;padding:13px;background:#2d6a4f;color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;margin-bottom:8px">Rendben, megnyitás →</button>'
    +'<button id="dpcancel" style="width:100%;padding:8px;background:none;color:#aaa;border:none;font-size:13px;cursor:pointer">Mégsem</button>'
    +'</div>';
    document.body.appendChild(ov);
    document.getElementById('dpcancel').onclick=function(){document.body.removeChild(ov);};
    document.getElementById('dpok').onclick=function(){document.body.removeChild(ov);genPDF();};
}

function genPDF(){
    var ek=eK(),pG=(ek*S.pP/100)/PROT_KCAL,fG=(ek*S.fP/100)/FAT_KCAL,cG=(ek*S.cP/100)/CARB_KCAL;
    var tl={cut:'Fogyás',maintain:'Szintentartás',bulk:'Tömegnövelés'};
    var fObj=FORMULAS[FORM],fName=(FORM==='manual_input')?'Manuális bevitel':(fObj?fObj.name:FORM);

    var css='<style>body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;padding:40px 50px;color:#1a1d1b;max-width:780px;margin:0 auto;font-size:13px;line-height:1.6}h1{font-size:20px;color:#2d6a4f;border-bottom:3px solid #2d6a4f;padding-bottom:10px;margin-bottom:6px}h2{font-size:14px;color:#2d6a4f;margin:24px 0 10px;border-bottom:1px solid #e2e6e4;padding-bottom:6px}.sub{font-size:11px;color:#666;margin:2px 0 20px}table{width:100%;border-collapse:collapse;margin:8px 0 16px}th{background:#f0f2f1;font-size:10px;text-transform:uppercase;letter-spacing:.05em;padding:6px 10px;text-align:left;border-bottom:2px solid #e2e6e4}td{padding:6px 10px;border-bottom:1px solid #e2e6e4}tr:nth-child(even){background:#fafafa}.grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin:12px 0}.box{padding:12px;background:#f0f2f1;border-radius:8px;text-align:center}.box-val{font-size:18px;font-weight:800}.box-lab{font-size:9px;text-transform:uppercase;color:#666;margin-top:2px}.note{font-size:11px;color:#555;background:#f8f8f8;border-left:3px solid #2d6a4f;padding:8px 14px;margin:8px 0;border-radius:0 6px 6px 0}.warn{font-size:11px;color:#b45309;background:rgba(245,158,11,.06);border-left:3px solid #f59e0b;padding:8px 14px;margin:8px 0}.danger{font-size:11px;color:#dc2626;background:rgba(239,68,68,.05);border-left:3px solid #ef4444;padding:8px 14px;margin:8px 0}.footer{margin-top:30px;padding-top:12px;border-top:2px solid #e2e6e4;font-size:10px;color:#999}a{color:#2d6a4f}hr{border:none;border-top:1px solid #e2e6e4;margin:20px 0}.rec{font-size:11px;background:#f8faf9;border:1px solid #e2e6e4;border-radius:6px;padding:10px 14px;margin:8px 0;line-height:1.7}.sc{margin:12px 0;padding:10px 14px;background:#fafafa;border-radius:6px;border:1px solid #e2e6e4}.sc-t{font-weight:800;font-size:11px;color:#2d6a4f;margin-bottom:6px}.sc-i{font-size:10px;line-height:1.8;color:#555}.st{display:inline-block;padding:1px 6px;border-radius:4px;font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin-right:4px}.st-p{background:rgba(59,130,246,.1);color:#1d4ed8}.st-s{background:rgba(245,158,11,.1);color:#b45309}.st-m{background:rgba(34,197,94,.1);color:#16a34a}.st-g{background:rgba(139,92,246,.1);color:#6d28d9}.st-r{background:rgba(239,68,68,.1);color:#dc2626}.st-n{background:rgba(107,114,128,.1);color:#374151}.detail-box{background:#f0f7f4;border:1px solid #d4e7dd;border-radius:8px;padding:12px 16px;margin:8px 0;font-size:11px;line-height:1.8}.detail-row{display:flex;justify-content:space-between;padding:3px 0;border-bottom:1px solid #e8ede9}.detail-row:last-child{border-bottom:none}.detail-label{color:#666}.detail-val{font-weight:700}.detail-val-accent{font-weight:800;color:#2d6a4f;font-size:13px}</style>';

    var p='';
    p+='<h1>\uD83D\uDCCA Táplálkozási terv \u2013 Teljes összegzés</h1>';
    p+='<p class="sub">'+UN+' \u00B7 '+new Date().toLocaleDateString('hu-HU')+' '+new Date().toLocaleTimeString('hu-HU',{hour:'2-digit',minute:'2-digit'})+'</p>';

    p+='<h2>\uD83D\uDCDD 1. Alapadatok</h2><table><tbody>';
    p+='<tr><td><strong>Nem</strong></td><td>'+(GEN==='male'?'Férfi':'Nő')+'</td></tr>';
    p+='<tr><td><strong>Kor</strong></td><td>'+AGE+' év</td></tr>';
    p+='<tr><td><strong>Magasság</strong></td><td>'+H+' cm</td></tr>';
    p+='<tr><td><strong>Testsúly</strong></td><td>'+W+' kg</td></tr>';
    p+='<tr><td><strong>BMI</strong></td><td>'+BMI+' kg/m\u00B2</td></tr>';
    p+='<tr><td><strong>Aktivitás</strong></td><td>'+(actLabels[String(ACT)]||('\u00D7'+ACT))+'</td></tr>';
    p+='<tr><td><strong>Sportoló</strong></td><td>'+(ATH?'Igen':'Nem')+'</td></tr>';
    p+='</tbody></table>';

    if(TRG.broca){
        var iw2=idealBroca(GEN,H),ab2=adjustedBW(GEN,H,W),sub2=GEN==='male'?100:104,mul2=GEN==='male'?'0,9':'0,85';
        p+='<div class="warn"><strong>\u2696\uFE0F Korrigált testsúllyal számolva (BMI: '+BMI+' kg/m\u00B2 \u2265 30)</strong><br><br>';
        p+='A BMI értéked '+BMI+' kg/m\u00B2, ami az elhízás kategóriájába esik. Elhízásnál a valós testsúly túlbecsüli az alapanyagcserét, ezért a kalkulátor korrigált testsúllyal számol.<br><br>';
        p+='<strong>1. Ideális testsúly (Broca):</strong><br>('+H+' \u2212 '+sub2+') \u00D7 '+mul2+' = '+r1(iw2)+' kg<br><br>';
        p+='<strong>2. Korrigált testsúly (ABW):</strong><br>'+r1(iw2)+' + 0,25 \u00D7 ('+W+' \u2212 '+r1(iw2)+') = <strong>'+ab2+' kg</strong><br><br>';
        p+='A többletsúly ~25%-a metabolikusan aktív szövet, ~75%-a zsírszövet.<br><br>';
        p+='<strong>Pontosabb alternatíva: zsírmentes testtömeg (FFM)</strong><br>';
        p+='Ha ismered a testzsírszázalékodat (pl. DEXA, BIA, kaliper), az FFM-alapú számítás pontosabb. Enélkül a Broca-korrekció a legjobb elérhető becslés.<br><br>';
        p+='<em>Források: Ireton-Jones 2005 (PMID: 16207687); Müller et al. 2004 (PMID: 15883556)</em></div>'
    }

    if(TRG.underweight){
        var mW2=minW4B();
        p+='<div class="danger"><strong>\u26A0\uFE0F Alultápláltság kockázata \u2013 BMI: '+BMI+' kg/m\u00B2</strong><br><br>';
        p+='A BMI értéked 18,5 kg/m\u00B2 alatt van (WHO: alultápláltság). Min. egészséges súly: <strong>'+mW2+' kg</strong>.<br><br>';
        p+='<strong>Ajánlott:</strong><br>';
        p+='\u2022 Napi energia: 35\u201340 kcal/ttkg \u2192 '+Math.round(35*W)+'\u2013'+Math.round(40*W)+' kcal/nap<br>';
        p+='\u2022 Fehérje: 1,2\u20131,5 g/ttkg \u2192 '+r1(1.2*W)+'\u2013'+r1(1.5*W)+' g/nap<br>';
        p+='\u2022 Elosztás: 20% fehérje \u00B7 30% zsír \u00B7 50% szénhidrát<br><br>';
        p+='<em>Források: WHO TRS 894 (PMID: 11234459); ESPEN \u2013 Cederholm 2017 (PMID: 27637832); MDOSZ Okostányér (2021)</em></div>'
    }

    if(TRG.athlete){
        p+='<div class="note"><strong>\uD83C\uDFCB\uFE0F Sportoló mód \u2013 fehérje valós testtömeg ('+W+' kg) alapján</strong><br><br>';
        p+='ISSN: 1,4\u20132,0 g/ttkg/nap'+(S.type==='cut'?', fogyásnál max 2,4 g/ttkg/nap':'')+'. Sportolóknál a fehérje a valós testtömeg alapján kerül kiszámításra.<br><br>';
        p+='<em>Forrás: Jäger et al. 2017 (PMID: 28698222)</em></div>'
    }

    p+='<h2>\uD83E\uDDEE 2. Kalóriaszükséglet</h2><table><tbody>';
    p+='<tr><td><strong>Képlet</strong></td><td>'+fName+'</td></tr>';
    if(FORM==='manual_input'){
        p+='<tr><td><strong>Napi kalóriabevitel (manuálisan megadott)</strong></td><td><strong>'+T+' kcal/nap</strong></td></tr>';
    }else{
        p+='<tr><td><strong>Alapanyagcsere (BMR)</strong></td><td>'+BMR_V+' kcal/nap</td></tr>';
        p+='<tr><td><strong>Aktivitási szorzó</strong></td><td>\u00D7 '+ACT+'</td></tr>';
        p+='<tr><td><strong>Napi kalóriaszükséglet (TDEE)</strong></td><td><strong>'+T+' kcal/nap</strong></td></tr>';
    }
    if(W>0)p+='<tr><td><strong>kcal/testtömeg-kg</strong></td><td>'+r1(T/W)+' kcal/ttkg</td></tr>';
    p+='</tbody></table>';

    p+='<h2>\uD83C\uDFAF 3. Cél</h2>';
    p+='<table><tbody><tr><td><strong>Típus</strong></td><td><strong>'+(tl[S.type]||S.type)+'</strong></td></tr></tbody></table>';

    if(S.type==='cut'&&S.df>0){
        var cutKcal=ek,defPctPdf=r1((S.df/T)*100),wkPdf=r1((S.df*7)/KCAL_PER_KG),mkgPdf=r1((S.df*30)/KCAL_PER_KG),bwPctPdf=W>0?r1((wkPdf/W)*100):0;
        p+='<div class="detail-box">';
        p+='<div class="detail-row"><span class="detail-label">Napi kalóriaszükséglet (TDEE)</span><span class="detail-val">'+T+' kcal/nap</span></div>';
        p+='<div class="detail-row"><span class="detail-label">Napi csökkentés</span><span class="detail-val" style="color:#dc2626">\u2212 '+S.df+' kcal/nap (\u2212'+defPctPdf+'%)</span></div>';
        p+='<div class="detail-row"><span class="detail-label"><strong>Napi kalória cél (deficites)</strong></span><span class="detail-val-accent">'+cutKcal+' kcal/nap</span></div>';
        p+='<div class="detail-row"><span class="detail-label">Számítás</span><span class="detail-val">'+T+' \u2212 '+S.df+' = '+cutKcal+' kcal</span></div>';
        p+='<div class="detail-row"><span class="detail-label">kcal/ttkg (deficites)</span><span class="detail-val">'+(W>0?r1(cutKcal/W):'-')+' kcal/ttkg</span></div>';
        p+='<div class="detail-row"><span class="detail-label">kcal/ttkg (szintentartó)</span><span class="detail-val">'+(W>0?r1(T/W):'-')+' kcal/ttkg</span></div>';
        p+='</div>';
        p+='<table><tbody>';
        p+='<tr><td>Becsült heti fogyás</td><td><strong>~'+wkPdf+' kg/hét</strong>'+(bwPctPdf>0?' ('+bwPctPdf+'% testtömeg/hét)':'')+'</td></tr>';
        p+='<tr><td>Becsült havi fogyás</td><td>~'+mkgPdf+' kg/hónap</td></tr>';
        p+='<tr><td>Biztonságos tartomány</td><td>heti 0,5\u20131,0% testtömeg'+(W>0?' ('+r1(W*0.005)+'\u2013'+r1(W*0.01)+' kg)':'')+'</td></tr>';
        p+='</tbody></table>';
        p+='<div class="note"><strong>Hogyan működik a fogyás?</strong><br>A tested naponta elhasznál '+T+' kcal energiát (TDEE). Ha ennél kevesebbet viszel be (\u2212'+S.df+' kcal), a szervezet a hiányt a tartalékaiból fedezi. 1 kg testsúlyváltozás \u2248 7000 kcal (75% zsír + 25% zsírmentes tömeg). A fogyás üteme idővel lassul az adaptív termogenezis miatt.<br><em>Hall 2008; Heymsfield et al. 2022; Hall et al. 2011</em></div>'
    }else if(S.type==='bulk'&&S.sp>0){
        var bulkKcal=ek,spPctPdf=r1((S.sp/T)*100);
        p+='<div class="detail-box">';
        p+='<div class="detail-row"><span class="detail-label">Napi kalóriaszükséglet (TDEE)</span><span class="detail-val">'+T+' kcal/nap</span></div>';
        p+='<div class="detail-row"><span class="detail-label">Napi többlet</span><span class="detail-val" style="color:#f59e0b">+ '+S.sp+' kcal/nap (+'+spPctPdf+'%)</span></div>';
        p+='<div class="detail-row"><span class="detail-label"><strong>Napi kalória cél (többletes)</strong></span><span class="detail-val-accent">'+bulkKcal+' kcal/nap</span></div>';
        p+='<div class="detail-row"><span class="detail-label">Számítás</span><span class="detail-val">'+T+' + '+S.sp+' = '+bulkKcal+' kcal</span></div>';
        p+='<div class="detail-row"><span class="detail-label">kcal/testtömeg-kg</span><span class="detail-val">'+(W>0?r1(bulkKcal/W):'-')+' kcal/ttkg</span></div>';
        p+='</div>';
        p+='<div class="note"><strong>Hogyan épül izom?</strong><br>Az izom építőköve izomfehérje (aktin, miozin), ezért edzésinger (mechanikus tenzió) és elegendő fehérje kell. Energia oldalról nem kötelező nagy kalóriatöbblet \u2013 gyakran elég +10\u201320%. Lassú súlynövekedés (0,25\u20130,5%/hét) csökkenti a zsírfelhalmozódást.<br><em>Kassiano et al. 2025; Jäger et al. 2017; Iraki et al. 2019</em></div>'
    }else{
        p+='<div class="detail-box">';
        p+='<div class="detail-row"><span class="detail-label">Napi kalóriaszükséglet (TDEE)</span><span class="detail-val">'+T+' kcal/nap</span></div>';
        p+='<div class="detail-row"><span class="detail-label"><strong>Napi kalória cél</strong></span><span class="detail-val-accent">'+ek+' kcal/nap</span></div>';
        if(W>0)p+='<div class="detail-row"><span class="detail-label">kcal/testtömeg-kg</span><span class="detail-val">'+r1(ek/W)+' kcal/ttkg</span></div>';
        p+='</div>'
    }

    p+='<h2>\uD83E\uDDEA 4. Makrók</h2>';
    p+='<div class="rec">'+lastRecNotice.replace(/<a [^>]*>/g,'').replace(/<\/a>/g,'')+'</div>';
    p+='<div class="grid"><div class="box"><div class="box-val" style="color:#ef4444">'+pG.toFixed(2)+' g</div><div class="box-lab">Fehérje ('+S.pP.toFixed(2)+' energia%)</div></div><div class="box"><div class="box-val" style="color:#f59e0b">'+fG.toFixed(2)+' g</div><div class="box-lab">Zsír ('+S.fP.toFixed(2)+' energia%)</div></div><div class="box"><div class="box-val" style="color:#3b82f6">'+cG.toFixed(2)+' g</div><div class="box-lab">Szénhidrát ('+S.cP.toFixed(2)+' energia%)</div></div></div>';
    p+='<table><tbody><tr><td>Fehérje/ttkg</td><td><strong>'+(W>0?(pG/W).toFixed(2):'-')+' g/ttkg</strong></td></tr><tr><td>Zsír/ttkg</td><td>'+(W>0?(fG/W).toFixed(2):'-')+' g/ttkg</td></tr><tr><td>Szénhidrát/ttkg</td><td>'+(W>0?(cG/W).toFixed(2):'-')+' g/ttkg</td></tr></tbody></table>';

    p+='<h2>\uD83C\uDF7D 5. Étkezés ('+S.mc+'\u00D7/nap)</h2><table><thead><tr><th>Étkezés</th><th>Energia%</th><th>kcal</th><th>Fehérje</th><th>Zsír</th><th>CH</th></tr></thead><tbody>';
    for(var i=0;i<S.mc;i++){
        var pc=S.md[i]||0;
        p+='<tr><td>'+(mealN[i]||(i+1)+'.')+'</td><td>'+pc.toFixed(2)+'</td><td>'+(ek*pc/100).toFixed(0)+'</td><td>'+(pG*pc/100).toFixed(1)+' g</td><td>'+(fG*pc/100).toFixed(1)+' g</td><td>'+(cG*pc/100).toFixed(1)+' g</td></tr>'
    }
    p+='</tbody></table>';

    p+='<hr><h2>\uD83D\uDCDA 6. Tudományos források</h2>';
    p+='<div class="sc"><div class="sc-t">\uD83D\uDD2C Energiabevitel és testsúlyszabályozás</div><div class="sc-i">';
    p+='<span class="st st-r">Eredeti kutatás</span><strong>Hall KD (2008)</strong> \u2013 1 kg testsúlyváltozás \u2248 7000 kcal (75% zsír + 25% FFM). Int J Obes. PMID: 17848938<br>';
    p+='<span class="st st-n">Narratív áttekintés</span><strong>Proceedings of the Nutrition Society (2021)</strong> \u2013 ~7000 kcal/kg modell validálása.<br>';
    p+='<span class="st st-r">Dinamikus modell</span><strong>Hall KD et al. (2011)</strong> \u2013 Lancet: adaptív termogenezis modell. PMID: 21872751<br>';
    p+='<span class="st st-s">Szisztematikus áttekintés</span><strong>Nunes et al. (2022)</strong> \u2013 Adaptív termogenezis. Clin Nutr. PMID: 33762040<br>';
    p+='<span class="st st-s">Sziszt. áttekintés + metaanalízis</span><strong>Heymsfield et al. (2022)</strong> \u2013 7000 kcal/kg validálása. Am J Clin Nutr. PMID: 35103583';
    p+='</div></div>';

    p+='<div class="sc"><div class="sc-t">\uD83C\uDFCB\uFE0F Fehérjebevitel \u2013 sportolók és fogyás</div><div class="sc-i">';
    p+='<span class="st st-p">ISSN állásfoglalás</span><strong>Jäger R et al. (2017)</strong> \u2013 1,4\u20132,0 g/ttkg/nap; fogyás: 2,3\u20133,1 g/ttkg. PMID: 28698222<br>';
    p+='<span class="st st-m">Metaanalízis</span><strong>Morton RW et al. (2018)</strong> \u2013 49 RCT: küszöb ~1,6 g/ttkg. PMID: 28698222<br>';
    p+='<span class="st st-p">Közös állásfoglalás</span><strong>Thomas DT et al. (2016)</strong> \u2013 AND+ACSM+DC: 1,2\u20132,0 g/ttkg. PMID: 26920240<br>';
    p+='<span class="st st-n">Áttekintés</span><strong>Helms ER et al. (2014)</strong> \u2013 Testépítők: heti 0,5\u20131,0% fogyás. PMID: 24864135<br>';
    p+='<span class="st st-n">Áttekintés</span><strong>Murphy & Koehler (2022)</strong> \u2013 LEA kockázatok. PMID: 34623696';
    p+='</div></div>';

    p+='<div class="sc"><div class="sc-t">\uD83D\uDCC8 Izomépítés és tömegnövelés</div><div class="sc-i">';
    p+='<span class="st st-s">Áttekintés</span><strong>Iraki J et al. (2019)</strong> \u2013 +10\u201320% surplus, 0,25\u20130,5%/hét. PMID: 31247944<br>';
    p+='<span class="st st-s">Áttekintés</span><strong>Lim C et al. (2022)</strong> \u2013 +1,5\u20132,0 kg FFM / 8\u201312 hét. PMID: 35291645<br>';
    p+='<span class="st st-n">Áttekintés</span><strong>Schoenfeld & Grgic (2020)</strong> \u2013 Hipertrófia edzésmúlt szerint. PMID: 31174557<br>';
    p+='<span class="st st-s">Áttekintés</span><strong>Roberts BM et al. (2020)</strong> \u2013 Nemtől független relatív ütem. PMID: 32218059<br>';
    p+='<span class="st st-m">Metaanalízis</span><strong>Kassiano W et al. (2025)</strong> \u2013 Deficit rontja izommegtartást. PMID: 40566702';
    p+='</div></div>';

    p+='<div class="sc"><div class="sc-t">\uD83C\uDF0D Nemzetközi szervezetek + \uD83C\uDDED\uD83C\uDDFA Magyar ajánlások</div><div class="sc-i">';
    p+='<span class="st st-g">WHO</span><strong>WHO TRS 894 (2000)</strong> \u2013 BMI kategóriák. PMID: 11234459<br>';
    p+='<span class="st st-g">WHO</span><strong>WHO (2023)</strong> \u2013 Makro: F 10\u201315%, Zs 15\u201330%, CH 55\u201375%.<br>';
    p+='<span class="st st-g">EFSA</span><strong>EFSA DRV (2017)</strong> \u2013 EU referencia-értékek.<br>';
    p+='<span class="st st-g">ESPEN</span><strong>Cederholm T et al. (2017)</strong> \u2013 30\u201340 kcal/ttkg, 1,2\u20131,5 g/ttkg. PMID: 27637832<br>';
    p+='<span class="st st-g">MDOSZ</span><strong>MDOSZ Okostányér (2021)</strong> \u2013 15/30/55 energia%. mdosz.hu/okostanyer';
    p+='</div></div>';

    p+='<div class="sc"><div class="sc-t">\uD83C\uDFE5 Klinikai módszertan</div><div class="sc-i">';
    p+='<span class="st st-r">Kutatás</span><strong>Ireton-Jones C (2005)</strong> \u2013 ABW módszer elhízottaknál. PMID: 16207687<br>';
    p+='<span class="st st-r">Kutatás</span><strong>Müller MJ et al. (2004)</strong> \u2013 FFM és BMR összefüggése. PMID: 15883556';
    p+='</div></div>';

    p+='<div class="footer">Automatikus összegzés \u00B7 '+window.location.hostname+' \u00B7 Atwater-faktorok: 1 g fehérje/szénhidrát = 4,1 kcal \u00B7 1 g zsír = 9,3 kcal</div>';

    var dateStr=new Date().toISOString().slice(0,10);
    var fileName='taplalkozasi-terv-'+dateStr+'.pdf';

    if(typeof html2pdf==='undefined'){
        pdfFallback(css+p);
        return;
    }

    var oldF=document.getElementById('dp-pdf-frame');
    if(oldF) oldF.remove();

    var iframe=document.createElement('iframe');
    iframe.id='dp-pdf-frame';
    iframe.style.position='fixed';
    iframe.style.left='0';
    iframe.style.top='0';
    iframe.style.width=A4_CONFIG.WIDTH_PX+'px';
    iframe.style.height=A4_CONFIG.HEIGHT_PX+'px';
    iframe.style.opacity='0.01';
    iframe.style.pointerEvents='none';
    iframe.style.border='0';
    iframe.style.zIndex='-1';
    document.body.appendChild(iframe);

    var html='<!doctype html><html><head><meta charset="utf-8">'+css+'</head><body>'+p+'</body></html>';
    iframe.srcdoc=html;

    iframe.onload=function(){
        try{
            var body=iframe.contentDocument.body;
            html2pdf().set({
                margin:[10,10,10,10],
                filename:fileName,
                image:{type:'jpeg',quality:0.95},
                html2canvas:{
                    scale:1.5,
                    useCORS:true,
                    logging:false,
                    backgroundColor:'#ffffff',
                    foreignObjectRendering:true,
                    allowTaint:true,
                    scrollX:0,
                    scrollY:0,
                    windowWidth:A4_CONFIG.WIDTH_PX,
                    windowHeight:A4_CONFIG.HEIGHT_PX
                },
                jsPDF:{unit:'mm',format:'a4',orientation:'portrait',compress:true},
                pagebreak:{mode:['avoid-all','css','legacy'],avoid:['.dg-card','.cj-origin-card','.dg-info-box','.dg-cut-intake','.dg-macro-grid','table']}
            }).from(body).outputPdf('blob').then(function(blob){
                var blobUrl=URL.createObjectURL(blob);
                window.open(blobUrl,'_blank');
                var a=document.createElement('a');
                a.href=blobUrl;
                a.download=fileName;
                document.body.appendChild(a);
                a.click();
                setTimeout(function(){document.body.removeChild(a);URL.revokeObjectURL(blobUrl);},1000);
            }).then(function(){
                iframe.remove();
            }).catch(function(){
                iframe.remove();
                pdfFallback(css+p);
            });
        }catch(e){
            iframe.remove();
            pdfFallback(css+p);
        }
    };
}

function pdfFallback(html){
    var w=window.open('','','width=900,height=800');
    w.document.write('<html><head><title>Táplálkozási terv \u2013 '+UN+'</title></head><body>'+html+'</body></html>');
    w.document.close();
    setTimeout(function(){w.print()},500)
}

var raf=false;
function rAll(){
    if(raf)return;
    raf=true;
    requestAnimationFrame(function(){
        raf=false;
        if(kEl)kEl.value=eK();
        if(S.type==='cut')updCut();
        else if(S.type==='bulk'){updBulk();buildExpect()}
        if(S.mm==='recommended')setRM();
        updMD();
        buildMT()
    })
}

updDerived();
uSt();
updTriggers();
setRM();
rAll();

})();
</script>
    <?php
}, 50 );
