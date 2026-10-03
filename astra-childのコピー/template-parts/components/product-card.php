<?php
/**
 * 商品表示コンポーネント
 *
 * 使い方：
 *   get_template_part('template-parts/components/product-card', null, [
 *       'product_id' => $id,
 *       'inline'     => false,  // true でインラインコンパクト表示
 *   ]);
 *
 * ショートコード経由：[greige_product id="123"]
 */

$args       = isset($args) ? $args : [];
$product_id = isset($args['product_id']) ? (int) $args['product_id'] : 0;
$is_inline  = !empty($args['inline']);

if (!$product_id) return;
$product = get_post($product_id);
if (!$product || $product->post_type !== 'greige_product') return;

// カスタムフィールド取得
$brand      = get_post_meta($product_id, 'product_brand', true);
$price      = get_post_meta($product_id, 'product_price', true);
$sale_price = get_post_meta($product_id, 'product_sale_price', true);
$currency   = get_post_meta($product_id, 'product_currency', true) ?: 'JPY';
$aff_url      = get_post_meta($product_id, 'affiliate_url', true);
$prod_url     = get_post_meta($product_id, 'product_url', true);
$aff_platform = get_post_meta($product_id, 'affiliate_platform', true);
$material     = get_post_meta($product_id, 'product_material', true);
$color        = get_post_meta($product_id, 'product_color', true);
$size         = get_post_meta($product_id, 'product_size', true);
$ext_image    = get_post_meta($product_id, 'product_external_image', true);
$ed_comment   = get_post_meta($product_id, 'editor_comment', true);

// CTA 構築（実データのみ使用）
$_display_price = $sale_price ?: $price;
$_price_suffix  = ($_display_price !== '' && $_display_price !== false)
    ? ' — ' . greige_format_price($_display_price, $currency) : '';
if ($aff_url) {
    if (mb_strpos((string) $aff_platform, '楽天') !== false)          $_dest = '楽天市場';
    elseif (stripos((string) $aff_platform, 'amazon') !== false)       $_dest = 'Amazon';
    elseif (stripos((string) $aff_platform, 'yahoo') !== false)        $_dest = 'Yahoo!ショッピング';
    else                                                                 $_dest = '公式サイト';
    $cta_url   = $aff_url;
    $cta_label = $_dest . 'で見る' . $_price_suffix;
} elseif ($prod_url) {
    $cta_url   = $prod_url;
    $cta_label = '公式サイトで見る' . $_price_suffix;
} else {
    $cta_url   = '';
    $cta_label = '';
}

// 画像（アイキャッチ優先、なければ外部URL）
$img_src = has_post_thumbnail($product_id)
    ? get_the_post_thumbnail_url($product_id, 'product-card')
    : $ext_image;

$wrap_class = $is_inline ? 'gp-card gp-card--inline' : 'gp-card';
?>
<div class="<?php echo esc_attr($wrap_class); ?>">
    <div class="gp-card-inner">

        <div class="gp-card-img">
            <?php if ($img_src) : ?>
                <img src="<?php echo esc_url($img_src); ?>"
                     alt="<?php echo esc_attr($product->post_title); ?>"
                     loading="lazy">
            <?php else : ?>
                <div class="gp-card-img-placeholder"></div>
            <?php endif; ?>
        </div>

        <div class="gp-card-info">
            <?php if ($brand) : ?>
                <p class="gp-card-brand"><?php echo esc_html($brand); ?></p>
            <?php endif; ?>

            <p class="gp-card-title"><?php echo esc_html($product->post_title); ?></p>

            <?php if ($price || $sale_price) : ?>
                <p class="gp-card-price">
                    <?php if ($sale_price) : ?>
                        <span class="gp-card-price-original"><?php echo greige_format_price($price, $currency); ?></span>
                        <span class="gp-card-price-sale"><?php echo greige_format_price($sale_price, $currency); ?></span>
                    <?php else : ?>
                        <span class="gp-card-price-regular"><?php echo greige_format_price($price, $currency); ?></span>
                    <?php endif; ?>
                </p>
            <?php endif; ?>

            <?php if ($material) : ?>
                <p class="gp-card-attr"><span class="gp-card-attr-label">素材</span><?php echo esc_html($material); ?></p>
            <?php endif; ?>
            <?php if ($color) : ?>
                <p class="gp-card-attr"><span class="gp-card-attr-label">カラー</span><?php echo esc_html($color); ?></p>
            <?php endif; ?>
            <?php if ($size) : ?>
                <p class="gp-card-attr"><span class="gp-card-attr-label">サイズ</span><?php echo esc_html($size); ?></p>
            <?php endif; ?>

            <?php if ($ed_comment && !$is_inline) : ?>
                <p class="gp-card-comment"><?php echo esc_html($ed_comment); ?></p>
            <?php endif; ?>

            <?php if ($cta_url) : ?>
                <a href="<?php echo esc_url($cta_url); ?>"
                   class="gp-card-cta"
                   target="_blank"
                   rel="noopener noreferrer"><?php echo esc_html($cta_label); ?></a>
            <?php endif; ?>
        </div>

    </div>
</div>
