<?php

/**
 * 28 – Header + Footer (CSAK a vizuális rész + JS)
 */
/**
 * 28 – Header + Footer (CSAK a vizuális rész + JS)
 * v2.0 – Céljaim menüpont hozzáadva
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* ── Kadence eltávolítás ── */
add_action( 'after_setup_theme', function() {
    remove_action( 'wp_body_open', 'Kadence\header_markup', 0 );
    remove_action( 'wp_body_open', 'Kadence\header_markup' );
}, 999 );

add_action( 'wp', function() {
    remove_action( 'kadence_header',        'Kadence\header_markup' );
    remove_action( 'kadence_before_header',  'Kadence\header_markup' );
    remove_action( 'kadence_footer',         'Kadence\footer_markup' );
    remove_action( 'kadence_before_footer',  'Kadence\footer_markup' );
    remove_action( 'kadence_after_footer',   'Kadence\footer_markup' );
    foreach ( array( 'kadence_footer', 'kadence_before_footer', 'kadence_after_footer' ) as $h ) {
        remove_all_actions( $h );
    }
}, 999 );

add_action( 'wp_head', function() {
    echo '<style>.site-header-wrap,header.site-header,.kadence-header,#masthead,.site-footer-wrap,footer.site-footer,.kadence-footer,#colophon{display:none!important}</style>';
}, 1 );

/* ── Menü regisztrálás ── */
add_action( 'after_setup_theme', function() {
    register_nav_menus( array( 'custom_header_menu' => 'Custom Header Menü' ) );
}, 5 );

