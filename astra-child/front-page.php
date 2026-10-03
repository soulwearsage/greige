<?php
/**
 * Template Name: GREIGE Front Page
 * GREIGE MAGAZINE - Front Page
 */
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
if ( function_exists('opcache_invalidate') ) { opcache_invalidate( __FILE__, true ); }

// ── カバー投稿取得
$cover_posts_all = get_posts(array(
    'category_name' => 'cover',
    'numberposts'   => -1,
    'post_status'   => 'publish',
    'orderby'       => 'date',
    'order'         => 'ASC',
));
$cover_volume = count($cover_posts_all);
$vol_display  = str_pad($cover_volume, 2, '0', STR_PAD_LEFT);

$cover_image  = '';
$cover_title  = '';
$cover_season = 'autumn';
$cover_year   = date('Y');
$cover_month  = date('m');
$cover_link   = home_url('/');

$cover_images = [];
if (!empty($cover_posts_all)) {
    $latest      = end($cover_posts_all);
    $cover_image = get_the_post_thumbnail_url($latest->ID, 'full');
    $cover_title = $latest->post_title;
    $cover_link  = get_permalink($latest->ID);
    $m           = (int) get_the_date('n', $latest->ID);
    $cover_year  = get_the_date('Y', $latest->ID);
    $cover_month = get_the_date('m', $latest->ID);
    if ($m >= 3  && $m <= 5)      $cover_season = 'spring';
    elseif ($m >= 6  && $m <= 8)  $cover_season = 'summer';
    elseif ($m >= 9  && $m <= 11) $cover_season = 'autumn';
    else                           $cover_season = 'winter';

    // フェード用画像配列（アイキャッチ＋追加画像）
    if ($cover_image) $cover_images[] = $cover_image;
    foreach (['cover_image_2', 'cover_image_3', 'cover_image_4'] as $key) {
        $extra = get_field($key, $latest->ID);
        if (!empty($extra) && is_string($extra) && filter_var($extra, FILTER_VALIDATE_URL)) {
            $cover_images[] = $extra;
        }
    }
}

// ロゴカラー配列
$logo_colors = [];
if (!empty($cover_images) && !empty($latest)) {
    $logo_mode    = get_field('cover_logo_mode', $latest->ID);
    $is_per_slide = ($logo_mode === 'per_slide' || $logo_mode === '写真ごとに個別設定');
    $is_white     = ($logo_mode === 'white_half' || $logo_mode === '全スライド白ロゴ（下半分）');
    if ($is_per_slide) {
        $slide_logo_keys = ['cover_slide_1_logo', 'cover_slide_2_logo', 'cover_slide_3_logo', 'cover_slide_4_logo'];
        foreach ($slide_logo_keys as $key) {
            $val = get_field($key, $latest->ID);
            $logo_colors[] = ($val === 'white_half' || $val === '白（下半分）') ? 'white_half' : 'black';
        }
        $logo_colors = array_slice($logo_colors, 0, count($cover_images));
    } elseif ($is_white) {
        $logo_colors = array_fill(0, count($cover_images), 'white_half');
    } else {
        $logo_colors = array_fill(0, count($cover_images), 'black');
    }
}
$initial_logo_color = !empty($logo_colors) ? $logo_colors[0] : 'black';

// ── 商品カテゴリー（Fashion のみ）
function greige_gothic_get_products($term_id, $num = 4) {
    return new WP_Query([
        'post_type'      => 'greige_product',
        'posts_per_page' => $num,
        'post_status'    => 'publish',
        'tax_query'      => [[
            'taxonomy' => 'product_category',
            'field'    => 'term_id',
            'terms'    => $term_id,
        ]],
    ]);
}
$product_cats = get_terms([
    'taxonomy'   => 'product_category',
    'hide_empty' => false,
    'parent'     => 0,
    'orderby'    => 'id',
    'order'      => 'ASC',
]);
if (is_wp_error($product_cats)) $product_cats = [];
$fashion_cat = !empty($product_cats) ? $product_cats[0] : null;

