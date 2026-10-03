<?php
/**
 * EDIT コンテンツブロック レンダラー
 *
 * ACF Flexible Content「edit_content_blocks」の各ブロックを
 * 管理画面で並べた順番通りにフロントへ出力する。
 *
 * 使い方：
 *   get_template_part('template-parts/components/edit-blocks', null, [
 *       'post_id' => $post->ID,
 *   ]);
 *
 * 対応ブロック：
 *   block_text / block_heading / block_image /
 *   block_product / block_product_group /
 *   block_editor_comment / block_comparison
 */

$args    = isset($args) ? $args : [];
$post_id = isset($args['post_id']) ? (int) $args['post_id'] : (get_the_ID() ?: 0);

if (!$post_id) return;
if (!function_exists('have_rows') || !function_exists('get_field')) return;

$_blocks_check = get_field('edit_content_blocks', $post_id);
if (empty($_blocks_check)) return;
?>
<div class="geb-blocks">
<?php while (have_rows('edit_content_blocks', $post_id)): the_row();
    $layout = get_row_layout();
    switch ($layout):

        // ── TEXT ───────────────────────────────────────────────────────────────
        case 'block_text':
            $content = get_sub_field('text_content');
            if ($content): ?>
            <div class="geb-text"><?php echo wp_kses_post($content); ?></div>
            <?php endif;
            break;

        // ── HEADING ────────────────────────────────────────────────────────────
        case 'block_heading':
            $text  = get_sub_field('heading_text');
            $level = get_sub_field('heading_level') ?: 'h2';
            $level = in_array($level, ['h2', 'h3', 'h4'], true) ? $level : 'h2';
            if ($text): ?>
            <<?php echo $level; ?> class="geb-<?php echo esc_attr($level); ?>"><?php echo esc_html($text); ?></<?php echo $level; ?>>
            <?php endif;
            break;

        // ── IMAGE ──────────────────────────────────────────────────────────────
        case 'block_image':
            $image   = get_sub_field('block_image_file');
            $caption = get_sub_field('image_caption');
            if ($image && !empty($image['url'])): ?>
            <div class="geb-image">
                <img src="<?php echo esc_url($image['url']); ?>"
                     alt="<?php echo esc_attr($image['alt'] ?: ($caption ?: '')); ?>"
                     <?php if (!empty($image['width']))  echo 'width="'  . esc_attr($image['width'])  . '"'; ?>
                     <?php if (!empty($image['height'])) echo 'height="' . esc_attr($image['height']) . '"'; ?>
                     loading="lazy">
                <?php if ($caption): ?>
                <p class="geb-caption"><?php echo esc_html($caption); ?></p>
                <?php endif; ?>
            </div>
            <?php endif;
            break;

        // ── PRODUCT ────────────────────────────────────────────────────────────
        case 'block_product':
            $pid = (int) get_sub_field('product_ref');
            if ($pid): ?>
            <div class="geb-product">
                <?php get_template_part('template-parts/components/product-card', null, [
                    'product_id' => $pid,
                    'inline'     => false,
                ]); ?>
            </div>
            <?php endif;
            break;

        // ── PRODUCT GROUP ──────────────────────────────────────────────────────
        case 'block_product_group':
            $rows = get_sub_field('products');
            if ($rows && is_array($rows)):
                $pids = array_values(array_filter(array_map(function($r) {
                    return !empty($r['product_ref']) ? (int) $r['product_ref'] : 0;
                }, $rows)));
                if (!empty($pids)): ?>
                <div class="geb-pg">
                    <?php foreach ($pids as $pid): ?>
                    <?php get_template_part('template-parts/components/product-card', null, [
                        'product_id' => $pid,
                        'inline'     => false,
                    ]); ?>
                    <?php endforeach; ?>
                </div>
                <?php endif;
            endif;
            break;

        // ── EDITOR COMMENT ─────────────────────────────────────────────────────
        case 'block_editor_comment':
            $comment = get_sub_field('comment_text');
            if ($comment): ?>
            <div class="geb-ec">
                <span class="geb-ec-label">EDITOR COMMENT</span>
                <p class="geb-ec-text"><?php echo nl2br(esc_html($comment)); ?></p>
            </div>
            <?php endif;
            break;

        // ── COMPARISON TABLE ───────────────────────────────────────────────────
        case 'block_comparison':
            $rows   = get_sub_field('compare_products');
            $fields = get_sub_field('compare_fields');
            $pids   = [];
            if ($rows && is_array($rows)) {
                foreach ($rows as $r) {
                    if (!empty($r['product_ref'])) {
                        $pids[] = (int) $r['product_ref'];
                    }
                }
            }
            if (!empty($pids) && !empty($fields)) {
                get_template_part('template-parts/components/comparison-table', null, [
                    'post_id'        => $post_id,
                    'product_ids'    => $pids,
                    'compare_fields' => (array) $fields,
                ]);
            }
            break;

    endswitch;
endwhile; ?>
</div>
