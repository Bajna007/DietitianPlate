<?php
/**
 * Template Name: Céljaim oldal
 * v22 – Atwater-faktorok javítva (4.1/9.3), fogyási szekció magyar szövegek,
 *        PDF audit bővített magyarázatok
 */
get_header();

$logged  = is_user_logged_in();
$uid     = $logged ? get_current_user_id() : 0;
$has_bmr = $logged && function_exists( 'dp_user_has_bmr' ) && dp_user_has_bmr();
$bmr     = $uid ? get_user_meta( $uid, 'dp_bmr_profile', true ) : null;
$goal    = ( $uid && function_exists( 'dp_get_user_goal' ) ) ? dp_get_user_goal( $uid ) : null;

$tdee     = $bmr ? intval( $bmr['tdee'] ) : 0;
$bmr_val  = $bmr ? intval( $bmr['bmr'] ) : 0;
$bw       = $bmr ? floatval( $bmr['weight'] ) : 75;
$height   = $bmr ? floatval( $bmr['height'] ) : 170;
$age      = $bmr ? intval( $bmr['age'] ) : 30;

/* ══ NEM FIX: a BMR modul "ferfi"/"no"-t ment, itt normalizáljuk ══ */
$raw_gender = $bmr && ! empty( $bmr['gender'] ) ? $bmr['gender'] : 'male';
if ( $raw_gender === 'ferfi' ) $gender = 'male';
elseif ( $raw_gender === 'no' ) $gender = 'female';
else $gender = $raw_gender;

$activity = $bmr ? floatval( $bmr['activity'] ) : 1.55;
$athlete  = $bmr && ! empty( $bmr['is_athlete'] ) ? true : false;
$formula  = $bmr && ! empty( $bmr['formula'] ) ? $bmr['formula'] : 'mifflin';
$saved_at = $bmr && ! empty( $bmr['saved_at'] ) ? $bmr['saved_at'] : '';

$bmi      = ( $bw > 0 && $height > 0 ) ? round( $bw / pow( $height / 100, 2 ), 1 ) : 22;
$kcal_pkg = $bw > 0 ? round( $tdee / $bw, 1 ) : 0;

$act_map = array(
    '1.2'   => 'Ülő (1.2)',
    '1.375' => 'Enyhén aktív (1.375)',
    '1.55'  => 'Mérsékelten aktív (1.55)',
    '1.725' => 'Nagyon aktív (1.725)',
    '1.9'   => 'Extra aktív (1.9)',
    '2.0'   => 'Sportoló – hobbi (2.0)',
    '2.3'   => 'Sportoló – versenyző (2.3)',
    '2.5'   => 'Sportoló – élsportoló (2.5)',
);
$act_label = 'Ismeretlen';
$best = 999;
foreach ( $act_map as $k => $v ) {
    $d = abs( floatval( $k ) - $activity );
    if ( $d < $best ) { $best = $d; $act_label = $v; }
}

$form_map = array(
    'mifflin' => 'Mifflin-St Jeor (1990)',
    'harris'  => 'Harris-Benedict (1919)',
    'roza'    => 'Roza & Shizgal (1984)',
);
$form_label = isset( $form_map[ $formula ] ) ? $form_map[ $formula ] : $formula;

/* ═══ MINDIG SZINTENTARTÁS – resetelődik minden betöltésnél ═══ */
$g_type = 'maintain';
$g_mc   = $goal ? intval( $goal->meal_count ) : 5;
$g_md   = $goal && is_array( $goal->meal_distribution ) ? $goal->meal_distribution : null;
?>
<div class="cj-page">

<div class="cj-hero">
    <div class="cj-hero-inner">
        <h1 class="cj-hero-title"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>Céljaim</h1>
        <p class="cj-hero-sub">Személyre szabott makró célok, étkezés tervezés, prognózis</p>
    </div>
</div>

