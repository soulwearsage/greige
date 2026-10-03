<?php
/**
 * PR / アフィリエイト表示コンポーネント
 *
 * EDIT記事で edit_pr_display が true の場合に表示。
 *
 * 使い方：
 *   get_template_part('template-parts/components/pr-notice', null, [
 *       'post_id' => $post->ID,
 *   ]);
 */

$args    = isset($args) ? $args : [];
$post_id = isset($args['post_id']) ? (int) $args['post_id'] : (get_the_ID() ?: 0);

if (!$post_id) return;

$pr_display = get_field('edit_pr_display', $post_id);
if (!$pr_display) return;

$pr_text = get_field('edit_pr_text', $post_id);
$pr_text = ($pr_text && trim($pr_text))
    ? trim($pr_text)
    : '※本記事にはプロモーションが含まれます。';
?>
<div class="gpr-notice">
    <p class="gpr-text"><?php echo esc_html($pr_text); ?></p>
</div>
