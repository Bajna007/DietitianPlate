<?php

/**
 * 27.  – HEADER + FOOTER BUILDER (Kadence kompatibilis)
 */
/**
 * 23 – Custom Header + Footer (Kadence felülírás) – JAVÍTOTT v2
 * Közös snippet – PHP + CSS + JS
 * Kadence header/footer eltüntetése + saját megjelenítés
 * Mobil menü: javított flexbox layout
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* ============================================================
   0. KADENCE HEADER/FOOTER ELTÁVOLÍTÁSA
   ============================================================ */
add_action( 'after_setup_theme', function() {
    remove_action( 'wp_body_open', 'Kadence\header_markup', 0 );
    remove_action( 'wp_body_open', 'Kadence\header_markup' );
}, 999 );

add_action( 'wp', function() {
    remove_action( 'kadence_header',       'Kadence\header_markup' );
    remove_action( 'kadence_before_header', 'Kadence\header_markup' );
    remove_action( 'kadence_footer',       'Kadence\footer_markup' );
    remove_action( 'kadence_before_footer', 'Kadence\footer_markup' );
    remove_action( 'kadence_after_footer',  'Kadence\footer_markup' );
    $footer_hooks = [ 'kadence_footer', 'kadence_before_footer', 'kadence_after_footer' ];
    foreach ( $footer_hooks as $hook ) {
        remove_all_actions( $hook );
    }
}, 999 );

/* ============================================================
   1. MENÜ REGISZTRÁLÁS
   ============================================================ */
add_action( 'after_setup_theme', function() {
    register_nav_menus([
        'custom_header_menu' => 'Custom Header Menü',
    ]);
}, 5 );

/* ============================================================
   2. HEADER MEGJELENÍTÉS
   ============================================================ */
add_action( 'wp_body_open', 'custom_site_header', 1 );
function custom_site_header() {
    ?>
    <header id="custom-header" class="ch-header">
        <div class="ch-container">
            <a href="<?php echo esc_url( home_url('/') ); ?>" class="ch-logo" aria-label="Főoldal">
                <span class="ch-logo-icon">🥗</span>
                <span class="ch-logo-text">Tápanyag<span class="ch-logo-accent">Lexikon</span></span>
            </a>
            <nav class="ch-nav" aria-label="Fő navigáció">
                <?php
                if ( has_nav_menu('custom_header_menu') ) {
                    wp_nav_menu([
                        'theme_location'  => 'custom_header_menu',
                        'container'       => false,
                        'menu_class'      => 'ch-menu',
                        'depth'           => 2,
                        'fallback_cb'     => 'custom_header_fallback_menu',
                        'walker'          => new Custom_Header_Walker(),
                    ]);
                } else {
                    custom_header_fallback_menu();
                }
                ?>
            </nav>
            <button class="ch-hamburger" id="ch-hamburger" aria-label="Menü megnyitása" aria-expanded="false">
                <span class="ch-hamburger-line"></span>
                <span class="ch-hamburger-line"></span>
                <span class="ch-hamburger-line"></span>
            </button>
        </div>
        <div class="ch-mobile-overlay" id="ch-mobile-overlay">
            <div class="ch-mobile-panel">
                <div class="ch-mobile-header">
                    <a href="<?php echo esc_url( home_url('/') ); ?>" class="ch-logo">
                        <span class="ch-logo-icon">🥗</span>
                        <span class="ch-logo-text">Tápanyag<span class="ch-logo-accent">Lexikon</span></span>
                    </a>
                    <button class="ch-mobile-close" id="ch-mobile-close" aria-label="Menü bezárása">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <nav class="ch-mobile-nav" aria-label="Mobil navigáció">
                    <?php
                    if ( has_nav_menu('custom_header_menu') ) {
                        wp_nav_menu([
                            'theme_location'  => 'custom_header_menu',
                            'container'       => false,
                            'menu_class'      => 'ch-mobile-menu',
                            'depth'           => 2,
                            'fallback_cb'     => 'custom_header_fallback_menu_mobile',
                        ]);
                    } else {
                        custom_header_fallback_menu_mobile();
                    }
                    ?>
                </nav>
                <div class="ch-mobile-footer">
                    <div class="ch-mobile-social">
                        <a href="#" aria-label="Facebook" class="ch-social-link"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>
                        <a href="#" aria-label="Instagram" class="ch-social-link"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg></a>
                        <a href="#" aria-label="TikTok" class="ch-social-link"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg></a>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <?php
}