// ── ロゴ取得
$logo_id  = get_theme_mod('custom_logo');
$logo_url = '';
if ($logo_id) {
    $logo_src = wp_get_attachment_image_src($logo_id, 'full');
    if ($logo_src) $logo_url = $logo_src[0];
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500;600&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
<?php wp_head(); ?>
<link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/css/greige-base.css?v=<?php echo filemtime(get_stylesheet_directory() . '/css/greige-base.css'); ?>">
<link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/css/greige-footer.css?v=<?php echo filemtime(get_stylesheet_directory() . '/css/greige-footer.css'); ?>">
<link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/css/greige-components.css?v=<?php echo filemtime(get_stylesheet_directory() . '/css/greige-components.css'); ?>">
<link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/css/greige-nav.css?v=<?php echo filemtime(get_stylesheet_directory() . '/css/greige-nav.css'); ?>">
<link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/css/greige-front.css?v=<?php echo filemtime(get_stylesheet_directory() . '/css/greige-front.css'); ?>">
</head>
<body <?php body_class('greige-front-gothic'); ?>>
<?php wp_body_open(); ?>

<?php get_template_part('template-parts/nav-overlay'); ?>

<!-- ══ 検索パネル ════════════════════════════════════════ -->
<div class="g-srch-bg" id="gSrchBg"></div>
<div class="g-srch-panel" id="gSrchPanel">
    <button class="g-srch-close" id="gSrchClose">&#x2715;</button>
    <form class="g-srch-form" role="search" action="<?php echo esc_url(home_url('/')); ?>" method="get">
        <input type="search" name="s" class="g-srch-input" placeholder="SEARCH..." autocomplete="off">
        <button type="submit" class="g-srch-submit">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="16.5" y1="16.5" x2="22" y2="22"/></svg>
            SEARCH
        </button>
    </form>
    <p class="g-srch-sites-label">NEWS SOURCES</p>
    <div class="g-srch-sites">
        <div class="g-srch-site">
            <a href="https://prtimes.jp" target="_blank" rel="noopener noreferrer">
                <span class="g-srch-site-name">PR TIMES</span>
                <span class="g-srch-site-url">prtimes.jp</span>
            </a>
        </div>
        <div class="g-srch-site">
            <a href="https://atpress.ne.jp" target="_blank" rel="noopener noreferrer">
                <span class="g-srch-site-name">@Press</span>
                <span class="g-srch-site-url">atpress.ne.jp</span>
            </a>
        </div>
        <div class="g-srch-site">
            <a href="https://fashion-press.net" target="_blank" rel="noopener noreferrer">
                <span class="g-srch-site-name">Fashion Press</span>
                <span class="g-srch-site-url">fashion-press.net</span>
            </a>
        </div>
        <div class="g-srch-site">
            <a href="https://fashionsnap.com" target="_blank" rel="noopener noreferrer">
                <span class="g-srch-site-name">FASHIONSNAP</span>
                <span class="g-srch-site-url">fashionsnap.com</span>
            </a>
        </div>
        <div class="g-srch-site">
            <a href="https://hypebeast.com/jp" target="_blank" rel="noopener noreferrer">
                <span class="g-srch-site-name">HYPEBEAST Japan</span>
                <span class="g-srch-site-url">hypebeast.com/jp</span>
            </a>
        </div>
    </div>

    <?php if (is_user_logged_in()): ?>
    <div class="g-srch-upload" id="gSrchUpload">
        <p class="g-srch-upload-label">商品画像を追加</p>
        <input type="file" id="gSrchUploadInput" accept="image/*" hidden>
        <div style="position:relative;">
            <button type="button" class="g-srch-upload-zone" id="gSrchUploadZone" aria-label="写真を選択">
                <span class="g-srch-upload-plus">＋</span>
                <img src="" alt="" class="g-srch-upload-img" id="gSrchUploadImg">
            </button>
            <button type="button" id="gSrchUploadClear" style="display:none;position:absolute;top:6px;right:6px;width:22px;height:22px;border-radius:50%;background:#1a1a1a;color:#fff;border:none;font-size:13px;line-height:1;cursor:pointer;z-index:10;padding:0;" aria-label="画像を削除">×</button>
        </div>
        <button type="button" class="g-srch-upload-send" id="gSrchUploadSend" disabled>送信</button>
        <p class="g-srch-upload-msg" id="gSrchUploadMsg"></p>
        <?php wp_nonce_field('greige_product_image', 'greige_product_image_nonce', false); ?>
    </div>
    <?php endif; ?>
</div>

<!-- ══ MOBILE FIXED HEADER ════════════════════════════════════════ -->
<header class="gfx-header<?php echo $initial_logo_color === 'white_half' ? ' logo-white-half' : ''; ?>" id="gfxHeader">
    <a href="<?php echo home_url('/'); ?>" class="gfx-logo">
        <?php if ($logo_url): ?>
            <span class="gfx-logo-wrap">
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php bloginfo('name'); ?>" class="gfx-logo-base">
                <span class="gfx-logo-bot" aria-hidden="true">
                    <img src="https://greige.online/wp-content/uploads/2026/09/logo_w.png" alt="" class="gfx-logo-bot-img">
                </span>
            </span>
        <?php else: ?>
            <span style="font:700 36px/1 Helvetica,Arial,sans-serif;color:var(--dark);"><?php bloginfo('name'); ?></span>
        <?php endif; ?>
    </a>
    <button class="gfx-srch-btn js-srch-open" aria-label="検索">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="16.5" y1="16.5" x2="22" y2="22"/></svg>
    </button>
    <button class="gfx-menu-btn js-menu-btn" aria-label="メニューを開く">
        <span></span><span></span>
    </button>
</header>

<!-- ══ MOBILE HERO ════════════════════════════════════════ -->
<section class="gm-hero">
    <div class="gm-photo">
        <?php if (!empty($cover_images)): ?>
            <?php foreach ($cover_images as $i => $img): ?>
            <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($cover_title); ?>"
                 class="cover-slide<?php echo $i === 0 ? ' is-active' : ''; ?>"
                 data-logo-color="<?php echo esc_attr($logo_colors[$i] ?? 'black'); ?>"
                 loading="<?php echo $i === 0 ? 'eager' : 'lazy'; ?>">
            <?php endforeach; ?>
        <?php else: ?>
            <div class="gm-photo-placeholder"></div>
        <?php endif; ?>
    </div>
    <div class="gm-sidebar">
        <div class="gm-vol">
            <span class="gm-vol-label">VOL.</span>
            <span class="gm-vol-num"><?php echo esc_html($vol_display); ?></span>
        </div>
        <div class="gm-divider-h"></div>
        <div class="gm-date">
            <?php echo esc_html(strtoupper($cover_season)); ?><br>
            <?php echo esc_html($cover_year . '.' . $cover_month); ?>
        </div>
        <div class="gm-title-wrap">
            <div class="gm-divider-v"></div>
            <p class="gm-title-vert"><?php echo esc_html($cover_title); ?></p>
        </div>
        <div class="gm-cats">
            <p>FASHION</p><p>LIFE</p><p>WELLBEING</p><p>and more...</p>
        </div>
    </div>
</section>

<!-- ══ PC STICKY HEADER（ヒーロー外・sticky追従）════ -->
<header class="gv2-hbar" id="gv2Hbar">
    <a href="<?php echo home_url('/'); ?>" class="gv2-hbar-logo">
        <?php if ($logo_url): ?>
            <img src="<?php echo esc_url($logo_url); ?>" alt="<?php bloginfo('name'); ?>">
        <?php else: ?>
            <span style="font:700 20px/1 Helvetica,Arial,sans-serif;letter-spacing:-0.02em;color:var(--dark);"><?php bloginfo('name'); ?></span>
        <?php endif; ?>
    </a>
    <nav class="gv2-hbar-nav">
        <?php
        $greige_menus = wp_get_nav_menus();
        if (!empty($greige_menus)) {
            wp_nav_menu(array(
                'menu'        => $greige_menus[0],
                'container'   => false,
                'fallback_cb' => false,
            ));
        }
        ?>
    </nav>
    <button class="gv2-hbar-btn js-menu-btn" aria-label="メニューを開く">
        <span></span><span></span>
    </button>
</header>

<!-- ══ PC HERO ════════════════════════════════════════
     ヘッダーバーは外に出した → height: 100vh - --rail
     [メインラップ flex:1]
       [左余白 --rail][ビジュアル flex:1][右余白 --rail]
     [ボトムバー --rail]
════════════════════════════════════════════════════ -->
<section class="gv2-hero" id="gv2Hero">

    <!-- メインラップ：左余白 ＋ ビジュアル ＋ 右余白 -->
    <div class="gv2-mwrap">

        <!-- 左余白（白スペース = --rail） -->
        <div class="gv2-mleft"></div>

        <!-- ビジュアルエリア：グレージュ50% ＋ 写真50% -->
        <div class="gv2-inner">

            <!-- グレージュパネル -->
            <div class="gv2-left-panel">
                <div class="gv2-editorial">
                    <div class="gv2-vol-label">VOL.</div>
                    <div class="gv2-vol-num"><?php echo esc_html($vol_display); ?></div>
                    <div class="gv2-divider"></div>
                    <div class="gv2-season"><?php echo esc_html(strtoupper($cover_season) . ' ' . $cover_year . '.' . $cover_month); ?></div>
                    <div class="gv2-cover-title"><?php echo esc_html($cover_title ?: 'GREIGE MAGAZINE'); ?></div>
                </div>
            </div>

            <!-- 写真パネル -->
            <div class="gv2-right-panel">
                <?php if (!empty($cover_images)): ?>
                    <?php foreach ($cover_images as $i => $img): ?>
                    <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($cover_title); ?>"
                         class="cover-slide<?php echo $i === 0 ? ' is-active' : ''; ?>"
                         data-logo-color="<?php echo esc_attr($logo_colors[$i] ?? 'black'); ?>"
                         loading="<?php echo $i === 0 ? 'eager' : 'lazy'; ?>">
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="gv2-right-placeholder"></div>
                <?php endif; ?>
            </div>

        </div>

        <!-- 右余白：SNSアイコン ＋ PRODUCT BOARD（= --rail） -->
        <div class="gv2-mright">
            <div class="gv2-sns-stack">
                <?php if (is_user_logged_in()): ?>
                <button class="gv2-search-btn js-srch-open" aria-label="検索">
                    <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </button>
                <div class="gv2-sns-divider"></div>
                <?php endif; ?>
                <a href="#" aria-label="Instagram">
                    <svg viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                </a>
                <a href="#" aria-label="Pinterest">
                    <svg viewBox="0 0 24 24"><path d="M12 0C5.373 0 0 5.373 0 12c0 5.084 3.163 9.426 7.627 11.174-.105-.949-.2-2.405.042-3.441.218-.937 1.407-5.965 1.407-5.965s-.359-.719-.359-1.782c0-1.668.967-2.914 2.171-2.914 1.023 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738a.36.36 0 0 1 .083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.359-.632-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/></svg>
                </a>
                <a href="#" aria-label="YouTube">
                    <svg viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                </a>
                <a href="#" aria-label="TikTok">
                    <svg viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                </a>
                <a href="#" aria-label="X">
                    <svg viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.748l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                </a>
                <a href="#" aria-label="Threads">
                    <svg viewBox="0 0 24 24"><path d="M18.263 11.097c-.03-3.486-1.92-5.586-5.111-5.586-2.13 0-3.922.963-4.863 2.499l2.062 1.438c.535-.843 1.272-1.543 2.628-1.543 1.528 0 2.318.85 2.544 2.431a15 15 0 0 0-2.236-.173c-4.125 0-6.068 1.867-6.068 4.336s1.943 3.99 4.804 3.99c3.139 0 5.013-2.115 5.781-4.735.798.361 1.348 1.204 1.348 2.47 0 3.387-3.907 5.232-7.22 5.232-4.885 0-8.077-3.207-8.077-8.424 0-6.392 4.223-10.487 9.9-10.487 3.808 0 5.69 1.671 6.97 3.914l2.108-1.475C21.44 2.078 18.331 0 13.663 0 6.227 0 1.168 5.277 1.168 12.934c0 7 4.953 11.066 10.856 11.066 4.878 0 9.809-2.846 9.809-7.716 0-2.545-1.46-4.231-3.569-5.187m-6.33 4.855c-1.077 0-2.026-.512-2.026-1.453 0-1.483 1.822-1.934 3.606-1.934.678 0 1.34.045 1.927.173-.422 1.927-1.671 3.215-3.508 3.214Z"/></svg>
                </a>
                <?php if (is_user_logged_in()): ?>
                <div class="gv2-sns-divider"></div>
                <a href="<?php echo esc_url(admin_url()); ?>" aria-label="管理画面">
                    <svg viewBox="0 0 24 24"><path d="M20 3H4c-1.1 0-2 .9-2 2v11c0 1.1.9 2 2 2h7v2H8v2h8v-2h-3v-2h7c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 13H4V5h16v11z"/></svg>
                </a>
                <?php endif; ?>
            </div>
            <?php if (is_user_logged_in()): ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=greige-product-board')); ?>" class="gv2-pb-link">CURATION</a>
            <?php endif; ?>
        </div>

    </div>

    <!-- ボトムバー：STAY CONNECTED ＋ スライドカウンター -->
    <footer class="gv2-bbar">
        <form class="gv2-newsletter" role="search" action="<?php echo esc_url(home_url('/')); ?>" method="get">
            <div class="gv2-search-inner">
                <input type="search" name="s" class="gv2-search-input" placeholder="What are you looking for?" autocomplete="off" value="<?php echo esc_attr(get_search_query()); ?>">
                <button type="submit" class="gv2-newsletter-btn">SEARCH</button>
            </div>
        </form>
        <div class="gv2-slide-counter">
            <span class="gv2-counter-num"><span id="gv2SlideNum">01</span> / <span id="gv2SlideTotal"><?php echo str_pad(count($cover_images), 2, '0', STR_PAD_LEFT); ?></span></span>
            <?php if (count($cover_images) > 1): ?>
            <div class="gv2-counter-dots" id="gv2CounterDots">
                <?php for ($di = 0; $di < count($cover_images); $di++): ?>
                <button class="gv2-dot<?php echo $di === 0 ? ' is-active' : ''; ?>" data-slide="<?php echo $di; ?>" aria-label="スライド<?php echo $di + 1; ?>へ"></button>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
            <div class="gv2-counter-arrows">
                <button id="gv2SlidePrev" aria-label="前へ">&#8592;</button>
                <button id="gv2SlideNext" aria-label="次へ">&#8594;</button>
            </div>
        </div>
    </footer>

</section>

<!-- ══ FASHION セクション ════════════════════════════════════════ -->
<?php if ($fashion_cat):
    $fq       = greige_gothic_get_products($fashion_cat->term_id, 5);
    $cat_link = get_term_link($fashion_cat, 'product_category');
    $cat_name = esc_html($fashion_cat->name);
?>
<section class="gv2-section">
    <div class="gv2-section-head">
        <h2 class="gv2-section-title"><?php echo $cat_name; ?></h2>
        <a href="<?php echo esc_url(is_wp_error($cat_link) ? '#' : $cat_link); ?>" class="gv2-section-more">VIEW ALL</a>
    </div>
    <div class="gv2-grid">
        <?php if ($fq->have_posts()):
            while ($fq->have_posts()): $fq->the_post();
                $pid       = get_the_ID();
                $img       = get_the_post_thumbnail_url($pid, 'medium_large');
                if (!$img) $img = get_post_meta($pid, 'product_external_image', true);
                $brand     = get_post_meta($pid, 'product_brand', true);
                $all_terms = get_the_terms($pid, 'product_category');
                $sub_label = '';
                if ($all_terms && !is_wp_error($all_terms)) {
                    foreach ($all_terms as $t) {
                        if ($t->parent != 0) { $sub_label = esc_html($t->name); break; }
                    }
                }
        ?>
        <div class="gv2-card">
            <a href="<?php the_permalink(); ?>">
                <div class="gv2-card-thumb">
                    <?php if ($img): ?>
                        <img src="<?php echo esc_url($img); ?>" alt="<?php the_title_attribute(); ?>" class="gv2-card-img">
                    <?php else: ?>
                        <div class="gv2-card-noimg"></div>
                    <?php endif; ?>
                    <?php if ($sub_label): ?><span class="gv2-card-label"><?php echo $sub_label; ?></span><?php endif; ?>
                </div>
                <div class="gv2-card-body">
                    <?php if ($brand): ?><p class="gv2-card-cat"><?php echo esc_html($brand); ?></p><?php endif; ?>
                    <p class="gv2-card-title"><?php the_title(); ?></p>
                </div>
            </a>
        </div>
        <?php endwhile; wp_reset_postdata();
        else:
            for ($i = 0; $i < 5; $i++): ?>
            <div class="gv2-card gv2-placeholder">
                <div class="gv2-card-thumb">
                    <div class="gv2-card-noimg"></div>
                    <span class="gv2-card-label"><?php echo $cat_name; ?></span>
                </div>
                <div class="gv2-card-body">
                    <p class="gv2-card-cat">—</p>
                    <p class="gv2-card-title">商品タイトルが入ります</p>
                </div>
            </div>
            <?php endfor;
        endif; ?>
    </div>
</section>
<?php endif; ?>

<!-- ══ NEWS セクション ════════════════════════════════════════ -->
<?php
$news_q = new WP_Query([
    'post_type'      => 'post',
    'posts_per_page' => 5,
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC',
]);
$news_posts   = $news_q->have_posts() ? $news_q->posts : [];
wp_reset_postdata();
$news_main    = !empty($news_posts) ? $news_posts[0] : null;
$news_subs    = !empty($news_posts) ? array_slice($news_posts, 1, 4) : [];
$news_archive = 'https://greige.online/news-list/';
?>
<section class="gv2-section gvn-section">
    <div class="gv2-section-head">
        <h2 class="gv2-section-title">NEWS</h2>
        <a href="<?php echo esc_url($news_archive); ?>" class="gv2-section-more">VIEW ALL</a>
    </div>
    <div class="gvn-grid">

        <!-- メイン記事 -->
        <div class="gvn-main">
            <?php if ($news_main):
                $nm_img  = get_the_post_thumbnail_url($news_main->ID, 'large');
                $nm_cats = get_the_terms($news_main->ID, 'category');
                $nm_top  = ($nm_cats && !is_wp_error($nm_cats)) ? (array_values(array_filter($nm_cats, fn($t) => $t->parent == 0))[0] ?? $nm_cats[0]) : null;
                $nm_cat  = $nm_top ? esc_html(strtoupper($nm_top->name)) : '';
                $nm_date = get_the_date('Y.m.d', $news_main->ID);
            ?>
            <a href="<?php echo esc_url(get_permalink($news_main->ID)); ?>">
                <div class="gvn-main-thumb">
                    <?php if ($nm_img): ?>
                        <img src="<?php echo esc_url($nm_img); ?>" alt="<?php echo esc_attr($news_main->post_title); ?>" class="gvn-main-img" loading="lazy">
                    <?php else: ?>
                        <div class="gvn-main-noimg"></div>
                    <?php endif; ?>
                    <?php if ($nm_cat): ?>
                    <span class="gvn-main-label"><?php echo $nm_cat; ?></span>
                    <?php endif; ?>
                    <div class="gvn-main-over">
                        <p class="gvn-main-over-title"><?php echo esc_html($news_main->post_title); ?></p>
                        <?php if ($nm_date): ?>
                        <p class="gvn-main-over-meta"><?php echo esc_html($nm_date); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
            <?php else: ?>
            <div class="gvn-main-thumb">
                <div class="gvn-main-noimg" style="opacity:0.35;"></div>
                <div class="gvn-main-over">
                    <p class="gvn-main-over-meta" style="opacity:0.4;">NEWS</p>
                    <p class="gvn-main-over-title" style="opacity:0.35;">ニュース記事が公開されると表示されます</p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- サブ記事（常に4スロット表示） -->
        <div class="gvn-sub">
            <?php
            $ns_count = 0;
            foreach ($news_subs as $ns):
                $ns_count++;
                $ns_img  = get_the_post_thumbnail_url($ns->ID, 'thumbnail');
                $ns_cats     = get_the_terms($ns->ID, 'category');
                $ns_top      = ($ns_cats && !is_wp_error($ns_cats)) ? (array_values(array_filter($ns_cats, fn($t) => $t->parent == 0))[0] ?? $ns_cats[0]) : null;
                $ns_cat      = $ns_top ? esc_html(strtoupper($ns_top->name)) : '';
                $ns_cat_link = $ns_top ? esc_url(get_term_link($ns_top)) : '#';
                $ns_date     = get_the_date('Y.m.d', $ns->ID);
            ?>
            <div class="gvn-sub-item">
                <?php $ns_permalink = esc_url(get_permalink($ns->ID)); ?>
                <a href="<?php echo $ns_permalink; ?>" class="gvn-sub-thumb-link">
                    <div class="gvn-sub-thumb">
                        <?php if ($ns_img): ?>
                            <img src="<?php echo esc_url($ns_img); ?>" alt="<?php echo esc_attr($ns->post_title); ?>" class="gvn-sub-img" loading="lazy">
                        <?php else: ?>
                            <div class="gvn-sub-noimg"></div>
                        <?php endif; ?>
                    </div>
                </a>
                <div class="gvn-sub-info">
                    <a href="<?php echo $ns_permalink; ?>" class="gvn-sub-title-link">
                        <p class="gvn-sub-title"><?php echo esc_html($ns->post_title); ?></p>
                    </a>
                    <?php if ($ns_cat || $ns_date): ?>
                    <div class="gvn-sub-meta">
                        <?php if ($ns_cat): ?><a href="<?php echo $ns_cat_link; ?>" class="gvn-sub-cat-badge"><?php echo $ns_cat; ?></a><?php endif; ?>
                        <?php if ($ns_date): ?><span class="gvn-sub-date"><?php echo esc_html($ns_date); ?></span><?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php for ($i = $ns_count; $i < 4; $i++): ?>
            <div class="gvn-sub-item gv2-placeholder">
                <div class="gvn-sub-link">
                    <div class="gvn-sub-thumb"><div class="gvn-sub-noimg"></div></div>
                    <div class="gvn-sub-info">
                        <p class="gvn-sub-title" style="opacity:0.18;">—</p>
                    </div>
                </div>
            </div>
            <?php endfor; ?>
        </div>

    </div>
</section>

<!-- ══ MAGAZINE セクション ════════════════════════════════════════ -->
<?php
$mag_term = get_term_by('slug', 'magazine', 'edit_category');
if (!$mag_term) $mag_term = get_term_by('slug', 'MAGAZINE', 'edit_category');
if (!$mag_term) $mag_term = get_term_by('name', 'MAGAZINE', 'edit_category');
$mag_link = ($mag_term && !is_wp_error(get_term_link($mag_term, 'edit_category')))
    ? get_term_link($mag_term, 'edit_category') : '';
$mq_args = [
    'post_type'      => 'greige_edit',
    'posts_per_page' => 6,
    'post_status'    => 'publish',
];
if ($mag_term) {
    $mq_args['tax_query'] = [[
        'taxonomy' => 'edit_category',
        'field'    => 'term_id',
        'terms'    => $mag_term->term_id,
    ]];
}
$mq = new WP_Query($mq_args);
if ($mq->have_posts()):
?>
<section class="gv2-section">
    <div class="gv2-section-head">
        <h2 class="gv2-section-title">MAGAZINE</h2>
        <?php if ($mag_link): ?><a href="<?php echo esc_url($mag_link); ?>" class="gv2-section-more">VIEW ALL</a><?php endif; ?>
    </div>
    <div class="gv2-grid gv2-grid--mag">
        <?php while ($mq->have_posts()): $mq->the_post();
                $pid       = get_the_ID();
                $img       = get_the_post_thumbnail_url($pid, 'medium_large');
                $all_terms = get_the_terms($pid, 'edit_category');
                $sub_label = '';
                if ($all_terms && !is_wp_error($all_terms)) {
                    foreach ($all_terms as $tobj) {
                        if ($tobj->parent != 0) { $sub_label = esc_html(strtoupper($tobj->name)); break; }
                    }
                }
        ?>
        <div class="gv2-card gv2-card--mag">
            <a href="<?php the_permalink(); ?>">
                <div class="gv2-card-thumb">
                    <?php if ($img): ?>
                        <img src="<?php echo esc_url($img); ?>" alt="<?php the_title_attribute(); ?>" class="gv2-card-img" loading="lazy">
                    <?php else: ?>
                        <div class="gv2-card-noimg"></div>
                    <?php endif; ?>
                    <?php if ($sub_label): ?><span class="gv2-card-label"><?php echo $sub_label; ?></span><?php endif; ?>
                </div>
                <div class="gv2-card-body">
                    <p class="gv2-card-title"><?php the_title(); ?></p>
                    <p class="gv2-card-date"><?php echo get_the_date('Y.m.d', $pid); ?></p>
                </div>
            </a>
        </div>
        <?php endwhile; wp_reset_postdata(); ?>
    </div>
</section>
<?php endif; // $mq->have_posts() ?>

<?php
// Wellbeing=2カラムfeature / Lifestyle=5カラム（カテゴリー名で判定）
// 順番を Fashion > Wellbeing > Lifestyle に強制
$remaining_cats = array_slice($product_cats, 1);
usort($remaining_cats, function($a, $b) {
    $aw = (stripos($a->name, 'wellbeing') !== false) ? 0 : 1;
    $bw = (stripos($b->name, 'wellbeing') !== false) ? 0 : 1;
    return $aw - $bw;
});
foreach ($remaining_cats as $ri => $rcat):
    $is_feature  = (stripos($rcat->name, 'wellbeing') !== false);
    $rnum        = $is_feature ? 4 : 5;
    $rgrid_class = $is_feature ? 'gv2-grid gv2-grid--feature' : 'gv2-grid';
    $rq          = greige_gothic_get_products($rcat->term_id, $rnum);
    $rcat_link   = get_term_link($rcat, 'product_category');
    $rcat_name   = esc_html($rcat->name);
?>
<!-- ══ <?php echo $rcat_name; ?> セクション ════════════════════════════════════════ -->
<section class="gv2-section">
    <div class="gv2-section-head">
        <h2 class="gv2-section-title"><?php echo $rcat_name; ?></h2>
        <a href="<?php echo esc_url(is_wp_error($rcat_link) ? '#' : $rcat_link); ?>" class="gv2-section-more">VIEW ALL</a>
    </div>
    <div class="<?php echo esc_attr($rgrid_class); ?>">
        <?php if ($rq->have_posts()):
            while ($rq->have_posts()): $rq->the_post();
                $pid       = get_the_ID();
                $img       = get_the_post_thumbnail_url($pid, 'medium_large');
                if (!$img) $img = get_post_meta($pid, 'product_external_image', true);
                $brand     = get_post_meta($pid, 'product_brand', true);
                $all_terms = get_the_terms($pid, 'product_category');
                $sub_label = '';
                if ($all_terms && !is_wp_error($all_terms)) {
                    foreach ($all_terms as $t) {
                        if ($t->parent != 0) { $sub_label = esc_html($t->name); break; }
                    }
                }
        ?>
        <div class="gv2-card">
            <a href="<?php the_permalink(); ?>">
                <div class="gv2-card-thumb">
                    <?php if ($img): ?>
                        <img src="<?php echo esc_url($img); ?>" alt="<?php the_title_attribute(); ?>" class="gv2-card-img">
                    <?php else: ?>
                        <div class="gv2-card-noimg"></div>
                    <?php endif; ?>
                    <?php if ($sub_label): ?><span class="gv2-card-label"><?php echo $sub_label; ?></span><?php endif; ?>
                </div>
                <div class="gv2-card-body">
                    <?php if ($brand): ?><p class="gv2-card-cat"><?php echo esc_html($brand); ?></p><?php endif; ?>
                    <p class="gv2-card-title"><?php the_title(); ?></p>
                </div>
            </a>
        </div>
        <?php endwhile; wp_reset_postdata();
        else:
            for ($i = 0; $i < $rnum; $i++): ?>
            <div class="gv2-card gv2-placeholder">
                <div class="gv2-card-thumb">
                    <div class="gv2-card-noimg"></div>
                    <span class="gv2-card-label"><?php echo $rcat_name; ?></span>
                </div>
                <div class="gv2-card-body">
                    <p class="gv2-card-cat">—</p>
                    <p class="gv2-card-title">商品タイトルが入ります</p>
                </div>
            </div>
            <?php endfor;
        endif; ?>
    </div>
</section>
<?php endforeach; ?>

<script>
(function(){
    var nav      = document.getElementById('gNav');
    var navBg    = document.getElementById('gNavBg');
    var navClose = document.getElementById('gNavClose');
    var mobileHeader = document.getElementById('gfxHeader');

    function openNav()  { if(nav) nav.classList.add('open'); if(navBg) navBg.classList.add('open'); }
    function closeNav() { if(nav) nav.classList.remove('open'); if(navBg) navBg.classList.remove('open'); }

    document.querySelectorAll('.js-menu-btn').forEach(function(btn){
        btn.addEventListener('click', openNav);
    });
    if (navClose) navClose.addEventListener('click', closeNav);
    if (navBg)    navBg.addEventListener('click', closeNav);

    /* 検索パネル */
    var srchPanel = document.getElementById('gSrchPanel');
    var srchBg    = document.getElementById('gSrchBg');
    var srchClose = document.getElementById('gSrchClose');
    function openSrch() {
        if(srchPanel) srchPanel.classList.add('open');
        if(srchBg)    srchBg.classList.add('open');
        var inp = srchPanel && srchPanel.querySelector('.g-srch-input');
        if(inp) setTimeout(function(){ inp.focus(); }, 350);
    }
    function closeSrch() {
        if(srchPanel) srchPanel.classList.remove('open');
        if(srchBg)    srchBg.classList.remove('open');
    }
    document.querySelectorAll('.js-srch-open').forEach(function(btn){
        btn.addEventListener('click', openSrch);
    });
    if(srchClose) srchClose.addEventListener('click', closeSrch);
    if(srchBg)    srchBg.addEventListener('click', closeSrch);

    /* 商品画像を追加 */
    var upWrap = document.getElementById('gSrchUpload');
    if (upWrap) {
        var upInput = document.getElementById('gSrchUploadInput');
        var upZone  = document.getElementById('gSrchUploadZone');
        var upImg   = document.getElementById('gSrchUploadImg');
        var upSend  = document.getElementById('gSrchUploadSend');
        var upMsg   = document.getElementById('gSrchUploadMsg');
        var upClear = document.getElementById('gSrchUploadClear');
        var upNonce = upWrap.querySelector('#greige_product_image_nonce');
        var upFile  = null;
        var upObjUrl = null;

        function clearUpload() {
            upFile = null;
            upInput.value = '';
            upZone.classList.remove('has-image');
            upImg.src = '';
            if (upObjUrl) { URL.revokeObjectURL(upObjUrl); upObjUrl = null; }
            upSend.disabled = true;
            if (upClear) upClear.style.display = 'none';
            setMsg('', false);
        }
        if (upClear) upClear.addEventListener('click', function(e){ e.stopPropagation(); clearUpload(); });

        function setMsg(text, isError) {
            upMsg.textContent = text;
            upMsg.classList.toggle('is-error', !!isError);
        }

        upZone.addEventListener('click', function(){ upInput.click(); });

        upInput.addEventListener('change', function(){
            var file = upInput.files && upInput.files[0];
            if (!file) return;
            upFile = file;
            if (upObjUrl) URL.revokeObjectURL(upObjUrl);
            upObjUrl = URL.createObjectURL(file);
            upImg.src = upObjUrl;
            upZone.classList.add('has-image');
            upSend.disabled = false;
            if (upClear) upClear.style.display = 'block';
            setMsg('', false);
        });

        upSend.addEventListener('click', function(){
            if (!upFile) return;
            upSend.disabled = true;
            setMsg('送信中...', false);

            var fd = new FormData();
            fd.append('action', 'greige_product_image_upload');
            fd.append('nonce', upNonce ? upNonce.value : '');
            fd.append('product_image', upFile);

            fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                method: 'POST',
                credentials: 'same-origin',
                body: fd
            })
            .then(function(r){ return r.json(); })
            .then(function(res){
                if (res && res.success) {
                    setMsg('送信しました', false);
                    clearUpload();
                } else {
                    upSend.disabled = false;
                    setMsg((res && res.data && res.data.message) || '送信に失敗しました', true);
                }
            })
            .catch(function(){
                upSend.disabled = false;
                setMsg('通信エラーが発生しました', true);
            });
        });
    }

    function onScroll() {
        if (!mobileHeader) return;
        var s = window.scrollY > 0;
        mobileHeader.classList.toggle('scrolled', s);
        document.body.classList.toggle('greige-scrolled', s);
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    /* Mobile header：メインビジュアル通過後にボーダー表示 */
    var mobileHero = document.querySelector('.gm-hero');
    function onMobileHeroScroll() {
        if (!mobileHeader || !mobileHero || window.innerWidth >= 768) return;
        mobileHeader.classList.toggle('past-hero', mobileHero.getBoundingClientRect().bottom <= 0);
    }
    window.addEventListener('scroll', onMobileHeroScroll, { passive: true });
    onMobileHeroScroll();

    /* PC sticky header：ヒーロー通過後にボーダー表示 */
    var pcHbar = document.getElementById('gv2Hbar');
    var pcHero = document.getElementById('gv2Hero');
    function onHeroScroll() {
        if (!pcHbar || !pcHero || window.innerWidth < 768) return;
        var heroBottom = pcHero.getBoundingClientRect().bottom;
        pcHbar.classList.toggle('scrolled', heroBottom <= 0);
    }
    window.addEventListener('scroll', onHeroScroll, { passive: true });
    onHeroScroll();

    if (window.navigator.standalone === true) { document.body.classList.add('pwa-standalone'); }

    /* Astra スティッキーヘッダー強制非表示 */
    ['#ast-fixed-header','.ast-sticky-header','.ast-sticky-header-wrap','.main-header-bar','#masthead'].forEach(function(sel){
        document.querySelectorAll(sel).forEach(function(el){
            function suppress(){ el.style.setProperty('display','none','important'); el.style.setProperty('height','0','important'); }
            suppress();
            new MutationObserver(suppress).observe(el,{attributes:true,attributeFilter:['style','class']});
        });
    });
    function resetBodyPadding(){
        document.body.style.setProperty('padding-top','0','important');
        document.body.style.setProperty('margin-top','0','important');
    }
    resetBodyPadding();
    window.addEventListener('scroll', resetBodyPadding, { passive: true });


})();
</script>

