<?php

/**
 * 24. – Alapanyag Single JS
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ============================================================
// 24 – Alapanyag Single JS (v6)
// Tab kezelés + dinamikus fejléc + kategória divider elrejtés
// ============================================================

function alapanyag_single_js() {
    if ( ! is_singular( 'alapanyag' ) ) return;

    $js = <<<'JSEOF'
(function() {
    'use strict';

    var tabs = document.querySelectorAll('.aa-tab');
    var rows = document.querySelectorAll('.aa-table tbody tr');
    var headerLabel = document.querySelector('.aa-table thead th:first-child');

    if (!tabs.length || !rows.length) return;

    // ── Fejléc szövegek tab-onként ──
    var headerLabels = {
        'all':      'Általános tápanyagtartalom',
        'vitamins': 'Vitaminok',
        'minerals': 'Ásványi anyagok',
        'trace':    'Nyomelemek',
        'fats':     'Zsírsavak'
    };

    // ── Szűrő függvény ──
    function filterRows(filter) {
        rows.forEach(function(row) {
            var group = row.getAttribute('data-group') || '';
            var isDivider = row.classList.contains('aa-row-divider');

            if (filter === 'all') {
                // Összes fül: 'all' szót tartalmazó sorok – dividerek is látszanak
                row.style.display = group.indexOf('all') !== -1 ? '' : 'none';
            } else if (filter === 'fats') {
                // Zsírsavak fül: 'fatsonly' group-ú sorok
                // Divider sor elrejtése – a fejléc már mutatja a nevet
                if (isDivider) {
                    row.style.display = 'none';
                } else {
                    row.style.display = group.indexOf('fatsonly') !== -1 ? '' : 'none';
                }
            } else {
                // Vitaminok / Ásványi anyagok / Nyomelemek
                // Divider sor elrejtése – a fejléc már mutatja a nevet
                if (isDivider) {
                    row.style.display = 'none';
                } else {
                    row.style.display = group.indexOf(filter) !== -1 ? '' : 'none';
                }
            }
        });

        // ── Fejléc szöveg frissítése ──
        if (headerLabel && headerLabels[filter]) {
            headerLabel.textContent = headerLabels[filter];
        }
    }

    // ── Tab klikk kezelés ──
    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            tabs.forEach(function(t) { t.classList.remove('is-active'); });
            this.classList.add('is-active');
            filterRows(this.dataset.tab);
        });
    });

    // ── BETÖLTÉSKOR: azonnal futtatjuk az aktív tab szűrését ──
    var activeTab = document.querySelector('.aa-tab.is-active');
    if (activeTab) {
        filterRows(activeTab.dataset.tab);
    }

})();
JSEOF;

    wp_register_script( 'alapanyag-single-js', false, [], '6.0', true );
    wp_enqueue_script( 'alapanyag-single-js' );
    wp_add_inline_script( 'alapanyag-single-js', $js );
}
add_action( 'wp_enqueue_scripts', 'alapanyag_single_js' );
