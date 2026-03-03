<?php
/**
 * Template Name: BMR Kalkulátor
 * v3.0 – Tiszta BMI + BMR/TDEE kalkulátor, cél nélkül
 */
get_header();
$logged_in = is_user_logged_in();
$nonce     = $logged_in ? wp_create_nonce( 'dp_profil_nonce' ) : '';
$ajax_url  = admin_url( 'admin-ajax.php' );
?>

<main id="primary" class="site-main bmr-page">
    <article class="bmr-container">

        <div class="bmr-hero">
            <h1>Kal&oacute;ria Kalkul&aacute;tor</h1>
            <p class="bmr-hero-sub">Sz&aacute;mold ki az alapanyagcser&eacute;d &eacute;s napi kal&oacute;riasz&uuml;ks&eacute;gleted tudom&aacute;nyos k&eacute;pletekkel</p>
            <div class="bmr-hero-disclaimer">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span>Ez a kalkul&aacute;tor kiz&aacute;r&oacute;lag t&aacute;j&eacute;koztat&oacute; jelleg&#369; &ndash; nem min&#337;s&uuml;l orvosi tan&aacute;csad&aacute;snak. Szem&eacute;lyre szabott &eacute;trendi t&aacute;j&eacute;koztat&aacute;s&eacute;rt vagy tan&aacute;cs&eacute;rt fordulj dietetikushoz vagy orvoshoz.</span>
            </div>
        </div>

        <div class="bmr-layout">

            <!-- ══ FORM ══ -->
            <div class="bmr-card bmr-card--form">
                <div class="bmr-card-header">Adatok megad&aacute;sa</div>
                <div class="bmr-card-body">

                    <div class="bmr-field">
                        <label class="bmr-field-label">Nem</label>
                        <div class="bmr-gender-toggle">
                            <button type="button" class="bmr-gender-btn is-active" data-gender="ferfi"><span class="bmr-gender-icon">&#9794;</span> F&eacute;rfi</button>
                            <button type="button" class="bmr-gender-btn" data-gender="no"><span class="bmr-gender-icon">&#9792;</span> N&#337;</button>
                        </div>
                    </div>

                    <div class="bmr-field">
                        <label class="bmr-field-label" for="bmr-kor">Kor</label>
                        <div class="bmr-input-row">
                            <input type="range" id="bmr-kor-slider" class="bmr-slider" min="10" max="120" value="30" step="1">
                            <div class="bmr-input-inline"><input type="number" id="bmr-kor" class="bmr-input-num" min="10" max="120" value="30" step="1"><span class="bmr-input-unit">&eacute;v</span></div>
                        </div>
                    </div>

                    <div class="bmr-field">
                        <label class="bmr-field-label" for="bmr-magassag">Magass&aacute;g</label>
                        <div class="bmr-input-row">
                            <input type="range" id="bmr-magassag-slider" class="bmr-slider" min="100" max="250" value="175" step="1">
                            <div class="bmr-input-inline"><input type="number" id="bmr-magassag" class="bmr-input-num" min="100" max="250" value="175" step="1"><span class="bmr-input-unit">cm</span></div>
                        </div>
                    </div>

                    <div class="bmr-field">
                        <label class="bmr-field-label" for="bmr-suly">Tests&uacute;ly</label>
                        <div class="bmr-input-row">
                            <input type="range" id="bmr-suly-slider" class="bmr-slider" min="30" max="300" value="75" step="0.5">
                            <div class="bmr-input-inline"><input type="number" id="bmr-suly" class="bmr-input-num" min="30" max="300" value="75" step="0.5"><span class="bmr-input-unit">kg</span></div>
                        </div>
                    </div>

                    <div class="bmr-field">
                        <label class="bmr-field-label">Aktivit&aacute;si szint</label>
                        <div class="bmr-activity-list">
                            <label class="bmr-activity-option"><input type="radio" name="bmr-activity" value="1.2" data-athlete="0"><div class="bmr-activity-card"><span class="bmr-activity-name">&Uuml;l&#337; &eacute;letm&oacute;d</span><span class="bmr-activity-desc">Kev&eacute;s vagy nincs testmozg&aacute;s</span><span class="bmr-activity-factor">&times; 1.2</span></div></label>
                            <label class="bmr-activity-option"><input type="radio" name="bmr-activity" value="1.375" data-athlete="0"><div class="bmr-activity-card"><span class="bmr-activity-name">Enyh&eacute;n akt&iacute;v</span><span class="bmr-activity-desc">Heti 1-3 k&ouml;nny&#369; edz&eacute;s</span><span class="bmr-activity-factor">&times; 1.375</span></div></label>
                            <label class="bmr-activity-option"><input type="radio" name="bmr-activity" value="1.55" data-athlete="0" checked><div class="bmr-activity-card"><span class="bmr-activity-name">M&eacute;rs&eacute;kelten akt&iacute;v</span><span class="bmr-activity-desc">Heti 3-5 k&ouml;zepes edz&eacute;s</span><span class="bmr-activity-factor">&times; 1.55</span></div></label>
                            <label class="bmr-activity-option"><input type="radio" name="bmr-activity" value="1.725" data-athlete="0"><div class="bmr-activity-card"><span class="bmr-activity-name">Nagyon akt&iacute;v</span><span class="bmr-activity-desc">Heti 6-7 intenz&iacute;v edz&eacute;s</span><span class="bmr-activity-factor">&times; 1.725</span></div></label>
                            <label class="bmr-activity-option"><input type="radio" name="bmr-activity" value="1.9" data-athlete="0"><div class="bmr-activity-card"><span class="bmr-activity-name">Extra akt&iacute;v</span><span class="bmr-activity-desc">Napi 2&times; edz&eacute;s / fizikai munka</span><span class="bmr-activity-factor">&times; 1.9</span></div></label>
                            <label class="bmr-activity-option bmr-activity-option--athlete"><input type="radio" name="bmr-activity" value="2.0" data-athlete="1"><div class="bmr-activity-card bmr-activity-card--athlete"><span class="bmr-activity-athlete-body"><span class="bmr-activity-name">Sportol&oacute; vagyok</span><span class="bmr-activity-desc">Izomt&ouml;megb&#337;l ad&oacute;d&oacute; t&uacute;ls&uacute;ly &ndash; ne sz&aacute;molj korrekci&oacute;t</span></span><span class="bmr-activity-factor" id="bmr-athlete-factor">&times; 2.0</span></div></label>
                            <div class="bmr-athlete-sub" id="bmr-athlete-sub">
                                <label class="bmr-athlete-level"><input type="radio" name="bmr-athlete-level" value="2.0" checked><div class="bmr-athlete-level-card"><span class="bmr-athlete-level-name">Hobbi sportol&oacute;</span><span class="bmr-athlete-level-desc">Napi 1-2 &oacute;ra intenz&iacute;v edz&eacute;s</span><span class="bmr-athlete-level-factor">&times; 2.0</span></div></label>
                                <label class="bmr-athlete-level"><input type="radio" name="bmr-athlete-level" value="2.3"><div class="bmr-athlete-level-card"><span class="bmr-athlete-level-name">Versenyez&#337; / test&eacute;p&iacute;t&#337;</span><span class="bmr-athlete-level-desc">Napi 2-4 &oacute;ra edz&eacute;s</span><span class="bmr-athlete-level-factor">&times; 2.3</span></div></label>
                                <label class="bmr-athlete-level"><input type="radio" name="bmr-athlete-level" value="2.5"><div class="bmr-athlete-level-card"><span class="bmr-athlete-level-name">&Eacute;lsportol&oacute;</span><span class="bmr-athlete-level-desc">Napi 4+ &oacute;ra, dupla edz&eacute;sek</span><span class="bmr-athlete-level-factor">&times; 2.5</span></div></label>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ══ EREDMÉNYEK ══ -->
            <div class="bmr-card bmr-card--results">
                <div class="bmr-card-header">Eredm&eacute;nyek</div>
                <div class="bmr-card-body">

                    <!-- BMI -->
                    <div class="bmr-bmi-section">
                        <div class="bmr-bmi-header">
                            <span class="bmr-result-label">Testt&ouml;meg-index (BMI)</span>
                            <div class="bmr-bmi-value-wrap">
                                <span class="bmr-bmi-number" id="bmr-bmi-number">0</span>
                                <span class="bmr-bmi-cat" id="bmr-bmi-cat"></span>
                            </div>
                        </div>
                        <div class="bmr-bmi-bar-wrap">
                            <div class="bmr-bmi-bar">
                                <div class="bmr-bmi-segment" data-cat="sovany"></div>
                                <div class="bmr-bmi-segment" data-cat="normal"></div>
                                <div class="bmr-bmi-segment" data-cat="tulsuly"></div>
                                <div class="bmr-bmi-segment" data-cat="elhizas"></div>
                                <div class="bmr-bmi-marker" id="bmr-bmi-marker"></div>
                            </div>
                            <div class="bmr-bmi-ticks">
                                <span class="bmr-bmi-tick" style="left:0%">12</span>
                                <span class="bmr-bmi-tick" id="bmr-tick-16">16</span>
                                <span class="bmr-bmi-tick" id="bmr-tick-18">18.5</span>
                                <span class="bmr-bmi-tick" id="bmr-tick-25">25</span>
                                <span class="bmr-bmi-tick" id="bmr-tick-30">30</span>
                                <span class="bmr-bmi-tick" id="bmr-tick-40">40</span>
                                <span class="bmr-bmi-tick" style="right:0%;left:auto">45+</span>
                            </div>
                        </div>
                        <!-- Sportoló BMI notice -->
                        <div class="bmr-athlete-bmi-notice" id="bmr-athlete-bmi-notice">
                            <div class="bmr-athlete-bmi-badge">&#127947;&#65039; Sportol&oacute; m&oacute;d &ndash; BMI nem relev&aacute;ns</div>
                            <p>Sportol&oacute;kn&aacute;l a <strong>BMI nem megb&iacute;zhat&oacute; mutat&oacute;</strong>, mert az izomsz&ouml;vet l&eacute;nyegesen s&#369;r&#369;bb &eacute;s nehezebb a zs&iacute;rsz&ouml;vetn&eacute;l.</p>
                            <div class="bmr-athlete-bmi-comparison">
                                <div class="bmr-athlete-bmi-col"><span class="bmr-athlete-bmi-col-icon">&#128170;</span><span class="bmr-athlete-bmi-col-title">Izomsz&ouml;vet</span><span class="bmr-athlete-bmi-col-data">1 liter &asymp; <strong>1,06 kg</strong></span><span class="bmr-athlete-bmi-col-sub">S&#369;r&#369;s&eacute;g: 1,06 g/cm&sup3;</span></div>
                                <div class="bmr-athlete-bmi-vs">vs</div>
                                <div class="bmr-athlete-bmi-col"><span class="bmr-athlete-bmi-col-icon">&#9898;</span><span class="bmr-athlete-bmi-col-title">Zs&iacute;rsz&ouml;vet</span><span class="bmr-athlete-bmi-col-data">1 liter &asymp; <strong>0,92 kg</strong></span><span class="bmr-athlete-bmi-col-sub">S&#369;r&#369;s&eacute;g: 0,92 g/cm&sup3;</span></div>
                            </div>
                            <p class="bmr-athlete-bmi-explain">Ugyanaz a t&eacute;rfogat izom <strong>~15%-kal nehezebb</strong> mint zs&iacute;r. Egy izmos sportol&oacute; BMI-je &bdquo;t&uacute;ls&uacute;lyos&rdquo; vagy &bdquo;elh&iacute;z&aacute;s&rdquo; kateg&oacute;ri&aacute;t mutathat, an&eacute;lk&uuml;l hogy val&oacute;di t&uacute;ls&uacute;lya lenne.</p>
                            <div class="bmr-athlete-bmi-accurate">
                                <div class="bmr-athlete-bmi-accurate-badge">&#128300; Pontosabb m&eacute;r&eacute;sek</div>
                                <p>A BMI helyett sportol&oacute;knak az al&aacute;bbi m&oacute;dszerek adnak megb&iacute;zhat&oacute;bb k&eacute;pet:</p>
                                <ul class="bmr-athlete-bmi-accurate-list">
                                    <li><strong>DEXA-szkennel&eacute;s</strong> &ndash; R&ouml;ntgen alap&uacute;, arany standard.</li>
                                    <li><strong>Bioelektromos impedancia (BIA)</strong> &ndash; Gyors, k&ouml;nnyen el&eacute;rhet&#337;.</li>
                                    <li><strong>B&#337;rred&#337;-m&eacute;r&eacute;s (kaliper)</strong> &ndash; Gyakorlott szak&eacute;rt&#337;vel megb&iacute;zhat&oacute;.</li>
                                    <li><strong>CT / MRI</strong> &ndash; Klinikai/kutat&aacute;si c&eacute;lra.</li>
                                </ul>
                            </div>
                            <p class="bmr-athlete-bmi-source">Forr&aacute;s: Fidanza F. (1991); Wang et al. (2010) &ndash; Am J Clin Nutr.</p>
                        </div>
                    </div>

                    <!-- Broca korrekció -->
                    <div class="bmr-broca-notice" id="bmr-broca-notice">
                        <div class="bmr-broca-badge">&#9878;&#65039; Korrig&aacute;lt tests&uacute;llyal sz&aacute;molva</div>
                        <p>A BMI &eacute;rt&eacute;ked <strong id="bmr-broca-bmi">0</strong>, ami az elh&iacute;z&aacute;s kateg&oacute;ri&aacute;j&aacute;ba esik (BMI &ge; 30).</p>
                        <p>Elh&iacute;z&aacute;sn&aacute;l a val&oacute;s tests&uacute;ly t&uacute;lbecs&uuml;li az alapanyagcser&eacute;t. A kalkul&aacute;tor <strong>korrig&aacute;lt tests&uacute;llyal</strong> (<strong id="bmr-broca-kg">0</strong> kg) sz&aacute;mol:</p>
                        <div class="bmr-broca-formula" id="bmr-broca-formula"></div>
                    </div>

                    <div class="bmr-result-divider"></div>

                    <!-- Képlet választó -->
                    <div class="bmr-result-block">
                        <span class="bmr-result-label">Sz&aacute;m&iacute;t&aacute;si k&eacute;plet</span>
                        <div class="bmr-formula-bar">
                            <button type="button" class="bmr-formula-chip" data-formula="mifflin"><span class="bmr-formula-chip-name">Mifflin-St Jeor</span><span class="bmr-formula-chip-year">1990</span></button>
                            <button type="button" class="bmr-formula-chip" data-formula="roza"><span class="bmr-formula-chip-name">Roza &amp; Shizgal</span><span class="bmr-formula-chip-year">1984</span></button>
                            <button type="button" class="bmr-formula-chip is-active" data-formula="harris"><span class="bmr-formula-chip-name">Harris-Benedict</span><span class="bmr-formula-chip-year">1919</span></button>
                        </div>
                        <div class="bmr-formula-summary" id="bmr-formula-summary"></div>
                    </div>
                    <div class="bmr-result-divider"></div>

                    <!-- BMR -->
                    <div class="bmr-result-block bmr-result-block--bmr">
                        <span class="bmr-result-label">Alapanyagcsere (BMR)</span>
                        <div class="bmr-result-value-wrap"><span class="bmr-result-value" id="bmr-result-bmr">0</span><span class="bmr-result-unit">kcal / nap</span></div>
                        <p class="bmr-result-desc">A tested ennyi kal&oacute;ri&aacute;t haszn&aacute;l el teljes nyugalomban: l&eacute;gz&eacute;s, v&eacute;rkering&eacute;s, sejtek m&#369;k&ouml;d&eacute;se, h&#337;szab&aacute;lyoz&aacute;s.</p>
                    </div>
                    <div class="bmr-result-divider"></div>

                    <!-- TDEE -->
                    <div class="bmr-result-block bmr-result-block--tdee">
                        <span class="bmr-result-label">Napi kal&oacute;riasz&uuml;ks&eacute;glet (TDEE)</span>
                        <div class="bmr-result-value-wrap"><span class="bmr-result-value" id="bmr-result-tdee">0</span><span class="bmr-result-unit">kcal / nap</span></div>
                        <p class="bmr-result-desc">BMR &times; aktivit&aacute;si szorz&oacute;. Ennyi kal&oacute;ri&aacute;t &eacute;getsz el naponta &ouml;sszesen &ndash; ez a kiindul&oacute;pont a szem&eacute;lyre szabott t&aacute;pl&aacute;lkoz&aacute;si tervedhez.</p>
                    </div>
                    <div class="bmr-result-divider"></div>

                    <!-- ══ CTA: TERV KÉSZÍTÉSE ══ -->
                    <div class="bmr-cta-section" id="bmr-cta-section">
                        <div class="bmr-cta-inner">
                            <div class="bmr-cta-icon">🎯</div>
                            <div class="bmr-cta-body">
                                <strong>K&eacute;sz vagy a k&ouml;vetkez&#337; l&eacute;p&eacute;sre?</strong>
                                <span>Ez a kalkul&aacute;tor kiz&aacute;r&oacute;lag t&aacute;j&eacute;koztat&oacute; jelleg&#369; &ndash; nem min&#337;s&uuml;l orvosi tan&aacute;csad&aacute;snak. Szem&eacute;lyre szabott &eacute;trendi t&aacute;j&eacute;koztat&aacute;s&eacute;rt vagy tan&aacute;cs&eacute;rt fordulj dietetikushoz vagy orvoshoz.</span>
                            </div>
                            <button type="button" id="bmr-cta-btn" class="bmr-cta-btn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
                                Szem&eacute;lyre szabott terv
                            </button>
                        </div>
                        <div class="bmr-cta-msg" id="bmr-cta-msg"></div>
                    </div>

                </div>
            </div>

        </div>

        <!-- ══ INFO ══ -->
        <div class="bmr-info-section">
            <div class="bmr-info-card">
                <h3 class="bmr-info-title">A haszn&aacute;lt k&eacute;pletr&#337;l</h3>
                <div class="bmr-info-body" id="bmr-info-body"></div>
            </div>
        </div>

    </article>
