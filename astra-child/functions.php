<?php
// ── Parent theme style ────────────────────────────────────────────────────────
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('astra-parent-style', get_template_directory_uri() . '/style.css');
});

// ── Ticker bar CSS ────────────────────────────────────────────────────────────
add_action('wp_head', function() { ?>
<style>
.g-ticker-bar {
    position: relative; width: 100%;
    height: 30px; background: #1a1a1a; z-index: 10000;
    overflow: hidden; display: flex; align-items: center;
}
.g-ticker-inner { position: relative; width: 100%; height: 100%; }
.g-ticker-item {
    position: absolute; top: 50%; left: 0; right: 0;
    text-align: center; white-space: nowrap;
    transform: translateY(calc(-50% + 36px));
    opacity: 0;
    transition: transform 0.6s cubic-bezier(0.4,0,0.2,1), opacity 0.4s;
    font-family: Helvetica, 'Helvetica Neue', Arial, 'Noto Sans JP', sans-serif;
    font-size: 10px; letter-spacing: 0.22em; color: #fff;
    font-weight: 700; text-decoration: none;
}
a.g-ticker-item:hover { color: #ccc; }
.g-ticker-item.is-active { transform: translateY(-50%); opacity: 1; }
.g-ticker-item.is-exit  { transform: translateY(calc(-50% - 36px)); opacity: 0; }
@media (max-width: 767px) {
    .g-ticker-item { font-size: 8px; letter-spacing: 0.06em; }
}
.g-header, .sg-header { top: 30px; }
.admin-bar .g-header, .admin-bar .sg-header { top: calc(32px + 30px); }
@media screen and (max-width: 782px) {
    .admin-bar .g-header, .admin-bar .sg-header { top: calc(46px + 30px); }
}
.g-nav-social a { color: #999; }
.g-nav-social a:hover { color: #1a1a1a; }
.g-nav-social svg { width: 18px; height: 18px; fill: currentColor; }
</style>
<?php }, 5);

// ── Ticker bar HTML + JS ──────────────────────────────────────────────────────
add_action('wp_body_open', function() {
    if (!is_front_page()) return;
    $items = [];
    $items[] = ['text' => 'GREIGE MAGAZINE｜ファッション、ライフスタイル、ウェルネスの情報サイト', 'link' => ''];
    $news_posts = get_posts([
        'posts_per_page' => 6, 'post_status' => 'publish',
        'orderby' => 'date', 'order' => 'DESC',
        'tax_query' => [['taxonomy' => 'category', 'field' => 'slug', 'terms' => ['news', 'ニュース']]],
    ]);
    foreach ($news_posts as $np) {
        $items[] = ['text' => 'NEWS — ' . get_the_title($np), 'link' => get_permalink($np)];
    }
    echo '<div class="g-ticker-bar" aria-hidden="true"><div class="g-ticker-inner">';
    foreach ($items as $item) {
        $text = esc_html($item['text']);
        if ($item['link']) {
            echo '<a class="g-ticker-item" href="' . esc_url($item['link']) . '">' . $text . '</a>';
        } else {
            echo '<span class="g-ticker-item">' . $text . '</span>';
        }
    }
    echo '</div></div>';
    echo '<script>(function(){
    var els = document.querySelectorAll(".g-ticker-item");
    if (!els.length) return;
    var cur = 0;
    els[0].classList.add("is-active");
    function tick() {
        var prev = cur;
        cur = (cur + 1) % els.length;
        els[prev].classList.remove("is-active");
        els[prev].classList.add("is-exit");
        setTimeout(function(){ els[prev].classList.remove("is-exit"); }, 650);
        els[cur].classList.add("is-active");
    }
    setInterval(tick, 5000);
})();</script>';
});

// ── Scroll fade-in ────────────────────────────────────────────────────────────
add_action('wp_footer', function() { ?>
<script>
(function(){
    var TARGETS = [
        '.sg-content > *','.arc-card','.arc-title-block','.arc-filter-bar',
        '.arc-count','.arc-tags','.gp-page-hero','.sg-gallery',
        '.sg-title','.sg-meta','.sg-meta-line','.sg-body','.sg-widget',
        '.gv2-section-head','.gv2-card','.gvn-main','.gvn-sub-item'
    ].join(',');
    if (!window.IntersectionObserver) return;
    var obs = new IntersectionObserver(function(entries){
        var rows = {};
        entries.forEach(function(e){
            if (!e.isIntersecting) return;
            var top = Math.round(e.boundingClientRect.top / 20) * 20;
            if (!rows[top]) rows[top] = [];
            rows[top].push(e.target);
        });
        Object.keys(rows).forEach(function(top){
            rows[top].forEach(function(el, i){
                el.style.transitionDelay = (i * 80) + 'ms';
                el.classList.add('is-visible');
                obs.unobserve(el);
            });
        });
    }, { threshold: 0.08 });
    document.querySelectorAll(TARGETS).forEach(function(el){
        el.classList.add('sg-fade');
        obs.observe(el);
    });
})();
</script>
<?php });

// ── Theme setup ───────────────────────────────────────────────────────────────
add_action('after_setup_theme', function() {
    register_nav_menus(['greige-main' => 'GREIGE メインナビ']);
    add_image_size('product-card', 300, 400, false);
    add_image_size('product-thumb', 120, 160, false);
});

// ── Product Board (editor-only admin tool) ────────────────────────────────────
add_action('admin_menu', function() {
    $hook = add_menu_page('Curation', 'Curation', 'edit_posts', 'greige-product-board',
        '__return_false', 'dashicons-grid-view', 30);
    add_action('load-' . $hook, function() {
        if (!current_user_can('edit_posts')) {
            wp_die('アクセス権限がありません', 'Access Denied', ['response' => 403]);
        }
        show_admin_bar(false);
        $dir = get_stylesheet_directory() . '/tools/product-board/';
        if (!file_exists($dir . 'search_edit.php')) {
            wp_die('Product Board: ファイルが見つかりません。<br>Path: ' . esc_html($dir));
        }
        require($dir . 'search_edit.php');
        exit;
    });
});

// ── Custom Post Types ─────────────────────────────────────────────────────────
add_action('init', function() {
    register_post_type('greige_product', [
        'labels' => [
            'name' => '商品', 'singular_name' => '商品',
            'add_new_item' => '商品を追加', 'edit_item' => '商品を編集',
            'new_item' => '新規商品', 'view_item' => '商品を表示',
            'search_items' => '商品を検索',
            'not_found' => '商品が見つかりません',
            'not_found_in_trash' => 'ゴミ箱に商品はありません',
        ],
        'public' => true, 'has_archive' => false,
        'menu_icon' => 'dashicons-tag', 'menu_position' => 5,
        'supports' => ['title', 'editor', 'author', 'thumbnail', 'excerpt', 'revisions', 'custom-fields', 'comments'],
        'rewrite' => ['slug' => 'product', 'with_front' => false],
        'show_in_rest' => true, 'taxonomies' => ['post_tag'],
    ]);
    register_post_type('greige_edit', [
        'labels' => [
            'name' => 'EDIT', 'singular_name' => 'EDIT',
            'add_new_item' => 'EDITを追加', 'edit_item' => 'EDITを編集',
            'new_item' => '新規EDIT', 'view_item' => 'EDITを表示',
            'search_items' => 'EDITを検索',
            'not_found' => 'EDITが見つかりません',
            'not_found_in_trash' => 'ゴミ箱にEDITはありません',
        ],
        'public' => true, 'has_archive' => false,
        'menu_icon' => 'dashicons-edit', 'menu_position' => 6,
        'supports' => ['title', 'editor', 'author', 'thumbnail', 'excerpt', 'revisions', 'custom-fields', 'comments'],
        'rewrite' => ['slug' => 'edit', 'with_front' => false],
        'show_in_rest' => true, 'taxonomies' => ['post_tag'],
    ]);
    register_taxonomy('product_category', 'greige_product', [
        'labels' => ['name' => '商品カテゴリー', 'singular_name' => 'カテゴリー',
            'add_new_item' => 'カテゴリーを追加', 'new_item_name' => '新規カテゴリー名'],
        'hierarchical' => true, 'public' => true, 'show_ui' => true,
        'show_admin_column' => true, 'show_in_rest' => true,
        'rewrite' => ['slug' => 'product-category'],
    ]);
    register_taxonomy('edit_category', 'greige_edit', [
        'labels' => ['name' => 'EDITカテゴリー', 'singular_name' => 'カテゴリー',
            'add_new_item' => 'カテゴリーを追加', 'new_item_name' => '新規カテゴリー名'],
        'hierarchical' => true, 'public' => true, 'show_ui' => true,
        'show_admin_column' => true, 'show_in_rest' => true,
        'rewrite' => ['slug' => 'edit-category'],
    ]);
});

// ── REST API meta registration ────────────────────────────────────────────────
add_action('init', function() {
    $args = ['object_subtype' => 'greige_product', 'type' => 'string', 'single' => true, 'show_in_rest' => true];
    foreach (['product_brand', 'product_price', 'product_external_image', 'product_cta_url',
              'affiliate_amazon', 'affiliate_rakuten', 'affiliate_yahoo', 'affiliate_official'] as $key) {
        register_post_meta('greige_product', $key, $args);
    }
});

// ── Price formatter ───────────────────────────────────────────────────────────
if (!function_exists('greige_format_price')) {
    function greige_format_price($amount, $currency = 'JPY') {
        if ($amount === '' || $amount === null || $amount === false) return '';
        if ($currency === 'JPY') return '¥' . number_format((int) $amount);
        if ($currency === 'USD') return '$' . number_format((float) $amount, 2);
        if ($currency === 'EUR') return '€' . number_format((float) $amount, 2);
        return number_format((int) $amount) . ' ' . esc_html($currency);
    }
}

// ── Affiliate CSS ─────────────────────────────────────────────────────────────
add_action('wp_enqueue_scripts', function() {
    $path = get_stylesheet_directory() . '/css/greige-affiliate.css';
    if (file_exists($path)) {
        wp_enqueue_style('greige-affiliate', get_stylesheet_directory_uri() . '/css/greige-affiliate.css', [], filemtime($path));
    }
});

// ── Shortcodes ────────────────────────────────────────────────────────────────
add_shortcode('greige_affiliate', function($atts) {
    $atts = shortcode_atts([
        'product_id' => 0, 'name' => '', 'brand' => '', 'image' => '',
        'price' => '', 'amazon' => '', 'rakuten' => '', 'yahoo' => '', 'official' => '',
        'note' => '', 'label_amazon' => '', 'label_rakuten' => '', 'label_yahoo' => '', 'label_official' => '',
    ], $atts, 'greige_affiliate');
    $card = [
        'product_id' => (int) $atts['product_id'],
        'name' => $atts['name'], 'brand' => $atts['brand'], 'image' => $atts['image'],
        'price' => $atts['price'], 'amazon' => $atts['amazon'], 'rakuten' => $atts['rakuten'],
        'yahoo' => $atts['yahoo'], 'official' => $atts['official'], 'note' => $atts['note'],
        'label_amazon' => $atts['label_amazon'], 'label_rakuten' => $atts['label_rakuten'],
        'label_yahoo' => $atts['label_yahoo'], 'label_official' => $atts['label_official'],
    ];
    ob_start();
    get_template_part('template-parts/affiliate-card', null, ['card' => $card]);
    return ob_get_clean();
});

// ── Image upload handler (Product Board) ──────────────────────────────────────
add_action('wp_ajax_greige_product_image_upload', function() {
    check_ajax_referer('greige_product_image', 'nonce');
    if (!current_user_can('upload_files')) {
        wp_send_json_error(['message' => 'アップロード権限がありません'], 403);
    }
    if (empty($_FILES['product_image'])) {
        wp_send_json_error(['message' => '画像が選択されていません'], 400);
    }
    $check = wp_check_filetype_and_ext($_FILES['product_image']['tmp_name'], $_FILES['product_image']['name']);
    if (empty($check['type']) || strpos($check['type'], 'image/') !== 0) {
        wp_send_json_error(['message' => '画像ファイルを選択してください'], 400);
    }
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    $attach_id = media_handle_upload('product_image', 0);
    if (is_wp_error($attach_id)) {
        wp_send_json_error(['message' => $attach_id->get_error_message()], 400);
    }
    update_post_meta($attach_id, '_greige_product_candidate', 1);
    update_post_meta($attach_id, '_greige_candidate_status', 'pending');
    wp_send_json_success(['id' => $attach_id, 'thumb' => wp_get_attachment_image_url($attach_id, 'medium')]);
});


// ── Admin bar: hide on frontend ───────────────────────────────────────────────
add_action('wp', function() {
    if (!is_front_page()) show_admin_bar(false);
});

// ── Title filter ──────────────────────────────────────────────────────────────
add_filter('document_title_parts', function($parts) {
    $parts['tagline'] = get_bloginfo('description');
    return $parts;
}, 9999);
add_filter('document_title_separator', function() { return '|'; }, 9999);

require get_stylesheet_directory() . '/inc/affiliate-inject.php';
require get_stylesheet_directory() . '/inc/acf-fields.php';
