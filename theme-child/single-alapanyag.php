<?php
/**
 * Single Alapanyag template
 * Child theme: single-alapanyag.php
 * v8.1 – function_exists() guard hozzáadva
 */

if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

while ( have_posts() ) : the_post();

    $post_id = get_the_ID();
    $title   = get_the_title();

    // ── Helper: érték lekérése ACF-ből, fallback post_meta-ra ──
    if ( ! function_exists( 'aa_get_nutrient_value' ) ) {
        function aa_get_nutrient_value( $key, $post_id ) {
            $val = get_field( $key, $post_id );
            if ( $val === null || $val === '' || $val === false ) {
                $val = get_post_meta( $post_id, $key, true );
            }
            return floatval( $val );
        }
    }

    // ── Meta adatok ──
    $eredeti_nev      = get_field( 'eredeti_nev', $post_id ) ?: '';
    $forditas_forras  = get_field( 'forditas_forras', $post_id ) ?: '';
    $forditas_datum   = get_field( 'forditas_datum', $post_id ) ?: '';
    $off_barcode      = get_field( 'off_barcode', $post_id ) ?: '';
    $off_url          = get_field( 'off_url', $post_id ) ?: '';
    $off_last_sync    = get_field( 'off_last_sync', $post_id ) ?: '';
    $usda_fdc_id      = get_field( 'usda_fdc_id', $post_id ) ?: '';
    $usda_last_sync   = get_field( 'usda_last_sync', $post_id ) ?: '';
    $elsodleges_forras = get_field( 'elsodleges_forras', $post_id ) ?: '';

    // ── Forrás badge-ek ──
    $has_off  = ! empty( $off_barcode );
    $has_usda = ! empty( $usda_fdc_id );

    // ── Makrók ──
    $kcal    = floatval( get_field( 'kaloria', $post_id ) );
    $feherje = floatval( get_field( 'feherje', $post_id ) );
    $szh     = floatval( get_field( 'szenhidrat', $post_id ) );
    $zsir    = floatval( get_field( 'zsir', $post_id ) );
    $rost    = floatval( get_field( 'rost', $post_id ) );
    $cukor   = floatval( get_field( 'cukor', $post_id ) );
    $t_zsir  = floatval( get_field( 'telitett', $post_id ) );

    // Zsírsav al-értékek
    $egyszeresen_t  = aa_get_nutrient_value( 'egyszeresen_telitetlen_zsir', $post_id );
    $tobbszorosen_t = aa_get_nutrient_value( 'tobbszorosen_telitetlen_zsir', $post_id );
    $koleszterin    = aa_get_nutrient_value( 'koleszterin', $post_id );

    // ── Energia% számítás – Atwater-faktorok ──
    $kcal_feherje = $feherje * 4.1;
    $kcal_szh     = $szh * 4.1;
    $kcal_zsir    = $zsir * 9.3;
    $total_energia_kcal = $kcal_feherje + $kcal_szh + $kcal_zsir;

    $energia_pct_f = $total_energia_kcal > 0 ? round( $kcal_feherje / $total_energia_kcal * 100 ) : 0;
    $energia_pct_s = $total_energia_kcal > 0 ? round( $kcal_szh / $total_energia_kcal * 100 ) : 0;
    $energia_pct_z = $total_energia_kcal > 0 ? round( $kcal_zsir / $total_energia_kcal * 100 ) : 0;

    // ── TÁPÉRTÉK CSOPORTOK ──

    $vitamins = [
        [ 'key' => 'vitamin_a',   'label' => 'A-vitamin',            'egyseg' => 'µg' ],
        [ 'key' => 'vitamin_c',   'label' => 'C-vitamin',            'egyseg' => 'mg' ],
        [ 'key' => 'vitamin_d',   'label' => 'D-vitamin',            'egyseg' => 'µg' ],
        [ 'key' => 'vitamin_e',   'label' => 'E-vitamin',            'egyseg' => 'mg' ],
        [ 'key' => 'vitamin_k',   'label' => 'K-vitamin',            'egyseg' => 'µg' ],
        [ 'key' => 'vitamin_b1',  'label' => 'B1 (Tiamin)',          'egyseg' => 'mg' ],
        [ 'key' => 'vitamin_b2',  'label' => 'B2 (Riboflavin)',      'egyseg' => 'mg' ],
        [ 'key' => 'vitamin_b3',  'label' => 'B3 (Niacin)',          'egyseg' => 'mg' ],
        [ 'key' => 'vitamin_b5',  'label' => 'B5 (Pantoténsav)',     'egyseg' => 'mg' ],
        [ 'key' => 'vitamin_b6',  'label' => 'B6-vitamin',           'egyseg' => 'mg' ],
        [ 'key' => 'vitamin_b9',  'label' => 'B9 (Folsav)',          'egyseg' => 'µg' ],
        [ 'key' => 'vitamin_b12', 'label' => 'B12-vitamin',          'egyseg' => 'µg' ],
    ];

    $minerals = [
        [ 'key' => 'natrium',   'label' => 'Nátrium',   'egyseg' => 'mg' ],
        [ 'key' => 'kalium',    'label' => 'Kálium',    'egyseg' => 'mg' ],
        [ 'key' => 'kalcium',   'label' => 'Kalcium',   'egyseg' => 'mg' ],
        [ 'key' => 'vas',       'label' => 'Vas',       'egyseg' => 'mg' ],
        [ 'key' => 'magnezium', 'label' => 'Magnézium', 'egyseg' => 'mg' ],
        [ 'key' => 'foszfor',   'label' => 'Foszfor',   'egyseg' => 'mg' ],
    ];

    $trace_elements = [
        [ 'key' => 'cink',    'label' => 'Cink',    'egyseg' => 'mg' ],
        [ 'key' => 'rez',     'label' => 'Réz',     'egyseg' => 'mg' ],
        [ 'key' => 'mangan',  'label' => 'Mangán',  'egyseg' => 'mg' ],
        [ 'key' => 'szelen',  'label' => 'Szelén',  'egyseg' => 'µg' ],
    ];

    $fats = [
        [ 'key' => 'telitett',                     'label' => 'Telített zsírsavak',                'egyseg' => 'g' ],
        [ 'key' => 'egyszeresen_telitetlen_zsir',   'label' => 'Egyszeresen telítetlen zsírsavak',  'egyseg' => 'g' ],
        [ 'key' => 'tobbszorosen_telitetlen_zsir',  'label' => 'Többszörösen telítetlen zsírsavak', 'egyseg' => 'g' ],
        [ 'key' => 'koleszterin',                   'label' => 'Koleszterin',                       'egyseg' => 'mg' ],
    ];

    // ── SVG ikon helper ──
    if ( ! function_exists( 'aa_icon' ) ) {
        function aa_icon( $name ) {
            $icons = [
                'chart'    => '<svg class="aa-ico" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
                'list'     => '<svg class="aa-ico" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>',
                'info'     => '<svg class="aa-ico" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
                'flame'    => '<svg class="aa-ico" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 14.5A2.5 2.5 0 0011 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 11-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 002.5 2.5z"/></svg>',
                'muscle'   => '<svg class="aa-ico" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 18L18 6M6 6l12 12"/></svg>',
                'grain'    => '<svg class="aa-ico" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 22L16 8"/><path d="M3.47 12.53L5 11l1.53 1.53a3.5 3.5 0 010 4.94L5 19l-1.53-1.53a3.5 3.5 0 010-4.94z"/><path d="M7.47 8.53L9 7l1.53 1.53a3.5 3.5 0 010 4.94L9 15l-1.53-1.53a3.5 3.5 0 010-4.94z"/><path d="M11.47 4.53L13 3l1.53 1.53a3.5 3.5 0 010 4.94L13 11l-1.53-1.53a3.5 3.5 0 010-4.94z"/><path d="M20 2h2v2a4 4 0 01-4 4h-2V6a4 4 0 014-4z"/></svg>',
                'droplet'  => '<svg class="aa-ico" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 11-11.31 0z"/></svg>',
                'leaf'     => '<svg class="aa-ico" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 20A7 7 0 019.8 6.9C15.5 4.9 17 3.5 19 2c1 2 2 4.5 2 8 0 5.5-4.78 10-10 10z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg>',
            ];
            return $icons[ $name ] ?? '';
        }
    }

