<?php
/**
 * Front Page Template – TápanyagLexikon Főoldal
 * Child theme: kadence-child/front-page.php
 * Tartalom megjelenítés a #24-es snippet-ből jön
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

// A snippet rendereli a teljes főoldal tartalmat
if ( function_exists( 'taplex_render_frontpage' ) ) {
    taplex_render_frontpage();
}

get_footer();