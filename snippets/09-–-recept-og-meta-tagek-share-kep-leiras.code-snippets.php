<?php

/**
 * 09 – Recept OG meta tagek (share kép + leírás)
 */
/**
 * 09 – Recept OG meta tagek (share kép + leírás)
 * Facebook, WhatsApp, iMessage stb. számára
 */
function recept_og_meta_tags() {
    if ( ! is_singular( 'recept' ) ) {
        return;
    }

    global $post;

    $title = get_the_title( $post->ID );
    $url   = get_the_permalink( $post->ID );
    $desc  = '';

    // Leírás összeállítás
    $ido      = get_field( 'elkeszitesi_ido', $post->ID );
    $adagok   = get_field( 'adagok_szama', $post->ID );
    $nehezseg = get_field( 'nehezsegi_fok', $post->ID );

    // Kalória számítás
    $total_kcal = 0;
    $total_feh  = 0;
    if ( have_rows( 'osszetevok', $post->ID ) ) {
        while ( have_rows( 'osszetevok', $post->ID ) ) {
            the_row();
            $m  = floatval( get_sub_field( 'mennyiseg' ) );
            $me = get_sub_field( 'mertekegyseg' ) ?: 'g';
            $a  = get_sub_field( 'alapanyag' );
            if ( $a && is_object( $a ) ) {
                $gs = 1;
                $map = array( 'g'=>1, 'ml'=>1, 'ek'=>15, 'tk'=>5, 'db'=>50, 'csipet'=>1 );
                if ( isset( $map[ $me ] ) ) $gs = $map[ $me ];
                $gramm = $m * $gs;
                $total_kcal += ( floatval( get_field( 'kaloria', $a->ID ) ) / 100 ) * $gramm;
                $total_feh  += ( floatval( get_field( 'feherje', $a->ID ) ) / 100 ) * $gramm;
            }
        }
    }

    $parts = array();
    if ( $total_kcal > 0 ) $parts[] = round( $total_kcal ) . ' kcal';
    if ( $total_feh > 0 )  $parts[] = round( $total_feh ) . 'g fehérje';
    if ( $ido )             $parts[] = $ido . ' perc';
    if ( $adagok )          $parts[] = $adagok . ' adag';
    if ( $nehezseg )        $parts[] = ucfirst( $nehezseg );

    $desc = implode( ' · ', $parts );
    if ( ! $desc ) {
        $desc = 'Egészséges, makró-számolt recept';
    }

    // Kép
    $image = get_the_post_thumbnail_url( $post->ID, 'large' );
    if ( ! $image ) {
        $image = '';
    }

    $site_name = get_bloginfo( 'name' );

    echo "\n<!-- Recept OG Meta -->\n";
    echo '<meta property="og:type" content="article" />' . "\n";
    echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
    echo '<meta property="og:description" content="' . esc_attr( $desc ) . '" />' . "\n";
    echo '<meta property="og:url" content="' . esc_url( $url ) . '" />' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '" />' . "\n";
    if ( $image ) {
        echo '<meta property="og:image" content="' . esc_url( $image ) . '" />' . "\n";
        echo '<meta property="og:image:width" content="1200" />' . "\n";
        echo '<meta property="og:image:height" content="630" />' . "\n";
    }
    echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '" />' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr( $desc ) . '" />' . "\n";
    if ( $image ) {
        echo '<meta name="twitter:image" content="' . esc_url( $image ) . '" />' . "\n";
    }
    echo "<!-- /Recept OG Meta -->\n";
}
add_action( 'wp_head', 'recept_og_meta_tags', 5 );
