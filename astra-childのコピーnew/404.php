<?php
/**
 * GREIGE MAGAZINE - 404 Not Found
 */

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
<title>404 Not Found | <?php bloginfo('name'); ?></title>
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
</head>
<body <?php body_class('greige-single greige-404'); ?>>
<?php wp_body_open(); ?>

<?php get_template_part('template-parts/nav-overlay'); ?>
<?php get_template_part('template-parts/header-single'); ?>

<div class="sg-wrap">
<div class="sg-layout">

    <!-- Sidebar -->
    <aside class="sg-sidebar">

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

    </aside>

    <!-- Content -->
    <main class="sg-content">

        <h1 class="sg-title">404</h1>

        <div class="sg-meta">
            <div class="sg-meta-item">PAGE NOT FOUND</div>
        </div>

        <div class="sg-meta-line"></div>

        <div class="sg-body">
            <p>お探しのページは見つかりませんでした。</p>
            <p>URLが変更されたか、削除された可能性があります。</p>
            <p><a href="<?php echo esc_url(home_url('/')); ?>">トップページへ戻る</a></p>
        </div>

    </main>

</div>
</div>

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
