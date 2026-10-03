<?php
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('astra-parent-style', get_template_directory_uri() . '/style.css');
});

add_action('init', function() {
    register_post_type('greige_product', [
        'labels'       => ['name' => '商品', 'singular_name' => '商品'],
        'public'       => true,
        'has_archive'  => false,
        'menu_icon'    => 'dashicons-tag',
        'menu_position'=> 5,
        'supports'     => ['title', 'editor', 'author', 'thumbnail', 'excerpt', 'revisions', 'custom-fields'],
        'rewrite'      => ['slug' => 'product', 'with_front' => false],
        'show_in_rest' => true,
        'taxonomies'   => ['post_tag'],
    ]);
    register_post_type('greige_edit', [
        'labels'       => ['name' => 'EDIT', 'singular_name' => 'EDIT'],
        'public'       => true,
        'has_archive'  => false,
        'menu_icon'    => 'dashicons-edit',
        'menu_position'=> 6,
        'supports'     => ['title', 'editor', 'author', 'thumbnail', 'excerpt', 'revisions', 'custom-fields'],
        'rewrite'      => ['slug' => 'edit', 'with_front' => false],
        'show_in_rest' => true,
        'taxonomies'   => ['post_tag'],
    ]);
    register_taxonomy('product_category', 'greige_product', [
        'hierarchical' => true, 'public' => true, 'show_ui' => true,
        'show_admin_column' => true, 'show_in_rest' => true,
        'rewrite' => ['slug' => 'product-category'],
    ]);
    register_taxonomy('edit_category', 'greige_edit', [
        'hierarchical' => true, 'public' => true, 'show_ui' => true,
        'show_admin_column' => true, 'show_in_rest' => true,
        'rewrite' => ['slug' => 'edit-category'],
    ]);
});

function greige_format_price($amount, $currency = 'JPY') {
    if ($amount === '' || $amount === null || $amount === false) return '';
    if ($currency === 'JPY') return '¥' . number_format((int) $amount);
    return number_format((float) $amount, 2) . ' ' . esc_html($currency);
}

add_action('wp', function() {
    if (!is_front_page()) show_admin_bar(false);
});
