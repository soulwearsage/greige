<?php
/**
 * GREIGE MAGAZINE - Search Results
 */
$search_query = get_search_query();
$paged    = max(1, get_query_var('paged'));
$per_page = 24;

$pq = new WP_Query([
    's'              => $search_query,
    'post_type'      => ['post', 'greige_edit', 'greige_product'],
    'posts_per_page' => $per_page,
    'paged'          => $paged,
    'post_status'    => 'publish',
]);

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
<title><?php echo $search_query ? esc_html($search_query) . ' — ' : ''; ?>SEARCH | <?php bloginfo('name'); ?> | <?php bloginfo('description'); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
<?php wp_head(); ?>
<link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/css/greige-base.css?v=<?php echo filemtime(get_stylesheet_directory() . '/css/greige-base.css'); ?>">
<link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/css/greige-nav.css?v=<?php echo filemtime(get_stylesheet_directory() . '/css/greige-nav.css'); ?>">
<link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/css/greige-footer.css?v=<?php echo filemtime(get_stylesheet_directory() . '/css/greige-footer.css'); ?>">
<link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/css/greige-components.css?v=<?php echo filemtime(get_stylesheet_directory() . '/css/greige-components.css'); ?>">
<link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/css/greige-archive.css?v=<?php echo filemtime(get_stylesheet_directory() . '/css/greige-archive.css'); ?>">
</head>
<body <?php body_class('greige-archive'); ?>>
<?php wp_body_open(); ?>

<?php get_template_part('template-parts/nav-overlay'); ?>
<?php get_template_part('template-parts/header-single'); ?>

<div class="arc-wrap">

    <div class="arc-title-block">
        <div class="arc-title-text">
            <?php get_template_part('template-parts/breadcrumb'); ?>
            <h1 class="arc-title">SEARCH</h1>
            <?php if ($search_query): ?>
            <p class="arc-desc"><?php echo esc_html($pq->found_posts); ?> results for &ldquo;<?php echo esc_html($search_query); ?>&rdquo;</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="arc-grid-wrap">
        <?php if ($pq->have_posts()): ?>
        <p class="arc-count"><?php echo $pq->found_posts; ?> ITEMS</p>
        <div class="arc-grid" id="arcGrid">
            <?php while ($pq->have_posts()): $pq->the_post();
                $pid = get_the_ID();
                $img = get_the_post_thumbnail_url($pid, 'medium_large');
            ?>
            <div class="arc-card">
                <a href="<?php the_permalink(); ?>">
                    <div class="arc-card-thumb">
                        <?php if ($img): ?>
                        <img src="<?php echo esc_url($img); ?>" alt="<?php the_title_attribute(); ?>" class="arc-card-img" loading="lazy">
                        <?php else: ?>
                        <div class="arc-card-noimg"></div>
                        <?php endif; ?>
                    </div>
                    <div class="arc-card-body">
                        <p class="arc-card-title"><?php the_title(); ?></p>
                    </div>
                </a>
            </div>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>

        <?php
        $total = $pq->max_num_pages;
        if ($total > 1):
        ?>
        <nav class="arc-pagination">
            <?php for ($i = 1; $i <= $total; $i++):
                $page_url = $i === 1 ? home_url('/?s=' . urlencode($search_query)) : home_url('/page/' . $i . '/?s=' . urlencode($search_query));
            ?>
            <?php if ($i === $paged): ?>
                <span class="current"><?php echo $i; ?></span>
            <?php else: ?>
                <a href="<?php echo esc_url($page_url); ?>"><?php echo $i; ?></a>
            <?php endif; ?>
            <?php endfor; ?>
        </nav>
        <?php endif; ?>

        <?php else: ?>
        <div class="arc-empty">
            <p>NO RESULTS FOUND</p>
            <?php if (!$search_query): ?>
            <p style="margin-top:12px;font-size:11px;opacity:0.5;">Please enter a keyword to search.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="srch-form-wrap srch-form-bottom">
        <form class="srch-form" role="search" action="<?php echo esc_url(home_url('/')); ?>" method="get">
            <div class="srch-form-inner">
                <input type="search" name="s" class="srch-form-input" placeholder="What are you looking for?" autocomplete="off" value="<?php echo esc_attr($search_query); ?>">
                <button type="submit" class="srch-form-btn">SEARCH</button>
            </div>
        </form>
    </div>

</div>

<?php get_template_part('template-parts/footer-site'); ?>

<script>
(function() {
    var nav    = document.getElementById('gNav');
    var navBg  = document.getElementById('gNavBg');
    var close  = document.getElementById('gNavClose');
    function openNav()  { nav && nav.classList.add('is-open'); navBg && navBg.classList.add('is-open'); }
    function closeNav() { nav && nav.classList.remove('is-open'); navBg && navBg.classList.remove('is-open'); }
    if (close) close.addEventListener('click', closeNav);
    if (navBg) navBg.addEventListener('click', closeNav);
})();
</script>

<?php wp_footer(); ?>
</body>
</html>
