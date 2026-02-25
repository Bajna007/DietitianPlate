<?php

/**
 * 16 – Open Food Facts attribution
 */
/**
 * 17 – Tápérték adatok Attribution (Footer fölé – OFF + USDA)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function openfoodfacts_footer_attribution() {
    $relevant_pages = [ 'tapanyag', 'alapanyag', 'recept' ];
    
    if ( 
        ! is_singular( $relevant_pages ) &&
        ! is_post_type_archive( $relevant_pages ) &&
        ! is_tax( [ 'tapanyag_tipus', 'oldhatosag', 'tapanyag_hatas' ] )
    ) { return; }

    // DUPLIKÁCIÓ ELLEN: session check
    if ( ! empty( $_SESSION['off_attribution_shown'] ) ) return;
    $_SESSION['off_attribution_shown'] = true;
    ?>

    <div class="off-footer-attribution">
        <div class="off-container">
            <span class="off-label">Tápérték adatok forrásai:</span>
            <a href="https://world.openfoodfacts.org" 
               target="_blank" 
               rel="noopener noreferrer"
               class="off-link">
                © Open Food Facts contributors
            </a>
            <span class="off-separator">|</span>
            <a href="https://fdc.nal.usda.gov/" 
               target="_blank" 
               rel="noopener noreferrer"
               class="off-link">
                USDA FoodData Central
            </a>
        </div>
        <div class="off-container off-license-row">
            <a href="https://opendatacommons.org/licenses/odbl/1-0/" 
               target="_blank" 
               rel="noopener noreferrer"
               class="off-license-link">
                ODbL licenc
            </a>
            <span class="off-separator">|</span>
            <a href="https://creativecommons.org/publicdomain/zero/1.0/" 
               target="_blank" 
               rel="noopener noreferrer"
               class="off-license-link">
                CC0 Public Domain
            </a>
            <span class="off-separator">|</span>
            <span class="off-modified">Az egyes adatok az eredeti forrásokhoz képest módosítva lettek.</span>
        </div>
    </div>

    <style>
    .off-footer-attribution {
        margin: 40px 0 20px;
        padding: 14px 0;
        background: rgba(255,255,255,0.92);
        border-top: 1px solid #e8ebe9;
        border-bottom: 1px solid #e8ebe9;
        text-align: center;
        font-size: 12px;
        color: #7c8a83;
    }
    .off-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .off-license-row {
        margin-top: 4px;
        font-size: 11px;
    }
    .off-separator {
        color: #cbd5e1;
        font-weight: 300;
    }
    .off-link {
        color: #666;
        text-decoration: none;
        font-weight: 500;
        padding: 3px 8px;
        border-radius: 5px;
        transition: all 0.25s ease;
    }
    .off-link:hover {
        background: rgba(245,158,11,0.08);
        color: #D97706;
    }
    .off-license-link {
        color: #94a3b8;
        text-decoration: none;
        transition: all 0.25s ease;
    }
    .off-license-link:hover {
        color: #D97706;
    }
    .off-modified {
        color: #94a3b8;
        font-style: italic;
    }
    @media (max-width: 768px) {
        .off-container {
            gap: 6px;
            padding: 0 16px;
            font-size: 11px;
        }
        .off-license-row {
            font-size: 10px;
        }
    }
    </style>
    <?php
}

// CSAK EGY HOOK – wp_footer 5-el (footer előtt)
add_action( 'wp_footer', 'openfoodfacts_footer_attribution', 5 );
