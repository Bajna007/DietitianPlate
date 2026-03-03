
 /* 32 – Auth modal + header auth CSS
 * v9.2 – GDPR checkbox CSS kiszedve + dp-auth-legal dizájn (checkbox nélkül)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_head', function() {

    // GDPR oldalak URL-jei dinamikusan
    $aszf_url  = esc_url( get_permalink( get_page_by_path( 'aszf' ) ) );
    $adatk_url = esc_url( get_permalink( get_page_by_path( 'adatkezelesi-tajekoztato' ) ) );

    $c = '';

    /* ── HEADER AUTH GOMBOK ── */
    $c .= '.ch-header-login{display:inline-flex!important;align-items:center!important;gap:7px!important;padding:8px 20px!important;border:1.5px solid var(--ch-accent)!important;border-radius:999px!important;background:transparent!important;color:var(--ch-accent)!important;font-size:13.5px!important;font-weight:600!important;cursor:pointer;transition:all .25s ease;white-space:nowrap;outline:none!important;box-shadow:none!important;-webkit-appearance:none!important;line-height:1.4!important}.ch-header-login:hover{background:var(--ch-accent)!important;color:#fff!important;box-shadow:0 4px 16px rgba(45,106,79,0.25)!important;transform:translateY(-1px)}.ch-header-login svg{width:16px;height:16px;stroke:currentColor;flex-shrink:0}';
    $c .= '.ch-header-avatar{display:flex;align-items:center;text-decoration:none}.ch-header-avatar img{width:32px;height:32px;border-radius:50%;border:2px solid var(--ch-border);object-fit:cover;transition:border-color .25s ease,box-shadow .25s ease}.ch-header-avatar:hover img{border-color:var(--ch-accent);box-shadow:0 0 0 3px rgba(45,106,79,0.15)}';
    $c .= '.ch-header-fav{display:inline-flex;align-items:center;gap:4px;padding:6px 12px;border-radius:999px;background:rgba(220,38,38,0.08);color:#dc2626;text-decoration:none;font-size:12px;font-weight:700;transition:all .25s ease}.ch-header-fav:hover{background:rgba(220,38,38,0.15);transform:translateY(-1px)}.ch-header-fav svg{width:16px;height:16px}';

    /* ── MOBIL AUTH ── */
    $c .= '.ch-mobile-login-btn{display:flex!important;align-items:center!important;justify-content:center!important;gap:10px!important;width:100%!important;padding:14px 20px!important;border:1.5px solid var(--ch-accent)!important;border-radius:var(--ch-radius-sm)!important;background:var(--ch-accent)!important;color:#fff!important;font-size:14.5px!important;font-weight:600!important;cursor:pointer;transition:all .25s ease;outline:none!important;box-shadow:none!important;-webkit-appearance:none!important;line-height:1.4!important}.ch-mobile-login-btn:hover{background:#245a42!important;border-color:#245a42!important}.ch-mobile-login-btn svg{width:18px;height:18px;stroke:#fff;flex-shrink:0}';
    $c .= '.ch-mobile-user-link{display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:var(--ch-radius-sm);background:var(--ch-accent-light);text-decoration:none;color:var(--ch-text);font-weight:600;font-size:14.5px;transition:background .25s ease}.ch-mobile-user-link:hover{background:rgba(45,106,79,0.12)}.ch-mobile-user-avatar{width:28px;height:28px;border-radius:50%;object-fit:cover;border:2px solid var(--ch-accent);flex-shrink:0}.ch-mobile-fav-badge{margin-left:auto;font-size:12px;font-weight:700;color:#dc2626;background:rgba(220,38,38,0.08);padding:3px 10px;border-radius:999px}';
    $c .= '.ch-mobile-logout{display:block;text-align:center;padding:10px;font-size:13px;font-weight:600;color:var(--ch-text-muted);text-decoration:none;border-radius:var(--ch-radius-sm);transition:color .25s ease,background .25s ease}.ch-mobile-logout:hover{color:#dc2626;background:rgba(220,38,38,0.06)}';

    /* ── AUTH MODAL – overlay pointer-events fix ── */
    $c .= '.dp-auth-overlay{position:fixed;inset:0;z-index:200000;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0);backdrop-filter:blur(0);-webkit-backdrop-filter:blur(0);pointer-events:none;visibility:hidden;transition:background .3s ease,backdrop-filter .3s ease,visibility .3s ease}';
    $c .= '.dp-auth-overlay.is-open{background:rgba(0,0,0,0.5);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);pointer-events:auto;visibility:visible}';
    $c .= '.dp-auth-modal{position:relative;width:420px;max-width:92vw;max-height:90vh;overflow-y:auto;background:#fff;border-radius:20px;box-shadow:0 24px 80px rgba(0,0,0,0.15),0 0 0 1px rgba(0,0,0,0.05);padding:36px 32px 32px;opacity:0;transform:translateY(20px) scale(.97);transition:opacity .3s ease,transform .3s ease}';
    $c .= '.dp-auth-overlay.is-open .dp-auth-modal{opacity:1;transform:translateY(0) scale(1)}';

    $c .= '.dp-auth-close{position:absolute;top:16px;right:16px;width:36px;height:36px;display:flex;align-items:center;justify-content:center;border:none!important;background:#f5f6f5!important;border-radius:50%!important;cursor:pointer;color:#666!important;transition:all .2s ease;outline:none!important;box-shadow:none!important;-webkit-appearance:none!important;padding:0!important}.dp-auth-close:hover{background:#e8ebe9!important;color:#1e1e1e!important}.dp-auth-close svg{width:18px;height:18px;stroke:currentColor}';
    $c .= '.dp-auth-tabs{display:flex;gap:0;margin-bottom:24px;border-bottom:2px solid #f0f1f3}.dp-auth-tab{flex:1;padding:12px 0!important;border:none!important;background:none!important;font-size:15px!important;font-weight:600!important;color:#999!important;cursor:pointer;transition:color .2s ease;border-bottom:2px solid transparent!important;margin-bottom:-2px;outline:none!important;box-shadow:none!important;-webkit-appearance:none!important;text-align:center;border-radius:0!important}.dp-auth-tab:hover{color:#666!important}.dp-auth-tab.is-active{color:#2d6a4f!important;border-bottom-color:#2d6a4f!important}';

    $c .= '.dp-auth-social{display:flex;flex-direction:column;gap:10px;margin-bottom:20px}.dp-social-btn{display:flex!important;align-items:center!important;justify-content:center!important;gap:10px!important;padding:12px 20px!important;border-radius:12px!important;font-size:14px!important;font-weight:600!important;text-decoration:none!important;transition:all .2s ease;cursor:pointer;border:1.5px solid #e8ebe9!important;line-height:1.4!important}.dp-social-btn svg{flex-shrink:0}.dp-social-btn--google{background:#fff!important;color:#333!important}.dp-social-btn--google:hover{background:#f8f9fa!important;border-color:#d0d5dd!important;box-shadow:0 2px 8px rgba(0,0,0,0.08)!important}';
    $c .= '.dp-auth-divider{display:flex;align-items:center;gap:16px;margin-bottom:20px;color:#bbb;font-size:13px;font-weight:500}.dp-auth-divider::before,.dp-auth-divider::after{content:"";flex:1;height:1px;background:#eee}';

    $c .= '.dp-auth-form{display:flex;flex-direction:column;gap:16px}.dp-auth-field{display:flex;flex-direction:column;gap:6px}.dp-auth-field label{font-size:13px;font-weight:600;color:#555}';
    $c .= '.dp-auth-field input[type="email"],.dp-auth-field input[type="password"],.dp-auth-field input[type="text"]{width:100%!important;padding:12px 16px!important;border:1.5px solid #e2e6e4!important;border-radius:10px!important;font-size:14.5px!important;font-weight:500!important;color:#1e1e1e!important;background:#fafbfa!important;outline:none!important;box-shadow:none!important;transition:border-color .2s ease,box-shadow .2s ease;-webkit-appearance:none!important;line-height:1.4!important;margin:0!important;height:auto!important}';
    $c .= '.dp-auth-field input:focus{border-color:#2d6a4f!important;box-shadow:0 0 0 3px rgba(45,106,79,0.12)!important;background:#fff!important}.dp-auth-field input::placeholder{color:#bbb!important;font-weight:400!important}';

    $c .= '.dp-auth-field-row{display:flex;align-items:center;justify-content:space-between}.dp-auth-remember{display:flex;align-items:center;gap:6px;font-size:13px;color:#666;cursor:pointer}.dp-auth-remember input[type="checkbox"]{width:16px!important;height:16px!important;accent-color:#2d6a4f;margin:0!important}';
    $c .= '.dp-auth-forgot{background:none!important;border:none!important;font-size:13px!important;font-weight:600!important;color:#2d6a4f!important;cursor:pointer;padding:0!important;outline:none!important;box-shadow:none!important;-webkit-appearance:none!important}.dp-auth-forgot:hover{color:#245a42!important;text-decoration:underline}';

    $c .= '.dp-auth-submit{width:100%!important;padding:14px 24px!important;border:none!important;border-radius:12px!important;background:#2d6a4f!important;color:#fff!important;font-size:15px!important;font-weight:700!important;cursor:pointer;transition:all .2s ease;outline:none!important;box-shadow:none!important;-webkit-appearance:none!important;line-height:1.4!important;margin-top:4px}.dp-auth-submit:hover{background:#245a42!important;box-shadow:0 4px 16px rgba(45,106,79,0.3)!important;transform:translateY(-1px)}.dp-auth-submit:disabled{opacity:.6;cursor:not-allowed;transform:none!important}';

    $c .= '.dp-auth-message{font-size:13px;font-weight:600;padding:0;border-radius:8px;min-height:0;transition:all .2s ease}.dp-auth-message:empty{display:none}.dp-auth-message--success{display:block;padding:10px 14px;background:rgba(45,106,79,0.08);color:#2d6a4f;border:1px solid rgba(45,106,79,0.15)}.dp-auth-message--error{display:block;padding:10px 14px;background:rgba(220,38,38,0.06);color:#dc2626;border:1px solid rgba(220,38,38,0.12)}';

    $c .= '.dp-auth-forgot-desc{font-size:14px;color:#666;line-height:1.6;margin:0 0 4px}.dp-auth-back{width:100%!important;padding:12px!important;border:1.5px solid #e2e6e4!important;border-radius:10px!important;background:#fff!important;color:#666!important;font-size:14px!important;font-weight:600!important;cursor:pointer;transition:all .2s ease;outline:none!important;box-shadow:none!important;-webkit-appearance:none!important;text-align:center;margin-top:4px}.dp-auth-back:hover{border-color:#2d6a4f!important;color:#2d6a4f!important}';
    $c .= '.dp-auth-hint{display:block;font-size:11px;color:#999;margin-top:4px;line-height:1.4}';
    $c .= '.dp-auth-resend{display:inline-block;margin-top:8px;padding:0;border:none!important;background:none!important;color:#2d6a4f!important;font-size:13px!important;font-weight:600!important;cursor:pointer;text-decoration:underline;outline:none!important;-webkit-appearance:none!important}.dp-auth-resend:hover{color:#245a42!important}';