?>

<div class="aa-single-wrap">

    <?php // ── HERO ── ?>

    <div class="aa-hero">
        <div class="aa-hero-content">
            <h1 class="aa-hero-title"><?php echo esc_html( $title ); ?></h1>

            <?php if ( $eredeti_nev && $eredeti_nev !== $title ) : ?>
                <div class="aa-hero-original">
                    <?php echo esc_html( $eredeti_nev ); ?>
                </div>
            <?php endif; ?>

            <div class="aa-hero-badges">
                <?php if ( $has_off ) : ?>
                    <span class="aa-badge aa-badge-off">OFF</span>
                <?php endif; ?>
                <?php if ( $has_usda ) : ?>
                    <span class="aa-badge aa-badge-usda">USDA</span>
                <?php endif; ?>
                <?php if ( $forditas_forras === 'deepl' ) : ?>
                    <span class="aa-badge aa-badge-deepl">DeepL</span>
                <?php endif; ?>
            </div>

            <div class="aa-hero-ids">
                <?php if ( $has_off ) : ?>
                    <span>Vonalkód: <?php echo esc_html( $off_barcode ); ?></span>
                <?php endif; ?>
                <?php if ( $has_usda ) : ?>
                    <span>FDC: <?php echo esc_html( $usda_fdc_id ); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="aa-content">

        <?php // ── MAKRÓ ÖSSZESÍTŐ ── ?>

        <section class="aa-section">
            <div class="aa-section-header">
                <span class="aa-section-icon"><?php echo aa_icon('chart'); ?></span>
                Tápérték összesítő
                <span class="aa-section-sub">100g termékben</span>
            </div>

            <div class="aa-macro-grid">
                <div class="aa-macro-card aa-macro-kcal">
                    <div class="aa-macro-value"><?php echo round( $kcal ); ?></div>
                    <div class="aa-macro-unit">kcal</div>
                    <div class="aa-macro-label">Kalória</div>
                </div>
                <div class="aa-macro-card aa-macro-protein">
                    <div class="aa-macro-value"><?php echo round( $feherje, 1 ); ?>g</div>
                    <div class="aa-macro-bar"><div class="aa-macro-bar-fill" style="width: <?php echo $energia_pct_f; ?>%"></div></div>
                    <div class="aa-macro-label">Fehérje <span class="aa-macro-pct"><?php echo $energia_pct_f; ?> energia%</span></div>
                </div>
                <div class="aa-macro-card aa-macro-carb">
                    <div class="aa-macro-value"><?php echo round( $szh, 1 ); ?>g</div>
                    <div class="aa-macro-bar"><div class="aa-macro-bar-fill" style="width: <?php echo $energia_pct_s; ?>%"></div></div>
                    <div class="aa-macro-label">Szénhidrát <span class="aa-macro-pct"><?php echo $energia_pct_s; ?> energia%</span></div>
                </div>
                <div class="aa-macro-card aa-macro-fat">
                    <div class="aa-macro-value"><?php echo round( $zsir, 1 ); ?>g</div>
                    <div class="aa-macro-bar"><div class="aa-macro-bar-fill" style="width: <?php echo $energia_pct_z; ?>%"></div></div>
                    <div class="aa-macro-label">Zsír <span class="aa-macro-pct"><?php echo $energia_pct_z; ?> energia%</span></div>
                </div>
            </div>

            <div class="aa-macro-notice">
                Az energia% (energiaszázalék) az összes kalóriatartalomból az adott makrotápanyagra jutó kalória arányát jelenti – nem a tömeg (gramm) arányát. A grammra számítás az Atwater-faktorokkal történik: 1 g szénhidrát/fehérje = 4,1 kcal, 1 g zsír = 9,3 kcal.
            </div>
        </section>

        <?php // ── RÉSZLETES TÁPÉRTÉK TÁBLÁZAT ── ?>

        <section class="aa-section">
            <div class="aa-section-header">
                <span class="aa-section-icon"><?php echo aa_icon('list'); ?></span>
                Részletes tápérték
                <span class="aa-section-sub">100g termékben</span>
            </div>

            <div class="aa-tabs">
                <button class="aa-tab is-active" data-tab="all">Összes</button>
                <button class="aa-tab" data-tab="vitamins">Vitaminok</button>
                <button class="aa-tab" data-tab="minerals">Ásványi anyagok</button>
                <button class="aa-tab" data-tab="trace">Nyomelemek</button>
                <button class="aa-tab" data-tab="fats">Zsírsavak</button>
            </div>

            <div class="aa-table-wrap">
                <table class="aa-table">
                    <thead>
                        <tr>
                            <th>Tápanyag</th>
                            <th class="aa-th-right">Mennyiség</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="aa-row-main" data-group="all macros">
                            <td><?php echo aa_icon('flame'); ?> Kalória</td>
                            <td class="aa-td-right"><strong><?php echo round( $kcal ); ?></strong> kcal</td>
                        </tr>
                        <tr class="aa-row-main" data-group="all macros">
                            <td><?php echo aa_icon('muscle'); ?> Fehérje</td>
                            <td class="aa-td-right"><strong><?php echo round( $feherje, 1 ); ?></strong> g</td>
                        </tr>
                        <tr class="aa-row-main" data-group="all macros">
                            <td><?php echo aa_icon('grain'); ?> Szénhidrát</td>
                            <td class="aa-td-right"><strong><?php echo round( $szh, 1 ); ?></strong> g</td>
                        </tr>
                        <tr class="aa-row-sub" data-group="all macros">
                            <td>↳ amelyből cukrok</td>
                            <td class="aa-td-right"><?php echo round( $cukor, 1 ); ?> g</td>
                        </tr>
                        <tr class="aa-row-main" data-group="all macros">
                            <td><?php echo aa_icon('droplet'); ?> Zsír</td>
                            <td class="aa-td-right"><strong><?php echo round( $zsir, 1 ); ?></strong> g</td>
                        </tr>
                        <tr class="aa-row-sub" data-group="all macros">
                            <td>↳ amelyből telített zsírsavak (SFA)</td>
                            <td class="aa-td-right"><?php echo round( $t_zsir, 1 ); ?> g</td>
                        </tr>
                        <?php if ( $egyszeresen_t > 0 ) : ?>
                        <tr class="aa-row-sub" data-group="all macros">
                            <td>↳ amelyből egyszeresen telítetlen (MUFA)</td>
                            <td class="aa-td-right"><?php echo round( $egyszeresen_t, 2 ); ?> g</td>
                        </tr>
                        <?php endif; ?>
                        <?php if ( $tobbszorosen_t > 0 ) : ?>
                        <tr class="aa-row-sub" data-group="all macros">
                            <td>↳ amelyből többszörösen telítetlen (PUFA)</td>
                            <td class="aa-td-right"><?php echo round( $tobbszorosen_t, 2 ); ?> g</td>
                        </tr>
                        <?php endif; ?>
                        <?php if ( $koleszterin > 0 ) : ?>
                        <tr class="aa-row-sub" data-group="all macros">
                            <td>↳ koleszterin</td>
                            <td class="aa-td-right"><?php echo round( $koleszterin, 2 ); ?> mg</td>
                        </tr>
                        <?php endif; ?>
                        <tr class="aa-row-main" data-group="all macros">
                            <td><?php echo aa_icon('leaf'); ?> Rost</td>
                            <td class="aa-td-right"><strong><?php echo round( $rost, 1 ); ?></strong> g</td>
                        </tr>

                        <?php // ── ZSÍRSAVAK ── ?>
                        <?php
                        $has_fats = false;
                        foreach ( $fats as $f ) { if ( aa_get_nutrient_value( $f['key'], $post_id ) > 0 ) { $has_fats = true; break; } }
                        if ( $has_fats ) : ?>
                            <tr class="aa-row-divider" data-group="fatsonly"><td colspan="2">Zsírsavak</td></tr>
                            <?php foreach ( $fats as $f ) :
                                $val = aa_get_nutrient_value( $f['key'], $post_id );
                                if ( $val <= 0 ) continue; ?>
                                <tr data-group="fatsonly"><td><?php echo esc_html( $f['label'] ); ?></td><td class="aa-td-right"><strong><?php echo round( $val, 2 ); ?></strong> <?php echo $f['egyseg']; ?></td></tr>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php // ── VITAMINOK ── ?>
                        <?php
                        $has_vitamins = false;
                        foreach ( $vitamins as $v ) { if ( aa_get_nutrient_value( $v['key'], $post_id ) > 0 ) { $has_vitamins = true; break; } }
                        if ( $has_vitamins ) : ?>
                            <tr class="aa-row-divider" data-group="all vitamins"><td colspan="2">Vitaminok</td></tr>
                            <?php foreach ( $vitamins as $v ) :
                                $val = aa_get_nutrient_value( $v['key'], $post_id );
                                if ( $val <= 0 ) continue; ?>
                                <tr data-group="all vitamins"><td><?php echo esc_html( $v['label'] ); ?></td><td class="aa-td-right"><strong><?php echo round( $val, 2 ); ?></strong> <?php echo $v['egyseg']; ?></td></tr>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php // ── ÁSVÁNYI ANYAGOK ── ?>
                        <?php
                        $has_minerals = false;
                        foreach ( $minerals as $m ) { if ( aa_get_nutrient_value( $m['key'], $post_id ) > 0 ) { $has_minerals = true; break; } }
                        if ( $has_minerals ) : ?>
                            <tr class="aa-row-divider" data-group="all minerals"><td colspan="2">Ásványi anyagok</td></tr>
                            <?php foreach ( $minerals as $m ) :
                                $val = aa_get_nutrient_value( $m['key'], $post_id );
                                if ( $val <= 0 ) continue; ?>
                                <tr data-group="all minerals"><td><?php echo esc_html( $m['label'] ); ?></td><td class="aa-td-right"><strong><?php echo round( $val, 2 ); ?></strong> <?php echo $m['egyseg']; ?></td></tr>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php // ── NYOMELEMEK ── ?>
                        <?php
                        $has_trace = false;
                        foreach ( $trace_elements as $te ) { if ( aa_get_nutrient_value( $te['key'], $post_id ) > 0 ) { $has_trace = true; break; } }
                        if ( $has_trace ) : ?>
                            <tr class="aa-row-divider" data-group="all trace"><td colspan="2">Nyomelemek</td></tr>
                            <?php foreach ( $trace_elements as $te ) :
                                $val = aa_get_nutrient_value( $te['key'], $post_id );
                                if ( $val <= 0 ) continue; ?>
                                <tr data-group="all trace"><td><?php echo esc_html( $te['label'] ); ?></td><td class="aa-td-right"><strong><?php echo round( $val, 2 ); ?></strong> <?php echo $te['egyseg']; ?></td></tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <?php // ── FORRÁS ── ?>

        <section class="aa-section">
            <div class="aa-section-header">
                <span class="aa-section-icon"><?php echo aa_icon('info'); ?></span>
                Forrás
            </div>
            <div class="aa-source-grid">
                <?php if ( $has_off ) : ?>
                    <div class="aa-source-card">
                        <div class="aa-source-title">OpenFoodFacts</div>
                        <div class="aa-source-row"><span class="aa-source-label">Vonalkód:</span><span><?php echo esc_html( $off_barcode ); ?></span></div>
                        <?php if ( $off_url ) : ?><div class="aa-source-row"><a href="<?php echo esc_url( $off_url ); ?>" target="_blank" rel="noopener" class="aa-source-link">Megtekintés az OFF-en →</a></div><?php endif; ?>
                        <?php if ( $off_last_sync ) : ?><div class="aa-source-row aa-source-muted">Szinkron: <?php echo esc_html( $off_last_sync ); ?></div><?php endif; ?>
                        <div class="aa-source-row aa-source-muted">Licenc: ODbL</div>
                    </div>
                <?php endif; ?>
                <?php if ( $has_usda ) : ?>
                    <div class="aa-source-card">
                        <div class="aa-source-title">USDA FoodData Central</div>
                        <div class="aa-source-row"><span class="aa-source-label">FDC ID:</span><span><?php echo esc_html( $usda_fdc_id ); ?></span></div>
                        <div class="aa-source-row"><a href="https://fdc.nal.usda.gov/fdc-app.html#/food-details/<?php echo intval( $usda_fdc_id ); ?>/nutrients" target="_blank" rel="noopener" class="aa-source-link">Megtekintés a USDA-n →</a></div>
                        <?php if ( $usda_last_sync ) : ?><div class="aa-source-row aa-source-muted">Szinkron: <?php echo esc_html( $usda_last_sync ); ?></div><?php endif; ?>
                        <div class="aa-source-row aa-source-muted">Licenc: CC0 Public Domain</div>
                    </div>
                <?php endif; ?>
                <?php if ( $forditas_forras ) : ?>
                    <div class="aa-source-card">
                        <div class="aa-source-title">Fordítás</div>
                        <div class="aa-source-row"><span class="aa-source-label">Forrás:</span><span><?php $fl = ['deepl'=>'DeepL API','magyar_eredeti'=>'Magyar eredeti','manual'=>'Kézi fordítás']; echo esc_html( $fl[$forditas_forras] ?? $forditas_forras ); ?></span></div>
                        <?php if ( $eredeti_nev && $eredeti_nev !== $title ) : ?><div class="aa-source-row"><span class="aa-source-label">Eredeti:</span><span style="font-style: italic;"><?php echo esc_html( $eredeti_nev ); ?></span></div><?php endif; ?>
                        <?php if ( $forditas_datum ) : ?><div class="aa-source-row aa-source-muted">Dátum: <?php echo esc_html( $forditas_datum ); ?></div><?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

    </div>
</div>

<?php endwhile;
get_footer();