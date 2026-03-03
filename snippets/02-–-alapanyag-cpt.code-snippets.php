<?php

/**
 * 02 – Alapanyag CPT
 */
// Alapanyag CPT makró adatbázishoz
function alapanyag_cpt_init() {
    register_post_type('alapanyag', [
        'labels' => [
            'name'                  => 'Alapanyagok',
            'singular_name'         => 'Alapanyag',
            'add_new'               => 'Új alapanyag',
            'add_new_item'          => 'Új alapanyag hozzáadása',
            'edit_item'             => 'Alapanyag szerkesztése',
            'new_item'              => 'Új alapanyag',
            'view_item'             => 'Alapanyag megtekintése',
            'view_items'            => 'Alapanyagok megtekintése',
            'search_items'          => 'Alapanyagok keresése',
            'not_found'             => 'Nem található alapanyag.',
            'not_found_in_trash'    => 'Nincs alapanyag a lomtárban.',
            'all_items'             => 'Összes alapanyag',
            'archives'              => 'Alapanyag Archívum',
            'attributes'            => 'Alapanyag tulajdonságai',
            'insert_into_item'      => 'Beszúrás az alapanyagba',
            'uploaded_to_this_item' => 'Feltöltve ehhez az alapanyaghoz',
            'featured_image'        => 'Kiemelt kép',
            'set_featured_image'    => 'Kiemelt kép beállítása',
            'remove_featured_image' => 'Kiemelt kép eltávolítása',
            'use_featured_image'    => 'Használat kiemelt képként',
            'menu_name'             => 'Alapanyagok',
            'item_published'        => 'Alapanyag közzétéve.',
            'item_updated'          => 'Alapanyag frissítve.',
            'item_reverted_to_draft'=> 'Alapanyag visszaállítva vázlattá.',
            'item_scheduled'        => 'Alapanyag ütemezve.',
            'item_link'             => 'Alapanyag link',
            'item_link_description' => 'Link egy alapanyagra.',
        ],
        'public'        => true,
        'supports'      => ['title', 'thumbnail', 'excerpt'],
        'menu_icon'     => 'dashicons-food',
        'menu_position' => 21,
        'has_archive'   => true,
        'rewrite'       => ['slug' => 'alapanyag'],
        'show_in_rest'  => true,
    ]);
}
add_action('init', 'alapanyag_cpt_init');