</main>

<?php get_footer(); ?>

<script>
(function(){
    'use strict';

    var bmiCats = [
        {max:16,label:'S\u00FAlyos sov\u00E1nys\u00E1g',color:'#6366f1'},
        {max:17,label:'M\u00E9rs\u00E9kelt sov\u00E1nys\u00E1g',color:'#818cf8'},
        {max:18.5,label:'Enyhe sov\u00E1nys\u00E1g',color:'#a5b4fc'},
        {max:25,label:'Norm\u00E1l tests\u00FAly',color:'#22c55e'},
        {max:30,label:'T\u00FAls\u00FAly',color:'#f59e0b'},
        {max:35,label:'I. fok\u00FA elh\u00EDz\u00E1s',color:'#f97316'},
        {max:40,label:'II. fok\u00FA elh\u00EDz\u00E1s',color:'#ef4444'},
        {max:999,label:'III. fok\u00FA (s\u00FAlyos) elh\u00EDz\u00E1s',color:'#dc2626'}
    ];
    var ATH_CAT = {label:'Sportol\u00F3',color:'#3b82f6'};
    var BMI_MIN = 12, BMI_MAX = 45;

    var formulas = {
        mifflin: {
            name:'Mifflin-St Jeor',year:1990,
            authors:'Mifflin MD, St Jeor ST, Hill LA, Scott BJ, Daugherty SA, Koh YO',
            source:'Am J Clin Nutr, 51(2), 241\u2013247. (1990)',
            desc:'A Mifflin-St Jeor k\u00E9pletet 1990-ben publik\u00E1lt\u00E1k. Az Academy of Nutrition and Dietetics ezt tartja a legpontosabb BMR becsl\u0151 k\u00E9pletnek. 498 szem\u00E9lyen tesztelt\u00E9k (19\u201378 \u00E9v).',
            extra:'Egyszer\u0171 fel\u00E9p\u00EDt\u00E9s\u0171, kevesebb hib\u00E1val becs\u00FCl. Izmos szem\u00E9lyekn\u00E9l alulbecs\u00FClhet.',
            summary:'A legpontosabbnak tartott modern k\u00E9plet (AND aj\u00E1nl\u00E1s). Egyszer\u0171 fel\u00E9p\u00EDt\u00E9s, alacsony hiba\u00E1tlag. Izmos szem\u00E9lyekn\u00E9l enyh\u00E9n alulbecs\u00FClhet.',
            calc:function(g,w,h,a){var b=(10*w)+(6.25*h)-(5*a);return g==='ferfi'?b+5:b-161},
            fm:'BMR = (10 \u00D7 s\u00FAly) + (6.25 \u00D7 mag.) \u2212 (5 \u00D7 kor) + 5',
            ff:'BMR = (10 \u00D7 s\u00FAly) + (6.25 \u00D7 mag.) \u2212 (5 \u00D7 kor) \u2212 161'
        },
        roza: {
            name:'Roza & Shizgal (revide\u00E1lt Harris-Benedict)',year:1984,
            authors:'Roza AM, Shizgal HM',
            source:'Am J Clin Nutr, 40(1), 168\u2013182. (1984)',
            desc:'Az eredeti Harris-Benedict 1984-es jav\u00EDtott v\u00E1ltozata. Roza \u00E9s Shizgal \u00FAjrakalibr\u00E1lt\u00E1k az egy\u00FCtthat\u00F3kat indirekt kalorimetri\u00E1val.',
            extra:'Kb. 5%-kal pontosabb az eredetin\u00E9l. Klinikai gyakorlatban sz\u00E9les k\u00F6rben haszn\u00E1lt.',
            summary:'Az eredeti Harris-Benedict jav\u00EDtott verzi\u00F3ja, ~5%-kal pontosabb. Klinikai gyakorlatban sz\u00E9les k\u00F6rben haszn\u00E1lt, megb\u00EDzhat\u00F3 \u00E1ltal\u00E1nos v\u00E1laszt\u00E1s.',
            calc:function(g,w,h,a){return g==='ferfi'?88.362+(13.397*w)+(4.799*h)-(5.677*a):447.593+(9.247*w)+(3.098*h)-(4.330*a)},
            fm:'BMR = 88.362 + (13.397 \u00D7 s\u00FAly) + (4.799 \u00D7 mag.) \u2212 (5.677 \u00D7 kor)',
            ff:'BMR = 447.593 + (9.247 \u00D7 s\u00FAly) + (3.098 \u00D7 mag.) \u2212 (4.330 \u00D7 kor)'
        },
        harris: {
            name:'Harris-Benedict (eredeti)',year:1919,
            authors:'Harris JA, Benedict FG',
            source:'Carnegie Institution of Washington, Publ. No. 279. (1919)',
            desc:'Az els\u0151 sz\u00E9les k\u00F6rben elfogadott BMR k\u00E9plet. 239 szem\u00E9lyen m\u00E9rt\u00E9k indirekt kalorimetri\u00E1val.',
            extra:'T\u00FAls\u00FAlyos \u00E9s id\u0151s szem\u00E9lyekn\u00E9l t\u00FAlbecs\u00FClhet. T\u00F6rt\u00E9nelmi jelent\u0151s\u00E9ge \u00F3ri\u00E1si.',
            summary:'Az els\u0151 \u00E9s legismertebb BMR k\u00E9plet (1919). T\u00F6rt\u00E9nelmi jelent\u0151s\u00E9ge \u00F3ri\u00E1si, de t\u00FAls\u00FAlyos \u00E9s id\u0151s szem\u00E9lyekn\u00E9l t\u00FAlbecs\u00FClheti az anyagcser\u00E9t.',
            calc:function(g,w,h,a){return g==='ferfi'?66.473+(13.7516*w)+(5.0033*h)-(6.755*a):655.0955+(9.5634*w)+(1.8496*h)-(4.6756*a)},
            fm:'BMR = 66.473 + (13.752 \u00D7 s\u00FAly) + (5.003 \u00D7 mag.) \u2212 (6.755 \u00D7 kor)',
            ff:'BMR = 655.096 + (9.563 \u00D7 s\u00FAly) + (1.850 \u00D7 mag.) \u2212 (4.676 \u00D7 kor)'
        }
    };

    var S = {formula:'harris',gender:'ferfi',age:30,height:175,weight:75,activity:1.55,athlete:false,athleteLevel:2.0};

    var D = {
        chips:document.querySelectorAll('.bmr-formula-chip'),
        formulaSummary:document.getElementById('bmr-formula-summary'),
        gBtns:document.querySelectorAll('.bmr-gender-btn'),
        kor:document.getElementById('bmr-kor'),korSl:document.getElementById('bmr-kor-slider'),
        mag:document.getElementById('bmr-magassag'),magSl:document.getElementById('bmr-magassag-slider'),
        suly:document.getElementById('bmr-suly'),sulySl:document.getElementById('bmr-suly-slider'),
        actR:document.querySelectorAll('input[name="bmr-activity"]'),
        athSub:document.getElementById('bmr-athlete-sub'),
        athLevels:document.querySelectorAll('input[name="bmr-athlete-level"]'),
        athFactor:document.getElementById('bmr-athlete-factor'),
        bmiNum:document.getElementById('bmr-bmi-number'),bmiCat:document.getElementById('bmr-bmi-cat'),bmiMark:document.getElementById('bmr-bmi-marker'),
        athBmiNotice:document.getElementById('bmr-athlete-bmi-notice'),
        brocaN:document.getElementById('bmr-broca-notice'),brocaBmi:document.getElementById('bmr-broca-bmi'),brocaKg:document.getElementById('bmr-broca-kg'),brocaF:document.getElementById('bmr-broca-formula'),
        bmr:document.getElementById('bmr-result-bmr'),tdee:document.getElementById('bmr-result-tdee'),
        info:document.getElementById('bmr-info-body')
    };

    function getCat(b){for(var i=0;i<bmiCats.length;i++){if(b<bmiCats[i].max)return bmiCats[i]}return bmiCats[bmiCats.length-1]}
    function bmiPct(v){return((Math.max(BMI_MIN,Math.min(BMI_MAX,v))-BMI_MIN)/(BMI_MAX-BMI_MIN))*100}
    function r1(v){return Math.round(v*10)/10}
    function idealBroca(g,h){return g==='ferfi'?(h-100)*0.9:(h-104)*0.85}
    function adjustedBW(g,h,w){var id=idealBroca(g,h);return id+0.25*(w-id)}

    function initBmiTicks(){
        var ticks={16:'bmr-tick-16',18.5:'bmr-tick-18',25:'bmr-tick-25',30:'bmr-tick-30',40:'bmr-tick-40'};
        for(var val in ticks){var el=document.getElementById(ticks[val]);if(el)el.style.left=bmiPct(parseFloat(val))+'%'}
    }

    function updSl(sl){var min=parseFloat(sl.min),max=parseFloat(sl.max),val=parseFloat(sl.value);sl.style.setProperty('--pct',((val-min)/(max-min))*100+'%')}

    function anim(el,t){
        var c=parseInt(el.textContent)||0,d=t-c;if(!d){el.textContent=t;return}
        var s=18,v=d/s,i=0;
        var iv=setInterval(function(){if(++i>=s){el.textContent=t;clearInterval(iv)}else el.textContent=Math.round(c+v*i)},15);
    }

    function updFormulaSummary(){
        var f=formulas[S.formula],fS=S.gender==='ferfi'?f.fm:f.ff;
        D.formulaSummary.innerHTML='<p class="bmr-formula-summary-text">'+f.summary+'</p><div class="bmr-formula-summary-formula"><code>'+fS+'</code></div>';
    }

    /* Utolsó értékek a CTA-hoz */
    var lastBmrVal=0, lastTdeeVal=0;

    function calc(){
        var f=formulas[S.formula],hm=S.height/100,bmi=S.weight/(hm*hm);
        var isObese=bmi>=30,useAdj=isObese&&!S.athlete;
        var idealW=idealBroca(S.gender,S.height),adjW=adjustedBW(S.gender,S.height,S.weight),cW=useAdj?adjW:S.weight;
        var act=S.athlete?S.athleteLevel:S.activity;

        /* BMI */
        D.bmiNum.textContent=bmi.toFixed(1);
        if(S.athlete){D.bmiCat.textContent=ATH_CAT.label;D.bmiCat.style.color=ATH_CAT.color;D.bmiMark.style.background=ATH_CAT.color}
        else{var cat=getCat(bmi);D.bmiCat.textContent=cat.label;D.bmiCat.style.color=cat.color;D.bmiMark.style.background=cat.color}
        D.bmiMark.style.left=bmiPct(bmi)+'%';
        D.athSub.classList.toggle('is-visible',S.athlete);
        D.athBmiNotice.style.display=S.athlete?'block':'none';

        /* Broca */
        if(useAdj){
            D.brocaN.style.display='block';D.brocaBmi.textContent=bmi.toFixed(1);D.brocaKg.textContent=r1(adjW);
            var sub=S.gender==='ferfi'?100:104,mul=S.gender==='ferfi'?'0,9':'0,85';
            D.brocaF.innerHTML='<div class="bmr-broca-step"><strong>1. Ide\u00E1lis tests\u00FAly (Broca):</strong><br><code>('+S.height+' \u2212 '+sub+') \u00D7 '+mul+' = '+r1(idealW)+' kg</code></div>'
                +'<div class="bmr-broca-step"><strong>2. Korrig\u00E1lt tests\u00FAly (ABW):</strong><br><code>'+r1(idealW)+' + 0,25 \u00D7 ('+S.weight+' \u2212 '+r1(idealW)+') = <strong>'+r1(adjW)+' kg</strong></code></div>'
                +'<p class="bmr-broca-explain">A t\u00F6bbletsúly ~25%-a metabolikusan akt\u00EDv sz\u00F6vet, ~75%-a zs\u00EDr.</p>';
        }else{D.brocaN.style.display='none'}

        updFormulaSummary();

        /* BMR & TDEE */
        var bmr=Math.round(f.calc(S.gender,cW,S.height,S.age));if(bmr<0)bmr=0;
        var tdee=Math.round(bmr*act);
        lastBmrVal=bmr;lastTdeeVal=tdee;
        anim(D.bmr,bmr);anim(D.tdee,tdee);

        /* Info */
        updInfo(f,useAdj,bmi,idealW,adjW,cW,act);
    }

    function updInfo(f,useAdj,bmi,idealW,adjW,cW,act){
        var gL=S.gender==='ferfi'?'F\u00E9rfi':'N\u0151',fS=S.gender==='ferfi'?f.fm:f.ff,mul=S.gender==='ferfi'?'0,9':'0,85';
        var h='';
        h+='<div class="bmr-info-meta"><span><strong>K\u00E9plet:</strong> '+f.name+' ('+f.year+')</span><span><strong>Szerz\u0151k:</strong> '+f.authors+'</span><span><strong>Forr\u00E1s:</strong> '+f.source+'</span></div>';
        h+='<p class="bmr-info-desc">'+f.desc+'</p>';
        if(f.extra)h+='<p class="bmr-info-desc">'+f.extra+'</p>';
        h+='<div class="bmr-info-formula"><strong>'+gL+' k\u00E9plet:</strong><br><code>'+fS+'</code></div>';

        if(useAdj){
            h+='<div class="bmr-info-broca"><strong>\u26A0\uFE0F Korrig\u00E1lt tests\u00FAly (ABW)</strong><br>BMI: <strong>'+bmi.toFixed(1)+'</strong><br><br>';
            h+='1) Broca ('+gL+', \u00D7'+mul+'): <code>'+r1(idealW)+' kg</code><br>';
            h+='2) ABW = '+r1(idealW)+' + 0,25 \u00D7 ('+S.weight+' \u2212 '+r1(idealW)+') = <code>'+r1(adjW)+' kg</code><br><br>';
            h+='Sz\u00E1m\u00EDt\u00E1s: <strong>'+r1(cW)+' kg</strong> (val\u00F3s: '+S.weight+' kg).</div>';
        }

        if(S.athlete){
            h+='<div class="bmr-info-athlete"><strong>\uD83C\uDFCB\uFE0F Sportol\u00F3 m\u00F3d (\u00D7 '+act+')</strong><br>Val\u00F3s tests\u00FAllyal ('+S.weight+' kg) sz\u00E1molunk. Forr\u00E1s: FAO/WHO/UNU 2001, Thomas et al. (J Acad Nutr Diet, 2016).</div>';
        }

        h+='<div class="bmr-info-activity"><strong>Aktivit\u00E1si szorz\u00F3k (PAL):</strong>';
        h+='<table class="bmr-info-table"><tr><th>Szint</th><th>Szorz\u00F3</th><th>Le\u00EDr\u00E1s</th></tr>';
        h+='<tr><td>\u00DCl\u0151 \u00E9letm\u00F3d</td><td>\u00D7 1.2</td><td>Irodai munka</td></tr>';
        h+='<tr><td>Enyh\u00E9n akt\u00EDv</td><td>\u00D7 1.375</td><td>Heti 1-3 k\u00F6nny\u0171 edz\u00E9s</td></tr>';
        h+='<tr><td>M\u00E9rs\u00E9kelten akt\u00EDv</td><td>\u00D7 1.55</td><td>Heti 3-5 k\u00F6zepes edz\u00E9s</td></tr>';
        h+='<tr><td>Nagyon akt\u00EDv</td><td>\u00D7 1.725</td><td>Heti 6-7 intenz\u00EDv edz\u00E9s</td></tr>';
        h+='<tr><td>Extra akt\u00EDv</td><td>\u00D7 1.9</td><td>Napi 2\u00D7 edz\u00E9s / fizikai munka</td></tr>';
        h+='<tr class="bmr-info-table-ath"><td>Hobbi sportol\u00F3</td><td>\u00D7 2.0</td><td>Napi 1-2 \u00F3ra intenz\u00EDv</td></tr>';
        h+='<tr class="bmr-info-table-ath"><td>Versenyez\u0151</td><td>\u00D7 2.3</td><td>Napi 2-4 \u00F3ra edz\u00E9s</td></tr>';
        h+='<tr class="bmr-info-table-ath"><td>\u00C9lsportol\u00F3</td><td>\u00D7 2.5</td><td>Napi 4+ \u00F3ra</td></tr>';
        h+='</table></div>';

        h+='<div class="bmr-info-bmi"><strong>BMI kateg\u00F3ri\u00E1k (WHO):</strong>';
        h+='<table class="bmr-info-table"><tr><th>BMI</th><th>Kateg\u00F3ria</th></tr>';
        h+='<tr><td>&lt; 16,0</td><td>S\u00FAlyos sov\u00E1nys\u00E1g</td></tr>';
        h+='<tr><td>16,0 \u2013 16,99</td><td>M\u00E9rs\u00E9kelt sov\u00E1nys\u00E1g</td></tr>';
        h+='<tr><td>17,0 \u2013 18,49</td><td>Enyhe sov\u00E1nys\u00E1g</td></tr>';
        h+='<tr><td>18,5 \u2013 24,99</td><td>Norm\u00E1l tests\u00FAly</td></tr>';
        h+='<tr><td>25,0 \u2013 29,99</td><td>T\u00FAls\u00FAly</td></tr>';
        h+='<tr><td>30,0 \u2013 34,99</td><td>I. fok\u00FA elh\u00EDz\u00E1s</td></tr>';
        h+='<tr><td>35,0 \u2013 39,99</td><td>II. fok\u00FA elh\u00EDz\u00E1s</td></tr>';
        h+='<tr><td>\u2265 40,0</td><td>III. fok\u00FA (s\u00FAlyos) elh\u00EDz\u00E1s</td></tr>';
        h+='</table></div>';

        D.info.innerHTML=h;
    }

    /* ── Eseménykezelők ── */
    D.chips.forEach(function(c){c.addEventListener('click',function(){D.chips.forEach(function(x){x.classList.remove('is-active')});c.classList.add('is-active');S.formula=c.dataset.formula;calc()})});
    D.gBtns.forEach(function(b){b.addEventListener('click',function(){D.gBtns.forEach(function(x){x.classList.remove('is-active')});b.classList.add('is-active');S.gender=b.dataset.gender;calc()})});

    function sync(inp,sl,key,fl){
        function p(v){return fl?parseFloat(v):parseInt(v,10)}
        inp.addEventListener('input',function(){var v=p(this.value);if(!isNaN(v)){sl.value=v;S[key]=v;updSl(sl);calc()}});
        sl.addEventListener('input',function(){var v=p(this.value);inp.value=v;S[key]=v;updSl(sl);calc()});
        updSl(sl);
    }
    sync(D.kor,D.korSl,'age',false);
    sync(D.mag,D.magSl,'height',false);
    sync(D.suly,D.sulySl,'weight',true);

    D.actR.forEach(function(r){r.addEventListener('change',function(){S.athlete=r.dataset.athlete==='1';if(S.athlete){S.athleteLevel=parseFloat(document.querySelector('input[name="bmr-athlete-level"]:checked').value);S.activity=S.athleteLevel}else{S.activity=parseFloat(r.value)}calc()})});
    D.athLevels.forEach(function(r){r.addEventListener('change',function(){S.athleteLevel=parseFloat(r.value);D.athFactor.textContent='\u00D7 '+r.value;document.querySelector('.bmr-activity-option--athlete input[name="bmr-activity"]').value=r.value;calc()})});

    initBmiTicks();
    calc();

    /* ═══════════════════════════════════════════════════════
       CTA: SZEMÉLYRE SZABOTT TERV – MENTÉS + REDIRECT
       ═══════════════════════════════════════════════════════ */
    (function(){
        var btn   = document.getElementById('bmr-cta-btn');
        var msgEl = document.getElementById('bmr-cta-msg');
        if(!btn) return;

        btn.addEventListener('click', function(){
            /* Nem bejelentkezett → auth modal */
            <?php if ( ! $logged_in ) : ?>
            if(typeof window.openAuth === 'function'){
                window.openAuth('register');
            } else {
                window.location.href = '<?php echo esc_url( wp_login_url( home_url('/bmr-kalkulator/') ) ); ?>';
            }
            return;
            <?php endif; ?>

            if(lastTdeeVal < 500) return;

            btn.disabled = true;
            var origHTML = btn.innerHTML;
            btn.textContent = 'Ment\u00E9s…';
            if(msgEl){msgEl.textContent='';msgEl.style.color=''}

            var fd = new FormData();
            fd.append('action', 'dp_save_bmr_to_profile');
            fd.append('nonce', '<?php echo esc_attr( $nonce ); ?>');
            fd.append('bmr', lastBmrVal);
            fd.append('tdee', lastTdeeVal);
            fd.append('formula', S.formula);
            fd.append('gender', S.gender);
            fd.append('age', S.age);
            fd.append('height', S.height);
            fd.append('weight', S.weight);
            fd.append('activity', S.athlete ? S.athleteLevel : S.activity);
            fd.append('is_athlete', S.athlete ? '1' : '0');
            fd.append('deficit', '0');

            fetch('<?php echo esc_url( $ajax_url ); ?>', {
                method:'POST', body:fd, credentials:'same-origin'
            })
            .then(function(r){return r.json()})
            .then(function(r){
                if(r.success){
                    btn.innerHTML = '\u2713 Mentve! \u00C1tir\u00E1ny\u00EDt\u00E1s…';
                    btn.style.background = '#22c55e';
                    if(msgEl){msgEl.textContent='\u2713 Adatok mentve a profilodba!';msgEl.style.color='#2d6a4f'}
                    setTimeout(function(){
                        window.location.href = '<?php echo esc_url( home_url('/celjaim/') ); ?>';
                    }, 800);
                } else {
                    btn.disabled = false;
                    btn.innerHTML = origHTML;
                    if(msgEl){msgEl.textContent=r.data&&r.data.message?r.data.message:'Hiba t\u00F6rt\u00E9nt.';msgEl.style.color='#dc2626'}
                }
            })
            .catch(function(){
                btn.disabled = false;
                btn.innerHTML = origHTML;
                if(msgEl){msgEl.textContent='H\u00E1l\u00F3zati hiba. Pr\u00F3b\u00E1ld \u00FAjra.';msgEl.style.color='#dc2626'}
            });
        });
    })();

})();
</script>