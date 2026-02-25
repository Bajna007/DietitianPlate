<?php

/**
 * 11 – Tápanyag CPT + taxonómiák
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ============================================================
// 11 – TÁPANYAG CPT + TAXONÓMIÁK
// ============================================================

// ── 1. CUSTOM POST TYPE ──────────────────────────────────────
function tapanyag_cpt_regisztralas() {
    $labels = [
        'name'               => 'Tápanyagok',
        'singular_name'      => 'Tápanyag',
        'menu_name'          => 'Tápanyagok',
        'add_new'            => 'Új tápanyag',
        'add_new_item'       => 'Új tápanyag hozzáadása',
        'edit_item'          => 'Tápanyag szerkesztése',
        'new_item'           => 'Új tápanyag',
        'view_item'          => 'Tápanyag megtekintése',
        'search_items'       => 'Tápanyag keresése',
        'not_found'          => 'Nem található tápanyag',
        'not_found_in_trash' => 'Nincs tápanyag a kukában',
        'all_items'          => 'Összes tápanyag',
    ];

    $args = [
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => 'tapanyagok',
        'rewrite'            => [ 'slug' => 'tapanyag', 'with_front' => false ],
        'supports'           => [ 'title', 'thumbnail', 'excerpt', 'revisions' ],
        'menu_icon'          => 'dashicons-carrot',
        'menu_position'      => 6,
        'show_in_rest'       => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'capability_type'    => 'post',
    ];

    register_post_type( 'tapanyag', $args );
}
add_action( 'init', 'tapanyag_cpt_regisztralas' );


// ── 2. TÁPANYAG TÍPUS (hierarchikus – kategória-szerű) ───────
function tapanyag_tipus_regisztralas() {
    $labels = [
        'name'              => 'Típus',
        'singular_name'     => 'Típus',
        'search_items'      => 'Típus keresése',
        'all_items'         => 'Összes típus',
        'parent_item'       => 'Szülő típus',
        'parent_item_colon' => 'Szülő típus:',
        'edit_item'         => 'Típus szerkesztése',
        'update_item'       => 'Típus frissítése',
        'add_new_item'      => 'Új típus hozzáadása',
        'new_item_name'     => 'Új típus neve',
        'menu_name'         => 'Típus',
    ];

    register_taxonomy( 'tapanyag_tipus', 'tapanyag', [
        'labels'            => $labels,
        'hierarchical'      => true,
        'public'            => true,
        'show_ui'           => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'rewrite'           => [ 'slug' => 'tapanyag-tipus' ],
    ]);
}
add_action( 'init', 'tapanyag_tipus_regisztralas' );


// ── 3. OLDHATÓSÁG (lapos) ────────────────────────────────────
function tapanyag_oldhatosag_regisztralas() {
    $labels = [
        'name'          => 'Oldhatóság',
        'singular_name' => 'Oldhatóság',
        'all_items'     => 'Összes oldhatóság',
        'edit_item'     => 'Oldhatóság szerkesztése',
        'add_new_item'  => 'Új oldhatóság',
        'menu_name'     => 'Oldhatóság',
    ];

    register_taxonomy( 'oldhatosag', 'tapanyag', [
        'labels'            => $labels,
        'hierarchical'      => false,
        'public'            => true,
        'show_ui'           => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'rewrite'           => [ 'slug' => 'oldhatosag' ],
    ]);
}
add_action( 'init', 'tapanyag_oldhatosag_regisztralas' );


// ── 4. CSOPORT (lapos) ───────────────────────────────────────
function tapanyag_csoport_regisztralas() {
    $labels = [
        'name'          => 'Csoport',
        'singular_name' => 'Csoport',
        'all_items'     => 'Összes csoport',
        'edit_item'     => 'Csoport szerkesztése',
        'add_new_item'  => 'Új csoport',
        'menu_name'     => 'Csoport',
    ];

    register_taxonomy( 'tapanyag_csoport', 'tapanyag', [
        'labels'            => $labels,
        'hierarchical'      => false,
        'public'            => true,
        'show_ui'           => true,
        'show_in_rest'      => true,
        'show_admin_column' => false,
        'rewrite'           => [ 'slug' => 'tapanyag-csoport' ],
    ]);
}
add_action( 'init', 'tapanyag_csoport_regisztralas' );


// ── 5. HATÁS (lapos – tag-szerű) ─────────────────────────────
function tapanyag_hatas_regisztralas() {
    $labels = [
        'name'          => 'Hatás',
        'singular_name' => 'Hatás',
        'all_items'     => 'Összes hatás',
        'edit_item'     => 'Hatás szerkesztése',
        'add_new_item'  => 'Új hatás',
        'menu_name'     => 'Hatás',
    ];

    register_taxonomy( 'tapanyag_hatas', 'tapanyag', [
        'labels'            => $labels,
        'hierarchical'      => false,
        'public'            => true,
        'show_ui'           => true,
        'show_in_rest'      => true,
        'show_admin_column' => false,
        'rewrite'           => [ 'slug' => 'tapanyag-hatas' ],
    ]);
}
add_action( 'init', 'tapanyag_hatas_regisztralas' );


// ── 6. ESSZENCIALITÁS (lapos) ────────────────────────────────
function tapanyag_esszencialis_regisztralas() {
    $labels = [
        'name'          => 'Esszencialitás',
        'singular_name' => 'Esszencialitás',
        'all_items'     => 'Összes',
        'edit_item'     => 'Szerkesztés',
        'add_new_item'  => 'Új hozzáadása',
        'menu_name'     => 'Esszencialitás',
    ];

    register_taxonomy( 'esszencialis', 'tapanyag', [
        'labels'            => $labels,
        'hierarchical'      => false,
        'public'            => true,
        'show_ui'           => true,
        'show_in_rest'      => true,
        'show_admin_column' => false,
        'rewrite'           => [ 'slug' => 'esszencialis' ],
    ]);
}
add_action( 'init', 'tapanyag_esszencialis_regisztralas' );


// ── 7. FLUSH REWRITE – csak aktiváláskor fut egyszer ─────────
function tapanyag_flush_rewrite() {
    tapanyag_cpt_regisztralas();
    tapanyag_tipus_regisztralas();
    tapanyag_oldhatosag_regisztralas();
    tapanyag_csoport_regisztralas();
    tapanyag_hatas_regisztralas();
    tapanyag_esszencialis_regisztralas();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'tapanyag_flush_rewrite' );
