<?php

/**
 * 26. – Alapanyag Archív JS
 */
/**
 * Code Snippet: Alapanyag archív JS betöltés + AJAX handler – v6 FINAL
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ══════════════════════════════════════════════════════════════
// JS BETÖLTÉS
// ══════════════════════════════════════════════════════════════

add_action( 'wp_enqueue_scripts', function () {
    if ( ! is_post_type_archive( 'alapanyag' ) && ! is_page_template( 'page-alapanyag.php' ) ) return;

    wp_enqueue_script(
        'alapanyag-archive-js',
        get_stylesheet_directory_uri() . '/alapanyag-archive.js',
        [],
        '6.0',
        true
    );

    wp_localize_script( 'alapanyag-archive-js', 'aaData', [
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'aa_archive_nonce' ),
    ]);
});


// ══════════════════════════════════════════════════════════════
// AJAX HANDLER
// ══════════════════════════════════════════════════════════════

function aa_archive_filter_handler() {
    check_ajax_referer( 'aa_archive_nonce', 'nonce' );

    $search   = sanitize_text_field( $_POST['search'] ?? '' );
    $source   = sanitize_key( $_POST['source'] ?? 'all' );
    $sort     = sanitize_key( $_POST['sort'] ?? 'title_asc' );
    $per_page = min( 48, max( 12, intval( $_POST['per_page'] ?? 24 ) ) );
    $page     = max( 1, intval( $_POST['page'] ?? 1 ) );

    $kcal_min    = max( 0, floatval( $_POST['kcal_min'] ?? 0 ) );
    $kcal_max    = min( 930, floatval( $_POST['kcal_max'] ?? 930 ) );
    $protein_min = max( 0, floatval( $_POST['protein_min'] ?? 0 ) );
    $protein_max = min( 100, floatval( $_POST['protein_max'] ?? 100 ) );
    $carb_min    = max( 0, floatval( $_POST['carb_min'] ?? 0 ) );
    $carb_max    = min( 100, floatval( $_POST['carb_max'] ?? 100 ) );
    $fat_min     = max( 0, floatval( $_POST['fat_min'] ?? 0 ) );
    $fat_max     = min( 100, floatval( $_POST['fat_max'] ?? 100 ) );

    $args = [
        'post_type'      => 'alapanyag',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $page,
    ];

    if ( ! empty( $search ) ) {
        $args['_aa_search'] = $search;
        add_filter( 'posts_where', 'aa_custom_search_where', 10, 2 );
        add_filter( 'posts_join',  'aa_custom_search_join',  10, 2 );
    }

    $meta_query = [];

    if ( $source === 'off' ) {
        $meta_query[] = [ 'key' => 'off_barcode', 'compare' => 'EXISTS' ];
        $meta_query[] = [ 'key' => 'off_barcode', 'value' => '', 'compare' => '!=' ];
    } elseif ( $source === 'usda' ) {
        $meta_query[] = [ 'key' => 'usda_fdc_id', 'compare' => 'EXISTS' ];
        $meta_query[] = [ 'key' => 'usda_fdc_id', 'value' => '', 'compare' => '!=' ];
    } elseif ( $source === 'both' ) {
        $meta_query[] = [ 'key' => 'off_barcode', 'compare' => 'EXISTS' ];
        $meta_query[] = [ 'key' => 'off_barcode', 'value' => '', 'compare' => '!=' ];
        $meta_query[] = [ 'key' => 'usda_fdc_id', 'compare' => 'EXISTS' ];
        $meta_query[] = [ 'key' => 'usda_fdc_id', 'value' => '', 'compare' => '!=' ];
    }

    if ( $kcal_min > 0 || $kcal_max < 930 ) {
        $meta_query[] = [
            'key'     => 'kaloria',
            'value'   => [ $kcal_min, $kcal_max ],
            'compare' => 'BETWEEN',
            'type'    => 'DECIMAL(10,2)',
        ];
    }

    if ( $protein_min > 0 || $protein_max < 100 ) {
        $meta_query[] = [
            'key'     => 'feherje',
            'value'   => [ $protein_min, $protein_max ],
            'compare' => 'BETWEEN',
            'type'    => 'DECIMAL(10,2)',
        ];
    }

    if ( $carb_min > 0 || $carb_max < 100 ) {
        $meta_query[] = [
            'key'     => 'szenhidrat',
            'value'   => [ $carb_min, $carb_max ],
            'compare' => 'BETWEEN',
            'type'    => 'DECIMAL(10,2)',
        ];
    }

    if ( $fat_min > 0 || $fat_max < 100 ) {
        $meta_query[] = [
            'key'     => 'zsir',
            'value'   => [ $fat_min, $fat_max ],
            'compare' => 'BETWEEN',
            'type'    => 'DECIMAL(10,2)',
        ];
    }

    if ( ! empty( $meta_query ) ) {
        $meta_query['relation'] = 'AND';
        $args['meta_query'] = $meta_query;
    }

    switch ( $sort ) {
        case 'title_desc':
            $args['orderby'] = 'title'; $args['order'] = 'DESC'; break;
        case 'kcal_desc':
            $args['meta_key'] = 'kaloria'; $args['orderby'] = 'meta_value_num'; $args['order'] = 'DESC'; break;
        case 'kcal_asc':
            $args['meta_key'] = 'kaloria'; $args['orderby'] = 'meta_value_num'; $args['order'] = 'ASC'; break;
        case 'protein_desc':
            $args['meta_key'] = 'feherje'; $args['orderby'] = 'meta_value_num'; $args['order'] = 'DESC'; break;
        case 'protein_asc':
            $args['meta_key'] = 'feherje'; $args['orderby'] = 'meta_value_num'; $args['order'] = 'ASC'; break;
        case 'carb_desc':
            $args['meta_key'] = 'szenhidrat'; $args['orderby'] = 'meta_value_num'; $args['order'] = 'DESC'; break;
        case 'carb_asc':
            $args['meta_key'] = 'szenhidrat'; $args['orderby'] = 'meta_value_num'; $args['order'] = 'ASC'; break;
        case 'fat_desc':
            $args['meta_key'] = 'zsir'; $args['orderby'] = 'meta_value_num'; $args['order'] = 'DESC'; break;
        case 'fat_asc':
            $args['meta_key'] = 'zsir'; $args['orderby'] = 'meta_value_num'; $args['order'] = 'ASC'; break;
        case 'date_desc':
            $args['orderby'] = 'date'; $args['order'] = 'DESC'; break;
        default:
            $args['orderby'] = 'title'; $args['order'] = 'ASC';
    }

    $query = new WP_Query( $args );

    if ( ! empty( $search ) ) {
        remove_filter( 'posts_where', 'aa_custom_search_where', 10 );
        remove_filter( 'posts_join',  'aa_custom_search_join',  10 );
    }

    $items = [];
    foreach ( $query->posts as $post ) {
        $items[] = [
            'id'            => $post->ID,
            'title'         => $post->post_title,
            'url'           => get_permalink( $post->ID ),
            'original_name' => get_post_meta( $post->ID, 'eredeti_nev', true ) ?: '',
            'kcal'          => floatval( get_post_meta( $post->ID, 'kaloria', true ) ),
            'protein'       => floatval( get_post_meta( $post->ID, 'feherje', true ) ),
            'carb'          => floatval( get_post_meta( $post->ID, 'szenhidrat', true ) ),
            'fat'           => floatval( get_post_meta( $post->ID, 'zsir', true ) ),
            'has_off'       => ! empty( get_post_meta( $post->ID, 'off_barcode', true ) ),
            'has_usda'      => ! empty( get_post_meta( $post->ID, 'usda_fdc_id', true ) ),
        ];
    }
    wp_reset_postdata();

    wp_send_json_success([
        'items'        => $items,
        'total'        => $query->found_posts,
        'pages'        => $query->max_num_pages,
        'current_page' => $page,
    ]);
}
add_action( 'wp_ajax_aa_archive_filter',        'aa_archive_filter_handler' );
add_action( 'wp_ajax_nopriv_aa_archive_filter', 'aa_archive_filter_handler' );


// ══════════════════════════════════════════════════════════════
// CUSTOM SEARCH
// ═══════════════════════════��══════════════════════════════════

function aa_custom_search_join( $join, $query ) {
    global $wpdb;
    if ( ! empty( $query->query_vars['_aa_search'] ) ) {
        $join .= " LEFT JOIN {$wpdb->postmeta} AS aa_meta_search ON ({$wpdb->posts}.ID = aa_meta_search.post_id AND aa_meta_search.meta_key = 'eredeti_nev') ";
    }
    return $join;
}

function aa_custom_search_where( $where, $query ) {
    global $wpdb;
    $search = $query->query_vars['_aa_search'] ?? '';
    if ( ! empty( $search ) ) {
        $like = '%' . $wpdb->esc_like( $search ) . '%';
        $where .= $wpdb->prepare(
            " AND ({$wpdb->posts}.post_title LIKE %s OR aa_meta_search.meta_value LIKE %s)",
            $like, $like
        );
    }
    return $where;
}
