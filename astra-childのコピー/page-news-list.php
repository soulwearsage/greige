<?php
/**
 * Template Name: NEWS LIST
 * GREIGE MAGAZINE - News Archive
 */

$paged      = max(1, get_query_var('page'));
$per_page   = 24;
$active_cat = isset($_GET['cat']) ? sanitize_text_field($_GET['cat']) : '';

$pq_args = [
    'post_type'      => 'post',
    'posts_per_page' => $per_page,
    'paged'          => $paged,
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC',
];
if ($active_cat) {
    $pq_args['category_name'] = $active_cat;
}
$pq = new WP_Query($pq_args);

// カテゴリー一覧
$news_cats = get_categories(['hide_empty' => true]);
if (is_wp_error($news_cats)) $news_cats = [];

// タグ一覧（現在表示中の投稿に付いているタグ）
$_news_ids = get_posts(array_merge($pq_args, ['posts_per_page' => -1, 'paged' => 1, 'fields' => 'ids']));
$news_tags = !empty($_news_ids) ? get_terms(['taxonomy' => 'post_tag', 'object_ids' => $_news_ids, 'hide_empty' => true]) : [];
if (is_wp_error($news_tags)) $news_tags = [];

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
<title>NEWS LIST | <?php bloginfo('name'); ?> | <?php bloginfo('description'); ?></title>
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
            <h1 class="arc-title">NEWS LIST</h1>
        </div>
        <div class="arc-col-switcher" id="arcColSwitcher">
            <button class="arc-col-btn" data-cols="1" data-breakpoint="mobile" title="1列">
                <svg viewBox="0 0 16 16"><rect x="2" y="2" width="12" height="12"/></svg>
                <span class="arc-col-label">1</span>
            </button>
            <button class="arc-col-btn is-active" data-cols="2" data-breakpoint="mobile" title="2列">
                <svg viewBox="0 0 16 16"><rect x="2" y="2" width="5" height="12"/><rect x="9" y="2" width="5" height="12"/></svg>
                <span class="arc-col-label">2</span>
            </button>
            <button class="arc-col-btn" data-cols="3" data-breakpoint="tablet" title="3列">
                <svg viewBox="0 0 16 16"><rect x="1" y="2" width="3.5" height="12"/><rect x="6.2" y="2" width="3.5" height="12"/><rect x="11.5" y="2" width="3.5" height="12"/></svg>
                <span class="arc-col-label">3</span>
            </button>
            <button class="arc-col-btn" data-cols="4" data-breakpoint="desktop" title="4列">
                <svg viewBox="0 0 16 16"><rect x="1" y="2" width="2.5" height="12"/><rect x="4.8" y="2" width="2.5" height="12"/><rect x="8.7" y="2" width="2.5" height="12"/><rect x="12.5" y="2" width="2.5" height="12"/></svg>
                <span class="arc-col-label">4</span>
            </button>
            <button class="arc-col-btn" data-cols="5" data-breakpoint="desktop" title="5列">
                <svg viewBox="0 0 16 16"><rect x="1" y="2" width="2" height="12"/><rect x="3.8" y="2" width="2" height="12"/><rect x="6.6" y="2" width="2" height="12"/><rect x="9.4" y="2" width="2" height="12"/><rect x="12.2" y="2" width="2" height="12"/></svg>
                <span class="arc-col-label">5</span>
            </button>
        </div>
    </div>

    <div class="arc-grid-wrap">
        <p class="arc-count"><?php echo $pq->found_posts; ?> ITEMS</p>

        <?php if ($pq->have_posts()): ?>
        <div class="arc-grid" id="arcGrid">
            <?php while ($pq->have_posts()): $pq->the_post();
                $pid   = get_the_ID();
                $img   = get_the_post_thumbnail_url($pid, 'medium_large');
                $cats  = get_the_category($pid);
                $label = (!empty($cats)) ? esc_html($cats[0]->name) : '';
            ?>
            <div class="arc-card">
                <a href="<?php the_permalink(); ?>">
                    <div class="arc-card-thumb">
                        <?php if ($img): ?>
                        <img src="<?php echo esc_url($img); ?>" alt="<?php the_title_attribute(); ?>" class="arc-card-img" loading="lazy">
                        <?php else: ?>
                        <div class="arc-card-noimg"></div>
                        <?php endif; ?>
                        <?php if ($label): ?><span class="arc-card-label"><?php echo $label; ?></span><?php endif; ?>
                    </div>
                    <div class="arc-card-body">
                        <p class="arc-card-title"><?php the_title(); ?></p>
                        <p class="arc-card-date"><?php echo get_the_date('Y.m.d', $pid); ?></p>
                    </div>
                </a>
            </div>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>

        <?php
        $total    = $pq->max_num_pages;
        $base_url = get_permalink();
        if ($total >= 1):
        ?>
        <nav class="arc-pagination">
            <?php for ($i = 1; $i <= $total; $i++):
                $page_url = $i === 1 ? $base_url : trailingslashit($base_url) . 'page/' . $i . '/';
            ?>
            <?php if ($i === $paged): ?>
                <span class="current"><?php echo $i; ?></span>
            <?php else: ?>
                <a href="<?php echo esc_url($page_url); ?>"><?php echo $i; ?></a>
            <?php endif; ?>
            <?php endfor; ?>
            <?php if ($paged < $total): ?>
                <?php $next_url = trailingslashit($base_url) . 'page/' . ($paged + 1) . '/'; ?>
                <a href="<?php echo esc_url($next_url); ?>" class="arc-pagination-next">&#9654;</a>
            <?php else: ?>
                <span class="arc-pagination-next" style="opacity:0.25;">&#9654;</span>
            <?php endif; ?>
        </nav>
        <?php endif; ?>

        <?php else: ?>
        <div class="arc-empty"><p>まだコンテンツがありません。</p></div>
        <?php endif; ?>
    </div>

    <!-- filter bar -->
    <?php if (!empty($news_cats)): ?>
    <div class="arc-filter-bar">
        <div class="arc-filter-row">
            <span class="arc-filter-label">FILTER</span>
            <a href="<?php echo esc_url(get_permalink()); ?>"
               class="arc-pill <?php echo $active_cat ? '' : 'is-active'; ?>">ALL</a>
            <?php foreach ($news_cats as $nc): ?>
            <a href="<?php echo esc_url(add_query_arg('cat', $nc->slug, get_permalink())); ?>"
               class="arc-pill <?php echo $active_cat === $nc->slug ? 'is-active' : ''; ?>">
                <?php echo esc_html(strtoupper($nc->name)); ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- tags -->
    <?php if (!empty($news_tags)): ?>
    <div class="arc-tags">
        <p class="arc-tags-label">BROWSE BY TAG</p>
        <div class="arc-tags-list">
            <?php foreach ($news_tags as $tag):
                $tag_link = get_term_link($tag, 'post_tag');
                if (is_wp_error($tag_link)) continue;
            ?>
            <a href="<?php echo esc_url($tag_link); ?>" class="arc-tag"><?php echo esc_html($tag->name); ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

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

    var grid     = document.getElementById('arcGrid');
    var switcher = document.getElementById('arcColSwitcher');
    var btns     = switcher ? Array.from(switcher.querySelectorAll('.arc-col-btn')) : [];
    var lastBp   = null;

    function getBreakpoint() {
        var w = window.innerWidth;
        if (w >= 1024) return 'desktop';
        if (w >= 768)  return 'tablet';
        return 'mobile';
    }

    var bpDefault = { mobile: 2, tablet: 3, desktop: 4 };
    var bpValid   = { mobile: [1,2], tablet: [2,3], desktop: [3,4,5] };

    function applyCol(cols) {
        if (!grid) return;
        grid.className = 'arc-grid cols-' + cols;
        btns.forEach(function(b) { b.classList.toggle('is-active', b.dataset.cols === String(cols)); });
        try { localStorage.setItem('greige_news_arc_cols', cols); } catch(e) {}
    }

    function setForBreakpoint() {
        var bp = getBreakpoint();
        if (bp === lastBp) return;
        lastBp = bp;
        var valid = bpValid[bp];
        var saved = null;
        try { saved = parseInt(localStorage.getItem('greige_news_arc_cols')); } catch(e) {}
        applyCol(valid.indexOf(saved) !== -1 ? saved : bpDefault[bp]);
    }

    btns.forEach(function(b) {
        b.addEventListener('click', function() { applyCol(parseInt(b.dataset.cols)); });
    });

    setForBreakpoint();
    window.addEventListener('resize', setForBreakpoint);
})();
</script>
<?php wp_footer(); ?>
</body>
</html>