/* ============================================================
   3. FALLBACK MENÜK
   ============================================================ */
function custom_header_fallback_menu() {
    $items = custom_header_get_menu_items();
    echo '<ul class="ch-menu">';
    foreach ( $items as $item ) {
        $active = custom_header_is_active( $item['url'], $item['cpt'] ?? '' );
        echo '<li class="ch-menu-item' . ($active ? ' is-active' : '') . '">';
        echo '<a href="' . esc_url($item['url']) . '" class="ch-menu-link">' . esc_html($item['label']) . '</a>';
        echo '</li>';
    }
    echo '</ul>';
}

function custom_header_fallback_menu_mobile() {
    $items = custom_header_get_menu_items();
    echo '<ul class="ch-mobile-menu">';
    foreach ( $items as $item ) {
        $active = custom_header_is_active( $item['url'], $item['cpt'] ?? '' );
        echo '<li class="ch-mobile-item' . ($active ? ' is-active' : '') . '">';
        echo '<a href="' . esc_url($item['url']) . '" class="ch-mobile-link">';
        echo '<span class="ch-mobile-icon">' . $item['icon'] . '</span>';
        echo esc_html($item['label']);
        echo '</a>';
        echo '</li>';
    }
    echo '</ul>';
}

function custom_header_get_menu_items() {
    return [
        [ 'label' => 'Főoldal',         'url' => home_url('/'),                'icon' => '🏠', 'cpt' => '' ],
        [ 'label' => 'Receptek',         'url' => home_url('/receptek/'),       'icon' => '🍳', 'cpt' => 'recept' ],
        [ 'label' => 'Alapanyag-adatbázis',      'url' => home_url('/alapanyagok/'),    'icon' => '🥕', 'cpt' => 'alapanyag' ],
        [ 'label' => 'Tápanyag lexikon', 'url' => home_url('/tapanyagok/'),     'icon' => '🧬', 'cpt' => 'tapanyag' ],
        [ 'label' => 'Kalória kalkulátor',   'url' => home_url('/bmr-kalkulator/'), 'icon' => '📊', 'cpt' => '' ],
    ];
}

function custom_header_is_active( $url, $cpt ) {
    if ( $cpt && ( is_singular($cpt) || is_post_type_archive($cpt) ) ) return true;
    $current = trailingslashit( strtok($_SERVER['REQUEST_URI'], '?') );
    $target  = trailingslashit( wp_parse_url($url, PHP_URL_PATH) ?: '/' );
    return $current === $target;
}

/* ============================================================
   4. WALKER CLASS
   ============================================================ */
class Custom_Header_Walker extends Walker_Nav_Menu {
    function start_lvl( &$output, $depth = 0, $args = null ) {
        $output .= '<ul class="ch-submenu">';
    }
    function end_lvl( &$output, $depth = 0, $args = null ) {
        $output .= '</ul>';
    }
    function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
        $active = in_array('current-menu-item', (array)$item->classes) || in_array('current-menu-ancestor', (array)$item->classes);
        $has_children = in_array('menu-item-has-children', (array)$item->classes);
        if ( $depth === 0 ) {
            $output .= '<li class="ch-menu-item' . ($active ? ' is-active' : '') . ($has_children ? ' has-submenu' : '') . '">';
            $output .= '<a href="' . esc_url($item->url) . '" class="ch-menu-link">' . esc_html($item->title);
            if ( $has_children ) {
                $output .= ' <svg class="ch-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>';
            }
            $output .= '</a>';
        } else {
            $output .= '<li class="ch-submenu-item' . ($active ? ' is-active' : '') . '">';
            $output .= '<a href="' . esc_url($item->url) . '" class="ch-submenu-link">' . esc_html($item->title) . '</a>';
        }
    }
    function end_el( &$output, $item, $depth = 0, $args = null ) {
        $output .= '</li>';
    }
}

/* ============================================================
   5. FOOTER MEGJELENÍTÉS
   ============================================================ */
