<?php
/**
 * 比較テーブルコンポーネント
 *
 * EDIT記事内で [greige_compare] ショートコード経由で使用。
 * edit_products（選択商品）と edit_comparison_fields（表示項目）をもとに自動生成。
 *
 * 使い方：
 *   get_template_part('template-parts/components/comparison-table', null, [
 *       'post_id' => $post->ID,
 *   ]);
 */

$args    = isset($args) ? $args : [];
$post_id = isset($args['post_id']) ? (int) $args['post_id'] : (get_the_ID() ?: 0);

if (!$post_id) return;

// Block args take precedence; fall back to post meta / ACF for [greige_compare] shortcode
$product_ids    = isset($args['product_ids'])    ? $args['product_ids']    : null;
$compare_fields = isset($args['compare_fields']) ? $args['compare_fields'] : null;

if ($product_ids === null) {
    $product_ids = get_post_meta($post_id, 'greige_edit_products', true);
}
if ($compare_fields === null) {
    $compare_fields = get_field('edit_comparison_fields', $post_id);
}

if (!is_array($product_ids) || empty($product_ids)) return;
if (!is_array($compare_fields) || empty($compare_fields)) return;

// 公開済み商品のみ
$products = array_filter(array_map('get_post', $product_ids), function($p) {
    return $p && $p->post_status === 'publish';
});
if (empty($products)) return;

// 比較項目ラベル
$field_labels = [
    'product_price'       => '価格',
    'product_material'    => '素材',
    'product_color'       => 'カラー',
    'product_size'        => 'サイズ',
    'product_description' => '商品説明',
    'editor_comment'      => '編集コメント',
];
?>

<div class="gc-compare">
    <div class="gc-compare-scroll">
        <table class="gc-compare-table">
            <thead>
                <tr>
                    <th class="gc-th gc-th--label"></th>
                    <?php foreach ($products as $p) :
                        $brand = get_post_meta($p->ID, 'product_brand', true);
                    ?>
                    <th class="gc-th">
                        <?php if ($brand) : ?>
                            <span class="gc-th-brand"><?php echo esc_html($brand); ?></span>
                        <?php endif; ?>
                        <span class="gc-th-name"><?php echo esc_html($p->post_title); ?></span>
                    </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($compare_fields as $field_key) :
                    if (!isset($field_labels[$field_key])) continue;
                ?>
                <tr>
                    <td class="gc-td-label"><?php echo esc_html($field_labels[$field_key]); ?></td>
                    <?php foreach ($products as $p) :
                        $value = '';
                        if ($field_key === 'product_price') {
                            $price      = get_post_meta($p->ID, 'product_price', true);
                            $sale_price = get_post_meta($p->ID, 'product_sale_price', true);
                            $currency   = get_post_meta($p->ID, 'product_currency', true) ?: 'JPY';
                            if ($sale_price) {
                                $value = greige_format_price($price, $currency) . ' → ' . greige_format_price($sale_price, $currency);
                            } elseif ($price !== '' && $price !== false) {
                                $value = greige_format_price($price, $currency);
                            }
                        } else {
                            $value = get_post_meta($p->ID, $field_key, true);
                        }
                    ?>
                    <td class="gc-td">
                        <?php if ($value !== '' && $value !== false) : ?>
                            <?php echo nl2br(esc_html($value)); ?>
                        <?php else : ?>
                            <span class="gc-td-empty">—</span>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <?php
            // 購入行：いずれかの商品にURLがあれば表示
            $has_cta = false;
            foreach ($products as $_cp) {
                if (get_post_meta($_cp->ID, 'affiliate_url', true) || get_post_meta($_cp->ID, 'product_url', true)) {
                    $has_cta = true; break;
                }
            }
            if ($has_cta): ?>
            <tfoot>
                <tr class="gc-tr-cta">
                    <td class="gc-td-label">購入</td>
                    <?php foreach ($products as $_cp):
                        $_aff_url  = get_post_meta($_cp->ID, 'affiliate_url', true);
                        $_prod_url = get_post_meta($_cp->ID, 'product_url', true);
                        $_platform = get_post_meta($_cp->ID, 'affiliate_platform', true);
                        $_price    = get_post_meta($_cp->ID, 'product_price', true);
                        $_sale     = get_post_meta($_cp->ID, 'product_sale_price', true);
                        $_currency = get_post_meta($_cp->ID, 'product_currency', true) ?: 'JPY';
                        $_dp       = $_sale ?: $_price;
                        $_psuffix  = ($_dp !== '' && $_dp !== false) ? ' — ' . greige_format_price($_dp, $_currency) : '';
                        if ($_aff_url) {
                            if (mb_strpos((string)$_platform, '楽天') !== false)         $_dest = '楽天市場';
                            elseif (stripos((string)$_platform, 'amazon') !== false)      $_dest = 'Amazon';
                            elseif (stripos((string)$_platform, 'yahoo') !== false)       $_dest = 'Yahoo!ショッピング';
                            else                                                            $_dest = '公式サイト';
                            $_cta_url   = $_aff_url;
                            $_cta_label = $_dest . 'で見る' . $_psuffix;
                        } elseif ($_prod_url) {
                            $_cta_url   = $_prod_url;
                            $_cta_label = '公式サイトで見る' . $_psuffix;
                        } else {
                            $_cta_url   = '';
                            $_cta_label = '';
                        }
                    ?>
                    <td class="gc-td gc-td-cta">
                        <?php if ($_cta_url): ?>
                            <a href="<?php echo esc_url($_cta_url); ?>" class="gc-cta-link" target="_blank" rel="noopener noreferrer"><?php echo esc_html($_cta_label); ?></a>
                        <?php else: ?>
                            <span class="gc-td-empty">—</span>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>
