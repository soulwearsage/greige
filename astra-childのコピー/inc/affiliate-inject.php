<?php
// 楽天API 商品画像取得（新エンドポイント）
function greige_rakuten_images($rakuten_url) {
    if (!$rakuten_url) return [];
    if (!preg_match('#item\.rakuten\.co\.jp/([^/?#]+)/([^/?#]+)#', $rakuten_url, $m)) return [];
    $item_code = $m[1] . ':' . $m[2];
    $cache_key = 'greige_rimg3_' . md5($item_code);
    $cached    = get_transient($cache_key);
    if ($cached !== false) return $cached;

    $api = 'https://openapi.rakuten.co.jp/ichibams/api/IchibaItem/Search/20260701?' . http_build_query([
        'format'        => 'json',
        'applicationId' => 'c632ac52-207f-4b01-886e-dd3082855c7b',
        'accessKey'     => 'pk_dr3Fe22me5VyEKWMlEew5t8eIWLV5sE63lfEGcdUosQ',
        'itemCode'      => $item_code,
    ]);

    $res = wp_remote_get($api, ['timeout' => 10]);
    if (is_wp_error($res)) return [];

    $data   = json_decode(wp_remote_retrieve_body($res), true);
    $images = [];
    $item   = $data['Items'][0]['Item'] ?? null;
    if ($item) {
        foreach (($item['largeImageUrls'] ?? $item['mediumImageUrls'] ?? []) as $u) {
            $images[] = is_array($u) ? $u['imageUrl'] : $u;
        }
    }

    if (!empty($images)) {
        set_transient($cache_key, $images, 12 * HOUR_IN_SECONDS);
    }
    return $images;
}

