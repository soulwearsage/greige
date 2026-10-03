<?php
/**
 * アフィリエイトカード共通テンプレート
 * 呼び出し方：
 *   get_template_part('template-parts/affiliate-card', null, ['card' => $card]);
 *
 * $card配列のキー:
 *   product_id  (int)    greige_product の投稿ID（指定時、未設定フィールドを自動補完）
 *   name        (string) 商品名
 *   brand       (string) ブランド名
 *   image       (string) 商品画像URL
 *   price       (string) 価格（数値文字列、例: "4400" or "4,400"）
 *   amazon      (string) Amazon アフィリエイトURL
 *   rakuten     (string) 楽天市場 アフィリエイトURL
 *   yahoo       (string) Yahoo!ショッピング アフィリエイトURL
 *   official    (string) 公式サイト アフィリエイトURL
 *   note        (string) 補足テキスト（任意）
 *   label_amazon   / label_rakuten / label_yahoo / label_official  ボタンラベル上書き
 */

$card = $args['card'] ?? [];

// $args が渡らなかった場合、現在の greige_product 投稿から自動取得
if (empty($card['amazon']) && empty($card['rakuten']) && empty($card['yahoo']) && empty($card['official'])) {
    $pid = get_the_ID();
    if ($pid && get_post_type($pid) === 'greige_product') {
        $card['amazon']   = get_post_meta($pid, 'affiliate_amazon', true)   ?: '';
        $card['rakuten']  = get_post_meta($pid, 'affiliate_rakuten', true)  ?: '';
        $card['yahoo']    = get_post_meta($pid, 'affiliate_yahoo', true)    ?: '';
        $card['official'] = get_post_meta($pid, 'affiliate_official', true) ?: (get_post_meta($pid, 'product_url', true) ?: '');
    }
}

// product_id が指定されていれば greige_product の投稿メタから補完
if (!empty($card['product_id'])) {
    $pid = (int) $card['product_id'];
    $p   = get_post($pid);
    if ($p && $p->post_type === 'greige_product') {
        $card['name']     = $card['name']     ?: $p->post_title;
        $card['brand']    = $card['brand']    ?: get_post_meta($pid, 'product_brand', true);
        $card['price']    = $card['price']    ?: get_post_meta($pid, 'product_price', true);
        $card['amazon']   = $card['amazon']   ?: get_post_meta($pid, 'affiliate_amazon', true);
        $card['rakuten']  = $card['rakuten']  ?: get_post_meta($pid, 'affiliate_rakuten', true);
        $card['yahoo']    = $card['yahoo']    ?: get_post_meta($pid, 'affiliate_yahoo', true);
        $card['official'] = $card['official'] ?: get_post_meta($pid, 'affiliate_official', true);
        if (empty($card['image'])) {
            if (has_post_thumbnail($pid)) {
                $card['image'] = get_the_post_thumbnail_url($pid, 'large');
            } else {
                $card['image'] = get_post_meta($pid, 'product_external_image', true);
            }
        }
    }
}

$name     = trim($card['name']     ?? '');
$brand    = trim($card['brand']    ?? '');
$image    = trim($card['image']    ?? '');
$price    = trim($card['price']    ?? '');
$amazon   = trim($card['amazon']   ?? '');
$rakuten  = trim($card['rakuten']  ?? '');
$yahoo    = trim($card['yahoo']    ?? '');
$official = trim($card['official'] ?? '');
$note     = trim($card['note']     ?? '');

$label_amazon   = trim($card['label_amazon']   ?? '') ?: 'Amazonで詳細を見る';
$label_rakuten  = trim($card['label_rakuten']  ?? '') ?: '楽天市場で詳細を見る';
$label_yahoo    = trim($card['label_yahoo']    ?? '') ?: 'Yahoo!ショッピングで見る';
$label_official = trim($card['label_official'] ?? '') ?: '公式サイトで詳細を見る';

$has_links = $amazon || $rakuten || $yahoo || $official;
if (!$name && !$has_links) return;

// 価格を数値フォーマット（カンマ区切り）
$price_display = '';
if ($price !== '') {
    $price_num = (float) preg_replace('/[^0-9.]/', '', $price);
    $price_display = $price_num > 0 ? number_format($price_num) : $price;
}
?>
<div class="aff-card">

    <div class="aff-card-img<?php echo $image ? '' : ' aff-card-img--empty'; ?>">
        <?php if ($image): ?>
        <img src="<?php echo esc_url($image); ?>"
             alt="<?php echo esc_attr($name ?: $brand); ?>"
             loading="lazy">
        <?php endif; ?>
    </div>

    <div class="aff-card-body">

        <?php if ($brand): ?>
        <p class="aff-card-brand"><?php echo esc_html($brand); ?></p>
        <?php endif; ?>

        <?php if ($name): ?>
        <p class="aff-card-name"><?php echo esc_html($name); ?></p>
        <?php endif; ?>

        <?php if ($note): ?>
        <p class="aff-card-note"><?php echo nl2br(esc_html($note)); ?></p>
        <?php endif; ?>

        <?php if ($price_display): ?>
        <div class="aff-card-price-row">
            <span class="aff-card-price-label">最安価格</span>
            <span class="aff-card-price">¥<?php echo esc_html($price_display); ?></span>
        </div>
        <?php endif; ?>

        <?php if ($has_links): ?>
        <div class="aff-card-btns">
            <?php if ($rakuten): ?>
            <a href="<?php echo esc_url($rakuten); ?>"
               class="aff-btn"
               target="_blank"
               rel="noopener noreferrer sponsored"><?php echo esc_html($label_rakuten); ?></a>
            <?php endif; ?>
            <?php if ($amazon): ?>
            <a href="<?php echo esc_url($amazon); ?>"
               class="aff-btn"
               target="_blank"
               rel="noopener noreferrer sponsored"><?php echo esc_html($label_amazon); ?></a>
            <?php endif; ?>
            <?php if ($yahoo): ?>
            <a href="<?php echo esc_url($yahoo); ?>"
               class="aff-btn"
               target="_blank"
               rel="noopener noreferrer sponsored"><?php echo esc_html($label_yahoo); ?></a>
            <?php endif; ?>
            <?php if ($official): ?>
            <a href="<?php echo esc_url($official); ?>"
               class="aff-btn aff-btn--outline"
               target="_blank"
               rel="noopener noreferrer sponsored"><?php echo esc_html($label_official); ?></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div><!-- /.aff-card-body -->

</div><!-- /.aff-card -->
