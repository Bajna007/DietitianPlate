<?php

/**
 * 17. - Alapanyag ↔ Tápanyag összekötés
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ============================================================
// 18 – ALAPANYAG ↔ TÁPANYAG ÖSSZEKÖTÉS (FINAL)
// 
// Funkciók:
//   A) Reverse query: Alapanyag oldalon megmutatja mely
//      tápanyagokban van linkelve (természetes források repeater)
//   B) Cache kezelés mentéskor
//   C) Shortcode: [alapanyag_tapanyagok] 
//   D) Alapanyag tápanyag mezők mapping (OFF importhoz #19)
//   E) CSS az összekötő komponensekhez
// ============================================================


// ── A) REVERSE QUERY HELPER ──────────────────────────────────
// Megkeresi az összes Tápanyag bejegyzést ahol az adott
// Alapanyag ki van választva a termeszetes_forrasok repeaterben

function tapanyag_get_linked_nutrients( $alapanyag_id ) {
    $cached = wp_cache_get( 'tapanyag_links_' . $alapanyag_id, 'tapanyag' );
    if ( $cached !== false ) {
        return $cached;
    }

    $results = [];

    $tapanyagok = get_posts( [
        'post_type'      => 'tapanyag',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'fields'         => 'ids',
    ] );

    foreach ( $tapanyagok as $tap_id ) {
        $forrasok = get_field( 'termeszetes_forrasok', $tap_id );
        if ( ! $forrasok ) continue;

        foreach ( $forrasok as $forras ) {
            $kapcsolodo = $forras['kapcsolodo_post'] ?? null;
            if ( ! $kapcsolodo ) continue;

            $linked_id = is_object( $kapcsolodo ) ? $kapcsolodo->ID : intval( $kapcsolodo );

            if ( $linked_id === intval( $alapanyag_id ) ) {
                $results[] = [
                    'tapanyag_id'   => $tap_id,
                    'tapanyag_nev'  => get_the_title( $tap_id ),
                    'tapanyag_url'  => get_permalink( $tap_id ),
                    'mennyiseg'     => $forras['mennyiseg_100g'] ?? '',
                    'egyseg'        => $forras['egyseg'] ?? 'mg',
                    'kiemelt_szin'  => get_field( 'kiemelt_szin', $tap_id ) ?: '#F59E0B',
                ];
                break; // egy tápanyagból elég egyszer
            }
        }
    }

    wp_cache_set( 'tapanyag_links_' . $alapanyag_id, $results, 'tapanyag', 3600 );

    return $results;
}


// ── B) CACHE TÖRLÉS ──────────────────────────────────────────
// Ha tápanyag bejegyzést mentesz, töröld a kapcsolódó cache-t

function tapanyag_clear_link_cache( $post_id ) {
    if ( get_post_type( $post_id ) !== 'tapanyag' ) return;

    $forrasok = get_field( 'termeszetes_forrasok', $post_id );
    if ( ! $forrasok ) return;

    foreach ( $forrasok as $forras ) {
        $kapcsolodo = $forras['kapcsolodo_post'] ?? null;
        if ( ! $kapcsolodo ) continue;

        $linked_id = is_object( $kapcsolodo ) ? $kapcsolodo->ID : intval( $kapcsolodo );
        wp_cache_delete( 'tapanyag_links_' . $linked_id, 'tapanyag' );
    }
}
add_action( 'acf/save_post', 'tapanyag_clear_link_cache', 20 );


// ── C) SHORTCODE: ALAPANYAG TÁPANYAG KÁRTYA ──────────────────
// Használat bármely template-ben:
//   [alapanyag_tapanyagok id="123"]
//   vagy ID nélkül (az aktuális post-ot használja):
//   [alapanyag_tapanyagok]
// PHP-ból:
//   echo tapanyag_alapanyag_nutrient_cards( $alapanyag_id );

function tapanyag_alapanyag_nutrient_cards( $alapanyag_id ) {
    $links = tapanyag_get_linked_nutrients( $alapanyag_id );
    if ( empty( $links ) ) return '';

    ob_start();
    ?>
    <div class="tapanyag-kapcsolodo-section">
        <h3 class="tapanyag-kapcsolodo-title">🧬 Gazdag ezekben a tápanyagokban</h3>
        <div class="tapanyag-kapcsolodo-grid">
            <?php foreach ( $links as $link ) : ?>
            <a href="<?php echo esc_url( $link['tapanyag_url'] ); ?>"
               class="tapanyag-kapcsolodo-kartya"
               style="--card-accent: <?php echo esc_attr( $link['kiemelt_szin'] ); ?>">
                <span class="tapanyag-kapcsolodo-nev"><?php echo esc_html( $link['tapanyag_nev'] ); ?></span>
                <?php if ( $link['mennyiseg'] ) : ?>
                <span class="tapanyag-kapcsolodo-ertek">
                    <?php echo esc_html( $link['mennyiseg'] . ' ' . $link['egyseg'] ); ?> / 100g
                </span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function tapanyag_alapanyag_shortcode( $atts ) {
    $atts = shortcode_atts( [ 'id' => 0 ], $atts );
    $id = intval( $atts['id'] );
    if ( ! $id ) {
        $id = get_the_ID();
    }
    if ( get_post_type( $id ) !== 'alapanyag' ) return '';
    return tapanyag_alapanyag_nutrient_cards( $id );
}
add_shortcode( 'alapanyag_tapanyagok', 'tapanyag_alapanyag_shortcode' );


// ── D) ALAPANYAG TÁPANYAG MEZŐK MAPPING ──────────────────────
// Pontos ACF mezőnevek a GUI-ból (2026-02-23 képek alapján)
// Használja: #19 OFF import, #20 Recept kalkulátor bővítés

function tapanyag_get_alapanyag_nutrient_fields() {
    return [

        // ── Alap makrók ("Alapanyag makrók" mezőcsoport – meglévő) ──
        'kaloria' => [
            'label'   => 'Kalória',
            'egyseg'  => 'kcal',
            'off_key' => 'energy-kcal_100g',
            'csoport' => 'makro',
        ],
        'feherje' => [
            'label'   => 'Fehérje',
            'egyseg'  => 'g',
            'off_key' => 'proteins_100g',
            'csoport' => 'makro',
        ],
        'szenhidrat' => [
            'label'   => 'Szénhidrát',
            'egyseg'  => 'g',
            'off_key' => 'carbohydrates_100g',
            'csoport' => 'makro',
        ],
        'zsir' => [
            'label'   => 'Zsír',
            'egyseg'  => 'g',
            'off_key' => 'fat_100g',
            'csoport' => 'makro',
        ],

        // ── Bővített makrók ("Alapanyag – Bővített tápanyagok" mezőcsoport) ──
        'rost' => [
            'label'   => 'Rost',
            'egyseg'  => 'g',
            'off_key' => 'fiber_100g',
            'csoport' => 'bovitett',
        ],
        'cukor' => [
            'label'   => 'Cukor',
            'egyseg'  => 'g',
            'off_key' => 'sugars_100g',
            'csoport' => 'bovitett',
        ],
        'telitett' => [
            'label'   => 'Telített zsír',
            'egyseg'  => 'g',
            'off_key' => 'saturated-fat_100g',
            'csoport' => 'bovitett',
        ],
        'egyszeresen_telitetlen_zsir' => [
            'label'   => 'Egyszeresen telítetlen zsír (MUFA)',
            'egyseg'  => 'g',
            'off_key' => 'monounsaturated-fat_100g',
            'csoport' => 'bovitett',
        ],
        'tobbszorosen_telitetlen_zsir' => [
            'label'   => 'Többszörösen telítetlen zsír (PUFA)',
            'egyseg'  => 'g',
            'off_key' => 'polyunsaturated-fat_100g',
            'csoport' => 'bovitett',
        ],
        'so' => [
            'label'   => 'Só',
            'egyseg'  => 'g',
            'off_key' => 'salt_100g',
            'csoport' => 'bovitett',
        ],

        // ── Vitaminok ──
        'vitamin_a' => [
            'label'   => 'A-vitamin',
            'egyseg'  => 'µg',
            'off_key' => 'vitamin-a_100g',
            'csoport' => 'vitamin',
        ],
        'vitamin_c' => [
            'label'   => 'C-vitamin',
            'egyseg'  => 'mg',
            'off_key' => 'vitamin-c_100g',
            'csoport' => 'vitamin',
        ],
        'vitamin_d' => [
            'label'   => 'D-vitamin',
            'egyseg'  => 'µg',
            'off_key' => 'vitamin-d_100g',
            'csoport' => 'vitamin',
        ],
        'vitamin_e' => [
            'label'   => 'E-vitamin',
            'egyseg'  => 'mg',
            'off_key' => 'vitamin-e_100g',
            'csoport' => 'vitamin',
        ],
        'vitamin_k' => [
            'label'   => 'K-vitamin',
            'egyseg'  => 'µg',
            'off_key' => 'vitamin-k_100g',
            'csoport' => 'vitamin',
        ],
        'vitamin_b1' => [
            'label'   => 'B1 – Tiamin',
            'egyseg'  => 'mg',
            'off_key' => 'vitamin-b1_100g',
            'csoport' => 'vitamin',
        ],
        'vitamin_b2' => [
            'label'   => 'B2 – Riboflavin',
            'egyseg'  => 'mg',
            'off_key' => 'vitamin-b2_100g',
            'csoport' => 'vitamin',
        ],
        'vitamin_b3' => [
            'label'   => 'B3 – Niacin',
            'egyseg'  => 'mg',
            'off_key' => 'vitamin-pp_100g',
            'csoport' => 'vitamin',
        ],
        'vitamin_b6' => [
            'label'   => 'B6',
            'egyseg'  => 'mg',
            'off_key' => 'vitamin-b6_100g',
            'csoport' => 'vitamin',
        ],
        'vitamin_b9' => [
            'label'   => 'B9 – Folsav',
            'egyseg'  => 'µg',
            'off_key' => 'vitamin-b9_100g',
            'csoport' => 'vitamin',
        ],
        'vitamin_b12' => [
            'label'   => 'B12',
            'egyseg'  => 'µg',
            'off_key' => 'vitamin-b12_100g',
            'csoport' => 'vitamin',
        ],

        // ── Ásványi anyagok ──
        'kalcium' => [
            'label'   => 'Kalcium',
            'egyseg'  => 'mg',
            'off_key' => 'calcium_100g',
            'csoport' => 'asvany',
        ],
        'vas' => [
            'label'   => 'Vas',
            'egyseg'  => 'mg',
            'off_key' => 'iron_100g',
            'csoport' => 'asvany',
        ],
        'magnezium' => [
            'label'   => 'Magnézium',
            'egyseg'  => 'mg',
            'off_key' => 'magnesium_100g',
            'csoport' => 'asvany',
        ],
        'cink' => [
            'label'   => 'Cink',
            'egyseg'  => 'mg',
            'off_key' => 'zinc_100g',
            'csoport' => 'asvany',
        ],
        'kalium' => [
            'label'   => 'Kálium',
            'egyseg'  => 'mg',
            'off_key' => 'potassium_100g',
            'csoport' => 'asvany',
        ],
        'natrium' => [
            'label'   => 'Nátrium',
            'egyseg'  => 'mg',
            'off_key' => 'sodium_100g',
            'csoport' => 'asvany',
        ],
        'foszfor' => [
            'label'   => 'Foszfor',
            'egyseg'  => 'mg',
            'off_key' => 'phosphorus_100g',
            'csoport' => 'asvany',
        ],

        // ── OFF meta mezők ──
        'off_barcode' => [
            'label'   => 'OpenFoodFacts vonalkód',
            'egyseg'  => '',
            'off_key' => '_barcode',
            'csoport' => 'meta',
        ],
        'off_url' => [
            'label'   => 'OFF termék URL',
            'egyseg'  => '',
            'off_key' => '_url',
            'csoport' => 'meta',
        ],
        'off_last_sync' => [
            'label'   => 'OFF utolsó szinkron',
            'egyseg'  => '',
            'off_key' => '_sync',
            'csoport' => 'meta',
        ],
    ];
}


// ── E) CSS AZ ÖSSZEKÖTŐ KOMPONENSEKHEZ ───────────────────────

function tapanyag_osszekoto_css() {
    if ( ! is_singular( 'tapanyag' ) && ! is_singular( 'alapanyag' ) ) return;

    $css = '
    /* ── Természetes források – kattintható link ── */
    .tapanyag-forras-nev a {
        color: var(--t-text);
        text-decoration: none;
        border-bottom: 1px dashed var(--t-accent, #F59E0B);
        transition: var(--t-transition);
    }
    .tapanyag-forras-nev a:hover {
        color: var(--t-accent, #F59E0B);
        border-bottom-style: solid;
    }

    /* ── Alapanyag oldal – kapcsolódó tápanyagok ── */
    .tapanyag-kapcsolodo-section {
        margin: 32px 0;
        padding: 0;
    }
    .tapanyag-kapcsolodo-title {
        font-size: 1.15rem;
        font-weight: 700;
        margin: 0 0 16px 0;
        color: var(--t-text, #1e1e1e);
    }
    .tapanyag-kapcsolodo-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 12px;
    }
    .tapanyag-kapcsolodo-kartya {
        display: flex;
        flex-direction: column;
        gap: 6px;
        padding: 16px 18px;
        background: var(--t-card, #fff);
        border-radius: var(--t-radius-sm, 10px);
        border-left: 4px solid var(--card-accent, #F59E0B);
        box-shadow: var(--t-shadow, 0 4px 24px rgba(0,0,0,0.05));
        text-decoration: none;
        transition: var(--t-transition, 0.25s cubic-bezier(0.4,0,0.2,1));
    }
    .tapanyag-kapcsolodo-kartya:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 32px rgba(0,0,0,0.10);
    }
    .tapanyag-kapcsolodo-nev {
        font-weight: 600;
        font-size: 0.95rem;
        color: var(--t-text, #1e1e1e);
    }
    .tapanyag-kapcsolodo-ertek {
        font-size: 0.82rem;
        color: var(--t-text-muted, #7c8a83);
    }

    /* ── Responsive ── */
    @media (max-width: 480px) {
        .tapanyag-kapcsolodo-grid {
            grid-template-columns: 1fr;
        }
    }
    ';

    wp_add_inline_style( 'tapanyag-single-css', $css );
}
add_action( 'wp_enqueue_scripts', 'tapanyag_osszekoto_css', 30 );
