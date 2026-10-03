/**
 * GREIGE MAGAZINE - PRODUCT DB → WordPress greige_product 同期
 *
 * ■ Script Properties に設定が必要な値（ファイル → プロジェクトの設定 → スクリプト プロパティ）
 *   WP_USER         : WordPress ユーザー名
 *   WP_APP_PASS     : WordPress アプリケーションパスワード（スペースなし）
 *   SPREADSHEET_ID  : スプレッドシートID（省略時：このスクリプトが紐付いたシート）
 *   SHEET_NAME      : シート名（省略時：PRODUCTS）
 *
 * ■ 使い方
 *   1. 初回のみ「WordPress 同期」→「列をセットアップ」を実行
 *   2. 記事が完成したら article_status を APPROVED にする
 *   3. 「ドラフト投稿（APPROVED行）」を実行
 *   4. 投稿完了行に wordpress_post_id / wordpress_url / wordpress_status が書き込まれる
 */

var WP_SITE = 'https://greige.online';
var WP_CPT  = 'greige_product';

// product_category タクソノミー: カテゴリー名 → WordPress term ID
var WP_CATEGORY_MAP = {
  'FASHION':    13,
  'LIFE STYLE': 16,
  'LIFESTYLE':  16,
  'WELLBEING':  18,
  'TOPS':       14,
  'BOTTOMS':    15,
  'T-SHIRT':    39,
  'INTERIOR':   17,
  'HAIR CARE':  20,
};

// スプレッドシートに追加が必要な列（初回セットアップ用）
var REQUIRED_COLUMNS = [
  'article_title',
  'article_body',
  'article_excerpt',
  'slug',
  'featured_image_id',
  'affiliate_enabled',
  'affiliate_cta',
  'wp_category',
  'wp_tags',
  'article_status',
  'wordpress_status',
  'wordpress_post_id',
  'wordpress_url',
  'updated_at',
];

// ─────────────────────────────────────────────────────────────
// カスタムメニュー
// ─────────────────────────────────────────────────────────────

function onOpen() {
  SpreadsheetApp.getActiveSpreadsheet().addMenu('WordPress 同期', [
    { name: '▶ ドラフト投稿（APPROVED行）', functionName: 'syncToWordPress' },
    { name: '▶ ドライラン（投稿せず確認）',  functionName: 'dryRunSync'      },
    { name: '─────────────────',            functionName: '_noop'            },
    { name: '⚙ 列をセットアップ（初回のみ）', functionName: 'setupColumns'   },
  ]);
}

function _noop() {}

// ─────────────────────────────────────────────────────────────
// Script Properties
// ─────────────────────────────────────────────────────────────

function getProps() {
  var p = PropertiesService.getScriptProperties();
  return {
    wpUser:   p.getProperty('WP_USER')        || '',
    wpPass:   p.getProperty('WP_APP_PASS')    || '',
    sheetId:  p.getProperty('SPREADSHEET_ID') || '',
    sheetTab: p.getProperty('SHEET_NAME')     || 'PRODUCTS',
  };
}

function getAuthHeader(props) {
  return 'Basic ' + Utilities.base64Encode(props.wpUser + ':' + props.wpPass);
}

// ─────────────────────────────────────────────────────────────
// スプレッドシートアクセス
// ─────────────────────────────────────────────────────────────

function getSheet() {
  var props = getProps();
  var ss = props.sheetId
    ? SpreadsheetApp.openById(props.sheetId)
    : SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(props.sheetTab);
  return sheet;
}

function getHeaderMap(sheet) {
  var headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
  var map = {};
  headers.forEach(function(h, i) {
    if (h) map[String(h).trim()] = i;
  });
  return map;
}

// ─────────────────────────────────────────────────────────────
// 初回セットアップ：不足列を追加
// ─────────────────────────────────────────────────────────────