// greige_product ページにアフィリエイトカードを注入
add_action('wp_footer', function() {
    if (!is_singular('greige_product')) return;
    $pid      = get_the_ID();
    $rakuten  = get_post_meta($pid, 'affiliate_rakuten',  true);
    $amazon   = get_post_meta($pid, 'affiliate_amazon',   true);
    $yahoo    = get_post_meta($pid, 'affiliate_yahoo',    true);
    $official = get_post_meta($pid, 'affiliate_official', true) ?: get_post_meta($pid, 'product_url', true);
    if (!$rakuten && !$amazon && !$yahoo && !$official) return;

    $brand = get_post_meta($pid, 'product_brand', true);
    $price = get_post_meta($pid, 'product_price', true);

    // 楽天アイテムコード抽出（JS側でAPI呼び出しに使う）
    $rakuten_item_code = '';
    if ($rakuten && preg_match('#item\.rakuten\.co\.jp/([^/?#]+)/([^/?#]+)#', $rakuten, $m)) {
        $rakuten_item_code = $m[1] . ':' . $m[2];
    }

    // 初期表示用画像（WP画像・手動フィールド）
    // 楽天APIから複数画像取得（サーバーサイド）
    $img_urls = $rakuten ? greige_rakuten_images($rakuten) : [];

    // フォールバック：WP画像・手動フィールド
    if (empty($img_urls)) {
        if (has_post_thumbnail($pid)) $img_urls[] = get_the_post_thumbnail_url($pid, 'large');
    $ext = get_post_meta($pid, 'product_external_image', true);
    if ($ext && !in_array($ext, $img_urls)) $img_urls[] = $ext;
    $extra = get_post_meta($pid, 'product_extra_images', true);
    if ($extra) {
        foreach (array_filter(array_map('trim', explode("\n", $extra))) as $u) {
            if ($u && !in_array($u, $img_urls)) $img_urls[] = $u;
        }
    }
        foreach (get_attached_media('image', $pid) as $m2) {
            $u = wp_get_attachment_image_url($m2->ID, 'large');
            if ($u && !in_array($u, $img_urls)) $img_urls[] = $u;
        }
    }

    $alt      = esc_attr(get_the_title($pid));
    $main_img = !empty($img_urls) ? esc_url($img_urls[0]) : '';

    $img_html  = '<div class="aff-card-img">';
    $img_html .= '<img id="gp-main-img" src="' . $main_img . '" alt="' . $alt . '" loading="lazy" style="' . ($main_img ? '' : 'display:none') . '">';
    $img_html .= '<div class="aff-card-thumbs" id="gp-thumbs">';
    foreach ($img_urls as $i => $u) {
        $img_html .= '<img src="' . esc_url($u) . '" alt="' . $alt . '" loading="lazy" class="aff-thumb' . ($i === 0 ? ' is-active' : '') . '" data-src="' . esc_url($u) . '">';
    }
    $img_html .= '</div></div>';

    $brand_html = $brand ? '<p class="aff-card-brand">' . esc_html($brand) . '</p>' : '';
    $name_html  = '<p class="aff-card-name">' . esc_html(get_the_title($pid)) . '</p>';
    $price_html = $price ? '<div class="aff-card-price-row"><span class="aff-card-price-label">最安価格</span><span class="aff-card-price">¥' . number_format((int)$price) . '</span></div>' : '';

    $price_sub = $price ? '<span class="aff-btn-price">¥' . number_format((int)$price) . '</span>' : '';
    $btns = '';
    if ($rakuten)  $btns .= '<a href="' . esc_url($rakuten)  . '" class="aff-btn" target="_blank" rel="noopener noreferrer sponsored"><span class="aff-btn-label">楽天市場で詳細を見る</span>' . $price_sub . '</a>';
    if ($amazon)   $btns .= '<a href="' . esc_url($amazon)   . '" class="aff-btn" target="_blank" rel="noopener noreferrer sponsored"><span class="aff-btn-label">Amazonで詳細を見る</span>' . $price_sub . '</a>';
    if ($yahoo)    $btns .= '<a href="' . esc_url($yahoo)    . '" class="aff-btn" target="_blank" rel="noopener noreferrer sponsored"><span class="aff-btn-label">Yahoo!ショッピングで見る</span>' . $price_sub . '</a>';
    if ($official) $btns .= '<a href="' . esc_url($official) . '" class="aff-btn aff-btn--outline" target="_blank" rel="noopener noreferrer sponsored"><span class="aff-btn-label">公式サイトで詳細を見る</span></a>';

    $card_html = '<div class="aff-card">' . $img_html . '<div class="aff-card-body">' . $brand_html . $name_html . $price_html . '<div class="aff-card-btns">' . $btns . '</div></div></div>';

    $item_code_js = json_encode($rakuten_item_code);
    $alt_js       = json_encode($alt);

    echo '<script>(function(){
        var card = ' . json_encode($card_html) . ';
        var itemCode = ' . $item_code_js . ';
        var alt = ' . $alt_js . ';

        // カード挿入
        var body = document.querySelector(".sg-body");
        var fallback = document.querySelector(".sg-meta-line");
        var wrap = document.createElement("div");
        wrap.className = "gp-aff-inject";
        wrap.innerHTML = card;
        if (body) {
            var share = body.querySelector(".sharedaddy,.addtoany_share_save_container,.wp-block-jetpack-sharing-buttons,[class*=\"share\"],[class*=\"social\"]");
            if (share) { share.parentNode.insertBefore(wrap, share); }
            else { body.appendChild(wrap); }
        } else if (fallback) {
            fallback.parentNode.insertBefore(wrap, fallback.nextSibling);
        } else { return; }

        // サムネイルクリック
        function bindThumbs() {
            wrap.querySelectorAll(".aff-thumb").forEach(function(t){
                t.addEventListener("click", function(){
                    document.getElementById("gp-main-img").src = this.dataset.src;
                    wrap.querySelectorAll(".aff-thumb").forEach(function(x){ x.classList.remove("is-active"); });
                    this.classList.add("is-active");
                });
            });
        }
        bindThumbs();

        // 楽天APIから画像を追加取得
        if (!itemCode) return;
        var api = "https://openapi.rakuten.co.jp/ichibams/api/IchibaItem/Search/20260701"
            + "?format=json"
            + "&applicationId=c632ac52-207f-4b01-886e-dd3082855c7b"
            + "&accessKey=pk_dr3Fe22me5VyEKWMlEew5t8eIWLV5sE63lfEGcdUosQ"
            + "&itemCode=" + encodeURIComponent(itemCode);
        fetch(api)
            .then(function(r){ return r.json(); })
            .then(function(data){
                var item = data.Items && data.Items[0];
                if (!item) return;
                var urls = (item.largeImageUrls || item.mediumImageUrls || []).map(function(u){
                    return typeof u === "object" ? u.imageUrl : u;
                }).filter(Boolean);
                if (!urls.length) return;

                var mainImg = document.getElementById("gp-main-img");
                var thumbs  = document.getElementById("gp-thumbs");
                if (!mainImg || !thumbs) return;

                // メイン画像を楽天1枚目に差し替え
                mainImg.src = urls[0];
                mainImg.style.display = "";

                // サムネイルを楽天画像で上書き
                thumbs.innerHTML = "";
                urls.forEach(function(u, i){
                    var img = document.createElement("img");
                    img.src = u;
                    img.alt = alt;
                    img.loading = "lazy";
                    img.className = "aff-thumb" + (i === 0 ? " is-active" : "");
                    img.dataset.src = u;
                    thumbs.appendChild(img);
                });
                bindThumbs();
            })
            .catch(function(){});
    })();</script>';
});
