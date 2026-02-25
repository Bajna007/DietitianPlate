<?php

/**
 * 01 – Recept CPT + kategória
 */
// Recept CPT + kategória taxonómia
function recept_cpt_init() {
    register_post_type('recept', [
        'labels' => [
            'name'          => 'Receptek',
            'singular_name' => 'Recept',
        ],
        'public'       => true,
        'supports'     => ['title', 'editor', 'thumbnail', 'excerpt'],
        'menu_icon'    => 'dashicons-carrot',
        'has_archive'  => true,
        'rewrite'      => ['slug' => 'recept'],
        'show_in_rest' => true,
    ]);

    register_taxonomy('recept_kategoria', 'recept', [
        'labels' => [
            'name'          => 'Recept kategóriák',
            'singular_name' => 'Recept kategória',
        ],
        'hierarchical' => true,
        'public'       => true,
        'rewrite'      => ['slug' => 'recept-kategoria'],
        'show_in_rest' => true,
    ]);
}
add_action('init', 'recept_cpt_init');
