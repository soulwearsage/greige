<?php
/**
 * パンくずリスト - 全ページ共通
 * ページタイプを自動判定して生成する
 */
$home = home_url('/');

function greige_bc_title($title) {
    $limit = 40;
    if (wp_is_mobile()) {
        $limit = 18;
    } elseif (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/tablet|ipad/i', $_SERVER['HTTP_USER_AGENT'])) {
        $limit = 28;
    }
    return mb_strimwidth($title, 0, $limit, '...', 'UTF-8');
}
?>
<nav class="sg-bc" aria-label="パンくずリスト">

<?php if (is_singular('greige_product')) :
    $terms = get_the_terms(get_the_ID(), 'product_category');
?>
    <a href="<?php echo $home; ?>">HOME</a>
    <span class="sg-bc-sep">/</span>
    <?php if ($terms && !is_wp_error($terms)) : ?>
    <a href="<?php echo esc_url(get_term_link($terms[0])); ?>"><?php echo esc_html(strtoupper($terms[0]->name)); ?></a>
    <span class="sg-bc-sep">/</span>
    <?php endif; ?>
    <span class="sg-bc-current"><?php echo esc_html(greige_bc_title(get_the_title())); ?></span>

<?php elseif (is_tax('product_category')) :
    $term = get_queried_object();
?>
    <a href="<?php echo $home; ?>">HOME</a>
    <span class="sg-bc-sep">/</span>
    <a href="<?php echo esc_url(get_post_type_archive_link('greige_product') ?: $home); ?>">PRODUCT</a>
    <span class="sg-bc-sep">/</span>
    <span class="sg-bc-current"><?php echo esc_html(strtoupper($term->name)); ?></span>

<?php elseif (is_singular('greige_edit')) :
    $terms = get_the_terms(get_the_ID(), 'edit_category');
?>
    <a href="<?php echo $home; ?>">HOME</a>
    <span class="sg-bc-sep">/</span>
    <?php if ($terms && !is_wp_error($terms)) : ?>
    <a href="<?php echo esc_url(get_term_link($terms[0])); ?>"><?php echo esc_html(strtoupper($terms[0]->name)); ?></a>
    <span class="sg-bc-sep">/</span>
    <?php endif; ?>
    <span class="sg-bc-current"><?php echo esc_html(greige_bc_title(get_the_title())); ?></span>

<?php elseif (is_tax('edit_category')) :
    $term = get_queried_object();
?>
    <a href="<?php echo $home; ?>">HOME</a>
    <span class="sg-bc-sep">/</span>
    <a href="<?php echo esc_url(home_url('/edit/')); ?>">EDIT</a>
    <span class="sg-bc-sep">/</span>
    <span class="sg-bc-current"><?php echo esc_html(strtoupper($term->name)); ?></span>

<?php elseif (is_singular()) :
    $cats = get_the_category();
?>
    <a href="<?php echo $home; ?>">HOME</a>
    <span class="sg-bc-sep">/</span>
    <?php if ($cats) : ?>
    <a href="<?php echo esc_url(get_category_link($cats[0]->term_id)); ?>"><?php echo esc_html(strtoupper($cats[0]->name)); ?></a>
    <span class="sg-bc-sep">/</span>
    <?php endif; ?>
    <span class="sg-bc-current"><?php echo esc_html(greige_bc_title(get_the_title())); ?></span>

<?php endif; ?>

</nav>
