<?php

/**
 * 15 – Tápanyag archív JS
 */
/**
 * 15 – Tápanyag archív JS betöltő – v2.1 FIX
 * Összefoglaló truncálva 150 karakterre a JSON méret csökkentése érdekében
 */
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_enqueue_scripts', function() {
    if ( ! is_post_type_archive( 'tapanyag' )
        && ! is_tax( 'tapanyag_tipus' )
        && ! is_tax( 'oldhatosag' )
        && ! is_tax( 'tapanyag_csoport' )
        && ! is_tax( 'tapanyag_hatas' )
        && ! is_tax( 'esszencialis' )
    ) { return; }

    $tapanyagok = get_posts([
        'post_type'      => 'tapanyag',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ]);

    $adatok = [];
    foreach ( $tapanyagok as $post ) {
        $thumb   = get_the_post_thumbnail_url( $post->ID, 'medium_large' );
        $tipus   = wp_get_post_terms( $post->ID, 'tapanyag_tipus',   ['fields' => 'names'] );
        $oldhat  = wp_get_post_terms( $post->ID, 'oldhatosag',       ['fields' => 'names'] );
        $csoport = wp_get_post_terms( $post->ID, 'tapanyag_csoport', ['fields' => 'names'] );
        $hatas   = wp_get_post_terms( $post->ID, 'tapanyag_hatas',   ['fields' => 'names'] );
        $esszenc = wp_get_post_terms( $post->ID, 'esszencialis',     ['fields' => 'names'] );

        // Összefoglaló truncálás – max 150 karakter a kártyához
        $raw_osszefoglalo = get_field( 'osszefoglalo', $post->ID ) ?: '';
        $osszefoglalo = mb_strlen( $raw_osszefoglalo ) > 150
            ? mb_substr( $raw_osszefoglalo, 0, 147 ) . '���'
            : $raw_osszefoglalo;

        $adatok[] = [
            'id'           => $post->ID,
            'cim'          => get_the_title( $post->ID ),
            'url'          => get_permalink( $post->ID ),
            'kep'          => $thumb ?: '',
            'kemiai_nev'   => get_field( 'kemiai_nev', $post->ID ) ?: '',
            'osszefoglalo' => $osszefoglalo,
            'tipus'        => $tipus ?: [],
            'oldhatosag'   => $oldhat ?: [],
            'csoport'      => $csoport ?: [],
            'hatas'        => $hatas ?: [],
            'esszencialis' => $esszenc ?: [],
        ];
    }

    wp_enqueue_script(
        'tapanyag-archive-js',
        get_stylesheet_directory_uri() . '/tapanyag-archive.js',
        [],
        '2.1',
        true
    );

    wp_add_inline_script(
        'tapanyag-archive-js',
        'var TAPANYAG_ADATOK = ' . wp_json_encode( $adatok ) . ';',
        'before'
    );
});
