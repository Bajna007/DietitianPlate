<?php

/**
 * 01 – Recept CPT + kategória
 */
// Recept CPT + kategória taxonómia
function recept_cpt_init() {
    register_post_type('recept', [
        'labels' => [
            'name'                  => 'Receptek',
            'singular_name'         => 'Recept',
            'add_new'               => 'Új recept',
            'add_new_item'          => 'Új recept hozzáadása',
            'edit_item'             => 'Recept szerkesztése',
            'new_item'              => 'Új recept',
            'view_item'             => 'Recept megtekintése',
            'view_items'            => 'Receptek megtekintése',
            'search_items'          => 'Receptek keresése',
            'not_found'             => 'Nem található recept.',
            'not_found_in_trash'    => 'Nincs recept a lomtárban.',
            'all_items'             => 'Összes recept',
            'archives'              => 'Recept archívum',
            'attributes'            => 'Recept tulajdonságai',
            'insert_into_item'      => 'Beszúrás a receptbe',
            'uploaded_to_this_item' => 'Feltöltve ehhez a recepthez',
            'featured_image'        => 'Kiemelt kép',
            'set_featured_image'    => 'Kiemelt kép beállítása',
            'remove_featured_image' => 'Kiemelt kép eltávolítása',
            'use_featured_image'    => 'Használat kiemelt képként',
            'menu_name'             => 'Receptek',
            'item_published'        => 'Recept közzétéve.',
            'item_updated'          => 'Recept frissítve.',
            'item_reverted_to_draft'=> 'Recept visszaállítva vázlattá.',
            'item_scheduled'        => 'Recept ütemezve.',
            'item_link'             => 'Recept link',
            'item_link_description' => 'Link egy receptre.',
        ],
        'public'       => true,
        'supports'     => ['title', 'thumbnail', 'excerpt'],
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
