<?php

/**
 * 03 – Kiemelt kép támogatás
 */
// Kiemelt kép támogatás a CPT-khez
function custom_theme_supports() {
    add_theme_support('post-thumbnails', ['post', 'page', 'recept', 'alapanyag']);
}
add_action('after_setup_theme', 'custom_theme_supports');