/* ── Menü itemek (Céljaim NÉLKÜL – az külön renderelődik) ── */
function dp27_menu_items() {
    return array(
        array( 'label' => 'Főoldal',            'url' => home_url( '/' ),                'cpt' => '',          'svg' => '<path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5z"/><polyline points="9 21 9 14 15 14 15 21"/>' ),
        array( 'label' => 'Receptek',            'url' => home_url( '/receptek/' ),       'cpt' => 'recept',    'svg' => '<path d="M12 2a5 5 0 0 1 5 5c0 2-1.5 3.5-3 4.5V14H10v-2.5C8.5 10.5 7 9 7 7a5 5 0 0 1 5-5z"/><path d="M10 14h4v2a2 2 0 0 1-4 0v-2z"/><line x1="8" y1="20" x2="16" y2="20"/><line x1="9" y1="22" x2="15" y2="22"/>' ),
        array( 'label' => 'Alapanyag-adatbázis', 'url' => home_url( '/alapanyagok/' ),    'cpt' => 'alapanyag', 'svg' => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/><line x1="11" y1="8" x2="11" y2="14"/>' ),
        array( 'label' => 'Tápanyag lexikon',    'url' => home_url( '/tapanyagok/' ),     'cpt' => 'tapanyag',  'svg' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/><line x1="9" y1="7" x2="16" y2="7"/><line x1="9" y1="11" x2="14" y2="11"/>' ),
        array( 'label' => 'Kalória kalkulátor',  'url' => home_url( '/bmr-kalkulator/' ), 'cpt' => '',          'svg' => '<rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="10" x2="10" y2="10"/><line x1="12" y1="10" x2="14" y2="10"/><line x1="8" y1="14" x2="10" y2="14"/><line x1="12" y1="14" x2="14" y2="14"/><line x1="8" y1="18" x2="14" y2="18"/>' ),
    );
}

function dp27_is_active( $url, $cpt ) {
    if ( $cpt && ( is_singular( $cpt ) || is_post_type_archive( $cpt ) ) ) return true;
    $current = trailingslashit( strtok( $_SERVER['REQUEST_URI'], '?' ) );
    $target  = trailingslashit( wp_parse_url( $url, PHP_URL_PATH ) ? wp_parse_url( $url, PHP_URL_PATH ) : '/' );
    return $current === $target;
}

/* ── Céljaim active check ── */
function dp27_is_celjaim_active() {
    $current = trailingslashit( strtok( $_SERVER['REQUEST_URI'], '?' ) );
    return $current === '/celjaim/';
}

/* ── Fallback menü desktop ── */
function dp27_fallback_desktop() {
    $items = dp27_menu_items();
    echo '<ul class="ch-menu">';
    foreach ( $items as $item ) {
        $a = dp27_is_active( $item['url'], $item['cpt'] );
        echo '<li class="ch-menu-item' . ( $a ? ' is-active' : '' ) . '"><a href="' . esc_url( $item['url'] ) . '" class="ch-menu-link">' . esc_html( $item['label'] ) . '</a></li>';
    }
    /* Céljaim – kiemelt pill */
    $ca = dp27_is_celjaim_active();
    echo '<li class="ch-menu-item ch-menu-item--celjaim' . ( $ca ? ' is-active' : '' ) . '"><a href="' . esc_url( home_url( '/celjaim/' ) ) . '" class="ch-menu-link ch-menu-link--celjaim"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg> Céljaim</a></li>';
    echo '</ul>';
}

/* ── Fallback menü mobil ── */
function dp27_fallback_mobile() {
    $items = dp27_menu_items();
    echo '<ul class="ch-mobile-menu">';
    foreach ( $items as $item ) {
        $a = dp27_is_active( $item['url'], $item['cpt'] );
        echo '<li class="ch-mobile-item' . ( $a ? ' is-active' : '' ) . '"><a href="' . esc_url( $item['url'] ) . '" class="ch-mobile-link"><svg class="ch-mobile-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $item['svg'] . '</svg><span>' . esc_html( $item['label'] ) . '</span></a></li>';
    }
    /* Céljaim – kiemelt mobil */
    $ca = dp27_is_celjaim_active();
    echo '<li class="ch-mobile-item ch-mobile-item--celjaim' . ( $ca ? ' is-active' : '' ) . '"><a href="' . esc_url( home_url( '/celjaim/' ) ) . '" class="ch-mobile-link ch-mobile-link--celjaim"><svg class="ch-mobile-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg><span>Céljaim</span></a></li>';
    echo '</ul>';
}

/* ── Walker ── */
if ( ! class_exists( 'DP27_Walker' ) ) {
    class DP27_Walker extends Walker_Nav_Menu {
        function start_lvl( &$o, $d = 0, $a = null ) { $o .= '<ul class="ch-submenu">'; }
        function end_lvl( &$o, $d = 0, $a = null ) { $o .= '</ul>'; }
        function start_el( &$o, $item, $d = 0, $a = null, $id = 0 ) {
            $cl = (array) $item->classes;
            $act = in_array( 'current-menu-item', $cl ) || in_array( 'current-menu-ancestor', $cl );
            $hc = in_array( 'menu-item-has-children', $cl );
            if ( $d === 0 ) {
                $o .= '<li class="ch-menu-item' . ( $act ? ' is-active' : '' ) . ( $hc ? ' has-submenu' : '' ) . '">';
                $o .= '<a href="' . esc_url( $item->url ) . '" class="ch-menu-link">' . esc_html( $item->title );
                if ( $hc ) $o .= ' <svg class="ch-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>';
                $o .= '</a>';
            } else {
                $o .= '<li class="ch-submenu-item' . ( $act ? ' is-active' : '' ) . '"><a href="' . esc_url( $item->url ) . '" class="ch-submenu-link">' . esc_html( $item->title ) . '</a>';
            }
        }
        function end_el( &$o, $item, $d = 0, $a = null ) { $o .= '</li>'; }
    }
}

/* ── HEADER HTML ── */
add_action( 'wp_body_open', function() {
    ?>
    <header id="custom-header" class="ch-header">
        <div class="ch-container">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="ch-logo" aria-label="Főoldal">
                <span class="ch-logo-icon">🥗</span>
                <span class="ch-logo-text">Tápanyag<span class="ch-logo-accent">Lexikon</span></span>
            </a>
            <nav class="ch-nav" aria-label="Fő navigáció">
                <?php
                if ( has_nav_menu( 'custom_header_menu' ) ) {
                    wp_nav_menu( array( 'theme_location' => 'custom_header_menu', 'container' => false, 'menu_class' => 'ch-menu', 'depth' => 2, 'fallback_cb' => 'dp27_fallback_desktop', 'walker' => new DP27_Walker() ) );
                } else {
                    dp27_fallback_desktop();
                }
                ?>
            </nav>
            <div class="ch-header-actions" id="ch-header-actions"></div>
            <button class="ch-hamburger" id="ch-hamburger" aria-label="Menü" aria-expanded="false">
                <span class="ch-hamburger-line"></span>
                <span class="ch-hamburger-line"></span>
                <span class="ch-hamburger-line"></span>
            </button>
        </div>
    </header>
    <div class="ch-mobile-overlay" id="ch-mobile-overlay" aria-hidden="true">
        <div class="ch-mobile-panel" id="ch-mobile-panel">
            <div class="ch-mobile-header">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="ch-logo"><span class="ch-logo-icon">🥗</span><span class="ch-logo-text">Tápanyag<span class="ch-logo-accent">Lexikon</span></span></a>
                <button class="ch-mobile-close" id="ch-mobile-close" aria-label="Bezárás"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <nav class="ch-mobile-nav" aria-label="Mobil navigáció">
                <?php
                if ( has_nav_menu( 'custom_header_menu' ) ) {
                    wp_nav_menu( array( 'theme_location' => 'custom_header_menu', 'container' => false, 'menu_class' => 'ch-mobile-menu', 'depth' => 2, 'fallback_cb' => 'dp27_fallback_mobile' ) );
                } else {
                    dp27_fallback_mobile();
                }
                ?>
            </nav>
            <div class="ch-mobile-auth" id="ch-mobile-auth"></div>
            <div class="ch-mobile-footer">
                <div class="ch-mobile-social">
                    <a href="#" aria-label="Facebook" class="ch-social-link"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>
                    <a href="#" aria-label="Instagram" class="ch-social-link"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg></a>
                    <a href="#" aria-label="TikTok" class="ch-social-link"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg></a>
                </div>
            </div>
        </div>
    </div>
    <?php
}, 1 );

/* ── FOOTER HTML ── */
add_action( 'wp_footer', function() {
    $y = date( 'Y' );
    ?>
    <footer id="custom-footer" class="cf-footer">
        <div class="cf-main"><div class="cf-container"><div class="cf-grid">
            <div class="cf-col cf-col-about">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="cf-logo"><span class="cf-logo-icon">🥗</span><span class="cf-logo-text">Tápanyag<span class="cf-logo-accent">Lexikon</span></span></a>
                <p class="cf-description">Tudományosan megalapozott tápanyag adatbázis, receptek és táplálkozási kalkulátorok – egy helyen, magyarul.</p>
                <div class="cf-social">
                    <a href="#" class="cf-social-link" aria-label="Facebook"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>
                    <a href="#" class="cf-social-link" aria-label="Instagram"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg></a>
                    <a href="#" class="cf-social-link" aria-label="TikTok"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg></a>
                </div>
            </div>
            <div class="cf-col"><h4 class="cf-col-title">Tartalom</h4><ul class="cf-links"><li><a href="<?php echo esc_url( home_url( '/receptek/' ) ); ?>">🍳 Receptek</a></li><li><a href="<?php echo esc_url( home_url( '/alapanyagok/' ) ); ?>">🥕 Alapanyag-adatbázis</a></li><li><a href="<?php echo esc_url( home_url( '/tapanyagok/' ) ); ?>">🧬 Tápanyag lexikon</a></li><li><a href="<?php echo esc_url( home_url( '/bmr-kalkulator/' ) ); ?>">📊 Kalória kalkulátor</a></li></ul></div>
            <div class="cf-col"><h4 class="cf-col-title">Jogi információk</h4><ul class="cf-links"><li><a href="<?php echo esc_url( home_url( '/adatvedelmi-iranyelvek/' ) ); ?>">🔒 Adatvédelem</a></li><li><a href="<?php echo esc_url( home_url( '/aszf/' ) ); ?>">📄 ÁSZF</a></li><li><a href="<?php echo esc_url( home_url( '/impresszum/' ) ); ?>">ℹ️ Impresszum</a></li><li><a href="<?php echo esc_url( home_url( '/suti-szabalyzat/' ) ); ?>">🍪 Süti szabályzat</a></li></ul></div>
            <div class="cf-col"><h4 class="cf-col-title">Adatforrások</h4><ul class="cf-links cf-links-sources"><li><a href="https://world.openfoodfacts.org" target="_blank" rel="noopener noreferrer">Open Food Facts <span class="cf-badge">ODbL</span></a></li><li><a href="https://fdc.nal.usda.gov/" target="_blank" rel="noopener noreferrer">USDA FoodData Central <span class="cf-badge cf-badge-blue">CC0</span></a></li></ul><p class="cf-source-note">A tápérték adatok az eredeti forrásokhoz képest módosítva lettek.</p></div>
        </div></div></div>
        <div class="cf-bottom"><div class="cf-container"><span class="cf-copyright">© <?php echo $y; ?> TápanyagLexikon. Minden jog fenntartva.</span><span class="cf-made">Remélem tetszik az oldal!</span></div></div>
    </footer>
    <button id="ch-back-to-top" class="ch-back-to-top" aria-label="Vissza a tetejére"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg></button>
    <?php
}, 10 );

/* ── JS ── */
add_action( 'wp_footer', function() {
    ?>
    <script>
    (function(){
    var h=document.getElementById('custom-header'),hb=document.getElementById('ch-hamburger'),ov=document.getElementById('ch-mobile-overlay'),pn=document.getElementById('ch-mobile-panel'),cb=document.getElementById('ch-mobile-close'),bt=document.getElementById('ch-back-to-top');
    if(!h)return;
    var tk=false;
    function hs(){var y=window.scrollY||window.pageYOffset;h.classList.toggle('is-scrolled',y>20);document.body.classList.toggle('ch-scrolled',y>20);if(bt)bt.classList.toggle('is-visible',y>400);tk=false}
    window.addEventListener('scroll',function(){if(!tk){requestAnimationFrame(hs);tk=true}},{passive:true});hs();
    function om(){if(!ov)return;ov.classList.add('is-open');ov.setAttribute('aria-hidden','false');if(hb){hb.classList.add('is-open');hb.setAttribute('aria-expanded','true')}document.body.style.overflow='hidden'}
    function cm(){if(!ov)return;ov.classList.remove('is-open');ov.setAttribute('aria-hidden','true');if(hb){hb.classList.remove('is-open');hb.setAttribute('aria-expanded','false');hb.blur()}document.body.style.overflow=''}
    if(hb)hb.addEventListener('click',function(){ov&&ov.classList.contains('is-open')?cm():om()});
    if(cb)cb.addEventListener('click',cm);
    if(ov)ov.addEventListener('click',function(e){if(pn&&!pn.contains(e.target))cm()});
    document.addEventListener('keydown',function(e){if(e.key==='Escape'&&ov&&ov.classList.contains('is-open'))cm()});
    if(bt)bt.addEventListener('click',function(){window.scrollTo({top:0,behavior:'smooth'})});
    document.querySelectorAll('.ch-mobile-link').forEach(function(l){l.addEventListener('click',function(){setTimeout(cm,150)})});
    })();
    </script>
    <?php
}, 99 );
