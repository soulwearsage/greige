<?php
/**
 * GREIGE MAGAZINE - 商品詳細ページ
 */

if (!have_posts()) { wp_redirect(home_url()); exit; }
the_post();

$logo_id  = get_theme_mod('custom_logo');
$logo_url = '';
if ($logo_id) {
    $logo_src = wp_get_attachment_image_src($logo_id, 'full');
    if ($logo_src) $logo_url = $logo_src[0];
}

$product_id = get_the_ID();

$brand        = get_post_meta($product_id, 'product_brand', true);
$price        = get_post_meta($product_id, 'product_price', true);
$sale_price   = get_post_meta($product_id, 'product_sale_price', true);
$currency     = get_post_meta($product_id, 'product_currency', true) ?: 'JPY';
$aff_amazon   = get_post_meta($product_id, 'affiliate_amazon', true)   ?: '';
$aff_rakuten  = get_post_meta($product_id, 'affiliate_rakuten', true)  ?: '';
$aff_yahoo    = get_post_meta($product_id, 'affiliate_yahoo', true)    ?: '';
$aff_official = get_post_meta($product_id, 'affiliate_official', true) ?: (get_post_meta($product_id, 'product_url', true) ?: '');
$ext_image    = get_post_meta($product_id, 'product_external_image', true);

$img_src  = has_post_thumbnail() ? get_the_post_thumbnail_url($product_id, 'large') : $ext_image;
$has_aff  = $aff_amazon || $aff_rakuten || $aff_yahoo || $aff_official;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $brand ? esc_html($brand) . ' ' : ''; ?><?php the_title(); ?> | <?php bloginfo('name'); ?> | <?php bloginfo('description'); ?></title>
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
<link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/css/greige-affiliate.css?v=<?php echo filemtime(get_stylesheet_directory() . '/css/greige-affiliate.css'); ?>">
<link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/css/greige-product.css?v=<?php echo filemtime(get_stylesheet_directory() . '/css/greige-product.css'); ?>">
</head>
<body <?php body_class('greige-single greige-product'); ?>>
<?php wp_body_open(); ?>

<?php get_template_part('template-parts/nav-overlay'); ?>
<?php get_template_part('template-parts/header-single'); ?>

<!-- ══ MAIN ══════════════════════════════════════════ -->
<div class="sg-wrap">
<div class="sg-layout">

    <!-- ── Sidebar (left on PC) ── -->
    <aside class="sg-sidebar">

        <!-- Categories -->
        <div class="sg-widget">
            <h2 class="sg-widget-title">CATEGORY</h2>
            <ul>
                <?php
                $cats = get_categories(array('hide_empty' => false));
                foreach ($cats as $cat) :
                ?>
                <li>
                    <a href="<?php echo esc_url(get_category_link($cat->term_id)); ?>">
                        <?php echo esc_html(strtoupper($cat->name)); ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Archives -->
        <div class="sg-widget">
            <h2 class="sg-widget-title">ARCHIVE</h2>
            <ul>
                <?php wp_get_archives(array(
                    'type'   => 'monthly',
                    'format' => 'html',
                    'before' => '<li>',
                    'after'  => '</li>',
                )); ?>
            </ul>
        </div>

        <!-- Related (tags as capsules): sticky -->
        <div class="sg-widget sg-widget-sticky">
            <h2 class="sg-widget-title">RELATED</h2>
            <?php
            $tags = get_the_tags();
            if ($tags) :
            ?>
            <div class="sg-tags">
                <?php foreach ($tags as $tag) : ?>
                <a href="<?php echo esc_url(get_tag_link($tag->term_id)); ?>" class="sg-tag">
                    <?php echo esc_html($tag->name); ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php else : ?>
            <p style="font-family:'Montserrat',sans-serif;font-size:10px;color:#ccc;letter-spacing:0.15em;">NO ITEMS</p>
            <?php endif; ?>
        </div>

    </aside>

    <!-- ── Content ── -->
    <main class="sg-content">

        <!-- Breadcrumb -->
        <?php get_template_part('template-parts/breadcrumb'); ?>

        <!-- 商品画像 -->
        <?php if ($img_src) : ?>
        <div class="gp-img-wrap">
            <img src="<?php echo esc_url($img_src); ?>"
                 alt="<?php the_title_attribute(); ?>"
                 loading="lazy">
        </div>
        <?php endif; ?>

        <!-- Title -->
        <h1 class="sg-title"><?php the_title(); ?></h1>

        <!-- Meta: brand / price -->
        <div class="sg-meta">
            <?php if ($brand) : ?>
            <div class="sg-meta-item">
                <i class="fa-regular fa-copyright"></i>
                <?php echo esc_html(strtoupper($brand)); ?>
            </div>
            <?php if ($price || $sale_price) : ?>
            <div class="sg-meta-divider"></div>
            <?php endif; ?>
            <?php endif; ?>
            <?php if ($price || $sale_price) : ?>
            <div class="sg-meta-item">
                <?php if ($sale_price) : ?>
                    <span class="gp-price-original"><?php echo greige_format_price($price, $currency); ?></span>
                    <span class="gp-price-sale"><?php echo greige_format_price($sale_price, $currency); ?></span>
                <?php else : ?>
                    <span class="gp-price"><?php echo greige_format_price($price, $currency); ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Separator -->
        <div class="sg-meta-line"></div>

        <!-- DEBUG: has_aff=<?php echo $has_aff ? 'true' : 'false'; ?> rakuten=<?php echo esc_html($aff_rakuten); ?> amazon=<?php echo esc_html($aff_amazon); ?> -->
        <!-- アフィリエイトカード -->
        <?php if ($has_aff) : ?>
        <div class="gp-aff-wrap">
            <?php
            $args = ['card' => [
                'amazon'   => $aff_amazon,
                'rakuten'  => $aff_rakuten,
                'yahoo'    => $aff_yahoo,
                'official' => $aff_official,
            ]];
            include(get_stylesheet_directory() . '/template-parts/affiliate-card.php');
            ?>
        </div>
        <?php endif; ?>

        <!-- 記事本文 -->
        <?php if (get_the_content()) : ?>
        <div class="sg-body">
            <?php the_content(); ?>
        </div>
        <?php endif; ?>

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