<?php if ( ! $logged ) : ?>
<div class="cj-gate"><div class="cj-gate-inner">
    <div class="cj-gate-icon">🔒</div>
    <h2 class="cj-gate-title">Jelentkezz be a célok beállításához</h2>
    <p class="cj-gate-subtitle">Személyre szabott makró célok, étkezés tervezés és prognózis.</p>
    <div class="cj-gate-features">
        <div class="cj-gate-feature"><span class="cj-gate-feat-icon">🎯</span><div><strong>Makró célok</strong><span>F/Zs/CH elosztás</span></div></div>
        <div class="cj-gate-feature"><span class="cj-gate-feat-icon">🍽</span><div><strong>Étkezés tervezés</strong><span>Napi elosztás</span></div></div>
        <div class="cj-gate-feature"><span class="cj-gate-feat-icon">📊</span><div><strong>Prognózis</strong><span>Fogyás / Tömegnövelés</span></div></div>
        <div class="cj-gate-feature"><span class="cj-gate-feat-icon">📄</span><div><strong>PDF riport</strong><span>Teljes összegzés</span></div></div>
    </div>
    <div class="cj-gate-cta">
        <button type="button" id="cj-gate-register" class="cj-gate-btn cj-gate-btn--primary">Regisztráció</button>
        <button type="button" id="cj-gate-login" class="cj-gate-btn cj-gate-btn--secondary">Bejelentkezés</button>
    </div>
</div></div>

<?php elseif ( ! $has_bmr ) : ?>
<div class="cj-gate"><div class="cj-gate-inner">
    <div class="cj-gate-icon">📊</div>
    <h2 class="cj-gate-title">Először töltsd ki a Kalória Kalkulátort</h2>
    <p class="cj-gate-subtitle">A célok beállításához szükségünk van az alapadataidra.</p>
    <div class="cj-gate-steps">
        <div class="cj-gate-step"><span class="cj-gate-step-num">1</span>Töltsd ki a Kalória Kalkulátort</div>
        <div class="cj-gate-step"><span class="cj-gate-step-num">2</span>Mentsd el az eredményt</div>
        <div class="cj-gate-step"><span class="cj-gate-step-num">3</span>Gyere vissza – minden automatikusan betölt</div>
    </div>
    <div class="cj-gate-cta" style="margin-top:24px"><a href="/bmr-kalkulator" class="cj-gate-btn cj-gate-btn--primary">Kalória Kalkulátor →</a></div>
</div></div>

