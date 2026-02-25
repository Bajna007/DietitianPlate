<?php

/**
 * 07 – Recept archív JS betöltése
 */
/**
 * 07 – Recept archív JS betöltése + összes recept adat átadása
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function recept_archive_enqueue_scripts() {
    if ( ! is_post_type_archive( 'recept' )
        && ! is_tax( 'recept_kategoria' )
        && ! is_tax( 'recept_jelleg' )
        && ! is_tax( 'recept_dieta' )
    ) { return; }

    $all = get_posts([
        'post_type'      => 'recept',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);

    $adatok = [];
    foreach ( $all as $post ) {
        $ido      = intval( get_field( 'elkeszitesi_ido', $post->ID ) );
        $adagok   = intval( get_field( 'adagok_szama', $post->ID ) );
        $nehezseg = get_field( 'nehezsegi_fok', $post->ID ) ?: '';
        $thumb    = get_the_post_thumbnail_url( $post->ID, 'medium_large' ) ?: '';
        $thumbSm  = get_the_post_thumbnail_url( $post->ID, 'thumbnail' ) ?: '';

        $kat_slugs  = wp_get_post_terms( $post->ID, 'recept_kategoria', ['fields' => 'slugs'] );
        $jel_slugs  = wp_get_post_terms( $post->ID, 'recept_jelleg',    ['fields' => 'slugs'] );
        $diet_slugs = wp_get_post_terms( $post->ID, 'recept_dieta',     ['fields' => 'slugs'] );
        $diet_names = wp_get_post_terms( $post->ID, 'recept_dieta',     ['fields' => 'all'] );

        $kat_slugs  = is_wp_error( $kat_slugs )  ? [] : $kat_slugs;
        $jel_slugs  = is_wp_error( $jel_slugs )  ? [] : $jel_slugs;
        $diet_slugs = is_wp_error( $diet_slugs ) ? [] : $diet_slugs;
        $diet_names = is_wp_error( $diet_names ) ? [] : $diet_names;

        $total_kcal    = 0;
        $total_feherje = 0;
        if ( have_rows( 'osszetevok', $post->ID ) ) {
            while ( have_rows( 'osszetevok', $post->ID ) ) {
                the_row();
                $mennyiseg    = floatval( get_sub_field( 'mennyiseg' ) );
                $mertekegyseg = get_sub_field( 'mertekegyseg' ) ?: 'g';
                $alapanyag    = get_sub_field( 'alapanyag' );
                if ( $alapanyag && is_object( $alapanyag ) ) {
                    $map = [ 'g'=>1, 'ml'=>1, 'ek'=>15, 'tk'=>5, 'db'=>50, 'csipet'=>1 ];
                    $szorzo = isset( $map[ $mertekegyseg ] ) ? $map[ $mertekegyseg ] : 1;
                    $gramm = $mennyiseg * $szorzo;
                    $total_kcal    += ( floatval( get_field( 'kaloria', $alapanyag->ID ) ) / 100 ) * $gramm;
                    $total_feherje += ( floatval( get_field( 'feherje', $alapanyag->ID ) ) / 100 ) * $gramm;
                }
            }
        }

        $cimkek = [];
        foreach ( $diet_names as $dt ) {
            $cimkek[] = [ 'slug' => $dt->slug, 'name' => $dt->name ];
        }

        $adatok[] = [
            'id'           => $post->ID,
            'cim'          => get_the_title( $post->ID ),
            'cimLower'     => mb_strtolower( get_the_title( $post->ID ) ),
            'url'          => get_permalink( $post->ID ),
            'kep'          => $thumb,
            'kepSm'        => $thumbSm,
            'ido'          => $ido,
            'adagok'       => $adagok,
            'nehezseg'     => $nehezseg,
            'kategoria'    => $kat_slugs,
            'jelleg'       => $jel_slugs,
            'dieta'        => $diet_slugs,
            'cimkek'       => $cimkek,
            'kcal'         => round( $total_kcal ),
            'feherje'      => round( $total_feherje ),
            'vegan'        => in_array( 'vegan', $diet_slugs ),
            'vegetarianus' => in_array( 'vegetarianus', $diet_slugs ),
            'tejmentes'    => in_array( 'tejmentes', $diet_slugs ),
            'tojasmentes'  => in_array( 'tojasmentes', $diet_slugs ),
            'date'         => get_the_date( 'Y-m-d H:i:s', $post->ID ),
        ];
    }

    wp_enqueue_script(
        'recept-archive',
        get_stylesheet_directory_uri() . '/recept-archive.js',
        [],
        filemtime( get_stylesheet_directory() . '/recept-archive.js' ),
        true
    );

    wp_add_inline_script(
        'recept-archive',
        'var RECEPT_ADATOK = ' . wp_json_encode( $adatok ) . ';',
        'before'
    );
}
add_action( 'wp_enqueue_scripts', 'recept_archive_enqueue_scripts' );