add_action( 'wp_footer', 'custom_site_footer', 10 );
function custom_site_footer() {
    $year = date('Y');
    ?>
    <footer id="custom-footer" class="cf-footer">
        <div class="cf-main">
            <div class="cf-container">
                <div class="cf-grid">
                    <div class="cf-col cf-col-about">
                        <a href="<?php echo esc_url( home_url('/') ); ?>" class="cf-logo">
                            <span class="cf-logo-icon">🥗</span>
                            <span class="cf-logo-text">Tápanyag<span class="cf-logo-accent">Lexikon</span></span>
                        </a>
                        <p class="cf-description">Tudományosan megalapozott tápanyag adatbázis, receptek és táplálkozási kalkulátorok – egy helyen, magyarul.</p>
                        <div class="cf-social">
                            <a href="#" class="cf-social-link" aria-label="Facebook"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>
                            <a href="#" class="cf-social-link" aria-label="Instagram"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg></a>
                            <a href="#" class="cf-social-link" aria-label="TikTok"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg></a>
                        </div>
                    </div>
                    <div class="cf-col">
                        <h4 class="cf-col-title">Tartalom</h4>
                        <ul class="cf-links">
                            <li><a href="<?php echo esc_url( home_url('/receptek/') ); ?>">🍳 Receptek</a></li>
                            <li><a href="<?php echo esc_url( home_url('/alapanyagok/') ); ?>">🥕 Alapanyagok</a></li>
                            <li><a href="<?php echo esc_url( home_url('/tapanyagok/') ); ?>">🧬 Tápanyag Lexikon</a></li>
                            <li><a href="<?php echo esc_url( home_url('/bmr-kalkulator/') ); ?>">📊 BMR Kalkulátor</a></li>
                        </ul>
                    </div>
                    <div class="cf-col">
                        <h4 class="cf-col-title">Jogi információk</h4>
                        <ul class="cf-links">
                            <li><a href="<?php echo esc_url( home_url('/adatvedelmi-iranyelvek/') ); ?>">🔒 Adatvédelem</a></li>
                            <li><a href="<?php echo esc_url( home_url('/aszf/') ); ?>">📄 ÁSZF</a></li>
                            <li><a href="<?php echo esc_url( home_url('/impresszum/') ); ?>">ℹ️ Impresszum</a></li>
                            <li><a href="<?php echo esc_url( home_url('/suti-szabalyzat/') ); ?>">🍪 Süti szabályzat</a></li>
                        </ul>
                    </div>
                    <div class="cf-col">
                        <h4 class="cf-col-title">Adatforrások</h4>
                        <ul class="cf-links cf-links-sources">
                            <li><a href="https://world.openfoodfacts.org" target="_blank" rel="noopener noreferrer">Open Food Facts <span class="cf-badge">ODbL</span></a></li>
                            <li><a href="https://fdc.nal.usda.gov/" target="_blank" rel="noopener noreferrer">USDA FoodData Central <span class="cf-badge cf-badge-blue">CC0</span></a></li>
                        </ul>
                        <p class="cf-source-note">A tápérték adatok az eredeti forrásokhoz képest módosítva lettek.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="cf-bottom">
            <div class="cf-container">
                <span class="cf-copyright">© <?php echo $year; ?> TápanyagLexikon. Minden jog fenntartva.</span>
                <span class="cf-made">Remélem tetszik az oldal!</span>
            </div>
        </div>
    </footer>
    <button id="ch-back-to-top" class="ch-back-to-top" aria-label="Vissza a tetejére" title="Vissza a tetejére">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
    </button>
    <?php
}

/* ============================================================
   6. KADENCE ELREJTÉS (CSS fallback)
   ============================================================ */
add_action( 'wp_head', 'custom_header_hide_kadence', 1 );
function custom_header_hide_kadence() {
    echo '<style>
        .site-header-wrap,header.site-header,.kadence-header,#masthead,
        .site-footer-wrap,footer.site-footer,.kadence-footer,#colophon{display:none!important}
    </style>';
}

/* ============================================================
   7. STÍLUSOK
   ============================================================ */
