<?php

/**
 * 28. – Főoldal (front-page) tartalom + CSS + JS
 */
/**
 * 24 – Főoldal v3 FINAL – Szakértői pozícionálás
 * Code Snippets – Run everywhere
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* ============================================================
   RENDERELÉS
   ============================================================ */
function taplex_render_frontpage() {

    $count_tapanyag  = wp_count_posts('tapanyag')->publish  ?? 0;
    $count_recept    = wp_count_posts('recept')->publish    ?? 0;
    $count_alapanyag = wp_count_posts('alapanyag')->publish ?? 0;

    $receptek = new WP_Query([
        'post_type' => 'recept', 'posts_per_page' => 4,
        'post_status' => 'publish', 'orderby' => 'date', 'order' => 'DESC',
    ]);
    $tapanyagok = new WP_Query([
        'post_type' => 'tapanyag', 'posts_per_page' => 6,
        'post_status' => 'publish', 'orderby' => 'date', 'order' => 'DESC',
    ]);
    $tipusok = get_terms([ 'taxonomy' => 'tapanyag_tipus', 'hide_empty' => false ]);
    $tipus_meta = [
        'vitamin'       => ['icon'=>'💊','color'=>'#F59E0B','desc'=>'Vízben és zsírban oldódó vitaminok áttekintése'],
        'asvanyi-anyag' => ['icon'=>'⛰️','color'=>'#8B5CF6','desc'=>'Makro- és mikroelemek, elektrolitok'],
        'zsírsav'       => ['icon'=>'🫒','color'=>'#3B82F6','desc'=>'Omega-3, Omega-6 és egyéb zsírsavak'],
        'aminosav'      => ['icon'=>'🧬','color'=>'#EF4444','desc'=>'Esszenciális és nem esszenciális aminosavak'],
        'szenhidrat'    => ['icon'=>'⚡','color'=>'#10B981','desc'=>'Egyszerű és összetett szénhidrátok'],
    ];
    ?>
    <div id="taplex-frontpage" class="tl-fp">

    <!-- ====== HERO – kompakt, szakértői ====== -->
    <section class="tl-hero">
        <div class="tl-hero-bg">
            <div class="tl-hero-orb tl-hero-orb--1"></div>
            <div class="tl-hero-orb tl-hero-orb--2"></div>
            <div class="tl-hero-pattern"></div>
        </div>
        <div class="tl-c">
            <div class="tl-hero-inner">
                <div class="tl-hero-text">
                    <span class="tl-hero-tag tl-a" data-anim="fade-down">Táplálkozástudományi adatbázis</span>
                    <h1 class="tl-hero-title tl-a" data-anim="fade-up">
                        Ismerd meg, hogy<br><span class="tl-hero-accent">mit eszel!</span>
                    </h1>
                    <p class="tl-hero-desc tl-a" data-anim="fade-up" data-delay="60">
                        Tápanyag-leírások, élelmiszer-összetétel adatok és kalkulátorok – 
                        hogy értsd, mit eszel. A személyre szabott tanácsadást szakember végzi.
                    </p>
                    <div class="tl-hero-btns tl-a" data-anim="fade-up" data-delay="120">
                        <a href="<?php echo esc_url(home_url('/tapanyagok/')); ?>" class="tl-btn tl-btn--primary">
                            Tápanyag Lexikon
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                        </a>
                        <a href="<?php echo esc_url(home_url('/receptek/')); ?>" class="tl-btn tl-btn--sec">Receptek</a>
                    </div>
                </div>
                <div class="tl-hero-nums tl-a" data-anim="fade-up" data-delay="180">
                    <div class="tl-hero-num">
                        <span class="tl-hero-num-val" data-count="<?php echo (int)$count_tapanyag; ?>">0</span>
                        <span class="tl-hero-num-lbl">Tápanyag</span>
                    </div>
                    <div class="tl-hero-num-div"></div>
                    <div class="tl-hero-num">
                        <span class="tl-hero-num-val" data-count="<?php echo (int)$count_recept; ?>">0</span>
                        <span class="tl-hero-num-lbl">Recept</span>
                    </div>
                    <div class="tl-hero-num-div"></div>
                    <div class="tl-hero-num">
                        <span class="tl-hero-num-val" data-count="<?php echo (int)$count_alapanyag; ?>">0</span>
                        <span class="tl-hero-num-lbl">Alapanyag</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ====== JOGI FIGYELMEZTETÉS ====== -->
    <div class="tl-disclaimer">
        <div class="tl-c">
            <p class="tl-disclaimer-text">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Az oldalon található adatok tájékoztató jellegűek, orvosi vagy dietetikai tanácsadást nem helyettesítenek. 
                Személyre szabott étrendhez fordulj szakemberhez.
            </p>
        </div>
    </div>

    <!-- ====== BMR KALKULÁTOR ====== -->
    <section class="tl-sec tl-sec--bmr">
        <div class="tl-c">
            <div class="tl-bmr tl-a" data-anim="fade-up">
                <div class="tl-bmr-content">
                    <span class="tl-badge tl-badge--blue"><span>📊</span> Kalkulátor</span>
                    <h2 class="tl-bmr-title">Napi kalóriaszükséglet becslés</h2>
                    <p class="tl-bmr-desc">
                        A BMR kalkulátor becsült értéket ad az alapanyagcseréről – 
                        ez csak egy kiindulópont, nem feltétlenül személyre szabott.
                    </p>
                    <ul class="tl-bmr-feat">
                        <li class="tl-a" data-anim="fade-right" data-delay="60">
                            <span class="tl-bmr-fi">⚡</span>Azonnali becslés, regisztráció nélkül
                        </li>
                        <li class="tl-a" data-anim="fade-right" data-delay="120">
                            <span class="tl-bmr-fi">🎯</span>Tájékoztató makróeloszlás
                        </li>
                        <li class="tl-a" data-anim="fade-right" data-delay="180">
                            <span class="tl-bmr-fi">📱</span>Mobilon is használható
                        </li>
                    </ul>
                    <a href="<?php echo esc_url(home_url('/bmr-kalkulator/')); ?>" class="tl-btn tl-btn--primary">
                        Kalkulátor megnyitása
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                    <p class="tl-bmr-note">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        Becslés – pontos értékekhez dietetikus szükséges.
                    </p>
                </div>
                <div class="tl-bmr-visual" aria-hidden="true">
                    <div class="tl-bmr-mock">
                        <div class="tl-bmr-mock-head">
                            <span class="tl-bmr-dot" style="background:#fca5a5"></span>
                            <span class="tl-bmr-dot" style="background:#fcd34d"></span>
                            <span class="tl-bmr-dot" style="background:#86efac"></span>
                        </div>
                        <div class="tl-bmr-mock-body">
                            <div class="tl-bmr-mock-row">
                                <span class="tl-bmr-mock-lbl">Becsült napi kalória</span>
                                <span class="tl-bmr-mock-val">~2 150 kcal</span>
                            </div>
                            <div class="tl-bmr-bars">
                                <div class="tl-bmr-bar"><div class="tl-bmr-bf tl-bmr-bf--p" style="width:30%"></div><span>Fehérje ~30%</span></div>
                                <div class="tl-bmr-bar"><div class="tl-bmr-bf tl-bmr-bf--c" style="width:45%"></div><span>Szénhidrát ~45%</span></div>
                                <div class="tl-bmr-bar"><div class="tl-bmr-bf tl-bmr-bf--f" style="width:25%"></div><span>Zsír ~25%</span></div>
                            </div>
                            <p class="tl-bmr-mock-disc">*Tájékoztató jellegű becslés</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ====== RECEPTEK ====== -->
    <?php if ($receptek->have_posts()) : ?>
    <section class="tl-sec tl-sec--recipes">
        <div class="tl-c">
            <div class="tl-sec-head tl-a" data-anim="fade-up">
                <span class="tl-badge tl-badge--red"><span>🍳</span> Receptek</span>
                <h2 class="tl-sec-title">Legújabb receptek</h2>
                <p class="tl-sec-sub">Tápanyagdús receptek tájékoztató makrotápanyag adatokkal – a tudatos konyha kiindulópontja.</p>
            </div>
            <div class="tl-recipe-grid">
                <?php $ri=0; while($receptek->have_posts()): $receptek->the_post(); $ri++;
                    $kal=get_field('kaloria')?:''; $feh=get_field('feherje')?:'';
                    $rc=get_the_terms(get_the_ID(),'recept_kategoria');
                    $rn=($rc&&!is_wp_error($rc))?$rc[0]->name:'';
                ?>
                <a href="<?php the_permalink(); ?>" class="tl-rcard tl-a" data-anim="fade-up" data-delay="<?php echo $ri*70; ?>">
                    <div class="tl-rcard-img">
                        <?php if(has_post_thumbnail()): the_post_thumbnail('medium_large',['loading'=>'lazy']); else: ?>
                        <div class="tl-rcard-ph">🍽️</div>
                        <?php endif; ?>
                        <?php if($rn): ?><span class="tl-rcard-badge"><?php echo esc_html($rn); ?></span><?php endif; ?>
                    </div>
                    <div class="tl-rcard-body">
                        <h3 class="tl-rcard-title"><?php the_title(); ?></h3>
                        <?php if($kal||$feh): ?>
                        <div class="tl-rcard-macros">
                            <?php if($kal): ?><span>🔥 <?php echo esc_html($kal); ?> kcal</span><?php endif; ?>
                            <?php if($feh): ?><span>💪 <?php echo esc_html($feh); ?>g</span><?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
            <div class="tl-sec-cta tl-a" data-anim="fade-up">
                <a href="<?php echo esc_url(home_url('/receptek/')); ?>" class="tl-btn tl-btn--outline">Összes recept →</a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ====== TÁPANYAG KATEGÓRIÁK ====== -->
    <section class="tl-sec tl-sec--cats">
        <div class="tl-c">
            <div class="tl-sec-head tl-a" data-anim="fade-up">
                <span class="tl-badge"><span>🧬</span> Lexikon</span>
                <h2 class="tl-sec-title">Tápanyag kategóriák</h2>
                <p class="tl-sec-sub">A táplálkozás összetett tudomány – itt megismerheted az alapokat.</p>
            </div>
            <div class="tl-cat-grid">
                <?php if(!empty($tipusok)&&!is_wp_error($tipusok)): foreach($tipusok as $i=>$t):
                    $s=$t->slug; $m=$tipus_meta[$s]??['icon'=>'📋','color'=>'#6B7280','desc'=>'Kategória'];
                ?>
                <a href="<?php echo esc_url(get_term_link($t)); ?>" class="tl-catc tl-a" data-anim="fade-up" data-delay="<?php echo ($i+1)*50; ?>" style="--cc:<?php echo esc_attr($m['color']); ?>">
                    <div class="tl-catc-icon"><?php echo $m['icon']; ?></div>
                    <h3 class="tl-catc-name"><?php echo esc_html($t->name); ?></h3>
                    <p class="tl-catc-desc"><?php echo esc_html($m['desc']); ?></p>
                    <div class="tl-catc-foot">
                        <span class="tl-catc-count"><?php echo (int)$t->count; ?> tápanyag</span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </div>
                </a>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </section>

    <!-- ====== LEGÚJABB TÁPANYAGOK ====== -->
    <?php if($tapanyagok->have_posts()): ?>
    <section class="tl-sec tl-sec--nut">
        <div class="tl-c">
            <div class="tl-sec-head tl-a" data-anim="fade-up">
                <span class="tl-badge tl-badge--orange"><span>💊</span> Új bejegyzések</span>
                <h2 class="tl-sec-title">Legújabb tápanyag leírások</h2>
                <p class="tl-sec-sub">Tudományos forrásokra épülő, folyamatosan bővülő adatbázis.</p>
            </div>
            <div class="tl-nut-grid">
                <?php $ni=0; while($tapanyagok->have_posts()): $tapanyagok->the_post(); $ni++;
                    $kem=get_field('kemiai_nev')?:''; $oss=get_field('osszefoglalo')?:'';
                    $szn=get_field('kiemelt_szin')?:'#2d6a4f';
                    $tt=get_the_terms(get_the_ID(),'tapanyag_tipus');
                    $tn=($tt&&!is_wp_error($tt))?$tt[0]->name:'';
                    $ts=($tt&&!is_wp_error($tt))?$tt[0]->slug:'';
                    $nc=isset($tipus_meta[$ts])?$tipus_meta[$ts]['color']:$szn;
                ?>
                <a href="<?php the_permalink(); ?>" class="tl-nutc tl-a" data-anim="fade-up" data-delay="<?php echo $ni*60; ?>" style="--nc:<?php echo esc_attr($nc); ?>">
                    <div class="tl-nutc-top">
                        <?php if(has_post_thumbnail()): ?>
                            <div class="tl-nutc-img"><?php the_post_thumbnail('medium',['loading'=>'lazy']); ?></div>
                        <?php else: ?>
                            <div class="tl-nutc-img tl-nutc-img--ph"><span><?php echo mb_substr(get_the_title(),0,1); ?></span></div>
                        <?php endif; ?>
                        <?php if($tn): ?><span class="tl-nutc-badge"><?php echo esc_html($tn); ?></span><?php endif; ?>
                    </div>
                    <div class="tl-nutc-body">
                        <h3 class="tl-nutc-title"><?php the_title(); ?></h3>
                        <?php if($kem): ?><span class="tl-nutc-chem"><?php echo esc_html($kem); ?></span><?php endif; ?>
                        <?php if($oss): ?><p class="tl-nutc-exc"><?php echo esc_html(wp_trim_words($oss,14,'…')); ?></p><?php endif; ?>
                    </div>
                    <div class="tl-nutc-foot"><span>Részletek</span>
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </div>
                </a>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
            <div class="tl-sec-cta tl-a" data-anim="fade-up">
                <a href="<?php echo esc_url(home_url('/tapanyagok/')); ?>" class="tl-btn tl-btn--outline">Összes tápanyag →</a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ====== MIÉRT FONTOS A SZAKEMBER? ====== -->
    <section class="tl-sec tl-sec--expert">
        <div class="tl-c">
            <div class="tl-expert tl-a" data-anim="fade-up">
                <div class="tl-expert-icon">👨‍⚕️</div>
                <h2 class="tl-expert-title">Az adat önmagában nem elég</h2>
                <p class="tl-expert-desc">
                    A tápanyag adatbázis segít megérteni az összefüggéseket – de a személyre szabott 
                    étrend kialakításához figyelembe kell venni az egyéni állapotot, betegségeket, 
                    gyógyszerhasználatot és az életmódot. Ezt csak szakember tudja megbízhatóan elvégezni.
                </p>
                <div class="tl-expert-points">
                    <div class="tl-expert-point tl-a" data-anim="fade-right" data-delay="60">
                        <span class="tl-expert-point-icon">🔬</span>
                        <div>
                            <strong>Egyéni szükséglet</strong>
                            <p>A napi beviteli ajánlások átlagértékek – a te szükségleted ettől eltérhet.</p>
                        </div>
                    </div>
                    <div class="tl-expert-point tl-a" data-anim="fade-right" data-delay="120">
                        <span class="tl-expert-point-icon">⚖️</span>
                        <div>
                            <strong>Kölcsönhatások</strong>
                            <p>A tápanyagok egymásra hatnak – a helyes kombináció nem triviális.</p>
                        </div>
                    </div>
                    <div class="tl-expert-point tl-a" data-anim="fade-right" data-delay="180">
                        <span class="tl-expert-point-icon">🎯</span>
                        <div>
                            <strong>Célzott megoldás</strong>
                            <p>Az internetes információ általános – a te problémádra célzott válasz kell.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ====== HOGYAN MŰKÖDIK ====== -->
    <section class="tl-sec tl-sec--how">
        <div class="tl-c">
            <div class="tl-sec-head tl-a" data-anim="fade-up">
                <span class="tl-badge tl-badge--green"><span>✨</span> 3 lépés</span>
                <h2 class="tl-sec-title">Hogyan használd az oldalt?</h2>
            </div>
            <div class="tl-how-grid">
                <div class="tl-howc tl-a" data-anim="fade-right">
                    <div class="tl-howc-num">1</div>
                    <div class="tl-howc-icon">🔍</div>
                    <h3 class="tl-howc-t">Tájékozódj</h3>
                    <p class="tl-howc-d">Böngéssz a tápanyag leírások között és ismerd meg, mi mire jó a szervezetben.</p>
                </div>
                <div class="tl-how-conn" aria-hidden="true"><svg width="36" height="2"><line x1="0" y1="1" x2="36" y2="1" stroke="#ddd" stroke-width="2" stroke-dasharray="5 4"/></svg></div>
                <div class="tl-howc tl-a" data-anim="fade-up" data-delay="100">
                    <div class="tl-howc-num">2</div>
                    <div class="tl-howc-icon">📖</div>
                    <h3 class="tl-howc-t">Értsd meg</h3>
                    <p class="tl-howc-d">Olvasd el a részleteket: források, szükséglet, hiánytünetek, kölcsönhatások.</p>
                </div>
                <div class="tl-how-conn" aria-hidden="true"><svg width="36" height="2"><line x1="0" y1="1" x2="36" y2="1" stroke="#ddd" stroke-width="2" stroke-dasharray="5 4"/></svg></div>
                <div class="tl-howc tl-a" data-anim="fade-left" data-delay="200">
                    <div class="tl-howc-num">3</div>
                    <div class="tl-howc-icon">👨‍⚕️</div>
                    <h3 class="tl-howc-t">Kérdezz szakembert</h3>
                    <p class="tl-howc-d">A megszerzett tudással felkészültebben fordulhatsz dietetikushoz.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ====== STATS ====== -->
    <section class="tl-sec tl-sec--stats">
        <div class="tl-c">
            <div class="tl-stats-grid">
                <div class="tl-stat tl-a" data-anim="fade-up">
                    <span class="tl-stat-icon">🧬</span>
                    <span class="tl-stat-num" data-count="<?php echo (int)$count_tapanyag; ?>">0</span>
                    <span class="tl-stat-lbl">Tápanyag leírás</span>
                </div>
                <div class="tl-stat tl-a" data-anim="fade-up" data-delay="60">
                    <span class="tl-stat-icon">🥕</span>
                    <span class="tl-stat-num" data-count="<?php echo (int)$count_alapanyag; ?>">0</span>
                    <span class="tl-stat-lbl">Alapanyag</span>
                </div>
                <div class="tl-stat tl-a" data-anim="fade-up" data-delay="120">
                    <span class="tl-stat-icon">🍳</span>
                    <span class="tl-stat-num" data-count="<?php echo (int)$count_recept; ?>">0</span>
                    <span class="tl-stat-lbl">Recept</span>
                </div>
                <div class="tl-stat tl-a" data-anim="fade-up" data-delay="180">
                    <span class="tl-stat-icon">📊</span>
                    <span class="tl-stat-num" data-count="2">0</span>
                    <span class="tl-stat-lbl">Adatforrás</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ====== CTA ZÁRÓ ====== -->
    <section class="tl-sec tl-sec--cta">
        <div class="tl-c">
            <div class="tl-cta tl-a" data-anim="fade-up">
                <div class="tl-cta-orb"></div>
                <h2 class="tl-cta-title">Tudatosan táplálkozni jó – szakemberrel még jobb</h2>
                <p class="tl-cta-desc">Ismerd meg a tápanyagokat, számold ki a becsült szükségleted, és ha készen állsz – keress egy dietetikust.</p>
                <div class="tl-cta-btns">
                    <a href="<?php echo esc_url(home_url('/tapanyagok/')); ?>" class="tl-btn tl-btn--white">
                        Tápanyag Lexikon
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                    <a href="<?php echo esc_url(home_url('/bmr-kalkulator/')); ?>" class="tl-btn tl-btn--ghost">BMR Kalkulátor</a>
                </div>
            </div>
        </div>
    </section>

    </div>
    <?php
}