/* ── JOGI SZÖVEG (checkbox helyett) ── */
$c .= '.dp-auth-legal{position:relative;margin:2px 0 0;padding:10px 12px 10px 40px;background:rgba(45,106,79,0.06);border:1px solid rgba(45,106,79,0.14);border-radius:12px;font-size:12.6px;color:#555;line-height:1.55}';
$c .= '.dp-auth-legal:before{content:"i";position:absolute;left:12px;top:11px;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:rgba(45,106,79,0.12);color:#2d6a4f;font-weight:900;font-size:12px;line-height:1}';
$c .= '.dp-auth-legal strong{color:#2d6a4f}';
$c .= '.dp-auth-legal a{display:inline!important;color:#2d6a4f;font-weight:800;text-decoration:underline;white-space:normal!important;word-break:normal!important;overflow-wrap:anywhere!important}';
$c .= '.dp-auth-legal a:hover{color:#245a42!important}';
$c .= '.dp-auth-legal{white-space:normal!important;word-break:normal!important;overflow-wrap:anywhere!important}';

    $c .= '.dp-auth-footer-note{font-size:11.5px;color:#aaa;text-align:center;margin:20px 0 0;line-height:1.5}.dp-auth-footer-note a{color:#2d6a4f;text-decoration:underline}';

    /* ── CLOUDFLARE TURNSTILE ── */
    $c .= '.dp-turnstile-wrap{display:flex;justify-content:center;margin:12px 0 4px;min-height:65px}';

    /* ── KEDVENC GOMB (recept kártyákon) ── */
    $c .= '.dp-fav-btn{position:absolute;top:12px;right:12px;width:36px;height:36px;display:flex;align-items:center;justify-content:center;border:none!important;border-radius:50%!important;background:rgba(255,255,255,0.85)!important;backdrop-filter:blur(6px);cursor:pointer;z-index:5;transition:all .25s ease;outline:none!important;box-shadow:0 2px 8px rgba(0,0,0,0.1)!important;-webkit-appearance:none!important;padding:0!important}.dp-fav-btn svg{width:18px;height:18px;transition:all .25s ease;fill:none;stroke:#999;stroke-width:2}.dp-fav-btn:hover{background:#fff!important;transform:scale(1.1)}.dp-fav-btn:hover svg{stroke:#dc2626}.dp-fav-btn.is-active{background:rgba(220,38,38,0.1)!important}.dp-fav-btn.is-active svg{fill:#dc2626;stroke:#dc2626}.dp-fav-btn.dp-fav-loading{opacity:.5;pointer-events:none}.dp-fav-btn--card{top:10px;right:10px}';

    /* ── RESPONSIVE ── */
    $c .= '@media(max-width:600px){.dp-auth-modal{padding:28px 20px 24px;border-radius:16px}.ch-header-login span{display:none}.ch-header-login{padding:8px 12px!important}}';

    echo '<style id="dp-auth-css">' . $c . '</style>' . "\n";

    // GDPR linkek a JS számára
    echo '<script>window.DP_GDPR={"aszf":"' . $aszf_url . '","adatkezeles":"' . $adatk_url . '"};</script>' . "\n";

}, 21 );
