// ═══════════════════════════════════════════════════════════
//  GREIGE — SELECTED_PRODUCTS → WordPress 自動投稿スクリプト
//  GASエディタに貼り付けて実行してください
// ═══════════════════════════════════════════════════════════

const WP_CONFIG = {
  site_url:  'https://greige.online',
  username:  'AICO',
  app_pass:  's1Bt HN6m l66L uK01 9uOx NpjC',
};

const SHEET_ID = '1C4ljnBvsJmGzQe6aaWbdIUj4mXYjl7Mk3bPhdQfJQWw';

// ── メイン実行関数（手動で「pushProductsToWordPress」を実行） ──
function pushProductsToWordPress() {
  const ss    = SpreadsheetApp.openById(SHEET_ID);
  const sheet = ss.getSheetByName('SELECTED_PRODUCTS');
  if (!sheet) { Logger.log('シートが見つかりません'); return; }

  const headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
  const rows    = sheet.getRange(3, 1, sheet.getLastRow() - 2, sheet.getLastColumn()).getValues();

  const col = (name) => headers.iguandexOf(name);

  let pushed = 0;

  rows.forEach((row, i) => {
    const rowNum      = i + 3;
    const productId   = row[col('product_id')]   || '';
    const wpPostId    = row[col('wordpress_post_id')] || '';
    const wpStatus    = row[col('wordpress_status')]  || '';

    // すでに投稿済みならスキップ
    if (wpPostId && wpPostId !== '') {
      Logger.log(`行${rowNum}: ${productId} → 投稿済み (ID: ${wpPostId})、スキップ`);
      return;
    }

    const title       = row[col('product_name')]  || '';
    const brand       = row[col('brand')]         || '';
    const price       = String(row[col('price')]  || '');
    const imageUrl    = row[col('image_url')]     || '';
    const rakutenUrl  = row[col('product_url')]   || '';
    const excerpt     = row[col('ai_reason')]     || '';

    if (!title) { Logger.log(`行${rowNum}: タイトルなし、スキップ`); return; }

    Logger.log(`行${rowNum}: ${productId} "${title}" を投稿中...`);

    const postData = {
      title:   title,
      status:  'draft',
      excerpt: excerpt,
      meta: {
        product_brand:          brand,
        product_price:          price,
        product_external_image: imageUrl,
        affiliate_rakuten:      rakutenUrl,
        affiliate_amazon:       '',
        affiliate_yahoo:        '',
        affiliate_official:     '',
      },
    };

    const result = wpApiRequest('POST', '/wp/v2/greige_product', postData);

    if (result && result.id) {
      Logger.log(`  → 成功！ WordPress投稿ID: ${result.id} URL: ${result.link}`);
      // スプレッドシートに投稿IDとステータスを書き戻す
      const wpPostIdCol = col('wordpress_post_id');
      const wpStatusCol = col('wordpress_status');
      if (wpPostIdCol >= 0) sheet.getRange(rowNum, wpPostIdCol + 1).setValue(result.id);
      if (wpStatusCol >= 0) sheet.getRange(rowNum, wpStatusCol + 1).setValue('DRAFT');
      pushed++;
    } else {
      Logger.log(`  → 失敗: ${JSON.stringify(result)}`);
    }
  });

  Logger.log(`完了: ${pushed}件を投稿しました`);
  SpreadsheetApp.getUi().alert(`完了: ${pushed}件をWordPressに投稿しました（下書き）`);
}

// ── WP REST API リクエスト共通関数 ──
function wpApiRequest(method, endpoint, data) {
  const url     = WP_CONFIG.site_url + '/wp-json' + endpoint;
  const creds   = Utilities.base64Encode(WP_CONFIG.username + ':' + WP_CONFIG.app_pass);
  const options = {
    method:      method.toLowerCase(),
    headers:     {
      'Authorization': 'Basic ' + creds,
      'Content-Type':  'application/json',
    },
    muteHttpExceptions: true,
  };
  if (data) options.payload = JSON.stringify(data);

  const resp   = UrlFetchApp.fetch(url, options);
  const code   = resp.getResponseCode();
  const body   = resp.getContentText();
  Logger.log(`HTTP ${code}: ${body.slice(0, 300)}`);

  try { return JSON.parse(body); } catch(e) { return { error: body }; }
}

// ── テスト接続確認用（まずこちらを実行して接続チェック） ──
function testWpConnection() {
  const result = wpApiRequest('GET', '/wp/v2/greige_product?per_page=1', null);
  Logger.log('接続テスト結果: ' + JSON.stringify(result).slice(0, 500));
  SpreadsheetApp.getUi().alert('接続テスト完了。実行ログを確認してください。');
}
