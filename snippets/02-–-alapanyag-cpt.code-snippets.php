<?php

/**
 * 02 – Alapanyag CPT
 */
// Alapanyag CPT makró adatbázishoz
function alapanyag_cpt_init() {
    register_post_type('alapanyag', [
        'labels' => [
            'name'          => 'Alapanyagok',
            'singular_name' => 'Alapanyag',
        ],
        'public'       => true,
        'supports'     => ['title', 'thumbnail', 'excerpt'],
        'menu_icon'    => 'dashicons-food',
        'has_archive'  => true,
        'rewrite'      => ['slug' => 'alapanyag'],
        'show_in_rest' => true,
    ]);
}
add_action('init', 'alapanyag_cpt_init');
