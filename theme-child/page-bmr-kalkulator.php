<?php
/**
 * Template Name: BMR Kalkulátor
 */
get_header();
?>

<main id="primary" class="site-main bmr-page">
    <article class="bmr-container">

        <div class="bmr-hero">
            <h1>Kal&oacute;ria Kalkul&aacute;tor</h1>
            <p class="bmr-hero-sub">Sz&aacute;mold ki az alapanyagcser&eacute;d &eacute;s napi kal&oacute;riasz&uuml;ks&eacute;gleted tudom&aacute;nyos k&eacute;pletekkel</p>
            <div class="bmr-hero-disclaimer">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span>Ez a kalkul&aacute;tor kiz&aacute;r&oacute;lag t&aacute;j&eacute;koztat&oacute; jelleg&#369; &ndash; nem min&#337;s&uuml;l orvosi tan&aacute;csad&aacute;snak. Szem&eacute;lyre szabott &eacute;trendi t&aacute;j&eacute;koztat&aacute;s&eacute;rt vagy tanácsért fordulj dietetikushoz vagy orvoshoz.</span>
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
                            <label class="bmr-activity-option bmr-activity-option--athlete"><input type="radio" name="bmr-activity" value="2.0" data-athlete="1"><div class="bmr-activity-card bmr-activity-card--athlete"><span class="bmr-activity-athlete-body"><span class="bmr-activity-name">Sportol&oacute; vagyok</span><span class="bmr-activity-desc">Izomtömegb&#337;l ad&oacute;d&oacute; t&uacute;ls&uacute;ly &ndash; ne sz&aacute;molj korrekci&oacute;t</span></span><span class="bmr-activity-factor" id="bmr-athlete-factor">&times; 2.0</span></div></label>
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
                        <div class="bmr-athlete-bmi-notice" id="bmr-athlete-bmi-notice">
                            <div class="bmr-athlete-bmi-badge">&#127947;&#65039; Sportol&oacute; m&oacute;d &ndash; BMI nem relev&aacute;ns</div>
                            <p>Sportol&oacute;kn&aacute;l a <strong>BMI nem megb&iacute;zhat&oacute; mutat&oacute;</strong>, mert az izomszövet l&eacute;nyegesen s&#369;r&#369;bb &eacute;s nehezebb a zs&iacute;rsz&ouml;vetn&eacute;l.</p>
                            <div class="bmr-athlete-bmi-comparison">
                                <div class="bmr-athlete-bmi-col"><span class="bmr-athlete-bmi-col-icon">&#128170;</span><span class="bmr-athlete-bmi-col-title">Izomszövet</span><span class="bmr-athlete-bmi-col-data">1 liter &asymp; <strong>1,06 kg</strong></span><span class="bmr-athlete-bmi-col-sub">S&#369;r&#369;s&eacute;g: 1,06 g/cm&sup3;</span></div>
                                <div class="bmr-athlete-bmi-vs">vs</div>
                                <div class="bmr-athlete-bmi-col"><span class="bmr-athlete-bmi-col-icon">&#9898;</span><span class="bmr-athlete-bmi-col-title">Zs&iacute;rsz&ouml;vet</span><span class="bmr-athlete-bmi-col-data">1 liter &asymp; <strong>0,92 kg</strong></span><span class="bmr-athlete-bmi-col-sub">S&#369;r&#369;s&eacute;g: 0,92 g/cm&sup3;</span></div>
                            </div>
                            <p class="bmr-athlete-bmi-explain">Ugyanaz a t&eacute;rfogat izom <strong>~15%-kal nehezebb</strong> mint zs&iacute;r. Egy izmos sportol&oacute; BMI-je &bdquo;t&uacute;ls&uacute;lyos&rdquo; vagy &bdquo;elh&iacute;z&aacute;s&rdquo; kateg&oacute;ri&aacute;t mutathat, an&eacute;lk&uuml;l hogy val&oacute;di t&uacute;ls&uacute;lya lenne. Ez&eacute;rt sportol&oacute; m&oacute;dban a <strong>korrig&aacute;lt tests&uacute;ly sz&aacute;m&iacute;t&aacute;s ki van kapcsolva</strong>, &eacute;s a val&oacute;s tests&uacute;llyal sz&aacute;molunk.</p>
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

                    <div class="bmr-broca-notice" id="bmr-broca-notice">
                        <div class="bmr-broca-badge">&#9878;&#65039; Korrig&aacute;lt tests&uacute;llyal sz&aacute;molva</div>
                        <p>A BMI &eacute;rt&eacute;ked <strong id="bmr-broca-bmi">0</strong>, ami az elh&iacute;z&aacute;s kateg&oacute;ri&aacute;j&aacute;ba esik (BMI &ge; 30).</p>
                        <p>Elh&iacute;z&aacute;sn&aacute;l a val&oacute;s tests&uacute;ly t&uacute;lbecs&uuml;li az alapanyagcser&eacute;t. A kalkul&aacute;tor <strong>korrig&aacute;lt tests&uacute;llyal</strong> (<strong id="bmr-broca-kg">0</strong> kg) sz&aacute;mol:</p>
                        <div class="bmr-broca-formula" id="bmr-broca-formula"></div>
                    </div>

                    <div class="bmr-result-divider"></div>

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

                    <div class="bmr-result-block bmr-result-block--bmr">
                        <span class="bmr-result-label">Alapanyagcsere (BMR)</span>
                        <div class="bmr-result-value-wrap"><span class="bmr-result-value" id="bmr-result-bmr">0</span><span class="bmr-result-unit">kcal / nap</span></div>
                        <p class="bmr-result-desc">A tested ennyi kal&oacute;ri&aacute;t haszn&aacute;l el teljes nyugalomban: l&eacute;gz&eacute;s, v&eacute;rkering&eacute;s, sejtek m&#369;k&ouml;d&eacute;se, h&#337;szab&aacute;lyoz&aacute;s.</p>
                    </div>
                    <div class="bmr-result-divider"></div>

                    <div class="bmr-result-block bmr-result-block--tdee">
                        <span class="bmr-result-label">Napi kal&oacute;riasz&uuml;ks&eacute;glet (TDEE)</span>
                        <div class="bmr-result-value-wrap"><span class="bmr-result-value" id="bmr-result-tdee">0</span><span class="bmr-result-unit">kcal / nap</span></div>
                        <p class="bmr-result-desc">BMR &times; aktivit&aacute;si szorz&oacute;. Ennyi kal&oacute;ri&aacute;ra van sz&uuml;ks&eacute;ged a s&uacute;lyod fenntart&aacute;s&aacute;hoz.</p>
                    </div>
                    <div class="bmr-result-divider"></div>

                    <div class="bmr-result-block">
                        <span class="bmr-result-label">Kal&oacute;ria c&eacute;lok</span>
                        <div class="bmr-goals-grid">
                            <div class="bmr-goal-card bmr-goal-card--cut"><span class="bmr-goal-icon">&#128201;</span><span class="bmr-goal-title">Fogy&aacute;s</span><span class="bmr-goal-value" id="bmr-goal-cut">0</span><span class="bmr-goal-unit">kcal/nap</span><span class="bmr-goal-note">&minus;500 kcal deficit</span></div>
                            <div class="bmr-goal-card bmr-goal-card--maintain"><span class="bmr-goal-icon">&#9878;&#65039;</span><span class="bmr-goal-title">Szintentart&aacute;s</span><span class="bmr-goal-value" id="bmr-goal-maintain">0</span><span class="bmr-goal-unit">kcal/nap</span><span class="bmr-goal-note">TDEE</span></div>
                            <div class="bmr-goal-card bmr-goal-card--bulk"><span class="bmr-goal-icon">&#128200;</span><span class="bmr-goal-title">T&ouml;megn&ouml;vel&eacute;s</span><span class="bmr-goal-value" id="bmr-goal-bulk">0</span><span class="bmr-goal-unit">kcal/nap</span><span class="bmr-goal-note">+500 kcal t&ouml;bblet</span></div>
                        </div>

                        <div class="bmr-cut-prognosis" id="bmr-cut-prognosis">
                            <div class="bmr-cut-prog-inner">
                                <div class="bmr-cut-prog-header"><span class="bmr-cut-prog-icon">&#128201;</span><span class="bmr-cut-prog-title">Fogy&aacute;si progn&oacute;zis</span></div>
                                <div class="bmr-cut-prog-slider-section">
                                    <label class="bmr-cut-prog-slider-label">Napi kal&oacute;riadeficit be&aacute;ll&iacute;t&aacute;sa</label>
                                    <div class="bmr-cut-prog-slider-row">
                                        <input type="range" id="bmr-deficit-slider" class="bmr-slider bmr-slider--deficit" min="0" max="1500" value="0" step="50">
                                        <div class="bmr-cut-prog-slider-value-wrap"><span class="bmr-cut-prog-slider-value" id="bmr-deficit-value">0</span><span class="bmr-cut-prog-slider-unit">kcal</span></div>
                                    </div>
                                    <div class="bmr-cut-prog-slider-zones">
                                        <span class="bmr-cut-prog-zone bmr-cut-prog-zone--green">0&ndash;500: eg&eacute;szs&eacute;ges</span>
                                        <span class="bmr-cut-prog-zone bmr-cut-prog-zone--yellow">500&ndash;750: m&eacute;rs&eacute;kelt</span>
                                        <span class="bmr-cut-prog-zone bmr-cut-prog-zone--red">750+: agressz&iacute;v</span>
                                    </div>
                                </div>
                                <div class="bmr-cut-prog-intake" id="bmr-cut-prog-intake"></div>
                                <div class="bmr-cut-prog-stats" id="bmr-cut-prog-stats"></div>
                                <div class="bmr-cut-prog-body" id="bmr-cut-prog-body"></div>
                            </div>
                        </div>
                    </div>
                    <div class="bmr-result-divider"></div>

                    <div class="bmr-result-block">
                        <span class="bmr-result-label">Javasolt napi makr&oacute;eloszt&aacute;s</span>
                        <div class="bmr-macro-notice" id="bmr-macro-notice"></div>
                        <div class="bmr-macro-grid">
                            <div class="bmr-macro-card bmr-macro-card--ch">
                                <div class="bmr-macro-card-header"><span class="bmr-macro-card-title">Sz&eacute;nhidr&aacute;t</span><span class="bmr-macro-card-pct" id="bmr-macro-c-pct">55 energia%</span></div>
                                <div class="bmr-macro-card-values"><div class="bmr-macro-card-main"><span class="bmr-macro-value" id="bmr-macro-c">0</span><span class="bmr-macro-g">g</span></div><div class="bmr-macro-card-perkg"><span class="bmr-macro-perkg-value" id="bmr-macro-c-perkg">0</span><span class="bmr-macro-perkg-unit">g / ttkg</span></div></div>
                            </div>
                            <div class="bmr-macro-card bmr-macro-card--fat">
                                <div class="bmr-macro-card-header"><span class="bmr-macro-card-title">Zs&iacute;r</span><span class="bmr-macro-card-pct" id="bmr-macro-f-pct">30 energia%</span></div>
                                <div class="bmr-macro-card-values"><div class="bmr-macro-card-main"><span class="bmr-macro-value" id="bmr-macro-f">0</span><span class="bmr-macro-g">g</span></div><div class="bmr-macro-card-perkg"><span class="bmr-macro-perkg-value" id="bmr-macro-f-perkg">0</span><span class="bmr-macro-perkg-unit">g / ttkg</span></div></div>
                            </div>
                            <div class="bmr-macro-card bmr-macro-card--protein">
                                <div class="bmr-macro-card-header"><span class="bmr-macro-card-title">Feh&eacute;rje</span><span class="bmr-macro-card-pct" id="bmr-macro-p-pct">15 energia%</span></div>
                                <div class="bmr-macro-card-values"><div class="bmr-macro-card-main"><span class="bmr-macro-value" id="bmr-macro-p">0</span><span class="bmr-macro-g">g</span></div><div class="bmr-macro-card-perkg"><span class="bmr-macro-perkg-value" id="bmr-macro-p-perkg">0</span><span class="bmr-macro-perkg-unit">g / ttkg</span></div></div>
                            </div>
                        </div>
                        <p class="bmr-macro-explain" id="bmr-macro-explain">Az <strong>energia%</strong> az &ouml;sszes napi kal&oacute;riabevitelb&#337;l az adott makrot&aacute;panyagra jut&oacute; ar&aacute;ny. A <strong>g/ttkg</strong> &eacute;rt&eacute;k 1 testtömeg-kilogrammra jut&oacute; mennyis&eacute;g. Atwater-faktorok: 1&nbsp;g CH/feh&eacute;rje = 4,1&nbsp;kcal, 1&nbsp;g zs&iacute;r = 9,3&nbsp;kcal.</p>
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
        {max: 16, label: 'S\u00FAlyos sov\u00E1nys\u00E1g', color: '#6366f1'},
        {max: 17, label: 'M\u00E9rs\u00E9kelt sov\u00E1nys\u00E1g', color: '#818cf8'},
        {max: 18.5, label: 'Enyhe sov\u00E1nys\u00E1g', color: '#a5b4fc'},
        {max: 25, label: 'Norm\u00E1l tests\u00FAly', color: '#22c55e'},
        {max: 30, label: 'T\u00FAls\u00FAly', color: '#f59e0b'},
        {max: 35, label: 'I. fok\u00FA elh\u00EDz\u00E1s', color: '#f97316'},
        {max: 40, label: 'II. fok\u00FA elh\u00EDz\u00E1s', color: '#ef4444'},
        {max: 999, label: 'III. fok\u00FA (s\u00FAlyos) elh\u00EDz\u00E1s', color: '#dc2626'}
    ];
    var ATH_CAT = {label: 'Sportol\u00F3', color: '#3b82f6'};
    var BMI_MIN = 12, BMI_MAX = 45;

    var formulas = {
        mifflin: {
            name: 'Mifflin-St Jeor',
            year: 1990,
            authors: 'Mifflin MD, St Jeor ST, Hill LA, Scott BJ, Daugherty SA, Koh YO',
            source: 'Am J Clin Nutr, 51(2), 241\u2013247. (1990)',
            desc: 'A Mifflin-St Jeor k\u00E9pletet 1990-ben publik\u00E1lt\u00E1k. Az Academy of Nutrition and Dietetics ezt tartja a legpontosabb BMR becsl\u0151 k\u00E9pletnek. 498 szem\u00E9lyen tesztelt\u00E9k (19\u201378 \u00E9v).',
            extra: 'Egyszer\u0171 fel\u00E9p\u00EDt\u00E9s\u0171, kevesebb hib\u00E1val becs\u00FCl. Izmos szem\u00E9lyekn\u00E9l alulbecs\u00FClhet.',
            summary: 'A legpontosabbnak tartott modern k\u00E9plet (AND aj\u00E1nl\u00E1s). Egyszer\u0171 fel\u00E9p\u00EDt\u00E9s, alacsony hiba\u00E1tlag. Izmos szem\u00E9lyekn\u00E9l enyh\u00E9n alulbecs\u00FClhet.',
            calc: function(g, w, h, a) {
                var b = (10 * w) + (6.25 * h) - (5 * a);
                return g === 'ferfi' ? b + 5 : b - 161;
            },
            fm: 'BMR = (10 \u00D7 s\u00FAly) + (6.25 \u00D7 mag.) \u2212 (5 \u00D7 kor) + 5',
            ff: 'BMR = (10 \u00D7 s\u00FAly) + (6.25 \u00D7 mag.) \u2212 (5 \u00D7 kor) \u2212 161'
        },
        roza: {
            name: 'Roza & Shizgal (revide\u00E1lt Harris-Benedict)',
            year: 1984,
            authors: 'Roza AM, Shizgal HM',
            source: 'Am J Clin Nutr, 40(1), 168\u2013182. (1984)',
            desc: 'Az eredeti Harris-Benedict 1984-es jav\u00EDtott v\u00E1ltozata. Roza \u00E9s Shizgal \u00FAjrakalibr\u00E1lt\u00E1k az egy\u00FCtthat\u00F3kat indirekt kalorimetri\u00E1val.',
            extra: 'Kb. 5%-kal pontosabb az eredetin\u00E9l. Klinikai gyakorlatban sz\u00E9les k\u00F6rben haszn\u00E1lt.',
            summary: 'Az eredeti Harris-Benedict jav\u00EDtott verzi\u00F3ja, ~5%-kal pontosabb. Klinikai gyakorlatban sz\u00E9les k\u00F6rben haszn\u00E1lt, megb\u00EDzhat\u00F3 \u00E1ltal\u00E1nos v\u00E1laszt\u00E1s.',
            calc: function(g, w, h, a) {
                return g === 'ferfi'
                    ? 88.362 + (13.397 * w) + (4.799 * h) - (5.677 * a)
                    : 447.593 + (9.247 * w) + (3.098 * h) - (4.330 * a);
            },
            fm: 'BMR = 88.362 + (13.397 \u00D7 s\u00FAly) + (4.799 \u00D7 mag.) \u2212 (5.677 \u00D7 kor)',
            ff: 'BMR = 447.593 + (9.247 \u00D7 s\u00FAly) + (3.098 \u00D7 mag.) \u2212 (4.330 \u00D7 kor)'
        },
        harris: {
            name: 'Harris-Benedict (eredeti)',
            year: 1919,
            authors: 'Harris JA, Benedict FG',
            source: 'Carnegie Institution of Washington, Publ. No. 279. (1919)',
            desc: 'Az els\u0151 sz\u00E9les k\u00F6rben elfogadott BMR k\u00E9plet. 239 szem\u00E9lyen m\u00E9rt\u00E9k indirekt kalorimetri\u00E1val.',
            extra: 'T\u00FAls\u00FAlyos \u00E9s id\u0151s szem\u00E9lyekn\u00E9l t\u00FAlbecs\u00FClhet. T\u00F6rt\u00E9nelmi jelent\u0151s\u00E9ge \u00F3ri\u00E1si.',
            summary: 'Az els\u0151 \u00E9s legismertebb BMR k\u00E9plet (1919). T\u00F6rt\u00E9nelmi jelent\u0151s\u00E9ge \u00F3ri\u00E1si, de t\u00FAls\u00FAlyos \u00E9s id\u0151s szem\u00E9lyekn\u00E9l t\u00FAlbecs\u00FClheti az anyagcser\u00E9t.',
            calc: function(g, w, h, a) {
                return g === 'ferfi'
                    ? 66.473 + (13.7516 * w) + (5.0033 * h) - (6.755 * a)
                    : 655.0955 + (9.5634 * w) + (1.8496 * h) - (4.6756 * a);
            },
            fm: 'BMR = 66.473 + (13.752 \u00D7 s\u00FAly) + (5.003 \u00D7 mag.) \u2212 (6.755 \u00D7 kor)',
            ff: 'BMR = 655.096 + (9.563 \u00D7 s\u00FAly) + (1.850 \u00D7 mag.) \u2212 (4.676 \u00D7 kor)'
        }
    };

    var macroProfiles = {
        '1.2':    {proteinPerKg: 1.0, fatPct: 0.30, source: 'WHO/FAO 2003'},
        '1.375':  {proteinPerKg: 1.2, fatPct: 0.28, source: 'ACSM 2016'},
        '1.55':   {proteinPerKg: 1.4, fatPct: 0.27, source: 'ACSM 2016'},
        '1.725':  {proteinPerKg: 1.6, fatPct: 0.25, source: 'ISSN 2017'},
        '1.9':    {proteinPerKg: 1.8, fatPct: 0.25, source: 'ISSN 2017'},
        'ath_2.0': {proteinPerKg: 2.0, fatPct: 0.25, source: 'ISSN 2017 (J\u00E4ger et al.)'},
        'ath_2.3': {proteinPerKg: 2.2, fatPct: 0.23, source: 'J\u00E4ger et al., JISSN 2017'},
        'ath_2.5': {proteinPerKg: 2.4, fatPct: 0.22, source: 'Iraki et al., Sports Med 2019'}
    };

    var underweightProfile = {
        proteinPct: 0.20,
        fatPct: 0.30,
        chPct: 0.50,
        source: 'NICE CG32 (2006), ESPEN 2021'
    };

    var KCAL_PER_KG_FAT = 7000;
    var S = {
        formula: 'harris',
        gender: 'ferfi',
        age: 30,
        height: 175,
        weight: 75,
        activity: 1.55,
        athlete: false,
        athleteLevel: 2.0,
        deficit: 0
    };

    var D = {
        chips: document.querySelectorAll('.bmr-formula-chip'),
        formulaSummary: document.getElementById('bmr-formula-summary'),
        gBtns: document.querySelectorAll('.bmr-gender-btn'),
        kor: document.getElementById('bmr-kor'),
        korSl: document.getElementById('bmr-kor-slider'),
        mag: document.getElementById('bmr-magassag'),
        magSl: document.getElementById('bmr-magassag-slider'),
        suly: document.getElementById('bmr-suly'),
        sulySl: document.getElementById('bmr-suly-slider'),
        actR: document.querySelectorAll('input[name="bmr-activity"]'),
        athSub: document.getElementById('bmr-athlete-sub'),
        athLevels: document.querySelectorAll('input[name="bmr-athlete-level"]'),
        athFactor: document.getElementById('bmr-athlete-factor'),
        bmiNum: document.getElementById('bmr-bmi-number'),
        bmiCat: document.getElementById('bmr-bmi-cat'),
        bmiMark: document.getElementById('bmr-bmi-marker'),
        athBmiNotice: document.getElementById('bmr-athlete-bmi-notice'),
        brocaN: document.getElementById('bmr-broca-notice'),
        brocaBmi: document.getElementById('bmr-broca-bmi'),
        brocaKg: document.getElementById('bmr-broca-kg'),
        brocaF: document.getElementById('bmr-broca-formula'),
        bmr: document.getElementById('bmr-result-bmr'),
        tdee: document.getElementById('bmr-result-tdee'),
        gC: document.getElementById('bmr-goal-cut'),
        gM: document.getElementById('bmr-goal-maintain'),
        gB: document.getElementById('bmr-goal-bulk'),
        cutProg: document.getElementById('bmr-cut-prognosis'),
        deficitSlider: document.getElementById('bmr-deficit-slider'),
        deficitValue: document.getElementById('bmr-deficit-value'),
        cutProgIntake: document.getElementById('bmr-cut-prog-intake'),
        cutProgStats: document.getElementById('bmr-cut-prog-stats'),
        cutProgBody: document.getElementById('bmr-cut-prog-body'),
        macroNotice: document.getElementById('bmr-macro-notice'),
        mC: document.getElementById('bmr-macro-c'),
        mF: document.getElementById('bmr-macro-f'),
        mP: document.getElementById('bmr-macro-p'),
        mCpk: document.getElementById('bmr-macro-c-perkg'),
        mFpk: document.getElementById('bmr-macro-f-perkg'),
        mPpk: document.getElementById('bmr-macro-p-perkg'),
        mCpct: document.getElementById('bmr-macro-c-pct'),
        mFpct: document.getElementById('bmr-macro-f-pct'),
        mPpct: document.getElementById('bmr-macro-p-pct'),
        macroExplain: document.getElementById('bmr-macro-explain'),
        info: document.getElementById('bmr-info-body')
    };

    /* ── Segédfüggvények ── */

    function getCat(b) {
        for (var i = 0; i < bmiCats.length; i++) {
            if (b < bmiCats[i].max) return bmiCats[i];
        }
        return bmiCats[bmiCats.length - 1];
    }

    function bmiPct(v) {
        return ((Math.max(BMI_MIN, Math.min(BMI_MAX, v)) - BMI_MIN) / (BMI_MAX - BMI_MIN)) * 100;
    }

    function r1(v) {
        return Math.round(v * 10) / 10;
    }

    function idealBroca(g, h) {
        return g === 'ferfi' ? (h - 100) * 0.9 : (h - 104) * 0.85;
    }

    function adjustedBW(g, h, w) {
        var id = idealBroca(g, h);
        return id + 0.25 * (w - id);
    }

    /* ── BMI tick pozíciók ── */

    function initBmiTicks() {
        var ticks = {
            16: 'bmr-tick-16',
            18.5: 'bmr-tick-18',
            25: 'bmr-tick-25',
            30: 'bmr-tick-30',
            40: 'bmr-tick-40'
        };
        for (var val in ticks) {
            var el = document.getElementById(ticks[val]);
            if (el) {
                el.style.left = bmiPct(parseFloat(val)) + '%';
            }
        }
    }

    /* ── Slider track frissítés ── */

    function updSl(sl) {
        var min = parseFloat(sl.min);
        var max = parseFloat(sl.max);
        var val = parseFloat(sl.value);
        var pct = ((val - min) / (max - min)) * 100;
        sl.style.setProperty('--pct', pct + '%');
    }

    /* ── Deficit slider track frissítés ── */

    function updDeficitSl() {
        var sl = D.deficitSlider;
        var min = parseFloat(sl.min);
        var max = parseFloat(sl.max);
        var val = parseFloat(sl.value);
        var pct = max > min ? ((val - min) / (max - min)) * 100 : 0;
        sl.style.setProperty('--pct', pct + '%');

        var color;
        if (val <= 500) {
            color = '#22c55e';
        } else if (val <= 750) {
            color = '#f59e0b';
        } else {
            color = '#e74c3c';
        }
        sl.style.setProperty('--deficit-color', color);
    }

    /* ── Animáció ── */

    function anim(el, t) {
        var c = parseInt(el.textContent) || 0;
        var d = t - c;
        if (!d) return;
        var s = 18;
        var v = d / s;
        var i = 0;
        var iv = setInterval(function() {
            if (++i >= s) {
                el.textContent = t;
                clearInterval(iv);
            } else {
                el.textContent = Math.round(c + v * i);
            }
        }, 15);
    }

    /* ── Képlet összefoglaló ── */

    function updFormulaSummary() {
        var f = formulas[S.formula];
        var fS = S.gender === 'ferfi' ? f.fm : f.ff;
        var h = '';
        h += '<p class="bmr-formula-summary-text">' + f.summary + '</p>';
        h += '<div class="bmr-formula-summary-formula"><code>' + fS + '</code></div>';
        D.formulaSummary.innerHTML = h;
    }

    /* ── Makró számítás ── */

    function calcMacros(tdee, bmi, weight) {
        var macroC, macroF, macroP;
        var macroMode;
        var macroSource;
        var proteinPerKg, fatPct, chPct, proteinPct;
        var proteinKcal, fatKcal, chKcal;

        /* 1. Alultápláltság (BMI < 18.5) – nem sportoló */
        if (bmi < 18.5 && !S.athlete) {
            macroMode = 'underweight';
            macroSource = underweightProfile.source;
            proteinPct = underweightProfile.proteinPct;
            fatPct = underweightProfile.fatPct;
            chPct = underweightProfile.chPct;

            proteinKcal = tdee * proteinPct;
            fatKcal = tdee * fatPct;
            chKcal = tdee * chPct;

            macroP = Math.round(proteinKcal / 4.1);
            macroF = Math.round(fatKcal / 9.3);
            macroC = Math.round(chKcal / 4.1);

            return {
                c: macroC,
                f: macroF,
                p: macroP,
                cPct: Math.round(chPct * 100),
                fPct: Math.round(fatPct * 100),
                pPct: Math.round(proteinPct * 100),
                mode: macroMode,
                source: macroSource
            };
        }

        /* 2. Sportoló mód */
        if (S.athlete) {
            macroMode = 'athlete';
            var athKey = 'ath_' + S.athleteLevel;
            var profile = macroProfiles[athKey];
            proteinPerKg = profile.proteinPerKg;
            fatPct = profile.fatPct;
            macroSource = profile.source;

            macroP = Math.round(proteinPerKg * weight);
            proteinKcal = macroP * 4.1;

            fatKcal = tdee * fatPct;
            macroF = Math.round(fatKcal / 9.3);

            chKcal = tdee - proteinKcal - fatKcal;
            if (chKcal < 0) chKcal = 0;
            macroC = Math.round(chKcal / 4.1);

            var totalKcal = proteinKcal + fatKcal + chKcal;
            var pPctActual = totalKcal > 0 ? Math.round((proteinKcal / totalKcal) * 100) : 0;
            var fPctActual = Math.round(fatPct * 100);
            var cPctActual = 100 - pPctActual - fPctActual;

            return {
                c: macroC,
                f: macroF,
                p: macroP,
                cPct: cPctActual,
                fPct: fPctActual,
                pPct: pPctActual,
                mode: macroMode,
                source: macroSource,
                proteinPerKg: proteinPerKg
            };
        }

        /* 3. Általános (aktivitási szint alapján) */
        macroMode = 'default';
        var actKey = String(S.activity);
        var defProfile = macroProfiles[actKey];

        if (!defProfile) {
            var bestKey = '1.55';
            var bestDiff = 999;
            for (var k in macroProfiles) {
                if (k.indexOf('ath_') === 0) continue;
                var diff = Math.abs(parseFloat(k) - S.activity);
                if (diff < bestDiff) {
                    bestDiff = diff;
                    bestKey = k;
                }
            }
            defProfile = macroProfiles[bestKey];
        }

        proteinPerKg = defProfile.proteinPerKg;
        fatPct = defProfile.fatPct;
        macroSource = defProfile.source;

        macroP = Math.round(proteinPerKg * weight);
        proteinKcal = macroP * 4.1;

        fatKcal = tdee * fatPct;
        macroF = Math.round(fatKcal / 9.3);

        chKcal = tdee - proteinKcal - fatKcal;
        if (chKcal < 0) chKcal = 0;
        macroC = Math.round(chKcal / 4.1);

        var totalKcalDef = proteinKcal + fatKcal + chKcal;
        var pPctDef = totalKcalDef > 0 ? Math.round((proteinKcal / totalKcalDef) * 100) : 0;
        var fPctDef = Math.round(fatPct * 100);
        var cPctDef = 100 - pPctDef - fPctDef;

        return {
            c: macroC,
            f: macroF,
            p: macroP,
            cPct: cPctDef,
            fPct: fPctDef,
            pPct: pPctDef,
            mode: macroMode,
            source: macroSource,
            proteinPerKg: proteinPerKg
        };
    }

    /* ── Makró notice frissítése ── */

    function updMacroNotice(macroResult, bmi) {
        var h = '';

        if (macroResult.mode === 'underweight') {
            h += '<div class="bmr-macro-notice-inner bmr-macro-notice--underweight">';
            h += '<div class="bmr-macro-notice-badge">\u26A0\uFE0F Alult\u00E1pl\u00E1lts\u00E1g \u2013 energiab\u0151, feh\u00E9rjed\u00FAs \u00E9trend javasolt</div>';
            h += '<p>A BMI \u00E9rt\u00E9ked <strong>' + bmi.toFixed(1) + '</strong>, ami alult\u00E1pl\u00E1lts\u00E1gra utal (BMI &lt; 18,5). ';
            h += 'Ilyenkor a szervezetnek <strong>t\u00F6bb feh\u00E9rj\u00E9re</strong> van sz\u00FCks\u00E9ge az izomtömeg meg\u0151rz\u00E9s\u00E9hez \u00E9s \u00FAj\u00E9p\u00EDt\u00E9s\u00E9hez, ';
            h += 'valamint <strong>energiab\u0151 \u00E9tkezésre</strong> a s\u00FAlygyarap\u00EDt\u00E1shoz.</p>';
            h += '<p>A makr\u00F3eloszt\u00E1s a <strong>NICE CG32 (2006)</strong> \u00E9s <strong>ESPEN (2021)</strong> ir\u00E1nyelvek alapj\u00E1n:</p>';
            h += '<ul class="bmr-macro-notice-list">';
            h += '<li><strong>Feh\u00E9rje: 20 energia%</strong> \u2013 emelt bevitel az izomtömeg v\u00E9delm\u00E9hez</li>';
            h += '<li><strong>Zs\u00EDr: 30 energia%</strong> \u2013 s\u0171r\u0171 energiaforr\u00E1s, esszenci\u00E1lis zs\u00EDrsavak</li>';
            h += '<li><strong>Sz\u00E9nhidr\u00E1t: 50 energia%</strong> \u2013 f\u0151 energiaforr\u00E1s</li>';
            h += '</ul>';
            h += '<p class="bmr-macro-notice-advice">\uD83D\uDC68\u200D\u2695\uFE0F K\u00E9rj\u00FCk, konzult\u00E1lj orvossal vagy dietetikussal az egyéni \u00E9trend kialak\u00EDt\u00E1s\u00E1hoz.</p>';
            h += '</div>';
        }

        if (macroResult.mode === 'athlete') {
            h += '<div class="bmr-macro-notice-inner bmr-macro-notice--athlete">';
            h += '<div class="bmr-macro-notice-badge">\uD83C\uDFCB\uFE0F Sportol\u00F3i makr\u00F3eloszt\u00E1s</div>';
            h += '<p>A makr\u00F3k sportol\u00F3i ir\u00E1nyelvek szerint sz\u00E1molva, <strong>g/ttkg alap\u00FA feh\u00E9rjeadag</strong> \u00E9s a marad\u00E9k sz\u00E9nhidr\u00E1t elv\u00E9n:</p>';
            h += '<ul class="bmr-macro-notice-list">';
            h += '<li><strong>Feh\u00E9rje: ' + macroResult.proteinPerKg + ' g/ttkg</strong> \u2013 izom\u00E9p\u00EDt\u00E9s \u00E9s regener\u00E1ci\u00F3</li>';
            h += '<li><strong>Zs\u00EDr: ' + macroResult.fPct + ' energia%</strong> \u2013 hormontermel\u00E9s, minimum 20% felett</li>';
            h += '<li><strong>Sz\u00E9nhidr\u00E1t: marad\u00E9k</strong> \u2013 edzéstüzel\u0151anyag, glikog\u00E9nfelt\u00F6lt\u00E9s</li>';
            h += '</ul>';
            h += '<p class="bmr-macro-notice-source">Forr\u00E1s: <strong>' + macroResult.source + '</strong></p>';
            h += '<table class="bmr-macro-notice-table"><tr><th>Sportol\u00F3i szint</th><th>Feh\u00E9rje</th><th>Zs\u00EDr</th><th>Forr\u00E1s</th></tr>';
            h += '<tr><td>Hobbi sportol\u00F3</td><td>2,0 g/ttkg</td><td>25%</td><td>ISSN 2017</td></tr>';
            h += '<tr><td>Versenyez\u0151 / test\u00E9p\u00EDt\u0151</td><td>2,2 g/ttkg</td><td>23%</td><td>J\u00E4ger et al. 2017</td></tr>';
            h += '<tr><td>\u00C9lsportol\u00F3</td><td>2,4 g/ttkg</td><td>22%</td><td>Iraki et al. 2019</td></tr>';
            h += '</table>';
            h += '</div>';
        }

        if (macroResult.mode === 'default' && bmi >= 18.5) {
            h += '<div class="bmr-macro-notice-inner bmr-macro-notice--default">';
            h += '<div class="bmr-macro-notice-badge">\u2139\uFE0F Aktivit\u00E1shoz igaz\u00EDtott eloszt\u00E1s</div>';
            h += '<p>A makr\u00F3eloszt\u00E1s az aktivit\u00E1si szinted alapj\u00E1n sz\u00E1molva: <strong>' + macroResult.proteinPerKg + ' g/ttkg feh\u00E9rje</strong>, ';
            h += macroResult.fPct + ' energia% zs\u00EDr, sz\u00E9nhidr\u00E1t marad\u00E9kb\u00F3l.</p>';
            h += '<p class="bmr-macro-notice-source">Forr\u00E1s: <strong>' + macroResult.source + '</strong></p>';
            h += '</div>';
        }

        D.macroNotice.innerHTML = h;
    }

    /* ── Fogyási prognózis frissítése ── */

    function updateCutProg(tdee) {
        var deficit = S.deficit;
        var cutKcal = tdee - deficit;

        if (cutKcal < 1200) {
            cutKcal = 1200;
            deficit = tdee - 1200;
            if (deficit < 0) deficit = 0;
        }

        var maxDeficit = Math.min(1500, tdee - 1200);
        if (maxDeficit < 0) maxDeficit = 0;
        D.deficitSlider.max = maxDeficit;

        if (S.deficit > maxDeficit) {
            S.deficit = maxDeficit;
            D.deficitSlider.value = maxDeficit;
            deficit = maxDeficit;
            cutKcal = tdee - deficit;
        }

        D.deficitValue.textContent = deficit;
        updDeficitSl();

        var intakeH = '';
        intakeH += '<div class="bmr-cut-prog-intake-inner">';
        intakeH += '<span class="bmr-cut-prog-intake-label">Napi energiabevitel:</span>';
        intakeH += '<span class="bmr-cut-prog-intake-value">' + cutKcal + ' kcal</span>';
        intakeH += '<span class="bmr-cut-prog-intake-detail">(TDEE: ' + tdee + ' kcal \u2212 deficit: ' + deficit + ' kcal)</span>';
        intakeH += '</div>';
        D.cutProgIntake.innerHTML = intakeH;

        if (deficit <= 0) {
            D.cutProgStats.innerHTML = '';
            D.cutProgBody.innerHTML = '<p class="bmr-cut-prog-note">Nincs kal\u00F3riadeficit be\u00E1ll\u00EDtva. H\u00FAzd a slidert a fogy\u00E1si progn\u00F3zis megtekint\u00E9s\u00E9hez.</p>';
            return;
        }

        var wKg = r1((deficit * 7) / KCAL_PER_KG_FAT);
        var mKg = r1((deficit * 30) / KCAL_PER_KG_FAT);

        var tempoClass, tempoText;
        if (wKg <= 0.5) {
            tempoClass = 'bmr-cut-prog-tempo--green';
            tempoText = 'Lassú, kíméletes fogyás';
        } else if (wKg <= 1.0) {
            tempoClass = 'bmr-cut-prog-tempo--green';
            tempoText = 'Egészséges fogyási tempó';
        } else {
            tempoClass = 'bmr-cut-prog-tempo--red';
            tempoText = 'Agresszív tempó – fokozott kockázat';
        }

        var statsH = '';
        statsH += '<div class="bmr-cut-prog-stat">';
        statsH += '<span class="bmr-cut-prog-stat-value">' + deficit + '</span>';
        statsH += '<span class="bmr-cut-prog-stat-label">kcal napi deficit</span>';
        statsH += '</div>';
        statsH += '<div class="bmr-cut-prog-stat-divider"></div>';
        statsH += '<div class="bmr-cut-prog-stat">';
        statsH += '<span class="bmr-cut-prog-stat-value">' + wKg + ' kg</span>';
        statsH += '<span class="bmr-cut-prog-stat-label">fogy\u00E1s / h\u00E9t</span>';
        statsH += '</div>';
        statsH += '<div class="bmr-cut-prog-stat-divider"></div>';
        statsH += '<div class="bmr-cut-prog-stat">';
        statsH += '<span class="bmr-cut-prog-stat-value bmr-cut-prog-stat-value--highlight">' + mKg + ' kg</span>';
        statsH += '<span class="bmr-cut-prog-stat-label">fogy\u00E1s / h\u00F3nap</span>';
        statsH += '</div>';
        D.cutProgStats.innerHTML = statsH;

        var bodyH = '';
        bodyH += '<div class="bmr-cut-prog-tempo ' + tempoClass + '">' + tempoText + '</div>';
        bodyH += '<p class="bmr-cut-prog-note">Ha naponta <strong>' + cutKcal + ' kcal</strong> energi\u00E1t viszel be, az a napi sz\u00FCks\u00E9gletedhez (TDEE: ' + tdee + ' kcal) k\u00E9pest <strong>' + deficit + ' kcal deficitet</strong> jelent. ';
        bodyH += 'Mivel 1 kg testzs\u00EDr el\u00E9get\u00E9s\u00E9hez \u00F6sszesen kb. <strong>7000 kcal</strong> \u00F6sszdeficit sz\u00FCks\u00E9ges, ez a m\u00E9rt\u00E9k\u0171 napi hi\u00E1ny 30 nap alatt v\u00E1rhat\u00F3an <strong>~' + mKg + ' kg fogy\u00E1st</strong> eredm\u00E9nyez.</p>';
        bodyH += '<p class="bmr-cut-prog-healthy-note">\u2139\uFE0F Az eg\u00E9szs\u00E9g\u00FCgyi aj\u00E1nl\u00E1sok szerint a fenntarthat\u00F3, eg\u00E9szs\u00E9ges fogy\u00E1s \u00FCteme <strong>heti 0,5\u20131 kg</strong>. A napi minim\u00E1lis kal\u00F3riabevitel nem mehet <strong>1200 kcal al\u00E1</strong>.</p>';

        if (deficit >= 750) {
            bodyH += '<p class="bmr-cut-prog-warning">\u26A0\uFE0F A napi ' + deficit + ' kcal-os kal\u00F3riadeficit agressz\u00EDvnak sz\u00E1m\u00EDt. ';
            bodyH += 'A t\u00FAl nagy kal\u00F3riadeficit izomveszt\u00E9shez, anyagcsere-lass\u00FAl\u00E1shoz, hormon\u00E1lis zavarokhoz \u00E9s t\u00E1panyaghi\u00E1nyhoz vezethet. ';
            bodyH += 'K\u00E9rj\u00FCk, konzult\u00E1lj dietetikussal vagy orvossal, miel\u0151tt ilyen m\u00E9rt\u00E9k\u0171 deficitet alkalmazn\u00E1l.</p>';
        }
        D.cutProgBody.innerHTML = bodyH;
    }

    /* ── Fő számítás ── */

    function calc() {
        var f = formulas[S.formula];
        var hm = S.height / 100;
        var bmi = S.weight / (hm * hm);
        var isObese = bmi >= 30;
        var useAdj = isObese && !S.athlete;
        var idealW = idealBroca(S.gender, S.height);
        var adjW = adjustedBW(S.gender, S.height, S.weight);
        var cW = useAdj ? adjW : S.weight;
        var act = S.athlete ? S.athleteLevel : S.activity;

        /* BMI kijelzés */
        D.bmiNum.textContent = bmi.toFixed(1);
        if (S.athlete) {
            D.bmiCat.textContent = ATH_CAT.label;
            D.bmiCat.style.color = ATH_CAT.color;
            D.bmiMark.style.background = ATH_CAT.color;
        } else {
            var cat = getCat(bmi);
            D.bmiCat.textContent = cat.label;
            D.bmiCat.style.color = cat.color;
            D.bmiMark.style.background = cat.color;
        }
        D.bmiMark.style.left = bmiPct(bmi) + '%';

        /* Sportoló alválasztó és BMI notice */
        D.athSub.classList.toggle('is-visible', S.athlete);
        D.athBmiNotice.style.display = S.athlete ? 'block' : 'none';

        /* Broca korrekció */
        if (useAdj) {
            D.brocaN.style.display = 'block';
            D.brocaBmi.textContent = bmi.toFixed(1);
            D.brocaKg.textContent = r1(adjW);
            var sub = S.gender === 'ferfi' ? 100 : 104;
            var mul = S.gender === 'ferfi' ? '0,9' : '0,85';
            var h = '<div class="bmr-broca-step"><strong>1. Ide\u00E1lis tests\u00FAly (Broca):</strong><br>';
            h += '<code>(' + S.height + ' \u2212 ' + sub + ') \u00D7 ' + mul + ' = ' + r1(idealW) + ' kg</code></div>';
            h += '<div class="bmr-broca-step"><strong>2. Korrig\u00E1lt tests\u00FAly (ABW):</strong><br>';
            h += '<code>' + r1(idealW) + ' + 0,25 \u00D7 (' + S.weight + ' \u2212 ' + r1(idealW) + ') = <strong>' + r1(adjW) + ' kg</strong></code></div>';
            h += '<p class="bmr-broca-explain">A t\u00F6bbletsúly ~25%-a metabolikusan akt\u00EDv sz\u00F6vet, ~75%-a zs\u00EDr.</p>';
            D.brocaF.innerHTML = h;
        } else {
            D.brocaN.style.display = 'none';
        }

        /* Képlet összefoglaló */
        updFormulaSummary();

        /* BMR és TDEE */
        var bmr = Math.round(f.calc(S.gender, cW, S.height, S.age));
        if (bmr < 0) bmr = 0;
        var tdee = Math.round(bmr * act);

        anim(D.bmr, bmr);
        anim(D.tdee, tdee);

        /* Kalória célok */
        var cutGoal = Math.max(1200, tdee - 500);
        anim(D.gC, cutGoal);
        anim(D.gM, tdee);
        anim(D.gB, tdee + 500);

        /* Makrók */
        var macroResult = calcMacros(tdee, bmi, S.weight);

        anim(D.mC, macroResult.c);
        anim(D.mF, macroResult.f);
        anim(D.mP, macroResult.p);

        /* g/ttkg */
        var w = S.weight;
        D.mCpk.textContent = w > 0 ? r1(macroResult.c / w) : '0';
        D.mFpk.textContent = w > 0 ? r1(macroResult.f / w) : '0';
        D.mPpk.textContent = w > 0 ? r1(macroResult.p / w) : '0';

        /* Energia% frissítése */
        D.mCpct.textContent = macroResult.cPct + ' energia%';
        D.mFpct.textContent = macroResult.fPct + ' energia%';
        D.mPpct.textContent = macroResult.pPct + ' energia%';

        /* Makró notice */
        updMacroNotice(macroResult, bmi);

        /* Fogyási prognózis */
        updateCutProg(tdee);

        /* Info szekció */
        updInfo(f, useAdj, bmi, idealW, adjW, cW, act, macroResult);
    }

    /* ── Info szekció ── */

    function updInfo(f, useAdj, bmi, idealW, adjW, cW, act, macroResult) {
        var gL = S.gender === 'ferfi' ? 'F\u00E9rfi' : 'N\u0151';
        var fS = S.gender === 'ferfi' ? f.fm : f.ff;
        var mul = S.gender === 'ferfi' ? '0,9' : '0,85';
        var h = '';

        h += '<div class="bmr-info-meta">';
        h += '<span><strong>K\u00E9plet:</strong> ' + f.name + ' (' + f.year + ')</span>';
        h += '<span><strong>Szerz\u0151k:</strong> ' + f.authors + '</span>';
        h += '<span><strong>Forr\u00E1s:</strong> ' + f.source + '</span>';
        h += '</div>';

        h += '<p class="bmr-info-desc">' + f.desc + '</p>';
        if (f.extra) {
            h += '<p class="bmr-info-desc">' + f.extra + '</p>';
        }

        h += '<div class="bmr-info-formula"><strong>' + gL + ' k\u00E9plet:</strong><br><code>' + fS + '</code></div>';

        if (useAdj) {
            h += '<div class="bmr-info-broca"><strong>\u26A0\uFE0F Korrig\u00E1lt tests\u00FAly (ABW)</strong><br>BMI: <strong>' + bmi.toFixed(1) + '</strong><br><br>';
            h += '1) Broca (' + gL + ', \u00D7' + mul + '): <code>' + r1(idealW) + ' kg</code><br>';
            h += '2) ABW = ' + r1(idealW) + ' + 0,25 \u00D7 (' + S.weight + ' \u2212 ' + r1(idealW) + ') = <code>' + r1(adjW) + ' kg</code><br><br>';
            h += 'Sz\u00E1m\u00EDt\u00E1s: <strong>' + r1(cW) + ' kg</strong> (val\u00F3s: ' + S.weight + ' kg).</div>';
        }

        if (S.athlete) {
            h += '<div class="bmr-info-athlete"><strong>\uD83C\uDFCB\uFE0F Sportol\u00F3 m\u00F3d (\u00D7 ' + act + ')</strong><br>Val\u00F3s tests\u00FAllyal (' + S.weight + ' kg) sz\u00E1molunk. Forr\u00E1s: FAO/WHO/UNU 2001, Thomas et al. (J Acad Nutr Diet, 2016).</div>';
        }

        h += '<div class="bmr-info-activity"><strong>Aktivit\u00E1si szorz\u00F3k (PAL):</strong>';
        h += '<table class="bmr-info-table"><tr><th>Szint</th><th>Szorz\u00F3</th><th>Le\u00EDr\u00E1s</th></tr>';
        h += '<tr><td>\u00DCl\u0151 \u00E9letm\u00F3d</td><td>\u00D7 1.2</td><td>Irodai munka</td></tr>';
        h += '<tr><td>Enyh\u00E9n akt\u00EDv</td><td>\u00D7 1.375</td><td>Heti 1-3 k\u00F6nny\u0171 edz\u00E9s</td></tr>';
        h += '<tr><td>M\u00E9rs\u00E9kelten akt\u00EDv</td><td>\u00D7 1.55</td><td>Heti 3-5 k\u00F6zepes edz\u00E9s</td></tr>';
        h += '<tr><td>Nagyon akt\u00EDv</td><td>\u00D7 1.725</td><td>Heti 6-7 intenz\u00EDv edz\u00E9s</td></tr>';
        h += '<tr><td>Extra akt\u00EDv</td><td>\u00D7 1.9</td><td>Napi 2\u00D7 edz\u00E9s / fizikai munka</td></tr>';
        h += '<tr class="bmr-info-table-ath"><td>Hobbi sportol\u00F3</td><td>\u00D7 2.0</td><td>Napi 1-2 \u00F3ra intenz\u00EDv</td></tr>';
        h += '<tr class="bmr-info-table-ath"><td>Versenyez\u0151</td><td>\u00D7 2.3</td><td>Napi 2-4 \u00F3ra edz\u00E9s</td></tr>';
        h += '<tr class="bmr-info-table-ath"><td>\u00C9lsportol\u00F3</td><td>\u00D7 2.5</td><td>Napi 4+ \u00F3ra</td></tr>';
        h += '</table></div>';

        h += '<div class="bmr-info-macro"><strong>Makr\u00F3eloszt\u00E1s:</strong> ';
        if (macroResult.mode === 'underweight') {
            h += 'Alult\u00E1pl\u00E1lts\u00E1g eset\u00E9n 20 energia% feh\u00E9rje, 30% zs\u00EDr, 50% sz\u00E9nhidr\u00E1t (NICE CG32 2006, ESPEN 2021). ';
            h += 'C\u00E9l: energiab\u0151, feh\u00E9rjed\u00FAs \u00E9trend az izomtömeg meg\u0151rz\u00E9s\u00E9hez.';
        } else if (macroResult.mode === 'athlete') {
            h += 'Sportol\u00F3i m\u00F3d \u2013 feh\u00E9rje g/ttkg alapon (' + macroResult.proteinPerKg + ' g/ttkg), ';
            h += 'zs\u00EDr ' + macroResult.fPct + ' energia%, sz\u00E9nhidr\u00E1t marad\u00E9kb\u00F3l. ';
            h += 'Forr\u00E1s: ' + macroResult.source + '. ';
            h += 'A sportol\u00F3i feh\u00E9rjebevitel 1,6\u20132,4 g/ttkg k\u00F6z\u00F6tt javasolt az izom\u00E9p\u00EDt\u00E9shez \u00E9s regener\u00E1ci\u00F3hoz.';
        } else {
            h += macroResult.pPct + ' energia% feh\u00E9rje (' + macroResult.proteinPerKg + ' g/ttkg), ';
            h += macroResult.fPct + ' energia% zs\u00EDr, ' + macroResult.cPct + ' energia% sz\u00E9nhidr\u00E1t. ';
            h += 'Forr\u00E1s: ' + macroResult.source + '.';
        }
        h += ' A grammra sz\u00E1m\u00EDt\u00E1s Atwater-faktorokkal: 1 g CH/feh\u00E9rje = 4,1 kcal, 1 g zs\u00EDr = 9,3 kcal.</div>';

        h += '<div class="bmr-info-fat"><strong>Fogy\u00E1s \u00E9s deficit:</strong> 1 kg testzs\u00EDr \u2248 7000 kcal (Hall KD, Int J Obes, 2008). ';
        h += 'Eg\u00E9szs\u00E9ges fogy\u00E1si tempó: heti 0,5\u20131 kg. Minim\u00E1lis napi bevitel: 1200 kcal.</div>';

        h += '<div class="bmr-info-bmi"><strong>BMI kateg\u00F3ri\u00E1k (WHO):</strong>';
        h += '<table class="bmr-info-table"><tr><th>BMI</th><th>Kateg\u00F3ria</th></tr>';
        h += '<tr><td>&lt; 16,0</td><td>S\u00FAlyos sov\u00E1nys\u00E1g</td></tr>';
        h += '<tr><td>16,0 \u2013 16,99</td><td>M\u00E9rs\u00E9kelt sov\u00E1nys\u00E1g</td></tr>';
        h += '<tr><td>17,0 \u2013 18,49</td><td>Enyhe sov\u00E1nys\u00E1g</td></tr>';
        h += '<tr><td>18,5 \u2013 24,99</td><td>Norm\u00E1l tests\u00FAly</td></tr>';
        h += '<tr><td>25,0 \u2013 29,99</td><td>T\u00FAls\u00FAly</td></tr>';
        h += '<tr><td>30,0 \u2013 34,99</td><td>I. fok\u00FA elh\u00EDz\u00E1s</td></tr>';
        h += '<tr><td>35,0 \u2013 39,99</td><td>II. fok\u00FA elh\u00EDz\u00E1s</td></tr>';
        h += '<tr><td>\u2265 40,0</td><td>III. fok\u00FA (s\u00FAlyos) elh\u00EDz\u00E1s</td></tr>';
        h += '</table></div>';

        D.info.innerHTML = h;
    }

    /* ── Eseménykezelők ── */

    D.chips.forEach(function(c) {
        c.addEventListener('click', function() {
            D.chips.forEach(function(x) {
                x.classList.remove('is-active');
            });
            c.classList.add('is-active');
            S.formula = c.dataset.formula;
            calc();
        });
    });

    D.gBtns.forEach(function(b) {
        b.addEventListener('click', function() {
            D.gBtns.forEach(function(x) {
                x.classList.remove('is-active');
            });
            b.classList.add('is-active');
            S.gender = b.dataset.gender;
            calc();
        });
    });

    function sync(inp, sl, key, fl) {
        function p(v) {
            return fl ? parseFloat(v) : parseInt(v, 10);
        }
        function update() {
            var v = p(inp.value);
            if (!isNaN(v)) {
                sl.value = v;
                S[key] = v;
                updSl(sl);
                calc();
            }
        }
        function updateFromSlider() {
            var v = p(sl.value);
            inp.value = v;
            S[key] = v;
            updSl(sl);
            calc();
        }
        inp.addEventListener('input', update);
        sl.addEventListener('input', updateFromSlider);
        updSl(sl);
    }

    sync(D.kor, D.korSl, 'age', false);
    sync(D.mag, D.magSl, 'height', false);
    sync(D.suly, D.sulySl, 'weight', true);

    D.actR.forEach(function(r) {
        r.addEventListener('change', function() {
            S.athlete = r.dataset.athlete === '1';
            if (S.athlete) {
                S.athleteLevel = parseFloat(document.querySelector('input[name="bmr-athlete-level"]:checked').value);
                S.activity = S.athleteLevel;
            } else {
                S.activity = parseFloat(r.value);
            }
            calc();
        });
    });

    D.athLevels.forEach(function(r) {
        r.addEventListener('change', function() {
            S.athleteLevel = parseFloat(r.value);
            D.athFactor.textContent = '\u00D7 ' + r.value;
            document.querySelector('.bmr-activity-option--athlete input[name="bmr-activity"]').value = r.value;
            calc();
        });
    });

    D.deficitSlider.addEventListener('input', function() {
        S.deficit = parseInt(D.deficitSlider.value, 10);
        calc();
    });

    /* ── Inicializálás ── */

    initBmiTicks();
    calc();

})();
</script>

<?php get_footer(); ?>