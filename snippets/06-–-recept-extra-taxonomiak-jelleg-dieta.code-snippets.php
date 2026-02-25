<?php

/**
 * 06 – Recept extra taxonómiák (jelleg + diéta)
 */
/**
 * 06 – Recept extra taxonómiák (jelleg + diéta) v2
 */
function recept_extra_taxonomies() {

    register_taxonomy( 'recept_jelleg', 'recept', array(
        'labels' => array(
            'name'          => 'Jelleg',
            'singular_name' => 'Jelleg',
            'all_items'     => 'Összes jelleg',
            'edit_item'     => 'Jelleg szerkesztése',
            'add_new_item'  => 'Új jelleg',
        ),
        'hierarchical'      => true,
        'public'            => true,
        'rewrite'           => array( 'slug' => 'recept-jelleg' ),
        'show_in_rest'      => true,
        'show_admin_column' => true,
    ) );

    register_taxonomy( 'recept_dieta', 'recept', array(
        'labels' => array(
            'name'          => 'Diéta',
            'singular_name' => 'Diéta',
            'all_items'     => 'Összes diéta',
            'edit_item'     => 'Diéta szerkesztése',
            'add_new_item'  => 'Új diéta',
        ),
        'hierarchical'      => true,
        'public'            => true,
        'rewrite'           => array( 'slug' => 'recept-dieta' ),
        'show_in_rest'      => true,
        'show_admin_column' => true,
    ) );
}
add_action( 'init', 'recept_extra_taxonomies' );

function recept_create_default_terms() {
    $version = 2; // növeld ha új termeket adsz hozzá
    if ( intval( get_option( 'recept_terms_version' ) ) >= $version ) {
        return;
    }

    $jellegek = array( 'Sós', 'Édes' );
    foreach ( $jellegek as $j ) {
        if ( ! term_exists( $j, 'recept_jelleg' ) ) {
            wp_insert_term( $j, 'recept_jelleg' );
        }
    }

    $dietak = array(
        'Laktózmentes',
        'Gluténmentes',
        'Vegetáriánus',
        'Vegán',
        'Tejmentes',
        'Tojásmentes',
    );
    foreach ( $dietak as $d ) {
        if ( ! term_exists( $d, 'recept_dieta' ) ) {
            wp_insert_term( $d, 'recept_dieta' );
        }
    }

    $kategoriak = array( 'Főétel', 'Leves', 'Desszert', 'Kisétkezés' );
    foreach ( $kategoriak as $k ) {
        if ( ! term_exists( $k, 'recept_kategoria' ) ) {
            wp_insert_term( $k, 'recept_kategoria' );
        }
    }

    update_option( 'recept_terms_version', $version );
}
add_action( 'init', 'recept_create_default_terms' );