function setupColumns() {
  var sheet = getSheet();
  if (!sheet) {
    SpreadsheetApp.getUi().alert('シートが見つかりません。Script Properties の SHEET_NAME を確認してください。');
    return;
  }

  var lastCol = sheet.getLastColumn();
  var headers = sheet.getRange(1, 1, 1, lastCol).getValues()[0];
  var existing = headers.map(function(h) { return String(h).trim(); });
  var added = [];

  REQUIRED_COLUMNS.forEach(function(col) {
    if (existing.indexOf(col) === -1) {
      lastCol += 1;
      sheet.getRange(1, lastCol).setValue(col);
      added.push(col);
    }
  });

  if (added.length > 0) {
    SpreadsheetApp.getUi().alert('追加した列:\n' + added.join('\n'));
  } else {
    SpreadsheetApp.getUi().alert('必要な列はすべて存在しています。');
  }
}

// ─────────────────────────────────────────────────────────────
// WordPress REST API ヘルパー
// ─────────────────────────────────────────────────────────────

function wpFetch(path, method, payload, props) {
  var options = {
    method: method,
    headers: { Authorization: getAuthHeader(props) },
    muteHttpExceptions: true,
  };
  if (payload) {
    options.contentType = 'application/json';
    options.payload = JSON.stringify(payload);
  }
  var res = UrlFetchApp.fetch(WP_SITE + '/wp-json/wp/v2/' + path, options);
  return {
    code: res.getResponseCode(),
    body: JSON.parse(res.getContentText()),
  };
}

function resolveCategoryIds(categoryStr) {
  if (!categoryStr) return [];
  var ids = [];
  String(categoryStr).split(/[,、]/).forEach(function(name) {
    var key = name.trim().toUpperCase();
    if (WP_CATEGORY_MAP[key] !== undefined) {
      ids.push(WP_CATEGORY_MAP[key]);
    }
  });
  return ids;
}

function resolveTagIds(tagStr, props) {
  if (!tagStr) return [];
  var ids = [];
  String(tagStr).split(/[,、]/).forEach(function(name) {
    var trimmed = name.trim();
    if (!trimmed) return;
    try {
      var searchRes = UrlFetchApp.fetch(
        WP_SITE + '/wp-json/wp/v2/tags?search=' + encodeURIComponent(trimmed) + '&per_page=1',
        { headers: { Authorization: getAuthHeader(props) }, muteHttpExceptions: true }
      );
      var found = JSON.parse(searchRes.getContentText());
      if (found.length > 0 && found[0].name === trimmed) {
        ids.push(found[0].id);
      } else {
        // 新規タグ作成
        var createRes = UrlFetchApp.fetch(WP_SITE + '/wp-json/wp/v2/tags', {
          method: 'post',
          contentType: 'application/json',
          headers: { Authorization: getAuthHeader(props) },
          payload: JSON.stringify({ name: trimmed }),
          muteHttpExceptions: true,
        });
        var newTag = JSON.parse(createRes.getContentText());
        if (newTag.id) ids.push(newTag.id);
      }
    } catch(e) {
      // タグ解決失敗は無視して続行
    }
  });
  return ids;
}

// ─────────────────────────────────────────────────────────────
// ペイロード生成
// ─────────────────────────────────────────────────────────────

function buildPayload(row, hmap, props) {
  var articleBody      = String(row[hmap['article_body']]      || '');
  var affiliateEnabled = String(row[hmap['affiliate_enabled']] || '').trim().toUpperCase();
  var affiliateCta     = String(row[hmap['affiliate_cta']]     || '').trim();

  // アフィリエイトCTA追記
  if ((affiliateEnabled === 'TRUE' || affiliateEnabled === '1' || affiliateEnabled === 'YES') && affiliateCta) {
    articleBody += '\n\n' + affiliateCta;
  }

  var payload = {
    status:  'draft',
    title:   String(row[hmap['article_title']]   || '').trim(),
    content: articleBody,
    excerpt: String(row[hmap['article_excerpt']] || '').trim(),
  };

  var slug = String(row[hmap['slug']] || '').trim();
  if (slug) payload.slug = slug;

  var featuredId = row[hmap['featured_image_id']];
  if (featuredId && !isNaN(Number(featuredId)) && Number(featuredId) > 0) {
    payload.featured_media = Number(featuredId);
  }

  var catIds = resolveCategoryIds(row[hmap['wp_category']]);
  if (catIds.length > 0) payload['product_category'] = catIds;

  var tagIds = resolveTagIds(row[hmap['wp_tags']], props);
  if (tagIds.length > 0) payload.tags = tagIds;

  return payload;
}

