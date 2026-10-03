<?php
/**
 * GREIGE MAGAZINE - EDIT記事テンプレート
 *
 * ショートコード:
 *   [greige_product id="123"]  — 本文内インライン商品カード
 *   [greige_compare]           — 比較テーブル（edit_show_comparison が true のとき有効）
 */

if (!have_posts()) { wp_redirect(home_url()); exit; }
the_post();

$logo_id  = get_theme_mod('custom_logo');
$logo_url = '';
if ($logo_id) {
    $logo_src = wp_get_attachment_image_src($logo_id, 'full');
    if ($logo_src) $logo_url = $logo_src[0];
}

$post_id = get_the_ID();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php the_title(); ?> | <?php bloginfo('name'); ?> | <?php bloginfo('description'); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500&family=Montserrat:wght@300;400;500;600&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<?php wp_head(); ?>
<link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/css/greige-base.css?v=<?php echo filemtime(get_stylesheet_directory() . '/css/greige-base.css'); ?>">
<link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/css/greige-nav.css?v=<?php echo filemtime(get_stylesheet_directory() . '/css/greige-nav.css'); ?>">
<link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/css/greige-footer.css?v=<?php echo filemtime(get_stylesheet_directory() . '/css/greige-footer.css'); ?>">
<link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/css/greige-components.css?v=<?php echo filemtime(get_stylesheet_directory() . '/css/greige-components.css'); ?>">
<link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/css/greige-single.css?v=<?php echo filemtime(get_stylesheet_directory() . '/css/greige-single.css'); ?>">
<link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/css/greige-edit.css?v=1">
</head>
<body <?php body_class('greige-edit'); ?>>
<?php wp_body_open(); ?>

<?php get_template_part('template-parts/nav-overlay'); ?>
<?php get_template_part('template-parts/header-single'); ?>

<div class="sg-wrap">
<div class="sg-layout">

    <!-- ── サイドバー ── -->
    <aside class="sg-sidebar">

        <!-- CATEGORY -->
        <div class="sg-widget">
            <h2 class="sg-widget-title">CATEGORY</h2>
            <ul>
                <?php
                $terms = get_terms(['taxonomy' => 'edit_category', 'hide_empty' => false]);
                if (!empty($terms) && !is_wp_error($terms)) :
                    foreach ($terms as $term) :
                ?>
                <li><a href="<?php echo esc_url(get_term_link($term)); ?>"><?php echo esc_html(strtoupper($term->name)); ?></a></li>
                <?php endforeach; else : ?>
                <li>カテゴリーなし</li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- ARCHIVE -->
        <div class="sg-widget">
            <h2 class="sg-widget-title">ARCHIVE</h2>
            <ul>
                <?php wp_get_archives(['post_type' => 'greige_edit', 'type' => 'monthly', 'format' => 'html', 'before' => '<li>', 'after' => '</li>']); ?>
            </ul>
        </div>

        <!-- RELATED -->
        <div class="sg-widget">
            <h2 class="sg-widget-title">RELATED</h2>
            <?php $post_tags = get_the_tags($post_id); ?>
            <?php if ($post_tags && !is_wp_error($post_tags)) : ?>
            <div class="sg-tags">
                <?php foreach ($post_tags as $tag) : ?>
                <a href="<?php echo esc_url(get_tag_link($tag->term_id)); ?>" class="sg-tag"><?php echo esc_html($tag->name); ?></a>
                <?php endforeach; ?>
            </div>
            <?php else : ?>
            <p style="font-family:'Montserrat',sans-serif;font-size:10px;color:#ccc;letter-spacing:0.15em;">NO ITEMS</p>
            <?php endif; ?>
        </div>

    </aside>

    <!-- ── コンテンツ ── -->
    <main class="sg-content">

        <!-- PR表示（タイトル上） -->
        <?php get_template_part('template-parts/components/pr-notice', null, ['post_id' => $post_id]); ?>

        <!-- パンくず -->
        <nav class="sg-bc" aria-label="パンくずリスト">
            <a href="<?php echo home_url('/'); ?>">HOME</a>
            <span class="sg-bc-sep">/</span>
            <?php
            $terms = get_the_terms($post_id, 'edit_category');
            if ($terms && !is_wp_error($terms)) :
            ?>
            <a href="<?php echo esc_url(get_term_link($terms[0])); ?>">
                <?php echo esc_html($terms[0]->name); ?>
            </a>
            <span class="sg-bc-sep">/</span>
            <?php endif; ?>
            <span><?php the_title(); ?></span>
        </nav>

        <!-- アイキャッチ -->
        <?php if (has_post_thumbnail()) : ?>
        <div class="sg-edit-thumb">
            <?php the_post_thumbnail('large'); ?>
        </div>
        <?php endif; ?>

        <!-- タイトル -->
        <h1 class="sg-title"><?php the_title(); ?></h1>

        <!-- メタ -->
        <div class="sg-meta">
            <div class="sg-meta-item">
                <i class="fa-regular fa-calendar"></i>
                <?php echo get_the_date('Y.m.d'); ?>
            </div>
            <?php
            $terms = get_the_terms($post_id, 'edit_category');
            if ($terms && !is_wp_error($terms)) :
            ?>
            <div class="sg-meta-divider"></div>
            <div class="sg-meta-item">
                <i class="fa-regular fa-folder"></i>
                <a href="<?php echo esc_url(get_term_link($terms[0])); ?>" style="color:var(--dark);transition:opacity 0.2s;" onmouseover="this.style.opacity='0.45'" onmouseout="this.style.opacity='1'">
                    <?php echo esc_html($terms[0]->name); ?>
                </a>
            </div>
            <?php endif; ?>
            <div class="sg-meta-divider"></div>
            <div class="sg-meta-item">
                <i class="fa-solid fa-pen"></i>
                <?php the_author(); ?>
            </div>
        </div>

        <div class="sg-meta-line"></div>

        <!-- コンテンツブロック or 本文フォールバック -->
        <?php
        $blocks = function_exists('get_field') ? get_field('edit_content_blocks', $post_id) : null;
        if (!empty($blocks)): ?>
            <?php get_template_part('template-parts/components/edit-blocks', null, ['post_id' => $post_id]); ?>
        <?php else: ?>
        <div class="sg-body">
            <?php the_content(); ?>
        </div>
        <?php endif; ?>

        <!-- SHOP THE EDIT -->
        <?php get_template_part('template-parts/components/shop-the-edit', null, ['post_id' => $post_id]); ?>

    </main>

</div><!-- /.sg-layout -->
</div><!-- /.sg-wrap -->

<?php get_template_part('template-parts/footer-site'); ?>

<script>
(function(){
    var menuBtn  = document.getElementById('sgMenuBtn');
    var nav      = document.getElementById('gNav');
    var navBg    = document.getElementById('gNavBg');
    var navClose = document.getElementById('gNavClose');

    function openNav()  { nav.classList.add('open'); navBg.classList.add('open'); }
    function closeNav() { nav.classList.remove('open'); navBg.classList.remove('open'); }

    if (menuBtn)  menuBtn.addEventListener('click', openNav);
    if (navClose) navClose.addEventListener('click', closeNav);
    if (navBg)    navBg.addEventListener('click', closeNav);
})();
</script>

<?php wp_footer(); ?>
</body>
</html>
