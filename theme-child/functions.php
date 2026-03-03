<?php
/**
 * Recommended way to include parent theme styles.
 * (Please see http://codex.wordpress.org/Child_Themes#How_to_Create_a_Child_Theme)
 *
 */  

add_action( 'wp_enqueue_scripts', 'kadence_child_style' );
				function kadence_child_style() {
					wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
					wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array('parent-style') );
				}

/**
 * Your code goes below.
 */

// Levágja a WordPress által automatikusan hozzáadott "Archívum:" prefixet CPT archív oldalakon.
add_filter('get_the_archive_title', function($title) {
    if (is_post_type_archive('alapanyag')) return 'Alapanyag Archívum';
    if (is_post_type_archive('recept'))    return 'Recept Archívum';
    if (is_post_type_archive('tapanyag'))  return 'Tápanyag Archívum';
    return $title;
});