/* ============================================================
   CSS
   ============================================================ */
add_action('wp_head','taplex_fp_css',25);
function taplex_fp_css(){
    if(!is_front_page()) return;
    ?><style id="taplex-fp-css">

    .tl-fp{--g:#2d6a4f;--g-dk:#1b4332;--g-lt:#40b882;--g-s:rgba(45,106,79,.08);--or:#F59E0B;--bl:#3B82F6;--rd:#EF4444;--pu:#8B5CF6;--em:#10B981;--bg:#f5f6f5;--bg2:#eef1ef;--card:#fff;--brd:#e8ebe9;--tx:#1e1e1e;--mu:#7c8a83;--r:14px;--rs:8px;--rl:20px;--sh:0 4px 20px rgba(0,0,0,.05);--shl:0 8px 32px rgba(0,0,0,.07);--shx:0 12px 44px rgba(0,0,0,.09);--tr:.3s cubic-bezier(.4,0,.2,1);overflow-x:hidden}
    .tl-fp *,.tl-fp *::before,.tl-fp *::after{box-sizing:border-box}
    .tl-c{max-width:1200px;margin:0 auto;padding:0 24px;position:relative}

    /* ANIMÁCIÓ */
    .tl-a{opacity:0;transition:opacity .6s cubic-bezier(.4,0,.2,1),transform .6s cubic-bezier(.4,0,.2,1)}
    .tl-a[data-anim="fade-up"]{transform:translateY(28px)}
    .tl-a[data-anim="fade-down"]{transform:translateY(-20px)}
    .tl-a[data-anim="fade-right"]{transform:translateX(-36px)}
    .tl-a[data-anim="fade-left"]{transform:translateX(36px)}
    .tl-a.is-visible{opacity:1;transform:translate(0) scale(1)}

    /* GOMBOK */
    .tl-btn{display:inline-flex;align-items:center;gap:6px;padding:11px 22px;font-size:13.5px;font-weight:600;border-radius:var(--rs);text-decoration:none;border:none;cursor:pointer;white-space:nowrap;transition:transform var(--tr),box-shadow var(--tr),background var(--tr),color var(--tr)}
    .tl-btn--primary{background:var(--g);color:#fff;box-shadow:0 3px 12px rgba(45,106,79,.25)}
    .tl-btn--primary:hover{background:var(--g-dk);transform:translateY(-2px);box-shadow:0 5px 18px rgba(45,106,79,.35);color:#fff}
    .tl-btn--sec{background:var(--card);color:var(--tx);border:1.5px solid var(--brd);box-shadow:var(--sh)}
    .tl-btn--sec:hover{border-color:var(--g);color:var(--g);transform:translateY(-2px)}
    .tl-btn--outline{background:transparent;color:var(--g);border:1.5px solid var(--g);padding:10px 20px}
    .tl-btn--outline:hover{background:var(--g-s);transform:translateY(-2px)}
    .tl-btn--white{background:#fff;color:var(--g-dk);box-shadow:0 4px 16px rgba(0,0,0,.12)}
    .tl-btn--white:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,.18);color:var(--g-dk)}
    .tl-btn--ghost{background:rgba(255,255,255,.12);color:#fff;border:1.5px solid rgba(255,255,255,.25)}
    .tl-btn--ghost:hover{background:rgba(255,255,255,.2);transform:translateY(-2px);color:#fff}

    /* BADGE */
    .tl-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:100px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;background:var(--g-s);color:var(--g);margin-bottom:10px}
    .tl-badge--or{background:rgba(245,158,11,.1);color:#D97706}
    .tl-badge--orange{background:rgba(245,158,11,.1);color:#D97706}
    .tl-badge--blue{background:rgba(59,130,246,.1);color:#2563EB}
    .tl-badge--green{background:rgba(16,185,129,.1);color:#059669}
    .tl-badge--red{background:rgba(239,68,68,.1);color:#DC2626}

    /* ===== HERO ===== */
    .tl-hero{position:relative;padding:48px 0 36px;background:linear-gradient(165deg,#f0faf4 0%,#f5f6f5 40%,#fefefe 100%);overflow:hidden}
    .tl-hero-bg{position:absolute;inset:0;pointer-events:none}
    .tl-hero-orb{position:absolute;border-radius:50%;filter:blur(70px);opacity:.25}
    .tl-hero-orb--1{width:360px;height:360px;background:radial-gradient(circle,rgba(45,106,79,.3),transparent 70%);top:-15%;right:-5%;animation:tlOrb 12s ease-in-out infinite}
    .tl-hero-orb--2{width:250px;height:250px;background:radial-gradient(circle,rgba(245,158,11,.2),transparent 70%);bottom:0;left:-8%;animation:tlOrb 15s ease-in-out infinite reverse}
    @keyframes tlOrb{0%,100%{transform:translate(0)}33%{transform:translate(20px,-14px)}66%{transform:translate(-14px,20px)}}
    .tl-hero-pattern{position:absolute;inset:0;background-image:linear-gradient(rgba(45,106,79,.03) 1px,transparent 1px),linear-gradient(90deg,rgba(45,106,79,.03) 1px,transparent 1px);background-size:60px 60px;mask-image:radial-gradient(ellipse 70% 60% at 50% 40%,black 30%,transparent 70%);-webkit-mask-image:radial-gradient(ellipse 70% 60% at 50% 40%,black 30%,transparent 70%)}
    .tl-hero-inner{max-width:680px;margin:0 auto;text-align:center}
    .tl-hero-tag{display:inline-flex;align-items:center;gap:6px;padding:5px 14px;background:var(--g-s);border:1px solid rgba(45,106,79,.1);border-radius:100px;font-size:11.5px;font-weight:600;color:var(--g);margin-bottom:16px}
    .tl-hero-title{font-size:clamp(28px,4vw,44px);font-weight:800;line-height:1.15;color:var(--tx);margin:0 0 12px;letter-spacing:-.3px}
    .tl-hero-accent{background:linear-gradient(135deg,var(--g),var(--g-lt));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
    .tl-hero-desc{font-size:14.5px;line-height:1.6;color:var(--mu);margin:0 0 20px;max-width:520px;margin-left:auto;margin-right:auto}
    .tl-hero-btns{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-bottom:28px}
    .tl-hero-nums{display:flex;align-items:center;justify-content:center;gap:20px}
    .tl-hero-num{display:flex;flex-direction:column;gap:1px;align-items:center}
    .tl-hero-num-val{font-size:24px;font-weight:800;color:var(--g);line-height:1.1;font-variant-numeric:tabular-nums}
    .tl-hero-num-lbl{font-size:11px;color:var(--mu);font-weight:500}
    .tl-hero-num-div{width:1px;height:28px;background:var(--brd)}

    /* ===== DISCLAIMER ===== */
    .tl-disclaimer{background:#fefce8;border-top:1px solid #fde68a;border-bottom:1px solid #fde68a;padding:10px 0}
    .tl-disclaimer-text{display:flex;align-items:center;justify-content:center;gap:6px;font-size:11.5px;color:#92400e;margin:0;text-align:center;line-height:1.4;flex-wrap:wrap}
    .tl-disclaimer-text svg{flex-shrink:0;color:#d97706}

    /* ===== SZEKCIÓ ALAP ===== */
    .tl-sec{padding:44px 0}
    .tl-sec--cats,.tl-sec--how{background:var(--bg)}
    .tl-sec--recipes,.tl-sec--nut{background:#fff}
    .tl-sec-head{text-align:center;margin-bottom:26px}
    .tl-sec-title{font-size:clamp(22px,2.8vw,30px);font-weight:800;color:var(--tx);margin:0 0 6px;letter-spacing:-.2px}
    .tl-sec-sub{font-size:13.5px;line-height:1.55;color:var(--mu);max-width:460px;margin:0 auto}
    .tl-sec-cta{text-align:center;margin-top:24px}

    /* ===== BMR ===== */
    .tl-sec--bmr{padding:0;margin-top:-6px;position:relative;z-index:3}
    .tl-bmr{display:grid;grid-template-columns:1fr 1fr;background:var(--card);border-radius:var(--rl);border:1px solid var(--brd);box-shadow:var(--shx);overflow:hidden}
    .tl-bmr-content{padding:32px 36px}
    .tl-bmr-title{font-size:22px;font-weight:800;color:var(--tx);margin:0 0 8px}
    .tl-bmr-desc{font-size:13.5px;line-height:1.6;color:var(--mu);margin:0 0 18px}
    .tl-bmr-feat{list-style:none;padding:0;margin:0 0 20px}
    .tl-bmr-feat li{display:flex;align-items:center;gap:10px;padding:7px 0;font-size:13px;font-weight:500;color:var(--tx)}
    .tl-bmr-fi{font-size:16px;width:30px;height:30px;display:flex;align-items:center;justify-content:center;background:var(--g-s);border-radius:var(--rs);flex-shrink:0}
    .tl-bmr-note{display:flex;align-items:center;gap:5px;margin:14px 0 0;font-size:11px;color:var(--mu);font-style:italic}
    .tl-bmr-note svg{flex-shrink:0;opacity:.6}
    .tl-bmr-visual{background:linear-gradient(135deg,#f0faf4,#e8f5ee);display:flex;align-items:center;justify-content:center;padding:32px 20px}
    .tl-bmr-mock{width:100%;max-width:260px;background:#fff;border-radius:var(--r);box-shadow:var(--shl);overflow:hidden;border:1px solid var(--brd)}
    .tl-bmr-mock-head{display:flex;gap:5px;padding:9px 12px;background:#f9fafb;border-bottom:1px solid var(--brd)}
    .tl-bmr-dot{width:8px;height:8px;border-radius:50%;background:#e5e7eb}
    .tl-bmr-mock-body{padding:18px}
    .tl-bmr-mock-row{display:flex;justify-content:space-between;align-items:baseline;margin-bottom:14px;padding-bottom:10px;border-bottom:1px dashed var(--brd)}
    .tl-bmr-mock-lbl{font-size:11px;color:var(--mu);font-weight:500}
    .tl-bmr-mock-val{font-size:18px;font-weight:800;color:var(--g)}
    .tl-bmr-bars{display:flex;flex-direction:column;gap:8px}
    .tl-bmr-bar{position:relative;height:22px;background:#f3f4f6;border-radius:6px;overflow:hidden}
    .tl-bmr-bar span{position:absolute;left:8px;top:50%;transform:translateY(-50%);font-size:10px;font-weight:600;color:#fff;z-index:2;text-shadow:0 1px 2px rgba(0,0,0,.15)}
    .tl-bmr-bf{position:absolute;top:0;left:0;height:100%;border-radius:6px;transition:width 1.4s cubic-bezier(.4,0,.2,1)}
    .tl-bmr-bf--p{background:linear-gradient(90deg,#EF4444,#F87171)}
    .tl-bmr-bf--c{background:linear-gradient(90deg,#F59E0B,#FBBF24)}
    .tl-bmr-bf--f{background:linear-gradient(90deg,#3B82F6,#60A5FA)}
    .tl-bmr-mock-disc{font-size:9.5px;color:var(--mu);text-align:center;margin:10px 0 0;font-style:italic}

    /* ===== RECEPT KÁRTYÁK ===== */
    .tl-recipe-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
    .tl-rcard{background:var(--card);border:1px solid var(--brd);border-radius:var(--r);overflow:hidden;text-decoration:none;transition:transform var(--tr),box-shadow var(--tr),border-color var(--tr)}
    .tl-rcard:hover{transform:translateY(-3px);box-shadow:var(--shl);border-color:var(--or)}
    .tl-rcard-img{position:relative;height:155px;overflow:hidden;background:var(--bg)}
    .tl-rcard-img img{width:100%;height:100%;object-fit:cover;transition:transform .5s ease}
    .tl-rcard:hover .tl-rcard-img img{transform:scale(1.04)}
    .tl-rcard-ph{display:flex;align-items:center;justify-content:center;font-size:36px;background:linear-gradient(135deg,#fef3c7,#fde68a);height:100%}
    .tl-rcard-badge{position:absolute;top:8px;left:8px;padding:3px 9px;background:rgba(255,255,255,.92);backdrop-filter:blur(6px);border-radius:100px;font-size:10.5px;font-weight:700;color:var(--or)}
    .tl-rcard-body{padding:12px 14px 14px}
    .tl-rcard-title{font-size:14px;font-weight:700;color:var(--tx);margin:0 0 7px;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
    .tl-rcard-macros{display:flex;gap:6px;flex-wrap:wrap}
    .tl-rcard-macros span{font-size:10.5px;font-weight:600;color:var(--mu);padding:3px 7px;background:var(--bg);border-radius:5px}

    /* ===== KATEGÓRIA KÁRTYÁK ===== */
    .tl-cat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px}
    .tl-catc{position:relative;background:var(--card);border:1px solid var(--brd);border-radius:var(--r);padding:20px 18px 16px;text-decoration:none;transition:transform var(--tr),box-shadow var(--tr),border-color var(--tr);overflow:hidden}
    .tl-catc::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--cc);transform:scaleX(0);transform-origin:left;transition:transform var(--tr)}
    .tl-catc:hover{transform:translateY(-3px);box-shadow:var(--shl);border-color:var(--cc)}
    .tl-catc:hover::before{transform:scaleX(1)}
    .tl-catc-icon{font-size:28px;margin-bottom:8px;line-height:1}
    .tl-catc-name{font-size:14.5px;font-weight:700;color:var(--tx);margin:0 0 5px}
    .tl-catc-desc{font-size:11.5px;line-height:1.45;color:var(--mu);margin:0 0 10px}
    .tl-catc-foot{display:flex;align-items:center;justify-content:space-between}
    .tl-catc-count{font-size:10.5px;font-weight:600;color:var(--cc)}
    .tl-catc-foot svg{color:var(--mu);transition:color var(--tr),transform var(--tr)}
    .tl-catc:hover .tl-catc-foot svg{color:var(--cc);transform:translateX(3px)}

    /* ===== TÁPANYAG KÁRTYÁK ===== */
    .tl-nut-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
    .tl-nutc{background:var(--card);border:1px solid var(--brd);border-radius:var(--r);overflow:hidden;text-decoration:none;display:flex;flex-direction:column;transition:transform var(--tr),box-shadow var(--tr),border-color var(--tr)}
    .tl-nutc:hover{transform:translateY(-3px);box-shadow:var(--shl);border-color:var(--nc)}
    .tl-nutc-top{position:relative;height:140px;overflow:hidden;background:var(--bg)}
    .tl-nutc-img{width:100%;height:100%}
    .tl-nutc-img img{width:100%;height:100%;object-fit:cover;transition:transform .5s ease}
    .tl-nutc:hover .tl-nutc-img img{transform:scale(1.04)}
    .tl-nutc-img--ph{display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--bg),var(--bg2))}
    .tl-nutc-img--ph span{font-size:36px;font-weight:800;color:var(--brd)}
    .tl-nutc-badge{position:absolute;top:8px;left:8px;padding:3px 9px;background:rgba(255,255,255,.92);backdrop-filter:blur(6px);border-radius:100px;font-size:10px;font-weight:700;color:var(--nc)}
    .tl-nutc-body{padding:14px 14px 6px;flex:1}
    .tl-nutc-title{font-size:14px;font-weight:700;color:var(--tx);margin:0 0 2px;line-height:1.3}
    .tl-nutc-chem{display:inline-block;font-size:10.5px;color:var(--mu);font-style:italic;margin-bottom:5px}
    .tl-nutc-exc{font-size:12px;line-height:1.45;color:var(--mu);margin:0}
    .tl-nutc-foot{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-top:1px solid var(--brd);font-size:11.5px;font-weight:600;color:var(--nc);transition:padding-left var(--tr)}
    .tl-nutc:hover .tl-nutc-foot{padding-left:18px}

    /* ===== SZAKEMBER SZEKCIÓ ===== */
    .tl-sec--expert{background:linear-gradient(135deg,#f0faf4,#f5f6f5);padding:44px 0}
    .tl-expert{max-width:760px;margin:0 auto;text-align:center}
    .tl-expert-icon{font-size:40px;margin-bottom:12px}
    .tl-expert-title{font-size:clamp(20px,2.5vw,26px);font-weight:800;color:var(--tx);margin:0 0 10px}
    .tl-expert-desc{font-size:13.5px;line-height:1.6;color:var(--mu);margin:0 0 28px;max-width:560px;margin-left:auto;margin-right:auto}
    .tl-expert-points{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;text-align:left}
    .tl-expert-point{display:flex;gap:12px;padding:18px 16px;background:var(--card);border:1px solid var(--brd);border-radius:var(--r);transition:transform var(--tr),box-shadow var(--tr)}
    .tl-expert-point:hover{transform:translateY(-2px);box-shadow:var(--shl)}
    .tl-expert-point-icon{font-size:24px;flex-shrink:0;width:40px;height:40px;display:flex;align-items:center;justify-content:center;background:var(--g-s);border-radius:var(--rs)}
    .tl-expert-point strong{display:block;font-size:13.5px;font-weight:700;color:var(--tx);margin-bottom:4px}
    .tl-expert-point p{font-size:12px;line-height:1.45;color:var(--mu);margin:0}

    /* ===== HOGYAN MŰKÖDIK ===== */
    .tl-how-grid{display:flex;align-items:flex-start;justify-content:center}
    .tl-howc{flex:1;max-width:240px;text-align:center;padding:24px 20px;background:var(--card);border:1px solid var(--brd);border-radius:var(--r);position:relative;transition:transform var(--tr),box-shadow var(--tr)}
    .tl-howc:hover{transform:translateY(-3px);box-shadow:var(--shl)}
    .tl-howc-num{position:absolute;top:-12px;left:50%;transform:translateX(-50%);width:26px;height:26px;background:var(--g);color:#fff;font-size:11px;font-weight:800;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 8px rgba(45,106,79,.25)}
    .tl-howc-icon{font-size:30px;margin-bottom:10px;line-height:1}
    .tl-howc-t{font-size:15px;font-weight:700;color:var(--tx);margin:0 0 6px}
    .tl-howc-d{font-size:12px;line-height:1.5;color:var(--mu);margin:0}
    .tl-how-conn{display:flex;align-items:center;padding-top:44px;flex-shrink:0;width:36px}

    /* ===== STATS ===== */
    .tl-sec--stats{background:linear-gradient(135deg,var(--g-dk),var(--g));padding:36px 0}
    .tl-stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px}
    .tl-stat{text-align:center;display:flex;flex-direction:column;align-items:center;gap:3px}
    .tl-stat-icon{font-size:24px;margin-bottom:2px}
    .tl-stat-num{font-size:30px;font-weight:800;color:#fff;line-height:1.1;font-variant-numeric:tabular-nums}
    .tl-stat-lbl{font-size:12px;color:rgba(255,255,255,.6);font-weight:500}

    /* ===== CTA ===== */
    .tl-sec--cta{padding:24px 0 44px;background:#fff}
    .tl-cta{position:relative;background:linear-gradient(135deg,var(--g-dk),var(--g),#34d399);border-radius:var(--rl);padding:38px 32px;text-align:center;overflow:hidden}
    .tl-cta-orb{position:absolute;width:300px;height:300px;border-radius:50%;background:rgba(255,255,255,.06);top:-80px;right:-50px;pointer-events:none}
    .tl-cta-title{font-size:clamp(20px,2.6vw,26px);font-weight:800;color:#fff;margin:0 0 8px;position:relative}
    .tl-cta-desc{font-size:13.5px;line-height:1.55;color:rgba(255,255,255,.8);max-width:450px;margin:0 auto 20px;position:relative}
    .tl-cta-btns{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;position:relative}

    /* ===== RESPONSIVE ===== */
    @media(max-width:960px){
        .tl-hero{padding:40px 0 28px}
        .tl-bmr{grid-template-columns:1fr}
        .tl-bmr-visual{padding:24px 16px}
        .tl-nut-grid{grid-template-columns:repeat(2,1fr)}
        .tl-recipe-grid{grid-template-columns:repeat(2,1fr)}
        .tl-how-grid{flex-direction:column;align-items:center}
        .tl-how-conn{transform:rotate(90deg);padding-top:0;width:36px;height:24px}
        .tl-howc{max-width:300px;width:100%}
        .tl-expert-points{grid-template-columns:1fr}
        .tl-stats-grid{grid-template-columns:repeat(2,1fr)}
    }
    @media(max-width:600px){
        .tl-sec{padding:34px 0}
        .tl-hero{padding:32px 0 22px}
        .tl-hero-title{font-size:26px}
        .tl-hero-nums{flex-direction:column;gap:10px}
        .tl-hero-num-div{width:32px;height:1px}
        .tl-bmr-content{padding:24px 18px}
        .tl-bmr-title{font-size:18px}
        .tl-cat-grid{grid-template-columns:1fr 1fr;gap:10px}
        .tl-catc{padding:16px 14px 14px}
        .tl-nut-grid,.tl-recipe-grid{grid-template-columns:1fr}
        .tl-stats-grid{grid-template-columns:1fr 1fr;gap:16px}
        .tl-stat-num{font-size:24px}
        .tl-cta{padding:28px 18px}
        .tl-cta-btns{flex-direction:column;align-items:center}
        .tl-expert-points{gap:10px}
    }
    @media(max-width:380px){
        .tl-cat-grid{grid-template-columns:1fr}
    }

    </style><?php
}

/* ============================================================
   JS
   ============================================================ */
add_action('wp_footer','taplex_fp_js',98);
function taplex_fp_js(){
    if(!is_front_page()) return;
    ?><script id="taplex-fp-js">
    (function(){
        'use strict';
        var els=document.querySelectorAll('.tl-a');
        if(!els.length) return;
        var obs=new IntersectionObserver(function(entries){
            entries.forEach(function(e){
                if(e.isIntersecting){
                    var el=e.target,d=parseInt(el.getAttribute('data-delay'))||0;
                    setTimeout(function(){el.classList.add('is-visible')},d);
                    obs.unobserve(el);
                }
            });
        },{threshold:.1,rootMargin:'0px 0px -30px 0px'});
        els.forEach(function(el){obs.observe(el)});

        var nums=document.querySelectorAll('[data-count]');
        var cO=new IntersectionObserver(function(entries){
            entries.forEach(function(e){
                if(e.isIntersecting){
                    var el=e.target,tgt=parseInt(el.getAttribute('data-count'))||0;
                    if(tgt===0){el.textContent='0';cO.unobserve(el);return;}
                    var dur=1800,start=performance.now();
                    function ease(t){return 1-Math.pow(1-t,4);}
                    function tick(now){
                        var p=Math.min((now-start)/dur,1);
                        el.textContent=Math.round(ease(p)*tgt).toLocaleString('hu-HU');
                        if(p<1) requestAnimationFrame(tick);
                    }
                    requestAnimationFrame(tick);
                    cO.unobserve(el);
                }
            });
        },{threshold:.3});
        nums.forEach(function(el){cO.observe(el)});

        var bars=document.querySelectorAll('.tl-bmr-bf');
        var bO=new IntersectionObserver(function(entries){
            entries.forEach(function(e){
                if(e.isIntersecting){
                    var bar=e.target,w=bar.style.width;
                    bar.style.width='0%';
                    setTimeout(function(){bar.style.width=w},200);
                    bO.unobserve(bar);
                }
            });
        },{threshold:.5});
        bars.forEach(function(b){bO.observe(b)});
    })();
    </script><?php
}