<?php else : ?>
<div class="cj-content">

    <!-- ① KIINDULÁS -->
    <div class="dg-card cj-origin-card">
        <div class="cj-origin-header">
            <div class="cj-origin-icon">📊</div>
            <div class="cj-origin-title-wrap">
                <div class="cj-origin-title">Az adataid a Kalória Kalkulátorból</div>
                <div class="cj-origin-date"><?php echo $saved_at ? esc_html( $saved_at ) : '—'; ?></div>
            </div>
            <label class="cj-manual-toggle">
                <span id="cj-manual-label">🔒 Importált</span>
                <input type="checkbox" id="cj-manual-check">
                <span class="cj-manual-slider"></span>
            </label>
        </div>
        <div class="cj-origin-grid" style="grid-template-columns:repeat(3,1fr)">
            <div class="cj-origin-stat"><div class="cj-origin-stat-value cj-origin-stat--accent" id="cj-stat-tdee"><?php echo $tdee; ?></div><div class="cj-origin-stat-label">Napi kalóriaszükséglet (kcal)</div></div>
            <div class="cj-origin-stat"><div class="cj-origin-stat-value" id="cj-stat-bmr"><?php echo $bmr_val; ?></div><div class="cj-origin-stat-label">Alapanyagcsere (kcal)</div></div>
            <div class="cj-origin-stat"><div class="cj-origin-stat-value" id="cj-stat-kcalpkg"><?php echo $kcal_pkg; ?></div><div class="cj-origin-stat-label">kcal/ttkg</div></div>
        </div>
        <div class="cj-origin-grid" style="grid-template-columns:repeat(5,1fr)">
            <div class="cj-origin-stat"><div class="cj-origin-stat-value" id="cj-stat-bw"><?php echo $bw; ?></div><div class="cj-origin-stat-label">Testsúly (kg)</div></div>
            <div class="cj-origin-stat"><div class="cj-origin-stat-value" id="cj-stat-height"><?php echo $height; ?></div><div class="cj-origin-stat-label">Magasság (cm)</div></div>
            <div class="cj-origin-stat"><div class="cj-origin-stat-value" id="cj-stat-bmi"><?php echo $bmi; ?></div><div class="cj-origin-stat-label">BMI (kg/m²)</div></div>
            <div class="cj-origin-stat"><div class="cj-origin-stat-value" id="cj-stat-age"><?php echo $age; ?></div><div class="cj-origin-stat-label">Kor (év)</div></div>
            <div class="cj-origin-stat"><div class="cj-origin-stat-value" id="cj-stat-gender"><?php echo $gender === 'male' ? 'Férfi' : 'Nő'; ?></div><div class="cj-origin-stat-label">Nem</div></div>
        </div>
        <div class="cj-origin-grid" style="grid-template-columns:repeat(3,1fr)">
            <div class="cj-origin-stat"><div class="cj-origin-stat-value" id="cj-stat-formula" style="font-size:.75rem"><?php echo esc_html( $form_label ); ?></div><div class="cj-origin-stat-label">Képlet</div></div>
            <div class="cj-origin-stat"><div class="cj-origin-stat-value" id="cj-stat-activity" style="font-size:.75rem"><?php echo esc_html( $act_label ); ?></div><div class="cj-origin-stat-label">Aktivitás</div></div>
            <div class="cj-origin-stat"><div class="cj-origin-stat-value" id="cj-stat-athlete"><?php echo $athlete ? '✅ Igen' : '—'; ?></div><div class="cj-origin-stat-label">Sportoló</div></div>
        </div>

        <div id="cj-broca-notice" class="dg-info-box dg-info-box--orange" style="margin-top:6px;display:none"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 9v4m0 4h.01M12 2L2 22h20L12 2z"/></svg><div id="cj-broca-notice-body"></div></div>
        <div id="cj-underweight-notice" class="dg-info-box dg-info-box--warn" style="margin-top:6px;display:none"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 9v4m0 4h.01M12 2L2 22h20L12 2z"/></svg><div id="cj-underweight-body"></div></div>
        <div id="cj-athlete-notice" class="dg-info-box dg-info-box--blue" style="margin-top:6px;display:none"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg><div id="cj-athlete-notice-body"></div></div>

        <div id="cj-manual-fields" class="cj-manual-fields" style="display:none">
            <div class="dg-info-box dg-info-box--orange" style="margin-bottom:14px"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 9v4m0 4h.01M12 2L2 22h20L12 2z"/></svg><div><strong>Manuális mód</strong><p>Az alábbi értékek felülírják az importált adatokat. Minden automatikusan újraszámolódik.</p></div></div>
            <div class="dg-row" style="grid-template-columns:1fr 1fr 1fr 1fr">
                <div class="dg-field"><label class="dg-field-label">Testsúly</label><div class="dg-field-wrap"><input type="number" id="cj-m-bw" class="dg-input" value="<?php echo $bw; ?>" min="30" max="300" step="0.1"><span class="dg-input-unit">kg</span></div></div>
                <div class="dg-field"><label class="dg-field-label">Magasság</label><div class="dg-field-wrap"><input type="number" id="cj-m-ht" class="dg-input" value="<?php echo $height; ?>" min="100" max="250" step="0.1"><span class="dg-input-unit">cm</span></div></div>
                <div class="dg-field"><label class="dg-field-label">Kor</label><div class="dg-field-wrap"><input type="number" id="cj-m-age" class="dg-input" value="<?php echo $age; ?>" min="10" max="120"><span class="dg-input-unit">év</span></div></div>
                <div class="dg-field"><label class="dg-field-label">Nem</label><select id="cj-m-gender" class="dg-input"><option value="male" <?php selected( $gender, 'male' ); ?>>Férfi</option><option value="female" <?php selected( $gender, 'female' ); ?>>Nő</option></select></div>
            </div>
            <div class="dg-row" style="grid-template-columns:1fr 1fr;margin-top:12px">
                <div class="dg-field"><label class="dg-field-label">Képlet</label><select id="cj-m-formula" class="dg-input"><option value="mifflin" <?php selected( $formula, 'mifflin' ); ?>>Mifflin-St Jeor (1990)</option><option value="harris" <?php selected( $formula, 'harris' ); ?>>Harris-Benedict (1919)</option><option value="roza" <?php selected( $formula, 'roza' ); ?>>Roza & Shizgal (1984)</option><option value="manual_input" <?php selected( $formula, 'manual_input' ); ?>>Manuális bevitel</option></select></div>
                <div class="dg-field"><label class="dg-field-label">Aktivitás</label><select id="cj-m-act" class="dg-input">
                    <option value="1.2" <?php selected( round( $activity, 3 ), '1.2' ); ?>>Ülő (×1.2)</option>
                    <option value="1.375" <?php selected( round( $activity, 3 ), '1.375' ); ?>>Enyhén aktív (×1.375)</option>
                    <option value="1.55" <?php selected( round( $activity, 3 ), '1.55' ); ?>>Mérsékelten aktív (×1.55)</option>
                    <option value="1.725" <?php selected( round( $activity, 3 ), '1.725' ); ?>>Nagyon aktív (×1.725)</option>
                    <option value="1.9" <?php selected( round( $activity, 3 ), '1.9' ); ?>>Extra aktív (×1.9)</option>
                    <option value="2.0" <?php selected( round( $activity, 3 ), '2' ); ?>>Sportoló – hobbi (×2.0)</option>
                    <option value="2.3" <?php selected( round( $activity, 3 ), '2.3' ); ?>>Sportoló – versenyző (×2.3)</option>
                    <option value="2.5" <?php selected( round( $activity, 3 ), '2.5' ); ?>>Sportoló – élsportoló (×2.5)</option>
                </select></div>
            </div>
            <div id="cj-broca-override-wrap" class="dg-field" style="display:none;margin-top:8px">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:.82rem;font-weight:600;color:var(--dg-text)">
                    <input type="checkbox" id="cj-broca-override" style="width:16px;height:16px;accent-color:var(--dg-accent);cursor:pointer">
                    <span>Korrigált testsúly (Broca) kikapcsolása – valós testsúllyal számol</span>
                </label>
                <div style="font-size:.7rem;color:var(--dg-text-muted);margin-top:4px;padding-left:24px">
                    ⚠️ Elhízásnál (BMI ≥ 30) a valós testsúly túlbecsülheti az alapanyagcserét. Csak akkor kapcsold ki, ha tudod mit csinálsz.
                </div>
            </div>
            <div id="cj-manual-kcal-fields" class="dg-row" style="grid-template-columns:1fr 1fr;margin-top:12px;display:none">
                <div class="dg-field">
                    <label class="dg-field-label">Napi kcal bevitel</label>
                    <div class="dg-field-wrap">
                        <input type="number" id="cj-m-kcal" class="dg-input" value="" min="800" max="15000" step="1">
                        <span class="dg-input-unit">kcal</span>
                    </div>
                </div>
                <div class="dg-field">
                    <label class="dg-field-label">kcal / testtömeg-kg</label>
                    <div class="dg-field-wrap">
                        <input type="number" id="cj-m-kcalpkg" class="dg-input" value="" min="10" max="80" step="0.1">
                        <span class="dg-input-unit">kcal/ttkg</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="cj-origin-footer"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg><span>Az importált adatok a <a href="/bmr-kalkulator">Kalória Kalkulátorból</a> származnak. A manuális módban itt helyben módosíthatsz mindent.</span></div>
    </div>

    <!-- ② CÉL -->
    <div class="dg-card">
        <div class="dg-card-title"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>Mit szeretnél elérni?</div>
        <div class="dg-type-grid">
            <button type="button" class="dg-type-card<?php echo $g_type === 'cut' ? ' is-active' : ''; ?>" data-type="cut" style="--gt-color:#22c55e"><span class="dg-type-icon">📉</span><span class="dg-type-label">Fogyás</span></button>
            <button type="button" class="dg-type-card<?php echo $g_type === 'maintain' ? ' is-active' : ''; ?>" data-type="maintain" style="--gt-color:#3b82f6"><span class="dg-type-icon">⚖️</span><span class="dg-type-label">Szintentartás</span></button>
            <button type="button" class="dg-type-card<?php echo $g_type === 'bulk' ? ' is-active' : ''; ?>" data-type="bulk" style="--gt-color:#f59e0b"><span class="dg-type-icon">📈</span><span class="dg-type-label">Tömegnövelés</span></button>
        </div>
        <div class="dg-row" style="grid-template-columns:1fr 1fr">
            <div class="dg-field"><label class="dg-field-label">Napi kalória cél</label><div class="dg-field-wrap"><input type="number" id="dg-kcal" class="dg-input dg-input--big dg-input--locked" value="<?php echo $tdee; ?>" readonly><span class="dg-input-unit">kcal</span></div></div>
            <div class="dg-field"><label class="dg-field-label">Jelenlegi testsúly</label><div class="dg-field-wrap"><input type="number" id="dg-bw-display" class="dg-input dg-input--locked" value="<?php echo $bw; ?>" readonly><span class="dg-input-unit">kg</span></div></div>
        </div>
    </div>

    <!-- ③ FOGYÁS -->
    <div class="dg-card" id="dg-cut-section" style="display:none">
        <div class="dg-card-title"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M23 6l-9.5 9.5-5-5L1 18"/></svg>Fogyási terv</div>
        <div class="dg-info-box dg-info-box--blue">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            <div>
                <strong>Hogyan működik a fogyás?</strong>
                <p>A tested naponta elhasznál egy bizonyos mennyiségű energiát (ez a napi kalóriaszükségleted). Ha a napi energiaszükségletednél tartósan kevesebbet viszel be, akkor a szervezet a hiányt a tartalékaiból fedezi, és elindul a fogyás. Vizsgálatok szerint 1 kg testsúlyváltozás átlagosan ~7000 kalóriának felel meg. A biztonságos fogyás heti 0,5–1,0% testtömeg körül tekinthető.</p>
                <p style="font-size:.68rem;color:var(--dg-text-muted);margin-top:6px">Források: <a href="https://pubmed.ncbi.nlm.nih.gov/17848938/" target="_blank">Hall 2008</a>; <a href="https://pubmed.ncbi.nlm.nih.gov/35103583/" target="_blank">Heymsfield et al. 2022</a> – szisztematikus áttekintés + metaanalízis; <a href="https://pubmed.ncbi.nlm.nih.gov/21872751/" target="_blank">Hall et al. 2011</a></p>
            </div>
        </div>
        <div class="dg-cut-slider-section">
            <div class="dg-field-label">Napi kalória csökkentés</div>
            <div class="dg-cut-slider-row">
                <input type="range" id="dg-deficit-slider" class="dg-slider dg-slider--deficit" min="0" max="1500" step="25" value="0">
                <div class="dg-cut-slider-val"><span id="dg-deficit-value">0</span><small> kcal</small></div>
            </div>
            <div id="dg-cut-hint" style="font-size:.72rem;color:var(--dg-text-muted)">↑ Húzd el a csúszkát</div>
        </div>
        <div id="dg-cut-intake" class="dg-cut-intake"></div>
        <div id="dg-cut-stats" class="dg-cut-stats"></div>
        <div id="dg-cut-tempo"></div>
        <div id="dg-cut-warning" class="dg-info-box dg-info-box--warn" style="display:none"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 9v4m0 4h.01M12 2L2 22h20L12 2z"/></svg><div id="dg-cut-warning-text"></div></div>
        <div id="dg-prog-wrap"></div>
    </div>

    <!-- ③B TÖMEGNÖVELÉS -->
    <div class="dg-card" id="dg-bulk-section" style="display:none">
        <div class="dg-card-title"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 18l9.5-9.5 5 5L23 6"/></svg>Tömegnövelés</div>
        <div class="dg-info-box dg-info-box--orange">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            <div>
                <strong>Hogyan épül izom?</strong>
                <p>Az izom építőköve nagyrészt izomfehérje (például aktin és miozin), ezért izomépítéshez edzésinger (mechanikus tenzió) és elegendő fehérje (aminosav „alapanyag") kell.</p>
                <p>Energia oldalról nem kötelező nagy kalóriatöbblet: az összegző eredmények azt mutatják, hogy a tartós, nagy energiahiány rontja a sovány tömeg gyarapodását ellenállásos edzés mellett – tehát gyakran elég a fenntartó környéke vagy egy minimális többlet.</p>
                <p>Ha mégis tömegnövelés a cél, egy gyakori, óvatos kiindulópont a kicsi plusz (pl. +10–20%) és lassú súlynövekedés (kb. 0,25–0,5%/hét) – de ezt mindig a saját trendjeidhez érdemes igazítani.</p>
                <p style="font-size:.68rem;color:var(--dg-text-muted);margin-top:6px">Források: <a href="https://pubmed.ncbi.nlm.nih.gov/40566702/" target="_blank">Kassiano et al. 2025</a> – sziszt. áttekintés + metaanalízis; <a href="https://pubmed.ncbi.nlm.nih.gov/28698222/" target="_blank">Jäger et al. / ISSN 2017</a>; <a href="https://pubmed.ncbi.nlm.nih.gov/34623696/" target="_blank">Murphy & Koehler 2022</a>; <a href="https://pubmed.ncbi.nlm.nih.gov/31247944/" target="_blank">Iraki et al. 2019</a></p>
            </div>
        </div>
        <div class="dg-cut-slider-section">
            <div class="dg-field-label">Napi kalória többlet</div>
            <div class="dg-cut-slider-row">
                <input type="range" id="dg-surplus-slider" class="dg-slider dg-slider--surplus" min="0" max="800" step="25" value="0">
                <div class="dg-cut-slider-val"><span id="dg-surplus-value">0</span><small> kcal</small></div>
            </div>
        </div>
        <div id="dg-bulk-intake" class="dg-cut-intake"></div>
        <div id="dg-bulk-warning" class="dg-info-box dg-info-box--warn" style="display:none"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 9v4m0 4h.01M12 2L2 22h20L12 2z"/></svg><div id="dg-bulk-warning-text"></div></div>
        <div style="margin-top:18px">
            <div class="dg-field-label" style="margin-bottom:10px">Mire számíthatsz?</div>
            <div id="dg-bulk-expect"></div>
        </div>
    </div>

    <!-- ④ MAKRÓK -->
    <div class="dg-card">
        <div class="dg-macro-top">
            <div class="dg-card-title" style="margin:0;padding:0;border:0"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path d="M9 12l2 2 4-4"/></svg>Makró elosztás</div>
            <span class="dg-tb dg-tb--ok" id="dg-tb">100.00 energia%</span>
        </div>
        <div class="dg-macro-mode">
            <button type="button" class="dg-macro-mode-btn is-active" data-mode="recommended">🔬 Tudományos ajánlás</button>
            <button type="button" class="dg-macro-mode-btn" data-mode="custom">✏️ Egyéni</button>
        </div>
        <div id="dg-macro-rec-notice" class="dg-macro-rec-notice"></div>
        <div id="dg-macro-warn-top" class="dg-macro-warn-banner" style="display:none"></div>
        <div class="dg-macro-grid">
            <div class="dg-mc dg-mc--prot">
                <div class="dg-mc-head"><span class="dg-mc-name"><span class="dg-mc-dot"></span>Fehérje</span><span class="dg-mc-pct" id="dg-p-pct"></span></div>
                <div class="dg-mc-body"><input type="range" id="dg-prot-sl" class="dg-slider" min="10" max="50" step="1" value="25" disabled><div class="dg-mc-vals"><span class="dg-mc-gram" id="dg-p-g">0<small> g</small></span><span class="dg-mc-perkg" id="dg-p-pk"></span></div><div class="dg-mc-custom-pkg" id="dg-p-pkg-wrap" style="display:none;margin-top:6px"><div class="dg-field-wrap" style="max-width:180px"><input type="number" id="dg-p-pkg-input" class="dg-input" min="0" max="5" step="0.1" placeholder="g/ttkg" style="font-size:14px;padding:6px 10px"><span class="dg-input-unit">g/ttkg</span></div></div></div>
            </div>
            <div class="dg-mc dg-mc--fat">
                <div class="dg-mc-head"><span class="dg-mc-name"><span class="dg-mc-dot"></span>Zsír</span><span class="dg-mc-pct" id="dg-f-pct"></span></div>
                <div class="dg-mc-body"><input type="range" id="dg-fat-sl" class="dg-slider" min="15" max="50" step="1" value="30" disabled><div class="dg-mc-vals"><span class="dg-mc-gram" id="dg-f-g">0<small> g</small></span><span class="dg-mc-perkg" id="dg-f-pk"></span></div><div class="dg-mc-custom-pkg" id="dg-f-pkg-wrap" style="display:none;margin-top:6px"><div class="dg-field-wrap" style="max-width:180px"><input type="number" id="dg-f-pkg-input" class="dg-input" min="0" max="5" step="0.1" placeholder="g/ttkg" style="font-size:14px;padding:6px 10px"><span class="dg-input-unit">g/ttkg</span></div></div></div>
            </div>
            <div class="dg-mc dg-mc--carb">
                <div class="dg-mc-head"><span class="dg-mc-name"><span class="dg-mc-dot"></span>Szénhidrát <small style="font-weight:400;color:var(--dg-text-muted)">(maradék)</small></span><span class="dg-mc-pct" id="dg-c-pct"></span></div>
                <div class="dg-mc-body"><input type="range" id="dg-carb-sl" class="dg-slider" min="10" max="70" step="1" value="45" disabled><div class="dg-mc-vals"><span class="dg-mc-gram" id="dg-c-g">0<small> g</small></span><span class="dg-mc-perkg" id="dg-c-pk"></span></div><div class="dg-mc-custom-pkg" id="dg-c-pkg-wrap" style="display:none;margin-top:6px"><div class="dg-field-wrap" style="max-width:180px"><input type="number" id="dg-c-pkg-input" class="dg-input" min="0" max="5" step="0.1" placeholder="g/ttkg" style="font-size:14px;padding:6px 10px"><span class="dg-input-unit">g/ttkg</span></div></div></div>
            </div>
        </div>
        <div class="dg-macro-bar">
            <div class="dg-bar-seg dg-bar--prot" id="dg-bp" style="width:25%"></div>
            <div class="dg-bar-seg dg-bar--fat" id="dg-bf" style="width:30%"></div>
            <div class="dg-bar-seg dg-bar--carb" id="dg-bc" style="width:45%"></div>
        </div>
    </div>

    <!-- ⑤ ÉTKEZÉS -->
    <div class="dg-card">
        <div class="dg-card-title"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 8h1a4 4 0 010 8h-1"/><path d="M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>Étkezés tervezés</div>
        <div class="dg-meals-top-center">
            <div class="dg-field-label">Napi étkezések száma</div>
            <div class="dg-stepper">
                <button type="button" class="dg-step-btn" id="dg-mc-minus">−</button>
                <span class="dg-step-num" id="dg-mc-num"><?php echo $g_mc; ?></span>
                <button type="button" class="dg-step-btn" id="dg-mc-plus">+</button>
            </div>
        </div>
        <div id="dg-meals-warn-top" class="dg-macro-warn-banner" style="display:none"></div>
        <div id="dg-meals-body"></div>
    </div>

    <!-- ⑥ MENTÉS -->
    <div class="dg-card">
        <div class="dg-card-title"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17,21 17,13 7,13 7,21"/><polyline points="7,3 7,8 15,8"/></svg>Mentés &amp; Export</div>
        <div class="dg-actions">
            <button type="button" id="dg-pdf" class="dg-save dg-save--audit"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>📄 Teljes összegzés (PDF + mentés)</button>
            <button type="button" id="dg-save" class="dg-save-secondary"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17,21 17,13 7,13 7,21"/></svg>Célok mentése</button>
        </div>
        <div id="dg-save-msg" class="dp-profil-setting-msg"></div>
        <div id="dg-saved-widget" class="dg-saved-widget">
            <?php if ( $goal ) :
                $tl = array( 'cut' => 'Fogyás', 'maintain' => 'Szintentartás', 'bulk' => 'Tömegnövelés' );
            ?>
            <div class="dg-saved-current">
                <div class="dg-saved-badge">✅ Aktív terv</div>
                <div class="dg-saved-grid">
                    <div class="dg-saved-item"><span class="dg-saved-val"><?php echo isset( $tl[ $goal->goal_type ] ) ? $tl[ $goal->goal_type ] : $goal->goal_type; ?></span><span class="dg-saved-label">Cél</span></div>
                    <div class="dg-saved-item"><span class="dg-saved-val"><?php echo intval( $goal->daily_kcal ); ?></span><span class="dg-saved-label">kcal/nap</span></div>
                    <div class="dg-saved-item"><span class="dg-saved-val"><?php echo round( $goal->protein_pct ) . '/' . round( $goal->fat_pct ) . '/' . round( $goal->carb_pct ); ?></span><span class="dg-saved-label">F/Zs/CH</span></div>
                    <div class="dg-saved-item"><span class="dg-saved-val"><?php echo $g_mc; ?></span><span class="dg-saved-label">Étkezés</span></div>
                </div>
                <div class="dg-saved-date">Mentve: <?php echo esc_html( $goal->updated_at ); ?></div>
            </div>
            <?php else : ?>
            <div class="dg-saved-empty">Még nincs mentett célod.</div>
            <?php endif; ?>
        </div>
    </div>

</div>
<?php endif; ?>

</div>
<?php get_footer(); ?>