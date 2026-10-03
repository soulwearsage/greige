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
.g-ticker-item.is-active {
    transform: translateY(-50%); opacity: 1;
}
.g-ticker-item.is-exit {
    transform: translateY(calc(-50% - 36px)); opacity: 0;
}
@media (max-width: 767px) {
    .g-ticker-item { font-size: 8px; letter-spacing: 0.06em; }
}

/* ヘッダーをティッカー分だけ下げる */
.g-header, .sg-header { top: 30px !important; }
.admin-bar .g-header, .admin-bar .sg-header { top: calc(32px + 30px) !important; }
@media screen and (max-width: 782px) {
    .admin-bar .g-header, .admin-bar .sg-header { top: calc(46px + 30px) !important; }
}

/* 全ページのコンテンツエリアをティッカー分ずらす */

/* ナビオーバーレイ SNS アイコン色（全ページ共通） */
.g-nav-social a { color: #999 !important; }
.g-nav-social a:hover { color: #1a1a1a !important; }
.g-nav-social svg { width: 18px; height: 18px; fill: currentColor; }
</style>
<?php }, 5);

// ── Ticker bar HTML + JS ──────────────────────────────────────────────────────
add_action('wp_body_open', function() {
    if (!is_front_page()) return;
    $items = [];
    $items[] = [
        'text' => 'GREIGE MAGAZINE｜ファッション、ライフスタイル、ウェルネスの情報サイト',
        'link' => '',
    ];

    // slug "news" で検索（カテゴリー名 NEWS に対応）
    $news_posts = get_posts([
        'posts_per_page' => 6,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
        'tax_query'      => [[
            'taxonomy' => 'category',
            'field'    => 'slug',
            'terms'    => ['news', 'ニュース'],
        ]],
    ]);
    foreach ($news_posts as $np) {
        $items[] = [
            'text' => 'NEWS — ' . get_the_title($np),
            'link' => get_permalink($np),
        ];
    }

    echo '<div class="g-ticker-bar" aria-hidden="true">';
    echo '<div class="g-ticker-inner">';
    foreach ($items as $item) {
        $text = esc_html($item['text']);
        if ($item['link']) {
            echo '<a class="g-ticker-item" href="' . esc_url($item['link']) . '">' . $text . '</a>';
        } else {
            echo '<span class="g-ticker-item">' . $text . '</span>';
        }
    }
    echo '</div></div>';
    echo '<script>
(function(){
    var els = document.querySelectorAll(".g-ticker-item");
    if (!els.length) return;
    var cur = 0;
    els[0].classList.add("is-active");
    function rotate() {
        var prev = cur;
        cur = (cur + 1) % els.length;
        els[prev].classList.remove("is-active");
        els[prev].classList.add("is-exit");
        setTimeout(function(){ els[prev].classList.remove("is-exit"); }, 650);
        if (cur !== prev) {
            els[cur].classList.add("is-active");
        } else {
            setTimeout(function(){ els[cur].classList.add("is-active"); }, 650);
        }
    }
    setInterval(rotate, 5000);
})();
</script>';
});


// ── フロントページ専用：ティッカー再表示 ────────────────────────────────────────
add_action('wp_footer', function() {
    if (!is_front_page()) return;
    ?>
<style>
.g-ticker-bar { display: flex !important; }
</style>
<?php }, 99);

