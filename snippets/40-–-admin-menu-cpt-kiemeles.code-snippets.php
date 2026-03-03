<?php

/**
 * 40 – Admin menü CPT kiemelés és rendezés
 *
 * - A 3 CPT (Receptek, Alapanyagok, Tápanyagok) egymás után,
 *   pozíció 20–22-n jelenik meg az admin menüben.
 * - Elválasztók a csoport előtt és után.
 * - Vizuális kiemelés (border-left) a 3 CPT menüpontnál.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ── 1. MENÜ POZÍCIÓK EGYEDI RENDEZÉSE ────────────────────────
add_filter( 'custom_menu_order', '__return_true' );
add_filter( 'menu_order', 'dietitian_cpt_menu_order' );
function dietitian_cpt_menu_order( $menu_ord ) {
    if ( ! $menu_ord ) return true;

    $cpt_slugs = [
        'edit.php?post_type=recept',
        'edit.php?post_type=alapanyag',
        'edit.php?post_type=tapanyag',
    ];

    // Távolítsuk el a 3 CPT-t az eredeti helyéről
    foreach ( $cpt_slugs as $slug ) {
        $key = array_search( $slug, $menu_ord, true );
        if ( false !== $key ) {
            unset( $menu_ord[ $key ] );
        }
    }
    $menu_ord = array_values( $menu_ord );

    // Az upload.php (Médiatár, ~10) utáni pozíció megkeresése
    $upload_pos = array_search( 'upload.php', $menu_ord, true );
    $insert_at  = ( false !== $upload_pos ) ? $upload_pos + 1 : 3;

    // Elválasztók és a 3 CPT beillesztése
    $inject = [
        'separator-cpt-before',
        'edit.php?post_type=recept',
        'edit.php?post_type=alapanyag',
        'edit.php?post_type=tapanyag',
        'separator-cpt-after',
    ];

    array_splice( $menu_ord, $insert_at, 0, $inject );

    return $menu_ord;
}

// ── 2. ELVÁLASZTÓK REGISZTRÁLÁSA ─────────────────────────────
add_filter( 'menu_order', 'dietitian_register_cpt_separators', 9 );
// A WordPress natív separator CSS-t alkalmaz az 'wp-menu-separator' class alapján.
// Az egyedi elválasztókat a wp_add_nav_menu_item helyett admin_menu-n regisztráljuk.
add_action( 'admin_menu', 'dietitian_add_cpt_separators' );
function dietitian_add_cpt_separators() {
    global $menu;
    // Elválasztó a csoport előtt (pozíció 19)
    $menu[19] = [ '', 'read', 'separator-cpt-before', '', 'wp-menu-separator' ];
    // Elválasztó a csoport után (pozíció 23)
    $menu[23] = [ '', 'read', 'separator-cpt-after', '', 'wp-menu-separator' ];
}

// ── 3. VIZUÁLIS KIEMELÉS – ADMIN CSS ─────────────────────────
add_action( 'admin_head', 'dietitian_cpt_admin_menu_css' );
function dietitian_cpt_admin_menu_css() {
    ?>
    <style>
        /* Dietitian CPT csoport – elegáns bal oldali szegély */
        #adminmenu li.menu-top[class*="toplevel_page_"] a[href*="post_type=recept"],
        #adminmenu li.menu-top a[href="edit.php?post_type=recept"],
        #adminmenu li.menu-top a[href="edit.php?post_type=alapanyag"],
        #adminmenu li.menu-top a[href="edit.php?post_type=tapanyag"] {
            /* Nincs szükség extra stílusra, a konténerre alkalmazzuk */
        }

        /* Szülő <li> elemek stílusozása */
        #adminmenu #toplevel_page_edit-php-post_type-recept,
        #adminmenu #toplevel_page_edit-php-post_type-alapanyag,
        #adminmenu #toplevel_page_edit-php-post_type-tapanyag {
            border-left: 3px solid #2d6a4f;
            background-color: rgba(45, 106, 79, 0.04);
        }

        #adminmenu #toplevel_page_edit-php-post_type-recept:hover,
        #adminmenu #toplevel_page_edit-php-post_type-alapanyag:hover,
        #adminmenu #toplevel_page_edit-php-post_type-tapanyag:hover,
        #adminmenu #toplevel_page_edit-php-post_type-recept.wp-has-current-submenu,
        #adminmenu #toplevel_page_edit-php-post_type-alapanyag.wp-has-current-submenu,
        #adminmenu #toplevel_page_edit-php-post_type-tapanyag.wp-has-current-submenu {
            border-left-color: #1b4332;
            background-color: rgba(45, 106, 79, 0.10);
        }
    </style>
    <?php
}
