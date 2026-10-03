<?php
/**
 * SHOP THE EDIT — 記事末尾商品一覧コンポーネント
 *
 * 使い方：
 *   get_template_part('template-parts/components/shop-the-edit', null, [
 *       'post_id' => $post->ID,
 *   ]);
 */

$args    = isset($args) ? $args : [];
$post_id = isset($args['post_id']) ? (int) $args['post_id'] : (get_the_ID() ?: 0);

if (!$post_id) return;

$product_ids = get_post_meta($post_id, 'greige_edit_products', true);
if (!is_array($product_ids) || empty($product_ids)) return;

// 公開済み商品のみに絞る
$product_ids = array_filter($product_ids, function($id) {
    return get_post_status($id) === 'publish';
});
if (empty($product_ids)) return;

$section_title = get_field('edit_shop_title', $post_id);
$section_title = ($section_title && trim($section_title)) ? trim($section_title) : 'SHOP THE EDIT';
?>

<section class="ste-section">
    <div class="ste-header">
        <span class="ste-title"><?php echo esc_html($section_title); ?></span>
    </div>
    <div class="ste-grid">
        <?php foreach ($product_ids as $pid) :
            $pid = (int) $pid;
            if (!$pid) continue;
        ?>
        <div class="ste-item">
            <?php get_template_part('template-parts/components/product-card', null, [
                'product_id' => $pid,
                'inline'     => false,
            ]); ?>
        </div>
        <?php endforeach; ?>
    </div>
</section>
