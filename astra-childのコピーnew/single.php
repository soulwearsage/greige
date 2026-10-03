<?php
/**
 * GREIGE MAGAZINE - Single Post Template
 */

if (!have_posts()) { wp_redirect(home_url()); exit; }
the_post();

$logo_id  = get_theme_mod('custom_logo');
$logo_url = '';
if ($logo_id) {
    $logo_src = wp_get_attachment_image_src($logo_id, 'full');
    if ($logo_src) $logo_url = $logo_src[0];
}

if (!function_exists('greige_cat_url')) {
    function greige_cat_url($slug) {
        $cat = get_category_by_slug($slug);
        return $cat ? esc_url(get_category_link($cat->term_id)) : '#';
    }
}
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
</head>
<body <?php body_class('greige-single'); ?>>
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
            <p style="font-family:'Montserrat',sans-serif;font-size:10px;color:#ccc;letter-spacing:0.15em;">NO TAGS</p>
            <?php endif; ?>
        </div>

    </aside>

    <!-- ── Content (right on PC) ── -->
    <?php
    /* Related/Latest News query */
    $sgnr_posts = [];
    $post_tags  = get_the_tags();
    if ($post_tags) {
        $tag_ids = wp_list_pluck($post_tags, 'term_id');
        $rq = new WP_Query([
            'post_type'      => 'post',
            'posts_per_page' => 5,
            'post_status'    => 'publish',
            'post__not_in'   => [get_the_ID()],
            'tag__in'        => $tag_ids,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);
        $sgnr_posts = $rq->posts;
        wp_reset_postdata();
    }
    if (count($sgnr_posts) < 5) {
        $exclude = array_merge([get_the_ID()], array_map(function($p){ return $p->ID; }, $sgnr_posts));
        $lq = new WP_Query([
            'post_type'      => 'post',
            'posts_per_page' => 5 - count($sgnr_posts),
            'post_status'    => 'publish',
            'post__not_in'   => $exclude,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);
        $sgnr_posts = array_merge($sgnr_posts, $lq->posts);
        wp_reset_postdata();
    }
    $sgnr_featured = !empty($sgnr_posts) ? $sgnr_posts[0] : null;
    $sgnr_grid     = count($sgnr_posts) > 1 ? array_slice($sgnr_posts, 1, 4) : [];
    ?>
    <main class="sg-content">

        <!-- Breadcrumb -->
        <?php get_template_part('template-parts/breadcrumb'); ?>

        <!-- Gallery: 非表示（一旦なし） -->

        <!-- Title -->
        <h1 class="sg-title"><?php the_title(); ?></h1>

        <!-- Meta -->
        <div class="sg-meta">
            <div class="sg-meta-item">
                <i class="fa-regular fa-calendar"></i>
                <?php echo get_the_date('Y.m.d'); ?>
            </div>
            <div class="sg-meta-divider"></div>
            <?php
            $post_cats = get_the_category();
            if ($post_cats) :
            ?>
            <div class="sg-meta-item">
                <i class="fa-regular fa-folder"></i>
                <a href="<?php echo esc_url(get_category_link($post_cats[0]->term_id)); ?>">
                    <?php echo esc_html($post_cats[0]->name); ?>
                </a>
            </div>
            <div class="sg-meta-divider"></div>
            <?php endif; ?>
            <div class="sg-meta-item">
                <i class="fa-solid fa-pen"></i>
                <?php the_author(); ?>
            </div>
        </div>

        <!-- Separator -->
        <div class="sg-meta-line"></div>

        <!-- Article body -->
        <div class="sg-body">
            <?php the_content(); ?>
        </div>

        <?php if ($sgnr_featured): ?>
        <div class="sgnr-inner">
        <div class="sgnr-divider"></div>
        <div class="sgnr-featured">
            <?php
            $f_permalink = esc_url(get_permalink($sgnr_featured->ID));
            $f_img       = get_the_post_thumbnail_url($sgnr_featured->ID, 'medium');
            $f_cats      = get_the_terms($sgnr_featured->ID, 'category');
            $f_cat       = ($f_cats && !is_wp_error($f_cats)) ? esc_html(strtoupper($f_cats[0]->name)) : '';
            $f_cat_link  = ($f_cats && !is_wp_error($f_cats)) ? esc_url(get_term_link($f_cats[0])) : '#';
            $f_date      = get_the_date('Y.m.d', $sgnr_featured->ID);
            ?>
            <div class="sgnr-featured-link">
                <a href="<?php echo $f_permalink; ?>" class="sgnr-featured-thumb-link">
                    <div class="sgnr-featured-thumb">
                        <?php if ($f_img): ?>
                            <img src="<?php echo esc_url($f_img); ?>" alt="<?php echo esc_attr($sgnr_featured->post_title); ?>" class="sgnr-featured-img" loading="lazy">
                        <?php else: ?>
                            <div class="sgnr-featured-noimg"></div>
                        <?php endif; ?>
                    </div>
                </a>
                <div class="sgnr-featured-info">
                    <a href="<?php echo $f_permalink; ?>" class="sgnr-featured-title-link">
                        <p class="sgnr-featured-title"><?php echo esc_html($sgnr_featured->post_title); ?></p>
                    </a>
                    <?php if ($f_cat || $f_date): ?>
                    <div class="sgnr-featured-meta">
                        <?php if ($f_cat): ?><a href="<?php echo $f_cat_link; ?>" class="sgnr-featured-cat-badge"><?php echo $f_cat; ?></a><?php endif; ?>
                        <?php if ($f_date): ?><span class="sgnr-featured-date"><?php echo esc_html($f_date); ?></span><?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="sgnr-divider"></div>
        </div><!-- /.sgnr-inner -->
        <?php endif; ?>

    </main>

</div><!-- /.sg-layout -->
</div><!-- /.sg-wrap -->

<?php if (!empty($sgnr_grid)): ?>
<div class="sgnr-grid-wrap">
    <div class="sgnr-grid">
        <?php foreach ($sgnr_grid as $sg_post):
            $sg_img  = get_the_post_thumbnail_url($sg_post->ID, 'medium_large');
            $sg_cats = get_the_terms($sg_post->ID, 'category');
            $sg_cat  = ($sg_cats && !is_wp_error($sg_cats)) ? esc_html(strtoupper($sg_cats[0]->name)) : '';
            $sg_date = get_the_date('Y.m.d', $sg_post->ID);
        ?>
        <div class="sgnr-card">
            <a href="<?php echo esc_url(get_permalink($sg_post->ID)); ?>">
                <div class="sgnr-card-thumb">
                    <?php if ($sg_img): ?>
                        <img src="<?php echo esc_url($sg_img); ?>" alt="<?php echo esc_attr($sg_post->post_title); ?>" class="sgnr-card-img" loading="lazy">
                    <?php else: ?>
                        <div class="sgnr-card-noimg"></div>
                    <?php endif; ?>
                </div>
                <div class="sgnr-card-body">
                    <p class="sgnr-card-title"><?php echo esc_html($sg_post->post_title); ?></p>
                    <div class="sgnr-card-meta">
                        <?php if ($sg_cat): ?><span class="sgnr-card-label"><?php echo $sg_cat; ?></span><?php endif; ?>
                        <?php if ($sg_date): ?><span class="sgnr-card-date"><?php echo esc_html($sg_date); ?></span><?php endif; ?>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php get_template_part('template-parts/footer-site'); ?>

<script>
(function(){
    // Gallery thumbnail switching
    var thumbs  = document.querySelectorAll('.sg-gallery-thumb');
    var mainImg = document.getElementById('sgMainImg');
    if (thumbs.length && mainImg) {
        thumbs.forEach(function(thumb) {
            thumb.addEventListener('click', function() {
                thumbs.forEach(function(t) { t.classList.remove('is-active'); });
                this.classList.add('is-active');
                mainImg.src = this.getAttribute('data-src');
            });
        });
    }

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