// ─────────────────────────────────────────────────────────────
// メイン同期処理
// ─────────────────────────────────────────────────────────────

function syncToWordPress() { _runSync(false); }
function dryRunSync()       { _runSync(true);  }

function _runSync(isDryRun) {
  var props = getProps();

  if (!props.wpUser || !props.wpPass) {
    SpreadsheetApp.getUi().alert(
      'Script Properties に WP_USER と WP_APP_PASS を設定してください。\n' +
      '（ファイル → プロジェクトの設定 → スクリプト プロパティ）'
    );
    return;
  }

  var sheet = getSheet();
  if (!sheet) {
    SpreadsheetApp.getUi().alert(
      'シート「' + getProps().sheetTab + '」が見つかりません。\n' +
      'SHEET_NAME の設定を確認してください。'
    );
    return;
  }

  var lastRow = sheet.getLastRow();
  if (lastRow < 2) {
    SpreadsheetApp.getUi().alert('データがありません。');
    return;
  }

  var hmap = getHeaderMap(sheet);

  // 必須列チェック
  if (hmap['article_status'] === undefined || hmap['article_title'] === undefined) {
    SpreadsheetApp.getUi().alert(
      '必須列が見つかりません。\n先に「列をセットアップ」を実行してください。'
    );
    return;
  }

  var allRows   = sheet.getRange(2, 1, lastRow - 1, sheet.getLastColumn()).getValues();
  var results   = [];
  var processed = 0;
  var skipped   = 0;

  allRows.forEach(function(row, idx) {
    var articleStatus = String(row[hmap['article_status']]   || '').trim().toUpperCase();
    var wpStatus      = String(row[hmap['wordpress_status']] || '').trim().toUpperCase();
    var wpPostId      = row[hmap['wordpress_post_id']];
    var articleTitle  = String(row[hmap['article_title']]    || '').trim();

    // 対象外はスキップ
    if (articleStatus !== 'APPROVED') { skipped++; return; }
    if (wpStatus === 'PUBLISHED')     { skipped++; return; }
    if (!articleTitle)                { skipped++; return; }

    processed++;
    var label = articleTitle.length > 30 ? articleTitle.slice(0, 30) + '…' : articleTitle;

    if (isDryRun) {
      results.push((wpPostId ? '更新' : '新規') + ': ' + label);
      return;
    }

    try {
      var payload  = buildPayload(row, hmap, props);
      var endpoint = wpPostId ? WP_CPT + '/' + wpPostId : WP_CPT;
      var result   = wpFetch(endpoint, 'post', payload, props);
      var sheetRow = idx + 2;

      if (result.code === 200 || result.code === 201) {
        var wpId  = result.body.id;
        var wpUrl = result.body.link;
        sheet.getRange(sheetRow, hmap['wordpress_post_id'] + 1).setValue(wpId);
        sheet.getRange(sheetRow, hmap['wordpress_url']     + 1).setValue(wpUrl);
        sheet.getRange(sheetRow, hmap['wordpress_status']  + 1).setValue('DRAFT');
        sheet.getRange(sheetRow, hmap['updated_at']        + 1).setValue(new Date().toISOString());
        results.push('✓ ' + label + ' (ID=' + wpId + ')');
      } else {
        var errMsg = result.body.message || JSON.stringify(result.body);
        results.push('✗ ' + label + ' → ' + result.code + ': ' + errMsg);
      }

      Utilities.sleep(300);

    } catch(e) {
      results.push('✗ ' + label + ' → 例外: ' + e.message);
    }
  });

  // 結果表示
  var summary = isDryRun ? '【DRY RUN — 実際には投稿していません】\n\n' : '';
  if (processed === 0) {
    summary += '対象行がありませんでした。\n（article_status=APPROVED かつ wordpress_status≠PUBLISHED の行が必要です）';
  } else {
    summary += processed + '件処理 / ' + skipped + '件スキップ\n\n' + results.join('\n');
  }
  SpreadsheetApp.getUi().alert(summary);
}