add_action( 'wp_head', 'custom_header_footer_css', 20 );
function custom_header_footer_css() {
    ?>
    <style id="custom-header-footer-css">

    :root {
        --ch-height: 72px;
        --ch-height-shrink: 58px;
        --ch-bg: rgba(255,255,255,0.92);
        --ch-bg-shrink: rgba(255,255,255,0.97);
        --ch-border: #e8ebe9;
        --ch-text: #1e1e1e;
        --ch-text-muted: #7c8a83;
        --ch-accent: #2d6a4f;
        --ch-accent-light: rgba(45,106,79,0.08);
        --ch-accent-glow: rgba(45,106,79,0.18);
        --ch-radius: 12px;
        --ch-radius-sm: 8px;
        --ch-transition: 0.25s cubic-bezier(0.4,0,0.2,1);
        --ch-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 24px rgba(0,0,0,0.03);
        --ch-shadow-shrink: 0 2px 12px rgba(0,0,0,0.06), 0 8px 32px rgba(0,0,0,0.04);
        --cf-bg: #1a2e23;
        --cf-bg-light: #223a2d;
        --cf-text: #c8d6ce;
        --cf-text-muted: #7c9a88;
        --cf-heading: #e8f0eb;
        --cf-accent: #40b882;
        --cf-accent-soft: rgba(64,184,130,0.12);
        --cf-border: rgba(255,255,255,0.06);
        --cf-bottom-bg: #142219;
    }

    /* === HEADER === */
    .ch-header {
        position: fixed; top: 0; left: 0; width: 100%;
        height: var(--ch-height);
        background: var(--ch-bg);
        backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
        border-bottom: 1px solid var(--ch-border);
        z-index: 9999;
        transition: height var(--ch-transition), background var(--ch-transition), box-shadow var(--ch-transition);
    }
    .ch-header.is-scrolled {
        height: var(--ch-height-shrink);
        background: var(--ch-bg-shrink);
        box-shadow: var(--ch-shadow-shrink);
    }
    body { padding-top: var(--ch-height) !important; transition: padding-top var(--ch-transition); }
    body.ch-scrolled { padding-top: var(--ch-height-shrink) !important; }
    .admin-bar .ch-header { top: 32px; }
    @media (max-width: 782px) { .admin-bar .ch-header { top: 46px; } }

    .ch-container {
        max-width: 1280px; margin: 0 auto; padding: 0 24px;
        height: 100%; display: flex; align-items: center; justify-content: space-between;
    }

    /* Logo */
    .ch-logo { display: flex; align-items: center; gap: 10px; text-decoration: none; flex-shrink: 0; }
    .ch-logo-icon { font-size: 26px; line-height: 1; transition: transform var(--ch-transition); }
    .ch-logo:hover .ch-logo-icon { transform: rotate(-12deg) scale(1.1); }
    .ch-logo-text { font-size: 20px; font-weight: 700; color: var(--ch-text); letter-spacing: -0.3px; line-height: 1.2; }
    .ch-logo-accent { color: var(--ch-accent); }

    /* Desktop nav */
    .ch-nav { display: flex; align-items: center; }
    .ch-menu { list-style: none; margin: 0; padding: 0; display: flex; align-items: center; gap: 4px; }
    .ch-menu-item { position: relative; }
    .ch-menu-link {
        display: flex; align-items: center; gap: 4px; padding: 8px 16px;
        font-size: 14.5px; font-weight: 500; color: var(--ch-text-muted);
        text-decoration: none; border-radius: var(--ch-radius-sm); white-space: nowrap;
        transition: color var(--ch-transition), background var(--ch-transition);
    }
    .ch-menu-link:hover { color: var(--ch-accent); background: var(--ch-accent-light); }
    .ch-menu-item.is-active > .ch-menu-link,
    .ch-menu-item.current-menu-item > .ch-menu-link,
    .ch-menu-item.current-menu-ancestor > .ch-menu-link {
        color: var(--ch-accent); background: var(--ch-accent-light); font-weight: 600;
    }
    .ch-menu-item.is-active > .ch-menu-link::after,
    .ch-menu-item.current-menu-item > .ch-menu-link::after {
        content: ''; position: absolute; bottom: -1px; left: 16px; right: 16px;
        height: 2px; background: var(--ch-accent); border-radius: 2px;
    }
    .ch-chevron { transition: transform var(--ch-transition); opacity: 0.5; }
    .ch-menu-item.has-submenu:hover .ch-chevron { transform: rotate(180deg); opacity: 1; }

    /* Desktop dropdown */
    .ch-submenu {
        position: absolute; top: calc(100% + 8px); left: 0; min-width: 200px;
        background: #fff; border: 1px solid var(--ch-border); border-radius: var(--ch-radius);
        box-shadow: 0 12px 40px rgba(0,0,0,0.08); padding: 8px; list-style: none;
        opacity: 0; visibility: hidden; transform: translateY(-8px);
        transition: opacity var(--ch-transition), transform var(--ch-transition), visibility var(--ch-transition);
    }
    .ch-menu-item.has-submenu:hover > .ch-submenu { opacity: 1; visibility: visible; transform: translateY(0); }
    .ch-submenu-link {
        display: block; padding: 10px 14px; font-size: 14px; font-weight: 450;
        color: var(--ch-text); text-decoration: none; border-radius: var(--ch-radius-sm);
        transition: color var(--ch-transition), background var(--ch-transition);
    }
    .ch-submenu-link:hover { color: var(--ch-accent); background: var(--ch-accent-light); }
    .ch-submenu-item.is-active .ch-submenu-link { color: var(--ch-accent); font-weight: 600; }

    /* Hamburger */
    .ch-hamburger {
        display: none; flex-direction: column; justify-content: center; align-items: center;
        width: 44px; height: 44px; padding: 0; background: transparent; border: none;
        cursor: pointer; gap: 5px; border-radius: var(--ch-radius-sm);
        transition: background var(--ch-transition);
    }
    .ch-hamburger:hover { background: var(--ch-accent-light); }
    .ch-hamburger-line {
        display: block; width: 22px; height: 2px; background: var(--ch-text);
        border-radius: 2px; transition: transform var(--ch-transition), opacity var(--ch-transition);
    }
    .ch-hamburger.is-open .ch-hamburger-line:nth-child(1) { transform: translateY(7px) rotate(45deg); }
    .ch-hamburger.is-open .ch-hamburger-line:nth-child(2) { opacity: 0; }
    .ch-hamburger.is-open .ch-hamburger-line:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

    /* === MOBIL OVERLAY === */
    .ch-mobile-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%; height: 100dvh;
        background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);
        z-index: 10000; opacity: 0; visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }
    .ch-mobile-overlay.is-open { opacity: 1; visibility: visible; }

    /* === MOBIL PANEL === */
    .ch-mobile-panel {
        position: absolute; top: 0; right: 0;
        width: 300px; max-width: 85vw; height: 100%; height: 100dvh;
        background: #fff; box-shadow: -8px 0 40px rgba(0,0,0,0.1);
        display: flex; flex-direction: column; overflow: hidden;
        transform: translateX(100%);
        transition: transform 0.35s cubic-bezier(0.4,0,0.2,1);
    }
    .ch-mobile-overlay.is-open .ch-mobile-panel { transform: translateX(0); }

    /* Mobil header */
    .ch-mobile-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 14px 18px; border-bottom: 1px solid var(--ch-border);
        flex-shrink: 0; min-height: 56px;
    }
    .ch-mobile-header .ch-logo-icon { font-size: 22px; }
    .ch-mobile-header .ch-logo-text { font-size: 16px; }

    /* Mobil bezárás gomb */
    .ch-mobile-close {
        display: flex; align-items: center; justify-content: center;
        width: 36px; height: 36px; border: none; background: transparent;
        border-radius: var(--ch-radius-sm); cursor: pointer;
        color: var(--ch-text-muted); flex-shrink: 0;
        transition: color var(--ch-transition), background var(--ch-transition);
    }
    .ch-mobile-close:hover { color: var(--ch-text); background: #f5f6f5; }

    /* === MOBIL NAV (a kulcs javítás) === */
    .ch-mobile-nav {
        flex: 1 1 auto;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        padding: 12px 10px;
        min-height: 0;
    }

    .ch-mobile-menu {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .ch-mobile-item {
        width: 100%;
        flex-shrink: 0;
    }

    .ch-mobile-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 13px 16px;
        font-size: 15px;
        font-weight: 500;
        color: var(--ch-text);
        text-decoration: none;
        border-radius: var(--ch-radius);
        width: 100%;
        transition: color var(--ch-transition), background var(--ch-transition);
    }
    .ch-mobile-link:hover {
        background: var(--ch-accent-light);
        color: var(--ch-accent);
    }
    .ch-mobile-item.is-active .ch-mobile-link,
    .ch-mobile-item.current-menu-item .ch-mobile-link {
        background: var(--ch-accent-light);
        color: var(--ch-accent);
        font-weight: 600;
    }

    .ch-mobile-icon {
        font-size: 18px;
        line-height: 1;
        width: 24px;
        text-align: center;
        flex-shrink: 0;
    }

    /* === MOBIL FOOTER (social) === */
    .ch-mobile-footer {
        padding: 14px 18px;
        border-top: 1px solid var(--ch-border);
        flex-shrink: 0;
    }
    .ch-mobile-social {
        display: flex;
        gap: 10px;
        justify-content: center;
    }
    .ch-social-link {
        display: flex; align-items: center; justify-content: center;
        width: 38px; height: 38px; border-radius: 50%;
        background: #f5f6f5; color: var(--ch-text-muted);
        text-decoration: none; flex-shrink: 0;
        transition: color var(--ch-transition), background var(--ch-transition), transform var(--ch-transition);
    }
    .ch-social-link:hover {
        background: var(--ch-accent-light); color: var(--ch-accent); transform: translateY(-2px);
    }

    /* === FOOTER === */
    .cf-footer { margin-top: 60px; }
    .cf-main { background: var(--cf-bg); padding: 60px 0 40px; }
    .cf-container { max-width: 1280px; margin: 0 auto; padding: 0 24px; }
    .cf-grid { display: grid; grid-template-columns: 1.4fr 1fr 1fr 1fr; gap: 48px; }
    .cf-logo { display: inline-flex; align-items: center; gap: 10px; text-decoration: none; margin-bottom: 16px; }
    .cf-logo-icon { font-size: 24px; }
    .cf-logo-text { font-size: 18px; font-weight: 700; color: var(--cf-heading); }
    .cf-logo-accent { color: var(--cf-accent); }
    .cf-description { font-size: 13.5px; line-height: 1.7; color: var(--cf-text-muted); margin: 0 0 20px; max-width: 280px; }
    .cf-social { display: flex; gap: 10px; }
    .cf-social-link {
        display: flex; align-items: center; justify-content: center;
        width: 38px; height: 38px; border-radius: 10px;
        background: var(--cf-bg-light); border: 1px solid var(--cf-border);
        color: var(--cf-text-muted); text-decoration: none;
        transition: color var(--ch-transition), background var(--ch-transition), border-color var(--ch-transition), transform var(--ch-transition);
    }
    .cf-social-link:hover {
        color: var(--cf-accent); background: var(--cf-accent-soft);
        border-color: var(--cf-accent); transform: translateY(-2px);
    }
    .cf-col-title {
        font-size: 13px; font-weight: 700; color: var(--cf-heading);
        text-transform: uppercase; letter-spacing: 1px; margin: 0 0 20px;
        padding-bottom: 12px; border-bottom: 2px solid var(--cf-accent); display: inline-block;
    }
    .cf-links { list-style: none; margin: 0; padding: 0; }
    .cf-links li { margin-bottom: 4px; }
    .cf-links a {
        display: inline-flex; align-items: center; gap: 6px; padding: 7px 10px;
        font-size: 14px; color: var(--cf-text); text-decoration: none;
        border-radius: var(--ch-radius-sm);
        transition: color var(--ch-transition), background var(--ch-transition), padding-left var(--ch-transition);
    }
    .cf-links a:hover { color: var(--cf-accent); background: var(--cf-accent-soft); padding-left: 14px; }
    .cf-badge {
        display: inline-block; font-size: 10px; font-weight: 700; padding: 2px 7px;
        border-radius: 6px; background: rgba(245,158,11,0.15); color: #F59E0B;
        letter-spacing: 0.5px; margin-left: 6px; vertical-align: middle;
    }
    .cf-badge-blue { background: rgba(59,130,246,0.15); color: #60A5FA; }
    .cf-source-note { font-size: 11.5px; color: var(--cf-text-muted); margin-top: 16px; font-style: italic; line-height: 1.5; opacity: 0.7; }
    .cf-bottom { background: var(--cf-bottom-bg); padding: 16px 0; border-top: 1px solid var(--cf-border); }
    .cf-bottom .cf-container { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
    .cf-copyright { font-size: 13px; color: var(--cf-text-muted); }
    .cf-made { font-size: 12px; color: var(--cf-text-muted); opacity: 0.6; }

    /* === VISSZA A TETEJÉRE === */
    .ch-back-to-top {
        position: fixed; bottom: 28px; right: 28px; width: 46px; height: 46px;
        display: flex; align-items: center; justify-content: center;
        background: var(--ch-accent); color: #fff; border: none; border-radius: 14px;
        cursor: pointer; box-shadow: 0 4px 20px rgba(45,106,79,0.3); z-index: 9998;
        opacity: 0; visibility: hidden; transform: translateY(16px);
        transition: opacity var(--ch-transition), visibility var(--ch-transition), transform var(--ch-transition), background var(--ch-transition), box-shadow var(--ch-transition);
    }
    .ch-back-to-top.is-visible { opacity: 1; visibility: visible; transform: translateY(0); }
    .ch-back-to-top:hover { background: #245a42; box-shadow: 0 6px 28px rgba(45,106,79,0.4); transform: translateY(-3px); }

    /* === RESPONSIVE === */
    @media (max-width: 960px) {
        .ch-nav { display: none; }
        .ch-hamburger { display: flex; }
        .cf-grid { grid-template-columns: 1fr 1fr; gap: 36px; }
    }
    @media (max-width: 600px) {
        :root { --ch-height: 62px; --ch-height-shrink: 52px; }
        .ch-container { padding: 0 16px; }
        .ch-logo-text { font-size: 17px; }
        .ch-logo-icon { font-size: 22px; }
        .cf-main { padding: 40px 0 32px; }
        .cf-grid { grid-template-columns: 1fr; gap: 32px; }
        .cf-description { max-width: 100%; }
        .cf-bottom .cf-container { flex-direction: column; text-align: center; }
        .ch-back-to-top { bottom: 20px; right: 20px; width: 42px; height: 42px; border-radius: 12px; }
        .ch-mobile-header { padding: 12px 16px; min-height: 52px; }
        .ch-mobile-link { padding: 12px 14px; font-size: 14.5px; }
        .ch-mobile-icon { font-size: 17px; }
        .ch-mobile-footer { padding: 12px 16px; }
        .ch-social-link { width: 36px; height: 36px; }
    }
    @media (max-width: 380px) {
        .ch-mobile-header .ch-logo-text { font-size: 14px; }
        .ch-mobile-link { padding: 10px 12px; font-size: 14px; gap: 10px; }
        .ch-mobile-nav { padding: 8px; }
    }

    @keyframes chFadeInUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
    .cf-col { animation: chFadeInUp 0.5s ease both; }
    .cf-col:nth-child(2) { animation-delay: 0.08s; }
    .cf-col:nth-child(3) { animation-delay: 0.16s; }
    .cf-col:nth-child(4) { animation-delay: 0.24s; }

    </style>
    <?php
}

/* ============================================================
   8. JAVASCRIPT
   ============================================================ */
add_action( 'wp_footer', 'custom_header_footer_js', 99 );
function custom_header_footer_js() {
    ?>
    <script id="custom-header-footer-js">
    (function() {
        'use strict';
        var header = document.getElementById('custom-header');
        var hamburger = document.getElementById('ch-hamburger');
        var overlay = document.getElementById('ch-mobile-overlay');
        var closeBtn = document.getElementById('ch-mobile-close');
        var backToTop = document.getElementById('ch-back-to-top');
        if (!header) return;

        function handleScroll() {
            var y = window.scrollY || window.pageYOffset;
            if (y > 20) {
                header.classList.add('is-scrolled');
                document.body.classList.add('ch-scrolled');
            } else {
                header.classList.remove('is-scrolled');
                document.body.classList.remove('ch-scrolled');
            }
            if (backToTop) {
                if (y > 400) backToTop.classList.add('is-visible');
                else backToTop.classList.remove('is-visible');
            }
        }
        window.addEventListener('scroll', handleScroll, { passive: true });
        handleScroll();

        function openMobile() {
            if (!overlay) return;
            overlay.classList.add('is-open');
            if (hamburger) hamburger.classList.add('is-open');
            if (hamburger) hamburger.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
        }
        function closeMobile() {
            if (!overlay) return;
            overlay.classList.remove('is-open');
            if (hamburger) hamburger.classList.remove('is-open');
            if (hamburger) hamburger.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
        }
        if (hamburger) {
            hamburger.addEventListener('click', function() {
                overlay.classList.contains('is-open') ? closeMobile() : openMobile();
            });
        }
        if (closeBtn) closeBtn.addEventListener('click', closeMobile);
        if (overlay) {
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) closeMobile();
            });
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && overlay && overlay.classList.contains('is-open')) closeMobile();
        });
        if (backToTop) {
            backToTop.addEventListener('click', function() {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }
        var mobileLinks = document.querySelectorAll('.ch-mobile-link');
        mobileLinks.forEach(function(link) {
            link.addEventListener('click', function() { setTimeout(closeMobile, 150); });
        });
    })();
    </script>
    <?php
}