<!-- ══ COVER FADE SLIDER ═════════════════════════════ -->
<script>
(function() {
    var photoCont = document.querySelector('.gm-photo');
    var rightCont = document.querySelector('.gv2-right-panel');
    var header    = document.getElementById('gfxHeader');

    var masterCont   = photoCont || rightCont;
    if (!masterCont) return;
    var masterSlides = Array.from(masterCont.querySelectorAll('.cover-slide'));
    var total = masterSlides.length;

    var counterNum = document.getElementById('gv2SlideNum');
    var dots       = Array.from(document.querySelectorAll('.gv2-dot'));
    var btnPrev    = document.getElementById('gv2SlidePrev');
    var btnNext    = document.getElementById('gv2SlideNext');

    var current = 0;

    function applyLogoColor(idx) {
        if (!header || !masterSlides[idx]) return;
        header.classList.toggle('logo-white-half', masterSlides[idx].dataset.logoColor === 'white_half');
    }

    function updateUI(idx) {
        if (counterNum) counterNum.textContent = String(idx + 1).padStart(2, '0');
        dots.forEach(function(d, i) { d.classList.toggle('is-active', i === idx); });
    }

    function goTo(idx) {
        [photoCont, rightCont].forEach(function(cont) {
            if (!cont) return;
            var s = cont.querySelectorAll('.cover-slide');
            Array.from(s).forEach(function(sl) { sl.classList.remove('is-active'); });
            if (s[idx]) s[idx].classList.add('is-active');
        });
        current = idx;
        applyLogoColor(idx);
        updateUI(idx);
    }

    applyLogoColor(0);
    updateUI(0);

    if (total < 2) return;

    var timer = setInterval(function() { goTo((current + 1) % total); }, 5000);

    function resetTimer() {
        clearInterval(timer);
        timer = setInterval(function() { goTo((current + 1) % total); }, 5000);
    }

    if (btnPrev) btnPrev.addEventListener('click', function() { goTo((current - 1 + total) % total); resetTimer(); });
    if (btnNext) btnNext.addEventListener('click', function() { goTo((current + 1) % total); resetTimer(); });

    dots.forEach(function(d) {
        d.addEventListener('click', function() { goTo(parseInt(this.dataset.slide, 10)); resetTimer(); });
    });
})();
</script>

<!-- ══ FOOTER ════════════════════════════════════════ -->
<div style="height:48px;"></div>
<?php get_template_part('template-parts/footer-site'); ?>


<?php wp_footer(); ?>
</body>
</html>
