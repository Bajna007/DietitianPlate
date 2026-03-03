<?php

/**
 * 04 – Recept makró kalkulátor
 */
/**
 * 04 – Recept makró kalkulátor
 */
/**
 * 04 – Recept makró kalkulátor v3 (PHP)
 * Per-összetevő mennyiség szerkesztés → makrók automatikus számítás
 */

function recept_mertekegyseg_to_gramm( $mennyiseg, $mertekegyseg ) {
    $mennyiseg = floatval( $mennyiseg );
    $map = array(
        'g'      => 1,
        'ml'     => 1,
        'ek'     => 15,
        'tk'     => 5,
        'db'     => 50,
        'csipet' => 1,
    );
    $szorzo = isset( $map[ $mertekegyseg ] ) ? $map[ $mertekegyseg ] : 1;
    return $mennyiseg * $szorzo;
}

function recept_calc_enqueue_scripts() {
    if ( ! is_singular( 'recept' ) ) {
        return;
    }

    global $post;

    $adagok_szama = intval( get_field( 'adagok_szama', $post->ID ) );
    if ( $adagok_szama < 1 ) {
        $adagok_szama = 1;
    }

    $ingredients = array();
    $missing     = array();

    if ( have_rows( 'osszetevok', $post->ID ) ) {
        $i = 0;
        while ( have_rows( 'osszetevok', $post->ID ) ) {
            the_row();

            $mennyiseg            = floatval( get_sub_field( 'mennyiseg' ) );
            $mertekegyseg         = get_sub_field( 'mertekegyseg' );
            if ( ! $mertekegyseg ) { $mertekegyseg = 'g'; }
            $alapanyag            = get_sub_field( 'alapanyag' );
            $kapcsolodo_alapanyag = get_sub_field( 'kapcsolodo_alapanyag' );
            $megjegyzes           = get_sub_field( 'megjegyzes' );

            $aa_kcal       = 0;
            $aa_feherje    = 0;
            $aa_szenhidrat = 0;
            $aa_zsir       = 0;
            $alapanyag_nev = '';
            $has_macro     = false;

            if ( $kapcsolodo_alapanyag && is_object( $kapcsolodo_alapanyag ) ) {
                $aa_kcal        = floatval( get_field( 'kaloria',    $kapcsolodo_alapanyag->ID ) );
                $aa_feherje     = floatval( get_field( 'feherje',    $kapcsolodo_alapanyag->ID ) );
                $aa_szenhidrat  = floatval( get_field( 'szenhidrat', $kapcsolodo_alapanyag->ID ) );
                $aa_zsir        = floatval( get_field( 'zsir',       $kapcsolodo_alapanyag->ID ) );
                $has_macro      = ( $aa_kcal + $aa_feherje + $aa_szenhidrat + $aa_zsir ) > 0;
            }

            $alapanyag_nev = is_string( $alapanyag ) ? $alapanyag : '';

            if ( ! $has_macro && ! empty( $alapanyag_nev ) ) {
                $missing[] = $alapanyag_nev;
            }

            // Gramm-konverziós szorzó az adott mértékegységhez
            $gramm_szorzo = recept_mertekegyseg_to_gramm( 1, $mertekegyseg );

            $ingredients[] = array(
                'index'         => $i,
                'mennyiseg'     => $mennyiseg,
                'mertekegyseg'  => $mertekegyseg,
                'grammSzorzo'   => $gramm_szorzo,
                'nev'           => $alapanyag_nev,
                'hasMacroData'  => $has_macro,
                // 100g-ra vonatkozó makrók
                'per100' => array(
                    'kcal'       => round( $aa_kcal, 2 ),
                    'feherje'    => round( $aa_feherje, 2 ),
                    'szenhidrat' => round( $aa_szenhidrat, 2 ),
                    'zsir'       => round( $aa_zsir, 2 ),
                ),
            );

            $i++;
        }
    }

    // JS fájl betöltése
    $js_file = get_stylesheet_directory() . '/recept-calc.js';
    $js_uri  = get_stylesheet_directory_uri() . '/recept-calc.js';

    if ( ! file_exists( $js_file ) ) {
        return;
    }

    wp_enqueue_script(
        'recept-calc',
        $js_uri,
        array(),
        filemtime( $js_file ),
        true
    );

    wp_localize_script( 'recept-calc', 'receptCalcData', array(
        'initialServings'         => $adagok_szama,
        'ingredients'             => $ingredients,
        'missingMacroIngredients' => $missing,
        'kcalFactors'             => array(
            'feherje'    => 4.1,
            'szenhidrat' => 4.1,
            'zsir'       => 9.3,
        ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'recept_calc_enqueue_scripts' );