// ── スクロールフェードイン ──────────────────────────────────────────────────────
add_action('wp_footer', function() { ?>
<script>
(function(){
    var TARGETS = [
        '.sg-content > *',
        '.arc-card',
        '.arc-title-block',
        '.arc-filter-bar',
        '.arc-count',
        '.arc-tags',
        '.gp-page-hero',
        '.sg-gallery',
        '.sg-title',
        '.sg-meta',
        '.sg-meta-line',
        '.sg-body',
        '.sg-widget',
        '.gv2-section-head',
        '.gv2-card',
        '.gvn-main',
        '.gvn-sub-item'
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
    $hook = add_menu_page(
        'Curation',
        'Curation',
        'edit_posts',
        'greige-product-board',
        '__return_false',
        'dashicons-grid-view',
        30
    );
    add_action('load-' . $hook, function() {
        if (!current_user_can('edit_posts')) {
            wp_die('アクセス権限がありません', 'Access Denied', ['response' => 403]);
        }
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

    // 商品 CPT
    register_post_type('greige_product', [
        'labels' => [
            'name'               => '商品',
            'singular_name'      => '商品',
            'add_new_item'       => '商品を追加',
            'edit_item'          => '商品を編集',
            'new_item'           => '新規商品',
            'view_item'          => '商品を表示',
            'search_items'       => '商品を検索',
            'not_found'          => '商品が見つかりません',
            'not_found_in_trash' => 'ゴミ箱に商品はありません',
        ],
        'public'        => true,
        'has_archive'   => false,
        'menu_icon'     => 'dashicons-tag',
        'menu_position' => 5,
        'supports'      => ['title', 'editor', 'author', 'thumbnail', 'excerpt', 'revisions', 'custom-fields', 'comments'],
        'rewrite'       => ['slug' => 'product', 'with_front' => false],
        'show_in_rest'  => true,
        'taxonomies'    => ['post_tag'],
    ]);

    // EDIT CPT
    register_post_type('greige_edit', [
        'labels' => [
            'name'               => 'EDIT',
            'singular_name'      => 'EDIT',
            'add_new_item'       => 'EDITを追加',
            'edit_item'          => 'EDITを編集',
            'new_item'           => '新規EDIT',
            'view_item'          => 'EDITを表示',
            'search_items'       => 'EDITを検索',
            'not_found'          => 'EDITが見つかりません',
            'not_found_in_trash' => 'ゴミ箱にEDITはありません',
        ],
        'public'        => true,
        'has_archive'   => false,
        'menu_icon'     => 'dashicons-edit',
        'menu_position' => 6,
        'supports'      => ['title', 'editor', 'author', 'thumbnail', 'excerpt', 'revisions', 'custom-fields', 'comments'],
        'rewrite'       => ['slug' => 'edit', 'with_front' => false],
        'show_in_rest'  => true,
        'taxonomies'    => ['post_tag'],
    ]);

    // 商品カテゴリー
    register_taxonomy('product_category', 'greige_product', [
        'labels'             => [
            'name'              => '商品カテゴリー',
            'singular_name'     => 'カテゴリー',
            'add_new_item'      => 'カテゴリーを追加',
            'new_item_name'     => '新規カテゴリー名',
            'parent_item'       => '親カテゴリー',
            'parent_item_colon' => '親カテゴリー:',
        ],
        'hierarchical'       => true,
        'public'             => true,
        'show_ui'            => true,
        'show_admin_column'  => true,
        'show_in_rest'       => true,
        'rewrite'            => ['slug' => 'product-category'],
    ]);

    // EDIT カテゴリー
    register_taxonomy('edit_category', 'greige_edit', [
        'labels'             => [
            'name'              => 'EDITカテゴリー',
            'singular_name'     => 'カテゴリー',
            'add_new_item'      => 'カテゴリーを追加',
            'new_item_name'     => '新規カテゴリー名',
            'parent_item'       => '親カテゴリー',
            'parent_item_colon' => '親カテゴリー:',
        ],
        'hierarchical'       => true,
        'public'             => true,
        'show_ui'            => true,
        'show_admin_column'  => true,
        'show_in_rest'       => true,
        'rewrite'            => ['slug' => 'edit-category'],
    ]);
});

// ── REST API meta registration (GAS integration) ──────────────────────────────
add_action('init', function() {
    $string_args = ['object_subtype' => 'greige_product', 'type' => 'string', 'single' => true, 'show_in_rest' => true];
    foreach (['product_brand', 'product_price', 'product_external_image', 'product_cta_url',
              'affiliate_amazon', 'affiliate_rakuten', 'affiliate_yahoo', 'affiliate_official'] as $key) {
        register_post_meta('greige_product', $key, $string_args);
    }
});

// ── Price formatter ───────────────────────────────────────────────────────────
function greige_format_price($amount, $currency = 'JPY') {
    if ($amount === '' || $amount === null || $amount === false) return '';
    if ($currency === 'JPY') return '¥' . number_format((int) $amount);
    if ($currency === 'USD') return '$' . number_format((float) $amount, 2);
    if ($currency === 'EUR') return '€' . number_format((float) $amount, 2);
    return number_format((int) $amount) . ' ' . esc_html($currency);
}

// ── ACF Local Field Groups ────────────────────────────────────────────────────
add_action('acf/init', function() {
    if (!function_exists('acf_add_local_field_group')) return;

    // ── 商品 CPT フィールド ───────────────────────────────────────────────────
    acf_add_local_field_group([
        'key'             => 'group_greige_product',
        'title'           => '商品情報',
        'label_placement' => 'top',
        'position'        => 'normal',
        'style'           => 'default',
        'fields'          => [
            [
                'key'          => 'field_gp_gas_id',
                'label'        => 'GAS商品ID',
                'name'         => 'gas_product_id',
                'type'         => 'text',
                'instructions' => 'GAS PRODUCT_POOL の product_id（例: P0001）。GAS連携のマッピングキー。',
                'required'     => 0,
                'wrapper'      => ['width' => '50'],
            ],
            [
                'key'      => 'field_gp_brand',
                'label'    => 'ブランド名',
                'name'     => 'product_brand',
                'type'     => 'text',
                'required' => 1,
                'wrapper'  => ['width' => '50'],
            ],
            [
                'key'      => 'field_gp_price',
                'label'    => '価格（通常）',
                'name'     => 'product_price',
                'type'     => 'number',
                'min'      => 0,
                'required' => 0,
                'wrapper'  => ['width' => '33'],
            ],
            [
                'key'      => 'field_gp_sale_price',
                'label'    => 'セール価格',
                'name'     => 'product_sale_price',
                'type'     => 'number',
                'min'      => 0,
                'required' => 0,
                'wrapper'  => ['width' => '33'],
            ],
            [
                'key'           => 'field_gp_currency',
                'label'         => '通貨',
                'name'          => 'product_currency',
                'type'          => 'select',
                'choices'       => ['JPY' => '円（JPY）', 'USD' => 'USD', 'EUR' => 'EUR'],
                'default_value' => 'JPY',
                'allow_null'    => 0,
                'required'      => 0,
                'wrapper'       => ['width' => '34'],
            ],
            [
                'key'      => 'field_gp_product_url',
                'label'    => '商品URL（公式）',
                'name'     => 'product_url',
                'type'     => 'url',
                'required' => 0,
                'wrapper'  => ['width' => '50'],
            ],
            [
                'key'           => 'field_gp_aff_available',
                'label'         => 'アフィリエイト可否',
                'name'          => 'affiliate_available',
                'type'          => 'select',
                'choices'       => ['UNKNOWN' => '未確認', 'YES' => '利用可', 'NO' => '利用不可'],
                'default_value' => 'UNKNOWN',
                'allow_null'    => 0,
                'required'      => 1,
                'wrapper'       => ['width' => '50'],
            ],
            [
                'key'      => 'field_gp_aff_url',
                'label'    => 'アフィリエイトURL',
                'name'     => 'affiliate_url',
                'type'     => 'url',
                'required' => 0,
                'wrapper'  => ['width' => '60'],
            ],
            [
                'key'          => 'field_gp_aff_platform',
                'label'        => 'アフィリエイト媒体',
                'name'         => 'affiliate_platform',
                'type'         => 'text',
                'instructions' => '例：A8.net、もしもアフィリエイト、楽天アフィリエイト',
                'required'     => 0,
                'wrapper'      => ['width' => '40'],
            ],
            [
                'key'      => 'field_gp_material',
                'label'    => '素材',
                'name'     => 'product_material',
                'type'     => 'text',
                'required' => 0,
                'wrapper'  => ['width' => '33'],
            ],
            [
                'key'      => 'field_gp_color',
                'label'    => 'カラー',
                'name'     => 'product_color',
                'type'     => 'text',
                'required' => 0,
                'wrapper'  => ['width' => '33'],
            ],
            [
                'key'      => 'field_gp_size',
                'label'    => 'サイズ',
                'name'     => 'product_size',
                'type'     => 'text',
                'required' => 0,
                'wrapper'  => ['width' => '34'],
            ],
            [
                'key'      => 'field_gp_description',
                'label'    => '商品説明',
                'name'     => 'product_description',
                'type'     => 'textarea',
                'rows'     => 3,
                'required' => 0,
            ],
            [
                'key'          => 'field_gp_editor_comment',
                'label'        => '編集コメント',
                'name'         => 'editor_comment',
                'type'         => 'textarea',
                'rows'         => 3,
                'instructions' => 'GREIGEとして選んだ理由・編集視点のコメント。商品ページと商品カードに表示可能。',
                'required'     => 0,
            ],
            [
                'key'          => 'field_gp_ext_image',
                'label'        => '商品画像URL（外部）',
                'name'         => 'product_external_image',
                'type'         => 'url',
                'instructions' => 'アイキャッチ未設定時のフォールバック。GAS由来のimage_urlなどを貼る。',
                'required'     => 0,
            ],
        ],
        'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'greige_product']]],
    ]);

    // ── EDIT CPT フィールド ───────────────────────────────────────────────────
    acf_add_local_field_group([
        'key'             => 'group_greige_edit',
        'title'           => 'EDIT 設定',
        'label_placement' => 'top',
        'position'        => 'side',
        'style'           => 'default',
        'fields'          => [
            [
                'key'           => 'field_ge_pr_display',
                'label'         => 'PR表示',
                'name'          => 'edit_pr_display',
                'type'          => 'true_false',
                'message'       => 'プロモーション・アフィリエイト表示を出す',
                'default_value' => 0,
                'required'      => 0,
            ],
            [
                'key'          => 'field_ge_pr_text',
                'label'        => 'PR表示テキスト',
                'name'         => 'edit_pr_text',
                'type'         => 'text',
                'instructions' => '空欄時：「※本記事にはプロモーションが含まれます。」',
                'required'     => 0,
                'conditional_logic' => [[
                    ['field' => 'field_ge_pr_display', 'operator' => '==', 'value' => '1'],
                ]],
            ],
            [
                'key'           => 'field_ge_show_compare',
                'label'         => '比較テーブル',
                'name'          => 'edit_show_comparison',
                'type'          => 'true_false',
                'message'       => '使用する（本文内に [greige_compare] を挿入）',
                'default_value' => 0,
                'required'      => 0,
            ],
            [
                'key'           => 'field_ge_compare_fields',
                'label'         => '比較テーブル — 表示項目',
                'name'          => 'edit_comparison_fields',
                'type'          => 'checkbox',
                'instructions'  => '比較テーブルに表示する項目を選択',
                'choices'       => [
                    'product_price'       => '価格',
                    'product_material'    => '素材',
                    'product_color'       => 'カラー',
                    'product_size'        => 'サイズ',
                    'product_description' => '商品説明',
                    'editor_comment'      => '編集コメント',
                ],
                'layout'        => 'vertical',
                'return_format' => 'value',
                'required'      => 0,
                'conditional_logic' => [[
                    ['field' => 'field_ge_show_compare', 'operator' => '==', 'value' => '1'],
                ]],
            ],
            [
                'key'         => 'field_ge_shop_title',
                'label'       => 'SHOP THE EDIT タイトル',
                'name'        => 'edit_shop_title',
                'type'        => 'text',
                'placeholder' => 'SHOP THE EDIT',
                'instructions'=> '空欄時は「SHOP THE EDIT」',
                'required'    => 0,
            ],
        ],
        'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'greige_edit']]],
    ]);

    // ── EDIT コンテンツブロック（Flexible Content / ACF Pro 必須）──────────────
    acf_add_local_field_group([
        'key'             => 'group_greige_edit_content',
        'title'           => 'EDIT CONTENT',
        'label_placement' => 'top',
        'position'        => 'normal',
        'style'           => 'default',
        'fields'          => [
            [
                'key'          => 'field_edit_content_blocks',
                'label'        => 'コンテンツブロック',
                'name'         => 'edit_content_blocks',
                'type'         => 'flexible_content',
                'instructions' => 'ブロックを追加して記事を構成してください。ドラッグで並び替えができます。',
                'button_label' => '+ ブロックを追加',
                'min'          => 0,
                'max'          => 0,
                'layouts'      => [
                    // ── TEXT ───────────────────────────────────────────────────
                    [
                        'key'        => 'layout_block_text',
                        'name'       => 'block_text',
                        'label'      => 'TEXT — 本文',
                        'display'    => 'block',
                        'sub_fields' => [
                            [
                                'key'          => 'field_bt_content',
                                'label'        => '本文',
                                'name'         => 'text_content',
                                'type'         => 'wysiwyg',
                                'toolbar'      => 'basic',
                                'media_upload' => 0,
                            ],
                        ],
                    ],
                    // ── HEADING ────────────────────────────────────────────────
                    [
                        'key'        => 'layout_block_heading',
                        'name'       => 'block_heading',
                        'label'      => 'HEADING — 見出し',
                        'display'    => 'block',
                        'sub_fields' => [
                            [
                                'key'     => 'field_bh_text',
                                'label'   => '見出しテキスト',
                                'name'    => 'heading_text',
                                'type'    => 'text',
                                'wrapper' => ['width' => '70'],
                            ],
                            [
                                'key'           => 'field_bh_level',
                                'label'         => 'レベル',
                                'name'          => 'heading_level',
                                'type'          => 'select',
                                'choices'       => [
                                    'h2' => 'H2（大見出し）',
                                    'h3' => 'H3（中見出し）',
                                    'h4' => 'H4（小見出し）',
                                ],
                                'default_value' => 'h2',
                                'allow_null'    => 0,
                                'wrapper'       => ['width' => '30'],
                            ],
                        ],
                    ],
                    // ── IMAGE ──────────────────────────────────────────────────
                    [
                        'key'        => 'layout_block_image',
                        'name'       => 'block_image',
                        'label'      => 'IMAGE — 画像',
                        'display'    => 'block',
                        'sub_fields' => [
                            [
                                'key'           => 'field_bi_image',
                                'label'         => '画像',
                                'name'          => 'block_image_file',
                                'type'          => 'image',
                                'return_format' => 'array',
                                'wrapper'       => ['width' => '60'],
                            ],
                            [
                                'key'     => 'field_bi_caption',
                                'label'   => 'キャプション（任意）',
                                'name'    => 'image_caption',
                                'type'    => 'text',
                                'wrapper' => ['width' => '40'],
                            ],
                        ],
                    ],
                    // ── PRODUCT ────────────────────────────────────────────────
                    [
                        'key'        => 'layout_block_product',
                        'name'       => 'block_product',
                        'label'      => 'PRODUCT — 商品',
                        'display'    => 'block',
                        'sub_fields' => [
                            [
                                'key'           => 'field_bp_product',
                                'label'         => '商品を選択',
                                'name'          => 'product_ref',
                                'type'          => 'post_object',
                                'post_type'     => ['greige_product'],
                                'return_format' => 'id',
                                'allow_null'    => 0,
                            ],
                        ],
                    ],
                    // ── PRODUCT GROUP ──────────────────────────────────────────
                    [
                        'key'        => 'layout_block_product_group',
                        'name'       => 'block_product_group',
                        'label'      => 'PRODUCT GROUP — 商品グループ',
                        'display'    => 'block',
                        'sub_fields' => [
                            [
                                'key'          => 'field_bpg_products',
                                'label'        => '商品一覧（追加順が表示順になります）',
                                'name'         => 'products',
                                'type'         => 'repeater',
                                'min'          => 1,
                                'max'          => 0,
                                'layout'       => 'table',
                                'button_label' => '商品を追加',
                                'sub_fields'   => [
                                    [
                                        'key'           => 'field_bpg_product_ref',
                                        'label'         => '商品',
                                        'name'          => 'product_ref',
                                        'type'          => 'post_object',
                                        'post_type'     => ['greige_product'],
                                        'return_format' => 'id',
                                        'allow_null'    => 0,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    // ── EDITOR COMMENT ─────────────────────────────────────────
                    [
                        'key'        => 'layout_block_editor_comment',
                        'name'       => 'block_editor_comment',
                        'label'      => 'EDITOR COMMENT — 編集コメント',
                        'display'    => 'block',
                        'sub_fields' => [
                            [
                                'key'  => 'field_bec_text',
                                'label'=> 'コメント',
                                'name' => 'comment_text',
                                'type' => 'textarea',
                                'rows' => 4,
                            ],
                        ],
                    ],
                    // ── COMPARISON TABLE ───────────────────────────────────────
                    [
                        'key'        => 'layout_block_comparison',
                        'name'       => 'block_comparison',
                        'label'      => 'COMPARISON TABLE — 比較テーブル',
                        'display'    => 'block',
                        'sub_fields' => [
                            [
                                'key'          => 'field_bc_products',
                                'label'        => '比較する商品（2つ以上・追加順が表示順）',
                                'name'         => 'compare_products',
                                'type'         => 'repeater',
                                'min'          => 2,
                                'max'          => 0,
                                'layout'       => 'table',
                                'button_label' => '商品を追加',
                                'sub_fields'   => [
                                    [
                                        'key'           => 'field_bc_product_ref',
                                        'label'         => '商品',
                                        'name'          => 'product_ref',
                                        'type'          => 'post_object',
                                        'post_type'     => ['greige_product'],
                                        'return_format' => 'id',
                                        'allow_null'    => 0,
                                    ],
                                ],
                            ],
                            [
                                'key'           => 'field_bc_compare_fields',
                                'label'         => '表示項目',
                                'name'          => 'compare_fields',
                                'type'          => 'checkbox',
                                'choices'       => [
                                    'product_price'       => '価格',
                                    'product_material'    => '素材',
                                    'product_color'       => 'カラー',
                                    'product_size'        => 'サイズ',
                                    'product_description' => '商品説明',
                                    'editor_comment'      => '編集コメント',
                                ],
                                'default_value' => ['product_price'],
                                'layout'        => 'horizontal',
                                'return_format' => 'value',
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'greige_edit']]],
    ]);
});

// ── EDIT 商品選択 メタボックス ────────────────────────────────────────────────
add_action('add_meta_boxes', function() {
    add_meta_box(
        'greige_edit_products',
        'SHOP THE EDIT — 商品選択',
        'greige_edit_products_cb',
        'greige_edit',
        'normal',
        'high'
    );
});

function greige_edit_products_cb($post) {
    wp_nonce_field('greige_products_save', 'greige_products_nonce');
    $selected = get_post_meta($post->ID, 'greige_edit_products', true);
    if (!is_array($selected)) $selected = [];
    $selected = array_map('intval', $selected);

    $products = get_posts([
        'post_type'      => 'greige_product',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'post_status'    => ['publish', 'draft'],
    ]);
    ?>
    <style>
    .gep-list{display:flex;flex-direction:column;gap:2px;max-height:360px;overflow-y:auto;padding:4px 0;border:1px solid #e0e0e0;border-radius:3px;}
    .gep-list label{display:flex;align-items:center;gap:10px;padding:7px 12px;cursor:pointer;font-size:13px;line-height:1.4;}
    .gep-list label:hover{background:#f6f6f6;}
    .gep-list input[type="checkbox"]:checked+span{font-weight:600;}
    .gep-brand{color:#999;font-size:11px;font-weight:400;}
    .gep-status-draft{color:#b0b0b0;font-size:11px;margin-left:4px;}
    .gep-note{margin-top:8px;font-size:11px;color:#999;line-height:1.5;}
    </style>
    <?php if (empty($products)) : ?>
        <p style="color:#999;font-size:13px;padding:8px 0;">商品が登録されていません。「商品」メニューから商品を追加してください。</p>
    <?php else : ?>
        <div class="gep-list">
        <?php foreach ($products as $p) :
            $brand = get_post_meta($p->ID, 'product_brand', true);
            $is_draft = $p->post_status === 'draft';
        ?>
            <label>
                <input type="checkbox"
                       name="greige_edit_products[]"
                       value="<?php echo esc_attr($p->ID); ?>"
                       <?php checked(in_array($p->ID, $selected)); ?>>
                <span>
                    <?php if ($brand) : ?><span class="gep-brand"><?php echo esc_html($brand); ?> — </span><?php endif; ?>
                    <?php echo esc_html($p->post_title); ?>
                    <?php if ($is_draft) : ?><span class="gep-status-draft">(下書き)</span><?php endif; ?>
                </span>
            </label>
        <?php endforeach; ?>
        </div>
        <p class="gep-note">選択した商品が記事末尾の「SHOP THE EDIT」に表示されます。本文内では <code>[greige_product id="投稿ID"]</code> でインライン表示も可能。</p>
    <?php endif; ?>
    <?php
}

add_action('save_post_greige_edit', function($post_id) {
    if (!isset($_POST['greige_products_nonce'])) return;
    if (!wp_verify_nonce($_POST['greige_products_nonce'], 'greige_products_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $ids = [];
    if (!empty($_POST['greige_edit_products'])) {
        $ids = array_map('intval', (array) $_POST['greige_edit_products']);
        $ids = array_filter($ids);
    }
    update_post_meta($post_id, 'greige_edit_products', array_values($ids));
});

// ── Shortcodes ────────────────────────────────────────────────────────────────

// [greige_product id="123"] — インライン商品カード
add_shortcode('greige_product', function($atts) {
    $atts = shortcode_atts(['id' => 0, 'slug' => ''], $atts, 'greige_product');
    $pid  = (int) $atts['id'];
    if (!$pid && $atts['slug']) {
        $p = get_page_by_path($atts['slug'], OBJECT, 'greige_product');
        if ($p) $pid = $p->ID;
    }
    if (!$pid) return '';
    ob_start();
    get_template_part('template-parts/components/product-card', null, ['product_id' => $pid, 'inline' => true]);
    return ob_get_clean();
});

// ── アフィリエイトCSS（全フロントページ共通）─────────────────────────────────
add_action('wp_enqueue_scripts', function() {
    $path = get_stylesheet_directory() . '/css/greige-affiliate.css';
    if (file_exists($path)) {
        wp_enqueue_style(
            'greige-affiliate',
            get_stylesheet_directory_uri() . '/css/greige-affiliate.css',
            [],
            filemtime($path)
        );
    }
});

// [greige_affiliate] — アフィリエイトカード（投稿・EDIT本文内で使用）
// 使用例（手動入力）:
//   [greige_affiliate name="商品名" brand="ブランド" image="https://..." price="4400" amazon="https://..." rakuten="https://..."]
// 使用例（データベース連携）:
//   [greige_affiliate product_id="123"]
add_shortcode('greige_affiliate', function($atts) {
    $atts = shortcode_atts([
        'product_id'     => 0,
        'name'           => '',
        'brand'          => '',
        'image'          => '',
        'price'          => '',
        'amazon'         => '',
        'rakuten'        => '',
        'yahoo'          => '',
        'official'       => '',
        'note'           => '',
        'label_amazon'   => '',
        'label_rakuten'  => '',
        'label_yahoo'    => '',
        'label_official' => '',
    ], $atts, 'greige_affiliate');

    $card = [
        'product_id'     => (int) $atts['product_id'],
        'name'           => $atts['name'],
        'brand'          => $atts['brand'],
        'image'          => $atts['image'],
        'price'          => $atts['price'],
        'amazon'         => $atts['amazon'],
        'rakuten'        => $atts['rakuten'],
        'yahoo'          => $atts['yahoo'],
        'official'       => $atts['official'],
        'note'           => $atts['note'],
        'label_amazon'   => $atts['label_amazon'],
        'label_rakuten'  => $atts['label_rakuten'],
        'label_yahoo'    => $atts['label_yahoo'],
        'label_official' => $atts['label_official'],
    ];

    ob_start();
    get_template_part('template-parts/affiliate-card', null, ['card' => $card]);
    return ob_get_clean();
});

// [greige_compare] — 比較テーブル（EDIT本文内で使用）
add_shortcode('greige_compare', function() {
    global $post;
    if (!$post) return '';
    ob_start();
    get_template_part('template-parts/components/comparison-table', null, ['post_id' => $post->ID]);
    return ob_get_clean();
});


// ── Search Menu：商品画像アップロード受け口 ───────────────────────────────────
// PRODUCT収集の入口。Media Library に保存し、候補フラグを付けるのみ。
// 商品認識・PRODUCT DB登録は行わない（次工程）。
add_action('wp_ajax_greige_product_image_upload', function() {
    check_ajax_referer('greige_product_image', 'nonce');

    if (!current_user_can('upload_files')) {
        wp_send_json_error(['message' => 'アップロード権限がありません'], 403);
    }
    if (empty($_FILES['product_image'])) {
        wp_send_json_error(['message' => '画像が選択されていません'], 400);
    }

    $file = $_FILES['product_image'];
    $check = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
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

    wp_send_json_success([
        'id'    => $attach_id,
        'thumb' => wp_get_attachment_image_url($attach_id, 'medium'),
    ]);
});

// 管理バー：フロントページ以外は非表示
add_action('wp', function() {
    if (!is_front_page()) {
        show_admin_bar(false);
    }
});


// ── ブラウザタブのタイトルにキャッチフレーズを追加 ───────────────────────────
add_filter('document_title_parts', function($parts) {
    $parts['tagline'] = get_bloginfo('description');
    return $parts;
}, 9999);
add_filter('document_title_separator', function() { return '|'; }, 9999);
